<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class BossReportController extends Controller
{
    public function index(Request $request)
    {
        // ----- Read filters -----
        $salespersonId = $request->input('salesperson', 'all');
        $period        = strtolower($request->input('period', 'monthly'));

        $startParam = $request->input('start_date');
        $endParam   = $request->input('end_date');

        // Month selector in your UI is short name (Jan/Feb/…).
        $mpMonthShort = $request->input('mpMonth');          // e.g. "Jun"
        $yearParam    = $request->input('year', Carbon::now()->year);

        if (!$mpMonthShort) {
            $mpMonthShort = Carbon::now()->format('M');      // Jan…Dec
            $yearParam    = Carbon::now()->year;
        }

        // Defaults: current month
        if (!$startParam || !$endParam) {
            $startParam = now()->startOfMonth()->toDateString();
            $endParam   = now()->endOfMonth()->toDateString();
        }

        // Reusable date objects
        $start = Carbon::parse($startParam)->startOfDay();
        $end   = Carbon::parse($endParam)->endOfDay();

        // =========================
        // LEADS BASE (shared)
        // =========================
        $monthStartC = Carbon::parse("1 {$mpMonthShort} {$yearParam}")->startOfMonth();
        $monthEndC   = Carbon::parse("1 {$mpMonthShort} {$yearParam}")->endOfMonth();

        $monthStart = $monthStartC->toDateString();
        $monthEnd   = $monthEndC->toDateString();
        $lq = DB::table('leads')
            ->whereBetween('created_at', [
                (clone $monthStartC)->startOfDay(),
                (clone $monthEndC)->endOfDay(),
            ]);

        $cumBase = DB::table('leads')
            ->where('created_at', '<=', (clone $monthEndC)->endOfDay());

        // apply salesperson filter to BOTH queries (important)
        if ($salespersonId !== 'all' && $salespersonId) {
            $lq->where('salesperson_id', $salespersonId);
            $cumBase->where('salesperson_id', $salespersonId);
        }

        $leadsThisMonth = (clone $lq)->count();

        // cumulative up to month end
        $acceptedCum   = (clone $cumBase)->where('status', 'accept')->count();
        $rejectedCum   = (clone $cumBase)->where('status', 'reject')->count();
        $fiftyFiftyCum = (clone $cumBase)->where('opportunity', '50/50')->count();

        // ⚠️ match your real DB value: in your earlier code you used "Low Chance"
        $lowChanceCum  = (clone $cumBase)->whereIn('opportunity', ['Low', 'Low Chance'])->count();

        $monthlyPerformance = [
            'month_short' => $mpMonthShort,
            'year'        => (int) $yearParam,
            'bars'        => [
                'leads_added' => (int) $leadsThisMonth,
                'accepted'    => (int) $acceptedCum,
                'rejected'    => (int) $rejectedCum,
                'fifty_fifty' => (int) $fiftyFiftyCum,
                'low_chance'  => (int) $lowChanceCum,
            ],
        ];

        // =========================
        // MEETINGS BASE (shared)
        // =========================
        $meetingBase = DB::table('meetings')
            ->whereBetween(DB::raw('DATE(start_time)'), [$start->toDateString(), $end->toDateString()]);

        if ($salespersonId !== 'all' && $salespersonId) {
            $meetingBase->where('user_id', $salespersonId);
        }

        $meetingAgg = (clone $meetingBase)->selectRaw("
            COUNT(*)                                             AS total_meetings,
            SUM(CASE WHEN status='scheduled' THEN 1 ELSE 0 END)  AS accepted_meets,
            SUM(CASE WHEN status='canceled' THEN 1 ELSE 0 END)   AS rejected_meets
        ")->first();

        $kpis = [
            'total_leads'    => $leadsThisMonth,
            'total_meetings' => (int)($meetingAgg->total_meetings ?? 0),
            'accepted_meets' => (int)($meetingAgg->accepted_meets ?? 0),
            'rejected_meets' => (int)($meetingAgg->rejected_meets ?? 0),
        ];

        // pie (meeting outcomes)
        $meetingOutcomes = [
            'accepted' => (int)($meetingAgg->accepted_meets ?? 0),
            'rejected' => (int)($meetingAgg->rejected_meets ?? 0),
            'filters'  => [
                'start_date'  => $start->toDateString(),
                'end_date'    => $end->toDateString(),
                'salesperson' => $salespersonId,
                'period'      => $period,
            ],
        ];

        // Salesperson list (dropdown)
        $salespeople = DB::table('users')
            ->select('id', 'name')
            ->whereIn('role', ['salesperson', 'head-salesperson'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        

        // ==========================
        // Meeting Outcomes (pie)
        // ==========================
        $meetingOutcomeQ = DB::table('meetings')
            ->whereBetween(DB::raw('DATE(start_time)'), [$start->toDateString(), $end->toDateString()]);

        if ($salespersonId !== 'all' && $salespersonId) {
            $meetingOutcomeQ->where('user_id', $salespersonId);
        }

        $meetingOutcomes = [
            'accepted' => (clone $meetingOutcomeQ)->where('status', 'scheduled')->count(),
            'rejected' => (clone $meetingOutcomeQ)->where('status', 'canceled')->count(),
            'filters'  => [
                'start_date'  => $start->toDateString(),
                'end_date'    => $end->toDateString(),
                'salesperson' => $salespersonId,
                'period'      => $period,
            ],
        ];


        // ===== Job Orders (show only REDO orders) =====
        $q          = trim($request->input('order_search', ''));    // text search
        $artistId   = $request->input('artist');                    // users.id or ''
        $onDate     = $request->input('order_date');                // YYYY-MM-DD (deadline)
        $statusUi   = $request->input('order_status');              // UI label

        // Map UI Status -> DB status
        $mapStatus = [
            'In Progress' => 'in_progress',
            'Completed'   => 'completed',
            'Rejected'    => 'rejected',
            'Assigned'    => 'assigned',
            'To Assign'   => 'to_assign',
        ];
        $dbStatus = $mapStatus[$statusUi] ?? null;

        $base = DB::table('orders as o')
            ->leftJoin('users as a', 'a.id', '=', 'o.artist_id')
            ->leftJoin('orders as orig', 'orig.id', '=', 'o.redo')   // for displaying original number + R
            ->select(
                'o.id',
                'o.order_number',
                'o.orderTitle',
                'o.companyName',
                'o.deadline',
                'o.orderStatus',
                'o.updated_at',
                'o.redo',
                'orig.order_number as orig_no',
                'a.name as artist_name'
            )
            ->where('o.status', '!=', 1); // exclude hidden rows

        // ----- apply filters -----
        if ($q !== '') {
            $base->where(function ($w) use ($q) {
                $like = '%'.$q.'%';
                $w->where('o.order_number', 'like', $like)
                ->orWhere('o.orderTitle',  'like', $like)
                ->orWhere('o.companyName', 'like', $like);
            });
        }
        if ($artistId !== null && $artistId !== '') {
            $base->where('o.artist_id', $artistId);
        }
        if (!empty($onDate)) {
            // Use deadline as the date filter (matches your UI date field)
            $base->whereDate('o.deadline', $onDate);
        }
        if ($dbStatus) {
            $base->where('o.orderStatus', $dbStatus);
        }

        // compute TOP-3 redo ids *within this filtered set*
        $redoTop3Ids = (clone $base)
            ->whereNotNull('o.redo')
            ->orderBy('o.updated_at', 'desc')
            ->limit(3)
            ->pluck('o.id')
            ->toArray();

        // final rows: filtered, latest first, limit 3
        $jobOrders = (clone $base)
            ->orderBy('o.updated_at', 'desc')
            ->limit(3)
            ->get();

        // artists for the dropdown (both artist + head-artist)
        $artists = DB::table('users')
            ->select('id','name')
            ->whereIn('role', ['artist','head-artist'])
            ->orderBy('name')
            ->get();

        // ===== Job Order Fulfillment (totals) =====
        // Bars: Total Orders, In Progress, Completed, Rejected
        $ordersBase = DB::table('orders')->where('status', '!=', 1);
        $jobFulfillment = [
            'total'      => (clone $ordersBase)->count(),
            'in_progress'=> (clone $ordersBase)->where('orderStatus', 'in_progress')->count(),
            'completed'  => (clone $ordersBase)->where('orderStatus', 'completed')->count(),
            'rejected'   => (clone $ordersBase)->where('orderStatus', 'rejected')->count(),
        ];

        // ===== Machine Usage (Artist dashboard → Reports → Machine Usage) =====
        $mq     = trim($request->input('machine_q', ''));
        $mtype  = $request->input('machine_type', '');          // '', 'Printer', 'Cutter'
        $mrange = $request->input('machine_range', 'last30');   // last30, last90, year

        // Custom date range (optional)
        $mfrom  = $request->input('machine_from');             // YYYY-MM-DD or null
        $mto    = $request->input('machine_to');               

        // Date range
        if ($mfrom || $mto) {
            // When user gives a custom range, use it and mark as "custom"
            $from = $mfrom ? \Carbon\Carbon::parse($mfrom) : now()->subDays(30);
            $to   = $mto   ? \Carbon\Carbon::parse($mto)   : now();

            // Ensure start <= end
            if ($from->gt($to)) {
                [$from, $to] = [$to, $from];
            }

            $start  = $from->toDateString();
            $end    = $to->toDateString();
            $mrange = 'custom';
        } else {
            // Preset ranges
            $end   = now()->toDateString();
            $start = match ($mrange) {
                'last90' => now()->subDays(90)->toDateString(),
                'year'   => now()->startOfYear()->toDateString(),
                default  => now()->subDays(30)->toDateString(), // last30
            };
        }       

        // common where for names
        $badNames = ['no','No','NO','tbc','TBC',''];   // exclude these

        // PRINTER query
        $printerAgg = DB::table('specifications as s')
            ->join('product_items as pi', 'pi.ItemID', '=', 's.ItemID')
            ->selectRaw("
                s.printer as machine_name,
                'Printer'  as machine_type,
                COUNT(DISTINCT s.ItemID) as used_items,
                COALESCE(SUM(pi.quantity),0) as total_qty
            ")
            ->whereNotNull('s.printer')
            ->where('s.printer', '<>', '')
            ->whereNotIn(DB::raw('LOWER(s.printer)'), array_map('strtolower', $badNames))
            ->whereBetween(DB::raw('DATE(COALESCE(pi.updated_at, pi.created_at))'), [$start, $end])
            ->when($mq !== '', fn($q) => $q->where('s.printer', 'like', "%{$mq}%"))
            ->groupBy('s.printer');

        // CUTTER query
        $cutterAgg = DB::table('specifications as s')
            ->join('product_items as pi', 'pi.ItemID', '=', 's.ItemID')
            ->selectRaw("
                s.cutter as machine_name,
                'Cutter'  as machine_type,
                COUNT(DISTINCT s.ItemID) as used_items,
                COALESCE(SUM(pi.quantity),0) as total_qty
            ")
            ->whereNotNull('s.cutter')
            ->where('s.cutter', '<>', '')
            ->whereNotIn(DB::raw('LOWER(s.cutter)'), array_map('strtolower', $badNames))
            ->whereBetween(DB::raw('DATE(COALESCE(pi.updated_at, pi.created_at))'), [$start, $end])
            ->when($mq !== '', fn($q) => $q->where('s.cutter', 'like', "%{$mq}%"))
            ->groupBy('s.cutter');

        // choose query
        if ($mtype === 'Printer') {
            $combined = $printerAgg;
        } elseif ($mtype === 'Cutter') {
            $combined = $cutterAgg;
        } else {
            // union both, then wrap to order + paginate
            $combined = $printerAgg->unionAll($cutterAgg);
        }

        // wrap subquery to order & paginate
        $machineUsage = DB::query()
            ->fromSub($combined, 'm')
            ->orderByDesc('total_qty')
            ->orderBy('machine_name')
            ->paginate(10)                      // 10 rows per page
            ->withQueryString()               // keep filters when paging
            ->fragment('machineSec');

        $machineFilters = [
            'machine_q'     => $mq,
            'machine_type'  => $mtype,
            'machine_range' => $mrange,
            'machine_from'  => $mfrom,
            'machine_to'    => $mto,
        ];

        // Top 6 base orders by # of redone products (newest redo kept visible; archived redos ignored)
        $redoAgg = DB::table('orders as r')
            ->join('products as p', 'p.OrderID', '=', 'r.id')
            ->whereNotNull('r.redo')                         // only redo orders (children)
            ->where(function ($w) {                          // keep visible (not archived)
                $w->whereNull('r.status')->orWhere('r.status', 0);
            })
            ->whereNotNull('p.redoOf')                       // this product is a redo copy
            ->where('p.editable', 1)                         // only the products actually selected for redo
            ->selectRaw('r.redo as base_id, COUNT(DISTINCT p.redoOf) as product_redo_count')
            ->groupBy('r.redo')
            ->orderByDesc('product_redo_count')
            ->limit(6)
            ->get();

        $redoLabels = [];
        $redoCounts = [];

        foreach ($redoAgg as $row) {
            $base = DB::table('orders')
                ->where('id', $row->base_id)
                ->select('id', 'orderDate', 'created_at')
                ->first();

            if (!$base) continue;

            $yr = $base->orderDate
                ? \Carbon\Carbon::parse($base->orderDate)->format('Y')
                : \Carbon\Carbon::parse($base->created_at ?? now())->format('Y');

            // Label format: #ORD-YYYY-####R
            $redoLabels[] = sprintf('#ORD-%s-%04dR', $yr, (int)$base->id);
            $redoCounts[] = (int)$row->product_redo_count;
        }

        $redoChart = [
            'labels' => $redoLabels,
            'counts' => $redoCounts,
        ];

        // ============ ORDER REPORT FILTERS ============
        $ordArtist = $request->input('ord_artist', 'all');
        $ordStart  = $request->input('ord_start');
        $ordEnd    = $request->input('ord_end');

        if (!$ordStart || !$ordEnd) {
            $ordStart = now()->startOfMonth()->toDateString();
            $ordEnd   = now()->endOfMonth()->toDateString();
        }

        // Base orders scope (visible rows only)
        $ordersBase = DB::table('orders')
            ->where(function ($w) {                // visible (not archived/hidden)
                $w->whereNull('status')->orWhere('status', '!=', 1);
            })
            // Use orderDate if you have it; fall back to created_at
            ->whereBetween(DB::raw("DATE(COALESCE(orderDate, created_at))"), [$ordStart, $ordEnd]);

        if ($ordArtist !== 'all' && $ordArtist !== null && $ordArtist !== '') {
            $ordersBase->where('artist_id', $ordArtist);
        }

        // Aggregates for the bar chart
        $jobFulfillment = [
            'total'       => (clone $ordersBase)->count(),
            'in_progress' => (clone $ordersBase)->where('orderStatus', 'in_progress')->count(),
            'completed'   => (clone $ordersBase)->where('orderStatus', 'completed')->count(),
            'rejected'    => (clone $ordersBase)->where('orderStatus', 'rejected')->count(),
        ];

        // expose filters to the blade
        $orderFilters = [
            'ord_artist' => $ordArtist,
            'ord_start'  => $ordStart,
            'ord_end'    => $ordEnd,
        ];

        return view('boss.reports', [
            'kpis'               => $kpis,
            'monthlyPerformance' => $monthlyPerformance,
            'meetingOutcomes'    => $meetingOutcomes,
            'salespeople'        => $salespeople,
            'jobOrders'          => $jobOrders,
            'jobFulfillment'     => $jobFulfillment,
            'redoTop3Ids'        => $redoTop3Ids,
            'artists'      => $artists,
            'filters' => [
                'order_search' => $q,
                'artist'       => $artistId,
                'order_date'   => $onDate,
                'order_status' => $statusUi,
            ],
            'machineUsage'   => $machineUsage,
            'machineFilters' => $machineFilters,
            'redoChart' => $redoChart,

            'salesFilters'       => [
                'salesperson' => $salespersonId,
                'period'      => $period,
                'start_date'  => $startParam,
                'end_date'    => $endParam,
            ],

            'orderFilters'   => $orderFilters,
        ]);
    }

    public function storeMachine(Request $request)
    {
        // Validate
        $data = $request->validate([
            'machine_name' => ['required','string','max:255'],
            'machine_type' => ['required', Rule::in(['printer','cutter','lamination'])],
        ]);

        // Optional: avoid duplicates by (name,type)
        $exists = DB::table('machines')
            ->whereRaw('LOWER(machine_name) = ?', [mb_strtolower($data['machine_name'])])
            ->where('machine_type', $data['machine_type'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'machine_name' => 'This machine already exists for the selected type.',
            ])->withFragment('machineSec');
        }

        // Insert
        DB::table('machines')->insert([
            'user_id'      => Auth::id(),                 // nullable field; saves current user
            'machine_name' => $data['machine_name'],
            'machine_type' => $data['machine_type'],
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Back to Machine tab with success flash
        return redirect()
            ->to(route('boss.reports') . '#machineSec')
            ->with('success', 'Machine added successfully.');
    }
}
