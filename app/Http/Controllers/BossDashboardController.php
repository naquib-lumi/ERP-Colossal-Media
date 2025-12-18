<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class BossDashboardController extends Controller
{
    public function index(Request $request)
    {
        // ----- Read filters from UI (with safe defaults) -----
        $startParam = $request->input('start_date');
        $endParam   = $request->input('end_date');
        $salespersonId = $request->input('salesperson');     // meetings.user_id, or 'all'

        // Month selector in your UI is short name (Jan/Feb/…).
        $mpMonthShort = $request->input('mpMonth');          // e.g. "Jun"
        $yearParam    = $request->input('year', Carbon::now()->year);

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
        $totalLeadsAdded = DB::table('leads')->count();

        $meetingBaseAllTime = DB::table('meetings');
        if ($salespersonId && $salespersonId !== 'all') {
            $meetingBaseAllTime->where('user_id', $salespersonId);
        }

        $totalMeetings    = (clone $meetingBaseAllTime)->count();
        $acceptedMeetings = (clone $meetingBaseAllTime)->where('status', 'scheduled')->count();
        $rejectedMeetings = (clone $meetingBaseAllTime)->where('status', 'canceled')->count();

        $kpis = [
            'total_leads'    => $totalLeadsAdded,
            'total_meetings' => $totalMeetings,
            'accepted_meets' => $acceptedMeetings,
            'rejected_meets' => $rejectedMeetings,
        ];

        // ===========================================
        // Monthly Performance (your 5 bars definition)
        // ===========================================
        // Bar 1: leads added IN selected month only
        $leadsThisMonth = DB::table('leads')
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->count();

        // Bars 2–5: cumulative from beginning up to end of selected month
        $cumBase = DB::table('leads')->where('date', '<=', $monthEnd);

        $acceptedCum   = (clone $cumBase)->where('status', 'accept')->count();
        $rejectedCum   = (clone $cumBase)->where('status', 'reject')->count();
        $fiftyFiftyCum = (clone $cumBase)->where('opportunity', '50/50')->count();
        $lowChanceCum  = (clone $cumBase)->where('opportunity', 'Low')->count();

        $monthlyPerformance = [
            'month_short' => $mpMonthShort,
            'year'        => (int)$yearParam,
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
            ->select('id', 'name')
            ->whereIn('role', ['salesperson', 'head-salesperson'])
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
            ->whereRaw("LOWER(TRIM(s.printer)) NOT IN ('no','none','n','0', 'TBC', 'tbc')")
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
            ->whereRaw("LOWER(TRIM(s.cutter)) NOT IN ('no','none','n','0', 'TBC', 'tbc')")
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
        $machineUsage = $rows->sortByDesc('total_qty')->take(3)->values();

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

        // ================= Costing Data Management TOP 3 =================
        $costingTopOrders = (function () {

            // 1) Base orders (last 30 days, same idea as "Another Data" default)
            $ordersQuery = DB::table('orders as o')
                ->leftJoin('orders as base', 'base.id', '=', 'o.redo')
                ->select(
                    'o.id',
                    'o.order_number',
                    'o.created_at',
                    'o.status',
                    'o.redo',
                    DB::raw('CASE WHEN o.redo IS NULL THEN 0 ELSE 1 END as is_redo'),
                    'base.order_number as base_order_number'
                )
                ->where('o.created_at', '>=', now()->subDays(30));

            $ordersAll = collect($ordersQuery->orderBy('o.created_at', 'desc')->get());
            if ($ordersAll->isEmpty()) {
                return collect();
            }

            $orderIds = $ordersAll->pluck('id')->all();

            // products_count (same as datamanagement)
            $productsAgg = DB::table('products')
                ->select('OrderID', DB::raw('COUNT(DISTINCT ProductID) as products_count'))
                ->whereIn('OrderID', $orderIds)
                ->groupBy('OrderID')
                ->get()
                ->keyBy('OrderID');

            // used_quantity = sum of item qty
            $usedAgg = DB::table('products as p')
                ->join('product_items as pi', 'pi.ProductID', '=', 'p.ProductID')
                ->select('p.OrderID', DB::raw('COALESCE(SUM(pi.quantity),0) as used_quantity'))
                ->whereIn('p.OrderID', $orderIds)
                ->groupBy('p.OrderID')
                ->get()
                ->keyBy('OrderID');

            // products + items for total_cost calc
            $products = DB::table('products')
                ->select('ProductID', 'OrderID', 'totalQuantity', 'status', 'created_at')
                ->whereIn('OrderID', $orderIds)
                ->get();

            $items = DB::table('product_items')
                ->select('ItemID','ProductID','quantity','sizeWidth','sizeHeight','sizeUnit','material')
                ->whereIn('ProductID', $products->pluck('ProductID')->all() ?: [0])
                ->get();

            $itemsByProduct = $items->groupBy('ProductID');

            // material costs
            $materialCosts = DB::table('materials')
                ->select('materialName','unitCost')
                ->get()
                ->keyBy(function ($m) {
                    return trim(mb_strtolower($m->materialName));
                });

            // helper: unit → inch factor
            $toInchFactor = function (?string $u): float {
                $u = strtolower((string)$u);
                return match ($u) {
                    'mm' => 1/25.4,
                    'cm' => 1/2.54,
                    'ft', 'feet' => 12.0,
                    'inch','in','"' => 1.0,
                    default => 1.0,
                };
            };

            // compute products_count, used_quantity, total_cost for each order
            $computed = $ordersAll->map(function ($ord)
                use ($products, $itemsByProduct, $materialCosts, $productsAgg, $usedAgg, $toInchFactor) {

                $ord->products_count = (int) optional($productsAgg->get($ord->id))->products_count ?? 0;
                $ord->used_quantity  = (int) optional($usedAgg->get($ord->id))->used_quantity ?? 0;
                $ord->total_item_quantity = $ord->used_quantity;

                $totalCost = 0.0;

                foreach ($products->where('OrderID', $ord->id) as $p) {
                    foreach ($itemsByProduct->get($p->ProductID, collect()) as $it) {
                        $qty = (int) ($it->quantity ?? 0);
                        if ($qty <= 0) continue;

                        $w = (float) ($it->sizeWidth ?? 0);
                        $h = (float) ($it->sizeHeight ?? 0);
                        $f = $toInchFactor($it->sizeUnit);
                        $areaSqIn = max(0, $w) * max(0, $h) * ($f * $f);

                        $names = [];
                        if (!empty($it->material)) {
                            try {
                                $dec = json_decode($it->material, true, flags: JSON_THROW_ON_ERROR);
                                if (is_array($dec)) {
                                    $names = array_filter(array_map('strval', $dec));
                                }
                            } catch (\Throwable $e) {
                                // ignore bad JSON
                            }
                        }

                        foreach ($names as $name) {
                            $key = trim(mb_strtolower($name));
                            if (isset($materialCosts[$key])) {
                                $unit = (float) $materialCosts[$key]->unitCost;
                                $totalCost += $qty * $areaSqIn * $unit;
                            }
                        }
                    }
                }

                $ord->total_cost = $totalCost;

                return $ord;
            });

            // same default sort as "Another Data" – latest first
            $computed = $computed->sortBy('created_at', SORT_REGULAR, true)->values();

            // only top 3 rows for dashboard
            return $computed->take(3);
        })();

        return view('boss.dashboard', [
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
            'costingTopOrders' => $costingTopOrders,
        ]);
    }

    public function ProfileShow(Request $request)
    {
        $user = $request->user();
        return view('boss.profile.show', compact('user'));
    }

    public function ProfileEdit(Request $request)
    {
        $user = $request->user();
        return view('boss.profile.edit', compact('user'));
    }

    public function ProfileUpdate(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'           => ['required','string','max:255'],
            'email'          => ['required','email','max:255'],
            'contact_number' => ['nullable','string','max:30'],

            // Password section (optional)
            // If 'password' is present, 'current_password' must match the logged-in user
            'current_password' => ['nullable','required_with:password','current_password'],
            'password'         => ['nullable', Password::min(8)->mixedCase()->numbers()->symbols(), 'confirmed'],
        ]);

        // Update profile fields
        $user->fill([
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'contact_number' => $validated['contact_number'] ?? null,
        ]);

        // Update password if provided
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Profile updated.');
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
            ]);
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
            ->to(route('boss.dashboard'))
            ->with('success', 'Machine added successfully.');
    }
}
