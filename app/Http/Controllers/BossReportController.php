<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class BossReportController extends Controller
{
    public function index(Request $request)
    {
        // ----- Read filters from UI (with safe defaults) -----
        $startParam    = $request->input('start_date');
        $endParam      = $request->input('end_date');
        $salespersonId = $request->input('salesperson', 'all');  // 'all' or users.id
        $period        = $request->input('period', 'monthly');

        // Month selector in your UI is short name (Jan/Feb/…).
        $mpMonthShort = $request->input('mpMonth');          // e.g. "Jun"
        $yearParam     = (int) $request->input('year', now()->year);

        // Defaults: KPI range = current month
        if (!$startParam || !$endParam) {
            $startParam = Carbon::now()->startOfMonth()->toDateString();
            $endParam   = Carbon::now()->endOfMonth()->toDateString();
        }

        // If no mpMonth provided, default to current month short (e.g., "Jun")
        if (!$mpMonthShort) {
            $mpMonthShort = Carbon::now()->format('M');      // Jan…Dec
            $yearParam    = Carbon::now()->year;
        }

        // Build month start/end from short name + year
        $monthStart = Carbon::parse("1 {$mpMonthShort} {$yearParam}")->startOfMonth()->toDateString();
        $monthEnd   = Carbon::parse("1 {$mpMonthShort} {$yearParam}")->endOfMonth()->toDateString();

        // =========================
        // KPI TILES (top 4 cards)
        // =========================
        // Leads in selected range (+ salesperson)
        $leadsBase = DB::table('leads');
        $leadsBase->whereBetween('date', [$startParam, $endParam]);
        if ($salespersonId !== 'all' && $salespersonId) {
            $leadsBase->where('user_id', $salespersonId);
        }
        $totalLeadsAdded = DB::table('leads')->count();

        // Meetings in selected range (+ salesperson)
        $meetingBase = DB::table('meetings')
            ->whereBetween(DB::raw('DATE(start_time)'), [$startParam, $endParam]);

        if ($salespersonId !== 'all' && $salespersonId) {
            $meetingBase->where('user_id', $salespersonId);
        }

        $totalMeetings    = (clone $meetingBase)->count();
        $acceptedMeetings = (clone $meetingBase)->where('status', 'scheduled')->count();
        $rejectedMeetings = (clone $meetingBase)->where('status', 'canceled')->count();

        $kpis = [
            'total_leads'    => $totalLeadsAdded,
            'total_meetings' => $totalMeetings,
            'accepted_meets' => $acceptedMeetings,
            'rejected_meets' => $rejectedMeetings,
            // keep current view logic intact but also return selected filters for UI state
            'filters'        => [
                'start_date'  => $startParam,
                'end_date'    => $endParam,
                'salesperson' => $salespersonId,
                'period'      => $period,
            ],
        ];

        // ===========================================
        // Monthly Performance (your 5 bars definition)
        // ===========================================
        // Bar 1: leads added IN selected month only
        $leadsMonthBase = DB::table('leads')->whereBetween('date', [$monthStart, $monthEnd]);
        if ($salespersonId !== 'all' && $salespersonId) {
            $leadsMonthBase->where('salesperson_id', $salespersonId);
        }
        $leadsThisMonth = (clone $leadsMonthBase)->count();

        // Bars 2–5: cumulative from beginning up to end of selected month
        $cumBase = DB::table('leads')->where('date', '<=', $monthEnd);
        if ($salespersonId !== 'all' && $salespersonId) {
            $cumBase->where('salesperson_id', $salespersonId);
        }

        $acceptedCum   = (clone $cumBase)->where('status', 'accept')->count();
        $rejectedCum   = (clone $cumBase)->where('status', 'reject')->count();
        $fiftyFiftyCum = (clone $cumBase)->where('opportunity', '50/50')->count();
        $lowChanceCum  = (clone $cumBase)->where('opportunity', 'Low')->count();

        $monthlyPerformance = [
            'month_short' => $mpMonthShort,
            'year'        => (int) $yearParam,
            'bars'        => [
                'leads_added' => $leadsThisMonth,
                'accepted'    => $acceptedCum,
                'rejected'    => $rejectedCum,
                'fifty_fifty' => $fiftyFiftyCum,
                'low_chance'  => $lowChanceCum,
            ],
        ];

        // ==========================
        // Meeting Outcomes (pie)
        // ==========================
        $pieStart = $request->input('start_date');   // e.g. 2025-10-01
        $pieEnd   = $request->input('end_date');     // e.g. 2025-10-31
        $salespersonId = $request->input('salesperson'); // 'all' or a users.id

        $meetingOutcomeQ = DB::table('meetings');

        // apply date range only if both provided
        if ($pieStart && $pieEnd) {
            $meetingOutcomeQ->whereBetween(DB::raw('DATE(start_time)'), [$pieStart, $pieEnd]);
        }

        // filter by salesperson if selected
        if ($salespersonId && $salespersonId !== 'all') {
            $meetingOutcomeQ->where('user_id', $salespersonId);
        }

        $meetingOutcomes = [
            'accepted' => (clone $meetingOutcomeQ)->where('status', 'scheduled')->count(),
            'rejected' => (clone $meetingOutcomeQ)->where('status', 'canceled')->count(),
            'filters'  => [
                'start_date'  => $pieStart ?: now()->startOfMonth()->toDateString(),
                'end_date'    => $pieEnd   ?: now()->endOfMonth()->toDateString(),
                'salesperson' => $salespersonId ?: 'all',
                'period'      => $request->input('period', 'monthly'),
            ],
        ];

        // Salesperson list (for your dropdown – optional)
        $salespeople = DB::table('users')
            ->select('id','name')
            ->where('role', 'salesperson')
            ->orderBy('name')
            ->get();

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

        // ===== Machine Usage Summary =====
        $mq     = trim($request->input('machine_q', ''));    
        $mtype  = $request->input('machine_type', '');        
        $mrange = $request->input('machine_range', 'last30'); 

        // Date range
        $start = null; $end = now()->toDateString();
        if ($mrange === 'last30') {
            $start = now()->subDays(30)->toDateString();
        } elseif ($mrange === 'last90') {
            $start = now()->subDays(90)->toDateString();
        } elseif ($mrange === 'year') {
            $start = now()->startOfYear()->toDateString();
        }

        // Helper for shared filtering
        $applyFilters = function ($query, $machineColumn) use ($mq, $start, $end) {
            if ($mq !== '') {
                $query->where($machineColumn, 'like', '%'.$mq.'%');
            }
            if ($start) {
                $query->whereBetween(
                    DB::raw('DATE(COALESCE(pi.updated_at, pi.created_at))'),
                    [$start, $end]
                );
            }
            return $query;
        };

        // Printer usage aggregation
        $printerAgg = DB::table('specifications as s')
            ->join('product_items as pi', 'pi.ItemID', '=', 's.ItemID')
            ->whereNotNull('s.printer')->where('s.printer','<>','')
            ->selectRaw("
                s.printer as machine_name,
                'Printer'  as machine_type,
                COUNT(DISTINCT s.ItemID) as used_items,
                COALESCE(SUM(pi.quantity),0) as total_qty
            ")
            ->groupBy('s.printer');
        $printerAgg = $applyFilters($printerAgg, 's.printer');

        // Cutter usage aggregation
        $cutterAgg = DB::table('specifications as s')
            ->join('product_items as pi', 'pi.ItemID', '=', 's.ItemID')
            ->whereNotNull('s.cutter')->where('s.cutter','<>','')
            ->selectRaw("
                s.cutter as machine_name,
                'Cutter'  as machine_type,
                COUNT(DISTINCT s.ItemID) as used_items,
                COALESCE(SUM(pi.quantity),0) as total_qty
            ")
            ->groupBy('s.cutter');
        $cutterAgg = $applyFilters($cutterAgg, 's.cutter');

        // Merge results
        $rows = collect();
        if ($mtype === 'Printer') {
            $rows = $printerAgg->get();
        } elseif ($mtype === 'Cutter') {
            $rows = $cutterAgg->get();
        } else {
            $rows = $printerAgg->get()->concat($cutterAgg->get());
        }

        // Sort by total quantity (usage) and take top 3
        $machineUsage = $rows->sortByDesc('total_qty')->values();

        $machineFilters = [
            'machine_q'    => $mq,
            'machine_type' => $mtype,
            'machine_range'=> $mrange,
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
        ]);
    }
}
