<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Product;
use App\Models\LeadAttachment;
use Carbon\Carbon;
use App\Models\ProductPermit;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use App\Models\OrderAttachment;

class AdminController extends Controller
{
    /* ========================= Auth (Login / Logout) ========================= */



    /* -------------------- Profile -------------------- */

    public function ProfileShow(Request $request)
    {
        $user = $request->user();
        return view('admin.profile.show', compact('user'));
    }

    public function ProfileUpdate(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'email', 'max:255'],
            'contact_number'    => ['nullable', 'string', 'max:30'],
            'current_password'  => ['nullable', 'required_with:password', 'current_password'],
            'password'          => ['nullable', Password::min(8)->mixedCase()->numbers()->symbols(), 'confirmed'],
        ]);

        $user->fill([
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'contact_number' => $validated['contact_number'] ?? null,
        ]);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Profile updated.');
    }

    /* -------------------- Dashboard -------------------- */

    public function dashboard(Request $request)
{
    $user = Auth::user();
    if (!$user->hasRole('admin')) abort(403, 'Unauthorized');

    $now = Carbon::now();
    $startOfYear = $now->copy()->startOfYear();

    // Exclude orders that have a 'redo' reference
    $excludedOrderIds = Order::whereNotNull('redo')->pluck('redo')->toArray();

    // ✅ All counts cumulative from start of year until now
    $totalOrders = Order::where('orderDate', '>=', $startOfYear)
        ->whereNotIn('id', $excludedOrderIds)
        ->count();

    $inProgressCount = Order::where('orderStatus', 'in_progress')
        ->where('orderDate', '>=', $startOfYear)
        ->whereNotIn('id', $excludedOrderIds)
        ->count();

    $completedCount = Order::where('orderStatus', 'completed')
        ->where('orderDate', '>=', $startOfYear)
        ->whereNotIn('id', $excludedOrderIds)
        ->count();

    // ✅ In-progress products
$inProgressProducts = Product::from('products as p')
    ->join('orders as o', 'o.id', '=', 'p.OrderID')
    ->where('p.status', 'in_progress')
    ->whereNotIn('o.id', $excludedOrderIds)
    ->with('order.artist')
    ->select('p.*')
    ->selectRaw("
        CONCAT(
            '#ORD-',
            YEAR(o.orderDate), '-',
            LPAD(COALESCE(o.redo, o.id), 3, '0'),
            '-P', LPAD(COALESCE(p.redoOf, p.ProductID), 4, '0'),
            CASE WHEN (p.redoOf IS NOT NULL AND p.editable = 1)
                THEN 'R'
                ELSE ''
            END
        ) AS display_product_id
    ")
    ->orderBy('p.created_at', 'desc')
    ->take(3)
    ->get();

    // ✅ Completed products
    $completedProducts = Product::query()
        ->join('orders as o', 'o.id', '=', 'products.OrderID')
        ->where('products.status', 'completed')
        ->whereNotIn('o.id', $excludedOrderIds)
        ->with('order.artist')
        ->select('products.*')
        ->orderBy('products.updated_at', 'asc')
        ->take(3)
        ->get();

    // ✅ Last updated = latest order update date
    $lastUpdatedOrder = Order::latest('updated_at')->first();
    $lastUpdated = $lastUpdatedOrder
        ? $lastUpdatedOrder->updated_at->format('M d, Y')
        : $now->format('M d, Y');

    return view('admin.dashboard', compact(
        'totalOrders', 'inProgressCount', 'completedCount',
        'inProgressProducts', 'completedProducts', 'lastUpdated'
    ));
}


    /* -------------------- Charts API -------------------- */

    public function leadsMonthly(Request $request)
    {
        $now = Carbon::now();
        $months = [];
        $counts = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $months[] = $month->format('M');
            $count = Lead::whereYear('created_at', $month->year)
                         ->whereMonth('created_at', $month->month)
                         ->count();
            $counts[] = $count;
        }
        return response()->json([
            'months' => array_reverse($months),
            'counts' => array_reverse($counts)
        ]);
    }

    public function leadsBreakdown(Request $request)
    {
        $period = $request->get('period', 'this_month');
        $now = Carbon::now();

        switch ($period) {
            case 'this_year':
                $start = $now->startOfYear()->toDateString();
                $end   = $now->endOfYear()->toDateString();
                break;
            case 'last_month':
                $start = $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
                $end   = $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
                break;
            case '3_months':
                $start = $now->copy()->subMonthsNoOverflow(3)->startOfMonth()->toDateString();
                $end   = $now->endOfMonth()->toDateString();
                break;
            case 'this_month':
            default:
                $start = $now->startOfMonth()->toDateString();
                $end   = $now->endOfMonth()->toDateString();
        }

        $added       = Lead::whereBetween('created_at', [$start, $end])->where('status', 'new')->count();
        $accepted    = Lead::whereBetween('created_at', [$start, $end])->where('status', 'accept')->count();
        $rejected    = Lead::whereBetween('created_at', [$start, $end])->where('status', 'reject')->count();
        $fiftyFifty  = Lead::whereBetween('created_at', [$start, $end])->where('opportunity', '50/50')->count();
        $lowChance   = Lead::whereBetween('created_at', [$start, $end])->where('opportunity', 'Low Chance')->count();

        return response()->json([
            'added'        => $added,
            'accepted'     => $accepted,
            'rejected'     => $rejected,
            'fifty_fifty'  => $fiftyFifty,
            'low_chance'   => $lowChance
        ]);
    }

    public function fulfillmentCounts(Request $request)
    {
        $period = $request->get('period', 'this_month');
        $now = Carbon::now();

        switch ($period) {
            case 'this_year':
                $start = $now->startOfYear()->toDateString();
                $end   = $now->endOfYear()->toDateString();
                break;
            case 'last_month':
                $start = $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
                $end   = $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
                break;
            case '3_months':
                $start = $now->copy()->subMonthsNoOverflow(3)->startOfMonth()->toDateString();
                $end   = $now->endOfMonth()->toDateString();
                break;
            case 'this_month':
            default:
                $start = $now->startOfMonth()->toDateString();
                $end   = $now->endOfMonth()->toDateString();
        }

        $excludedOrderIds = Order::whereNotNull('redo')->pluck('redo')->toArray();

        $newOrders = Order::where('orderStatus', 'to_assign')
            ->whereBetween('orderDate', [$start, $end])
            ->whereNotIn('id', $excludedOrderIds)
            ->count();

        $inProgress = Order::where('orderStatus', 'in_progress')
            ->whereBetween('orderDate', [$start, $end])
            ->whereNotIn('id', $excludedOrderIds)
            ->count();

        $completed = Order::where('orderStatus', 'completed')
            ->whereBetween('orderDate', [$start, $end])
            ->whereNotIn('id', $excludedOrderIds)
            ->count();

        $overdue = Order::where('orderStatus', 'in_progress')
            ->whereBetween('orderDate', [$start, $end])
            ->whereNotIn('id', $excludedOrderIds)
            ->where('deadline', '<', $now->toDateString())
            ->count();

        return response()->json([
            'new_orders'  => $newOrders,
            'in_progress' => $inProgress,
            'completed'   => $completed,
            'overdue'     => $overdue
        ]);
    }


    /* -------------------- Orders (Admin Overview) -------------------- */

    public function orders(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) abort(403, 'Unauthorized');

        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 50]) ? $perPage : 10;

        $excludedOrderIds = Order::whereNotNull('redo')->pluck('redo')->all();

        $totalOrders = Order::whereNotIn('id', $excludedOrderIds)->count();
        $inProgress  = Order::where('orderStatus', 'in_progress')->whereNotIn('id', $excludedOrderIds)->count();
        $completed   = Order::where('orderStatus', 'completed')->whereNotIn('id', $excludedOrderIds)->count();
        $rejected    = Order::where('orderStatus', 'rejected')->whereNotIn('id', $excludedOrderIds)->count();

        $q = Order::with(['lead','artist'])->whereNotIn('id', $excludedOrderIds);

        if ($id = $request->input('order_id')) {
            $q->where('order_number', 'like', "%{$id}%");
        }
        if ($artist = $request->input('artist')) {
            $q->whereHas('artist', fn($qq) => $qq->where('name','like',"%{$artist}%"));
        }
        if ($kw = $request->input('q')) {
            $q->where(fn($qq)=>$qq->where('orderTitle','like',"%{$kw}%")
                                  ->orWhere('description','like',"%{$kw}%"));
        }
        if ($status = $request->input('status')) {
            $q->where('orderStatus', $status);
        }
        if ($range = $request->input('date_range')) {
            [$from, $to] = array_map('trim', explode(' - ', $range)) + [null, null];
            if ($from && $to) {
                try {
                    $from = Carbon::createFromFormat('d/m/Y', $from)->startOfDay();
                    $to   = Carbon::createFromFormat('d/m/Y', $to)->endOfDay();
                    $q->whereBetween('deadline', [$from, $to]);
                } catch (\Exception $e) {}
            }
        }

        $orders = $q->orderByDesc('created_at')
                    ->paginate($perPage)
                    ->withQueryString();

        return view('admin.order.index', compact(
            'orders', 'totalOrders', 'inProgress', 'completed', 'rejected'
        ));
    }

    public function getOrders(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $orders = Order::with('lead', 'salesperson', 'products', 'originalOrder')->orderBy('created_at', 'desc');

        if (!$user->hasRole('admin')) {
            $orders->where('salesperson_id', $user->id);
        } else {
            if ($request->has('salesperson') && $request->salesperson) {
                $orders->where('salesperson_id', $request->salesperson);
            }
        }

        if ($request->has('status') && $request->status) {
            $orders->where('orderStatus', $request->status);
        }

        if ($request->has('search') && $request->input('search')['value']) {
            $search = $request->input('search')['value'];
            $orders->where(function ($query) use ($search) {
                $query->where('orderTitle', 'like', "%{$search}%")
                      ->orWhere('order_number', 'like', "%{$search}%")
                      ->orWhereHas('lead', function ($q) use ($search) {
                          $q->where('company_name', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                      });
            });
        }

        $orders->whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('orders as child')
                ->whereColumn('child.redo', 'orders.id');
        });

        return DataTables::of($orders)
            ->addColumn('order_id', function ($order) {
                if ($order->originalOrder) {
                    return $order->originalOrder->order_number . 'R';
                }
                return $order->order_number ?? $order->id;
            })
            ->addColumn('order_name', function ($order) {
                return $order->orderTitle;
            })
            ->addColumn('company_info', function ($order) {
                $lead = $order->lead;
                return '<div class="company-info-cell text-secondary">' .
                       '<div class="d-flex align-items-center mb-1"><i class="bx bxs-building me-2"></i>' . ($lead->company_name ?? 'N/A') . '</div>' .
                       '<div class="d-flex align-items-center mb-1"><i class="bx bxs-phone me-2"></i>' . ($lead->company_phone ?? 'N/A') . '</div>' .
                       '</div>';
            })
            ->addColumn('lead_details', function ($order) {
                $lead = $order->lead;
                $assignedTo = $order->artist?->name ?? 'Unassigned';
                return '<div class="lead-details-cell text-secondary">' .
                       '<div class="d-flex align-items-center mb-1"><i class="bx bxs-user me-2"></i>' . ($lead->name ?? 'N/A') . '</div>' .
                       '<div class="d-flex align-items-center mb-1"><i class="bx bxs-phone me-2"></i>' . ($lead->phone ?? 'N/A') . '</div>' .
                       '<div class="d-flex align-items-center mb-1"><i class="bx bx-envelope me-2"></i>' . ($lead->email ?? 'N/A') . '</div>' .
                       '<div class="d-flex align-items-center mb-1"><i class="bx bxs-id-card me-2"></i>Assigned To: ' . $assignedTo . '</div>' .
                '</div>';
            })
            ->addColumn('status', function ($order) {
                $color = match($order->orderStatus) {
                    'to_assign'   => 'danger',
                    'assigned'    => 'primary',
                    'pending'     => 'warning',
                    'in_progress' => 'info',
                    'completed'   => 'success',
                    'rejected'    => 'danger',
                    default       => 'secondary',
                };
                return '<span class="btn btn-sm btn-label-' . $color . '" 
                         style="white-space: nowrap; min-width:120px; text-align:center;">'
                    . ucwords(str_replace('_', ' ', $order->orderStatus)) .
                    '</span>';
            })
            ->addColumn('actions', function ($order) {
                $leadViewRoute = route('admin.orders.show', $order->id);
                $html = '<div class="actions-cell d-flex gap-2">';
                $html .= '<a href="' . $leadViewRoute . '" class="btn" title="View"><i class="bx bx-show" style="font-size: 1.5em;"></i></a>';
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['company_info', 'lead_details', 'status', 'products', 'actions'])
            ->toJson();
    }

    public function showOrder($id)
    {
        $order = Order::with('lead.attachments', 'salesperson', 'products', 'artist')->findOrFail($id);

        $attachments = method_exists($order, 'getAttachmentPathsAttribute')
            ? $order->getAttachmentPathsAttribute()->map(function ($path) {
                return [
                    'url'  => Storage::url($path),
                    'name' => basename($path),
                    'size' => Storage::size($path),
                ];
            })
            : collect();

        $leadAttachments = $order->lead
            ? $order->lead->attachments->map(function ($attachment) {
                return [
                    'url'  => asset('storage/' . $attachment->file_location),
                    'name' => basename($attachment->file_location),
                    'size' => $attachment->file_size,
                ];
            })
            : collect();

        return view('admin.order.view', compact('order', 'attachments', 'leadAttachments'));
    }

    /* -------------------- Other Admin Pages -------------------- */

    public function calendar()
   {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $salespeople = User::whereIn('role', ['salesperson', 'head-salesperson'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.calendar', compact('salespeople'));
    }




    /* -------------------- Fulfillment -------------------- */
    public function fulfillment(Request $request)
    {
        // --- pagination
        $perPage = (int) $request->query('per_page', 10);
        $perPage = $perPage > 0 ? $perPage : 10;

        // --- filters from toolbar
        $orderId  = trim((string) $request->query('order_id', ''));
        $artistId = $request->query('artist', '');           // allow '' | '0' | '123'
        $q        = trim((string) $request->query('q', ''));
        $task     = strtolower(trim((string) $request->query('task', '')));
        $status   = strtolower(trim((string) $request->query('status', '')));

        // ---- Date range from "dd/mm/yyyy - dd/mm/yyyy"
        $from = $to = null;
        if ($dr = trim((string) $request->query('date_range', ''))) {
            // tolerate " - " or "~"
            $parts = preg_split('/\s*[-~]\s*/', $dr);
            if (!empty($parts[0])) {
                try { $from = \Carbon\Carbon::createFromFormat('d/m/Y', trim($parts[0]))->format('Y-m-d'); } catch (\Throwable $e) {}
            }
            if (!empty($parts[1])) {
                try { $to = \Carbon\Carbon::createFromFormat('d/m/Y', trim($parts[1]))->format('Y-m-d'); } catch (\Throwable $e) {}
            }
        }

        // ---- latest permit row per product
        $latestPermit = DB::table('product_permit')
            ->select('product_id', DB::raw('MAX(id) as last_id'))
            ->groupBy('product_id');

        $query = DB::table('products as p')
            ->join('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('products as op', 'op.ProductID', '=', 'p.redoOf')
            ->leftJoin('delivery_breakdowns as d', 'd.ProductID', '=', 'p.ProductID')
            ->leftJoinSub($latestPermit, 'pp', 'pp.product_id', '=', 'p.ProductID')
            ->leftJoin('product_permit as pf', 'pf.id', '=', 'pp.last_id')
            ->where(function ($q) {
                $q->whereNull('o.status')->orWhere('o.status', 0);
            })
            // 1) not awaiting_keyin
            ->where(function ($w) {
                $w->whereNull('o.orderStatus')
                ->orWhere('o.orderStatus', '!=', 'awaiting_keyin');
            })
            // 2) editable = 0 OR not in_progress
            ->where(function ($w) {
                $w->where('p.editable', 0)
                ->orWhere(function ($w2) {
                    $w2->whereNull('o.orderStatus')
                        ->orWhere('o.orderStatus', '!=', 'in_progress');
                });
            })
            ->whereNotNull('p.taskType')
            ->select([
                'p.ProductID','p.OrderID','p.productName','p.taskType','p.status as product_status',
                'p.redoOf','p.editable',
                'o.id as order_id','o.redo as order_redo','o.orderTitle','o.companyName',
                'o.orderDate','o.deadline as order_deadline','o.created_at as order_created_at',
                'd.BreakdownID as breakdown_id','d.date as delivery_date','d.time as delivery_time',
                'd.location as delivery_location','d.deliver_install_type',
                'pf.permit_file', 
                'p.installation_task_type','p.installation_status','p.installation_accepted',
                'd.method as delivery_method',
            ]);

        // priority: how many of [permit, delivery date, delivery location] are empty
        $missingExpr = '((pf.permit_file IS NULL OR pf.permit_file = "")'
            .' + (d.date IS NULL)'
            .' + (d.location IS NULL OR d.location = ""))';

        $query
            ->orderByRaw("
                CASE
                    WHEN $missingExpr = 3 THEN 0
                    WHEN $missingExpr = 2 THEN 1
                    WHEN $missingExpr = 1 THEN 2
                    ELSE 3
                END ASC
            ")
            // then, inside each group, sort by date/time as before
            ->orderByRaw('COALESCE(d.date, o.orderDate, o.created_at) ASC')
            ->orderByRaw("COALESCE(d.time, '00:00:00') ASC");

        // (1) Order box: numeric id or text (job title)
        if ($orderId !== '') {
            if (preg_match('/^\d+$/', $orderId)) {
                $query->where(function ($w) use ($orderId) {
                    $w->where('o.id', $orderId)
                    ->orWhere('p.ProductID', 'like', "%{$orderId}%")
                    ->orWhere('p.redoOf',   'like', "%{$orderId}%");
                });
            } else {
                $query->where('o.orderTitle', 'like', "%{$orderId}%");
            }
        }

        // (2) Artist filter (assignee)
        if ($artistId !== '' && $artistId !== null) {
            $query->where('o.artist_id', (int) $artistId);
        }

        // (3) Global search
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('o.orderTitle',  'like', "%{$q}%")
                ->orWhere('o.companyName','like', "%{$q}%")
                ->orWhere('p.productName','like', "%{$q}%");
            });
        }

        // (4) Task filter - follow same logic as installation Job Order
        if ($task !== '') {
            // normalize
            $taskNorm = strtolower($task);

            if (in_array($taskNorm, ['printing', 'furnishing'], true)) {
                // simple 1-to-1
                $query->whereRaw('LOWER(p.taskType) = ?', [$taskNorm]);

            } elseif ($taskNorm === 'delivery') {
                // Dispatch Control only:
                //  - base taskType = delivery
                //  - NOT the "delivery & installation" combo
                $query->where(function ($w) {
                    $w->whereRaw('LOWER(p.taskType) = ?', ['delivery'])
                    ->where(function ($w2) {
                        $w2->whereNull('p.installation_task_type')
                            ->orWhere('p.installation_task_type', '!=', 1)
                            ->orWhereRaw('LOWER(d.method) <> "delivery_installation"');
                    });
                });

            } elseif (in_array($taskNorm, ['installation', 'delivery_installation', 'delivery_and_installation'], true)) {
                // Delivery & Installation only:
                //  - either real installation task
                //  - OR delivery rows that are the installation leg:
                //        installation_task_type = 1 AND d.method = 'delivery_installation'
                $query->where(function ($w) {
                    $w->whereRaw('LOWER(p.taskType) = ?', ['installation'])
                    ->orWhere(function ($w2) {
                        $w2->whereRaw('LOWER(p.taskType) = ?', ['delivery'])
                            ->where('p.installation_task_type', 1)
                            ->whereRaw('LOWER(d.method) = "delivery_installation"');
                    });
                });
            }
        }

        // (5) Status filter
        if ($status !== '') {
            $query->whereRaw('LOWER(p.status) = ?', [$status]);
        }

        // --- DELIVERY DATE range (accepts either from/to or date_range)
    $from = trim((string) $request->query('from', ''));
    $to   = trim((string) $request->query('to',   ''));

    // normalize to Y-m-d; input[type=date] already posts Y-m-d
    $normDate = function (?string $v) {
        if (!$v) return null;
        $v = trim($v);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return $v;  // already Y-m-d
        try { return \Carbon\Carbon::parse($v)->format('Y-m-d'); } catch (\Throwable $e) { return null; }
    };

    $from = $normDate($from);
    $to   = $normDate($to);

    // fallback: support old "date_range" field in dd/mm/yyyy - dd/mm/yyyy
    if (!$from && !$to) {
        if ($dr = trim((string) $request->query('date_range', ''))) {
            $parts = preg_split('/\s*[-~]\s*/', $dr);
            if (!empty($parts[0])) {
                try { $from = \Carbon\Carbon::createFromFormat('d/m/Y', trim($parts[0]))->format('Y-m-d'); } catch (\Throwable $e) {}
            }
            if (!empty($parts[1])) {
                try { $to   = \Carbon\Carbon::createFromFormat('d/m/Y', trim($parts[1]))->format('Y-m-d'); } catch (\Throwable $e) {}
            }
        }
    }

        if ($from && $to) {
            $query->whereBetween('d.date', [$from, $to]);
        } elseif ($from) {
            $query->whereDate('d.date', '>=', $from);
        } elseif ($to) {
            $query->whereDate('d.date', '<=', $to);
        }

        // paginate
        $rows = $query->paginate($perPage)->appends($request->query());

        // map display
        $rows->setCollection(
            $rows->getCollection()->map(function ($r) {
                $baseOrderId   = $r->order_redo ?: $r->order_id;
                $baseProductId = $r->redoOf ?: $r->ProductID;
                $rFlag         = ($r->redoOf && (int)$r->editable === 1) ? 'R' : '';

                $year = $r->orderDate
                    ? \Carbon\Carbon::parse($r->orderDate)->format('Y')
                    : \Carbon\Carbon::parse($r->order_created_at)->format('Y');

                $productCode = sprintf('#ORD-%s-%03d-P%04d%s', $year, (int)$baseOrderId, (int)$baseProductId, $rFlag);

                // --- Decide logical task type (same idea as installation Job Order) ---
                $baseTask       = strtolower((string) ($r->taskType ?? ''));          // printing / furnishing / delivery / installation
                $deliveryMethod = strtolower(trim((string) ($r->delivery_method ?? '')));
                $installFlag    = (int) ($r->installation_task_type ?? 0);

                if ($installFlag === 1 && ($deliveryMethod === 'delivery' || $deliveryMethod === 'installation')) {
                    // treat as installation so it shows as Delivery & Installation
                    $logicalTask = 'installation';
                } else {
                    $logicalTask = $baseTask;
                }

                // Final label shown in the pill
                $taskLabel = match ($logicalTask) {
                    'delivery'     => 'Dispatch Control',
                    'installation' => 'Delivery & Installation',
                    default        => ($logicalTask !== '' ? ucfirst($logicalTask) : '-'),
                };

                $dt = null;
                if ($r->delivery_date) {
                    $dt = $r->delivery_time
                        ? \Carbon\Carbon::parse($r->delivery_date.' '.$r->delivery_time)->format('Y-m-d H:i')
                        : \Carbon\Carbon::parse($r->delivery_date)->format('Y-m-d');
                }

                $installLabel = match (strtolower((string)$r->deliver_install_type)) {
                    'in_house','in-house' => 'In House',
                    'outsource'           => 'Outsource',
                    'both'                => 'Both',
                    default               => '—',
                };

                $deadline = null;
                if (!empty($r->order_deadline)) {
                    try {
                        $deadline = \Carbon\Carbon::parse($r->order_deadline)->format('Y-m-d');
                    } catch (\Throwable $e) {
                        $deadline = $r->order_deadline; // fallback raw
                    }
                }

                // permit_url from either "path" or "path|OriginalName"
                $permitUrl = null;
                if (!empty($r->permit_file)) {
                    $parts = explode('|', $r->permit_file, 2);
                    $storedPath = ltrim($parts[0], '/');               // e.g. "permits/2025/11/file.pdf"
                    // files are saved on "public" disk → served at /storage/...
                    $permitUrl  = asset('storage/'.$storedPath);
                }

                return (object)[
                    'product_code'   => $productCode,
                    'product_id'     => (int)$r->ProductID,
                    'breakdown_id'   => (int)($r->breakdown_id ?? 0),
                    'order_title'    => $r->orderTitle,
                    'company'        => $r->companyName,
                    'task_label'     => $taskLabel,
                    'status'         => (string)($r->product_status ?? ''),
                    'delivery_dt'    => $dt,
                    'deadline'     => $deadline,
                    'delivery_loc'   => (string)($r->delivery_location ?? ''),
                    'install_type'   => $installLabel,
                    // 'outsource_cost' => is_null($r->outsource_cost) ? null : (float)$r->outsource_cost,
                    'permit_url'     => $permitUrl,
                ];
            })
        );

        // --- populate artist dropdown (all artists & head-artists)
        $assignees = DB::table('users')
            ->whereIn('role', ['artist', 'head-artist'])
            ->orderBy('name')
            ->get(['id','name','role']);

        // optional: distinct statuses for the status dropdown
        $statuses = DB::table('products')
            ->whereNotNull('status')
            ->selectRaw('LOWER(status) as status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->toArray();

        return view('admin.fulfillment', [
            'rows'       => $rows,
            'per_page'   => $perPage,
            'assignees'  => $assignees,
            'statuses'   => $statuses,
            'filters'    => [
                'from' => $from,
                'to'   => $to,
                // ...whatever else you’re already passing
            ],
        ]);
    }

    public function fulfillmentShow(Request $request, int $id)
    {
        $product = Product::findOrFail($id);
        $order   = $product->order()->first();

        // Eager load everything the page needs (same as artist example)
        $product->load([
            'order:id,order_number,orderTitle,companyName,leadName,leadPhone,leadEmail,lead_id,deadline,created_at,artist_id,salesperson_id,orderAttachment',
            'order.artist:id,name',
            'order.salesperson:id,name',

            'items' => fn($q) => $q->select(
                'ItemID','ProductID','itemName','quantity',
                'sizeWidth','sizeUnit','sizeHeight',
                'bleedUnit','bleedTop','bleedBottom','bleedLeft','bleedRight',
                'finishing','material','prime_centre'
            )->with('spec:SpecificationID,ItemID,printer,cutter,lamination'),

            'deliveryBreakdowns:BreakdownID,ProductID,method,location,quantity,date,time,deliver_install_type,outsource_cost',

            // remarks + author
            'remarks' => function ($q) {
                $q->select('RemarkID','ProductID','operation','remark','created_at','user_id')
                ->with('user:id,name');
            },
        ]);

        // ---------- Redo-aware display IDs ----------
        $isRedo     = !is_null($product->redoOf);
        $isEditable = (int)($product->editable ?? 0) === 1; // only the selected redo shows "R"
        $showR      = $isRedo && $isEditable;

        // display ProductID: original for redo
        $pidForDisplay = $isRedo ? (int)$product->redoOf : (int)$product->ProductID;

        // display OrderID: original order for redo (else current)
        $displayOrderId = (int)$product->OrderID;
        if ($isRedo) {
            $origOrderId = Product::where('ProductID', $product->redoOf)->value('OrderID');
            if ($origOrderId) $displayOrderId = (int)$origOrderId;
        }

        // resolve the year from the display order's dates
        $displayOrderYear = now()->format('Y');
        if ($displayOrderId) {
            $ordDates = DB::table('orders')->where('id', $displayOrderId)
                ->select(['orderDate','created_at'])->first();
            $dateForYear = $ordDates->orderDate ?? $ordDates->created_at ?? now();
            $displayOrderYear = \Carbon\Carbon::parse($dateForYear)->format('Y');
        }

        $productCode = sprintf(
            '#ORD-%s-%04d-P%04d%s',
            $displayOrderYear,
            $displayOrderId,
            $pidForDisplay,
            $showR ? 'R' : ''
        );

        // ---------- Lead attachments ----------
        $toPublicUrl = function (string $p): string {
            $p = ltrim($p, '/');
            if (Str::startsWith($p, 'storage/')) {
                return url($p);
            }
            return Storage::disk('public')->url($p);
        };

        // roles considered "artist-side"
        $artistRoles = ['artist', 'head-artist', 'data-entry', 'boss', 'admin'];

        // 1) Try order_attachments table first
        $rows = OrderAttachment::with('uploader:id,name,role')
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get();

        $mapRow = function (OrderAttachment $att) use ($toPublicUrl) {
            $p = ltrim((string) $att->file_path, '/');
            $web = \Illuminate\Support\Str::startsWith($p, 'storage/') ? $p : 'storage/' . $p;

            return [
                'id'            => $att->id,
                'name'          => $att->original_name ?: basename($p),
                'url'           => $toPublicUrl($web),
                'size'          => (int) $att->size,
                'ext'           => pathinfo($p, PATHINFO_EXTENSION),
                'uploaded_by'   => optional($att->uploader)->name,
                'uploader_role' => optional($att->uploader)->role,
                'uploaded_at'   => optional($att->created_at)->format('d M Y'),
            ];
        };

        if ($rows->isNotEmpty()) {
            // split by uploader role
            $headerAttachments = $rows
                ->filter(function ($att) use ($artistRoles) {
                    return !in_array(optional($att->uploader)->role, $artistRoles, true);
                })
                ->map($mapRow)
                ->values();

            $attachments = $rows
                ->filter(function ($att) use ($artistRoles) {
                    return in_array(optional($att->uploader)->role, $artistRoles, true);
                })
                ->map($mapRow)
                ->values();
        }

        $leadAttachments = collect();
        if ($order?->lead_id) {
            $leadAttachments = LeadAttachment::where('lead_id', $order->lead_id)
                ->orderBy('id')
                ->get()
                ->map(function ($row) {
                    $p = $row->file_location;
                    $p = ltrim($p, '/');
                    $p = preg_replace('#^public/#', '', $p);
                    $p = preg_replace('#^storage/#', '', $p);
                    $web = 'storage/' . $p;

                    return (object)[
                        'name' => basename($p),
                        'size' => (int) $row->file_size,
                        'ext'  => $row->file_extension,
                        'url'  => asset($web),
                    ];
                });
        }

        // attachments saved on order
        // $raw   = $order->orderAttachment; // string|array|null
        // $paths = [];
        // if (is_array($raw)) {
        //     $paths = $raw;
        // } elseif (is_string($raw)) {
        //     $rawTrim = trim($raw);
        //     if (Str::startsWith($rawTrim, '[')) {
        //         $paths = json_decode($rawTrim, true) ?: [];
        //     } else {
        //         $paths = array_filter(array_map('trim', explode(',', $rawTrim)));
        //     }
        // }

        // $orderFiles = collect($paths)->map(function ($p) use ($toPublicUrl) {
        //     $p   = ltrim($p, '/');
        //     $url = $toPublicUrl($p);
        //     return [
        //         'name' => basename($p),
        //         'ext'  => pathinfo($p, PATHINFO_EXTENSION),
        //         'url'  => $url,
        //     ];
        // });

        // // Attachments helper fallback
        // $attachments = [];
        // if (method_exists($this, 'getOrderAttachments')) {
        //     $attachments = (array) $this->getOrderAttachments($order);
        // } else {
        //     $raw = $order?->orderAttachment;
        //     if (is_string($raw) && trim($raw) !== '') {
        //         $decoded = json_decode($raw, true);
        //         if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        //             $attachments = $decoded;
        //         } else {
        //             $attachments = [$raw];
        //         }
        //     } elseif (is_array($raw)) {
        //         $attachments = $raw;
        //     }
        // }

        // ---------- Installation proof files ----------
        $installationProofs = DB::table('installation_proofs')
            ->where('ProductID', $product->ProductID)
            ->orderBy('created_at')
            ->get()
            ->map(function ($row) use ($toPublicUrl) {
                $path = (string) ($row->file_path ?? '');
                if ($path === '') {
                    return null;
                }

                $url  = $toPublicUrl($path);

                return (object) [
                    'id'   => $row->id,
                    'name' => $row->original_name ?: basename($path),
                    'url'  => $url,
                    'size' => (int) ($row->size ?? 0),
                    'mime' => $row->mime,
                ];
            })
            ->filter()
            ->values();

        // ---------- Fulfillment progress (printing, furnishing, delivery, installation) ----------
        $ALL_STAGES = ['printing', 'furnishing', 'delivery', 'installation'];

        $rows = DB::table('fulfillment_progress')
            ->where('ProductID', $product->ProductID)
            ->whereIn('stage', $ALL_STAGES)
            ->get();

        // keep latest row per stage
        $latest = []; // stage => ['status','acceptedAt','completedAt','_rank']
        foreach ($rows as $r) {
            $rank = $r->completedAt ?? $r->acceptedAt ?? $r->created_at;
            $cur  = $latest[$r->stage]['_rank'] ?? null;
            if (!$cur || $rank > $cur) {
                $latest[$r->stage] = [
                    'status'      => strtolower((string)$r->status),
                    'acceptedAt'  => $r->acceptedAt,
                    'completedAt' => $r->completedAt,
                    '_rank'       => $rank,
                ];
            }
        }

        $currentStage  = strtolower((string)$product->taskType);
        $currentStatus = strtolower((string)$product->status);

        // $hasDelivery     = isset($latest['delivery']);
        // $hasInstallation = isset($latest['installation']);

        $st = fn($k) => $latest[$k]['status'] ?? null;
        $isCompleted = fn($k) => ($latest[$k]['status'] ?? null) === 'completed';

        $laterCompleted = [
            'printing'     => $isCompleted('furnishing')
                            || $isCompleted('delivery')
                            || $isCompleted('installation'),
            'furnishing'   => $isCompleted('delivery') || $isCompleted('installation'),
            'delivery'     => false,
            'installation' => false,
        ];

        $progress = collect($ALL_STAGES)->mapWithKeys(function ($stage) use (
            $latest,
            $currentStage,
            $currentStatus,
            $laterCompleted
        ) {
            $row = $latest[$stage] ?? null;

            if ($row) {
                $status      = $row['status'];
                $acceptedAt  = $row['acceptedAt'];
                $completedAt = $row['completedAt'];
            } else {
                // No DB row for this stage
                // If a later stage is completed → BLANK; otherwise default to "pending"
                $status      = $laterCompleted[$stage] ? '' : 'pending';
                $acceptedAt  = null;
                $completedAt = null;
            }

            // If this is the product's current stage and it's in progress,
            // show in_progress (unless already completed/rejected)
            if (
                $currentStage === $stage &&
                $currentStatus === 'in_progress' &&
                !in_array($status, ['completed', 'rejected'], true)
            ) {
                $status = 'in_progress';
            }

            // Optional duration when both timestamps exist
            $duration = null;
            if ($acceptedAt && $completedAt) {
                $start    = \Carbon\Carbon::parse($acceptedAt);
                $end      = \Carbon\Carbon::parse($completedAt);
                $duration = $start->diffForHumans($end, [
                    'parts'  => 3,
                    'short'  => true,
                    'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
                ]);
            }

            return [
                $stage => [
                    'status'       => $status,      // '' means: render no pill
                    'accepted_at'  => $acceptedAt,
                    'completed_at' => $completedAt,
                    'duration'     => $duration,
                ],
            ];
        });

        // ---------- Extra business rules to hide unnecessary pending stages ----------
        $progress = $progress->map(function (array $row, string $stage) use ($product) {

            // hide "pending" for installation if product is not installation 
            // AND all installation_* fields are null.
            if (
                $stage === 'installation' &&
                strtolower((string) $product->taskType) !== 'installation' &&
                is_null($product->installation_task_type) &&
                is_null($product->installation_status) &&
                is_null($product->installation_accepted) &&
                ($row['status'] ?? null) === 'pending'
            ) {
                $row['status'] = '';   // Blade will hide pill completely
            }

            // hide "pending" for delivery stage if product is not delivery
            if (
                $stage === 'delivery' &&
                strtolower((string) $product->taskType) !== 'delivery' &&
                ($row['status'] ?? null) === 'pending'
            ) {
                $row['status'] = '';
            }

            return $row;
        });

        // Deliveries list (nulls last)
        $deliveries = $product->deliveryBreakdowns()
            ->orderByRaw('CASE WHEN `date` IS NULL THEN 1 ELSE 0 END, `date` ASC, `time` ASC')
            ->get();

        return view('admin.fulfillment.show', [
            'product'         => $product,
            'order'           => $order,
            'items'           => $product->items,
            'attachments'     => $attachments,
            'progress'        => $progress,
            'deliveries'      => $deliveries,
            'leadAttachments' => $leadAttachments,
            // 'orderFiles'      => $orderFiles,
            'headerAttachments' => $headerAttachments,
            'productCode'     => $productCode,
            'displayOrderId'  => $displayOrderId,
            'installationProofs' => $installationProofs,
        ]);
    }

    public function updateDeliveries(Request $request, int $productId)
    {
        $data = $request->validate([
            'rows'                        => ['required','array'],
            'rows.*.id'                   => ['nullable','integer'],
            'rows.*.method'               => ['required','string','max:255'],
            'rows.*.date'                 => ['nullable','date'],
            'rows.*.time'                 => ['nullable','date_format:H:i'],
            'rows.*.location'             => ['nullable','string','max:255'],
            'rows.*.quantity'             => ['required','integer','min:0'],
            'rows.*.deliver_install_type' => ['nullable','string','in:in-house,outsource,both'],
            'rows.*.outsource_cost'       => ['nullable','numeric','min:0'],
            'rows.*._delete'              => ['nullable','boolean'],
        ]);

        // product quantity for server-side guard
        $productQty = (int) DB::table('products')->where('ProductID', $productId)->value('totalQuantity');

        // normalize method to DB values
        $canonMethod = function (?string $m): ?string {
            $m = strtolower(trim((string)$m));
            if ($m === '') return null;
            if ($m === 'courier') return 'courier';
            if (in_array($m, ['self pickup','self_pickup','pickup'], true)) return 'self pickup';
            if ($m === 'delivery') return 'delivery';
            if ($m === 'installation' || str_contains($m,'install')) return 'installation';
            return $m;
        };

        // SERVER-SIDE QUANTITY GUARD (exclude deletions)
        $sum = 0;
        foreach ($data['rows'] as $r) {
            if (!empty($r['_delete'])) continue;
            $sum += (int)($r['quantity'] ?? 0);
        }
        if ($sum > $productQty) {
            return back()->withInput()->withErrors([
                'rows' => "Total delivery quantity ({$sum}) cannot exceed product quantity ({$productQty})."
            ]);
        }

        DB::beginTransaction();
        try {
            // 🔒 lock product row so status/accepted updates are consistent
            $productRow = DB::table('products')
                ->where('ProductID', $productId)
                ->select(
                    'ProductID',
                    'taskType',
                    'status',
                    'accepted',
                    'editable',
                    'installation_task_type',
                    'installation_status',
                    'installation_accepted'
                )
                ->lockForUpdate()
                ->first();

            if (!$productRow) {
                throw new \RuntimeException('Product not found.');
            }

            foreach ($data['rows'] as $row) {
                $id     = $row['id'] ?? null;
                $method = $canonMethod($row['method'] ?? null);

                $type   = strtolower(trim((string)($row['deliver_install_type'] ?? '')));
                if (!in_array($type, ['in-house','outsource','both'], true)) {
                    $type = null;
                }

                $qty    = isset($row['quantity']) ? (int)$row['quantity'] : null;

                $allowCost = ($method === 'delivery' || $method === 'installation') && in_array($type, ['outsource','both'], true);
                $cost      = $allowCost ? ($row['outsource_cost'] ?? null) : null;

                $payload = [
                    'method'               => $method,
                    'date'                 => $row['date'] ?? null,
                    'time'                 => $row['time'] ?? null,
                    'location'             => $row['location'] ?? null,
                    'quantity'             => $qty,
                    'deliver_install_type' => $type,
                    'outsource_cost'       => $cost,
                    'updated_at'           => now(),
                ];

                if (!empty($row['_delete'])) {
                    if ($id) {
                        DB::table('delivery_breakdowns')
                            ->where('BreakdownID', (int)$id)
                            ->where('ProductID', $productId)
                            ->delete();
                    }
                    continue;
                }

                if ($id) {
                    DB::table('delivery_breakdowns')
                        ->where('BreakdownID', (int)$id)
                        ->where('ProductID', $productId)
                        ->update($payload);
                } else {
                    $payload['ProductID'] = $productId;
                    $payload['created_at'] = now();
                    DB::table('delivery_breakdowns')->insert($payload);
                }
            }

            // 🔁 If this product was rejected, push it back to in_progress
            if ($productRow) {
                $now = now();

                $taskType = strtolower(trim((string)($productRow->taskType ?? '')));
                $status   = strtolower(trim((string)($productRow->status ?? '')));

                $accepted       = $productRow->accepted;
                $editable       = (int)($productRow->editable ?? 0);

                $instTaskType   = (int)($productRow->installation_task_type ?? 0);
                $instStatus     = strtolower(trim((string)($productRow->installation_status ?? '')));
                $instAccepted   = $productRow->installation_accepted;

                $acceptedIsZero     = (!is_null($accepted)     && (int)$accepted     === 0);
                $instAcceptedIsZero = (!is_null($instAccepted) && (int)$instAccepted === 0);

                // Dispatch rejected
                $dispatchRejected =
                    ($taskType === 'delivery'
                    && $status   === 'rejected'
                    && $acceptedIsZero
                    && $editable === 1);

                // Installation rejected via main status
                $installationRejectedByTask =
                    ($taskType === 'installation'
                    && $status   === 'rejected'
                    && $acceptedIsZero
                    && $editable === 1);

                // Installation rejected via installation_* fields
                $installationRejectedByFields =
                    ($instTaskType === 1
                    && $instStatus   === 'rejected'
                    && $instAcceptedIsZero);

                $productUpdates = [];

                // For any dispatch / installation rejection → reset main status
                if ($dispatchRejected || $installationRejectedByTask) {
                    $productUpdates['status']   = 'in_progress';
                    $productUpdates['accepted'] = null;
                }

                // For installation rejection → also reset installation_* flags
                if ($installationRejectedByFields) {
                    $productUpdates['installation_status']   = 'in_progress';
                    $productUpdates['installation_accepted'] = null;
                }

                if (!empty($productUpdates)) {
                    $productUpdates['updated_at'] = $now;

                    DB::table('products')
                        ->where('ProductID', $productId)
                        ->update($productUpdates);
                }
            }

            DB::commit();
            return back()->with('ok','Deliveries updated.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->with('error','Failed to update deliveries.');
        }
    }

    public function storePermit(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'permit'     => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,gif,webp', 'max:20480'], // 20MB
        ]);

        $userId    = $request->user()->id ?? null;
        $productId = (int) $data['product_id'];
        $file      = $request->file('permit');

        // store to storage/app/public/permits/YYYY/mm/
        $path = $file->store('permits/' . date('Y/m'), 'public');
        $originalName = $file->getClientOriginalName();

        // store both the path and original filename together (joined by |)
        $pathWithName = $path . '|' . $originalName;

        DB::table('product_permit')->updateOrInsert(
            ['product_id' => $productId],
            [
                'user_id'     => $userId,
                'permit_file' => $pathWithName,  // store full string: path|filename
                'uploaded_at' => now(),
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );

        return back()->with('success', 'Permit uploaded.');
    }

    public function fulfillmentStoreRemark(Request $request, int $productId)
    {
        // ensure product exists
        $product = Product::findOrFail($productId);

        $data = $request->validate([
            'operation' => 'required|in:printing,furnishing,installation,courier,self_pickup,artist',
            'remark'    => 'required|string|max:2000',
        ]);

        DB::table('product_remarks')->insert([
            'ProductID'  => $product->ProductID ?? $product->id,
            'user_id'    => Auth::id(),
            'operation'  => $data['operation'],
            'remark'     => $data['remark'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Product remark added.');
    }

    public function fulfillmentUpdateRemark(Request $request, int $productId, int $remarkId)
    {
        // ensure remark belongs to this product
        $remark = DB::table('product_remarks')
            ->where('RemarkID', $remarkId)
            ->where('ProductID', $productId)
            ->first();

        if (!$remark) {
            abort(404);
        }

        // only owner can edit
        if ((int)$remark->user_id !== (int)Auth::id()) {
            abort(403);
        }

        $data = $request->validate([
            'operation' => 'required|in:printing,furnishing,installation,courier,self_pickup,artist',
            'remark'    => 'required|string|max:2000',
        ]);

        DB::table('product_remarks')
            ->where('RemarkID', $remarkId)
            ->update([
                'operation'  => $data['operation'],
                'remark'     => $data['remark'],
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Product remark updated.');
    }

    public function fulfillmentDestroyRemark(int $productId, int $remarkId)
    {
        $remark = DB::table('product_remarks')
            ->where('RemarkID', $remarkId)
            ->where('ProductID', $productId)
            ->first();

        if (!$remark) {
            abort(404);
        }

        // only owner can delete
        if ((int)$remark->user_id !== (int)Auth::id()) {
            abort(403);
        }

        DB::table('product_remarks')->where('RemarkID', $remarkId)->delete();

        return back()->with('success', 'Product remark deleted.');
    }

    public function viewPermit($product_id)
{
    $permit = DB::table('product_permit')
                ->where('product_id', $product_id)
                ->first();

    if (!$permit || !str_contains($permit->permit_file, '|')) {
        abort(404);
    }

    [$path, $originalName] = explode('|', $permit->permit_file, 2);
    $fullPath = storage_path('app/public/' . $path);

    if (!file_exists($fullPath)) {
        abort(404);
    }

    return response()->file($fullPath, [
        'Content-Disposition' => 'inline; filename="' . $originalName . '"'
    ]);
}

    public function downloadPermit(int $productId)
    {
        $row = DB::table('product_permit')->where('product_id', $productId)->first();

        if (!$row || empty($row->permit_file)) {
            abort(404, 'Permit not found.');
        }

        // Stored as "path|originalName"
        [$path, $originalName] = array_pad(explode('|', $row->permit_file, 2), 2, null);

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'Permit file missing.');
        }

        return Storage::disk('public')->download($path, $originalName ?: basename($path));
    }


    public function fulfillmentEdit($id)
    {
        $order = Order::with('artist')->findOrFail($id);
        return view('admin.fulfillment-edit', compact('order'));
    }

    public function dataKeyIn()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) abort(403, 'Unauthorized');

        return view('admin.data-key-in');
    }

    public function settings()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) abort(403, 'Unauthorized');

        $users = User::all();
        return view('admin.settings', compact('users'));
    }

    public function exportOrders(Request $request)
    {
        return response()->noContent();
    }
   
public function dispatchControl()
{
    $tasks = [
        ['id' => '#ORD006-P3', 'name' => 'Posters',        'type' => 'Self Pick up', 'deadline' => '2025-07-30', 'status' => 'In Progress'],
        ['id' => '#ORD003-P1', 'name' => 'Business Cards', 'type' => 'Self Pick up', 'deadline' => '2025-08-15', 'status' => 'In Progress'],
        ['id' => '#ORD007-P2', 'name' => 'Banner Signs',   'type' => 'Courier',      'deadline' => '2025-08-20', 'status' => 'Completed'],
        ['id' => '#ORD009-P4', 'name' => 'Vinyl Decals',   'type' => 'Courier',      'deadline' => '2025-08-25', 'status' => 'Completed'],
    ];

    return view('admin.dispatch', compact('tasks')); // 对应 resources/views/admin/dispatch.blade.php
}

public function dispatchExport()
{
    return back()->with('status', 'CSV export is not implemented yet.');
}

// Delivery & Installation — 列表页（带筛选 + 分页）
// Delivery & Installation — 列表页（用 orders 表，带筛选 + 分页）
public function installation(Request $request)
{
    $q         = trim($request->get('q', ''));
    $artist    = trim($request->get('artist', ''));
    $details   = trim($request->get('details', ''));
    $status    = $request->get('status', 'all');
    $dateRange = trim($request->get('date_range', ''));

    $taskCol     = \Schema::hasColumn('orders','task_type')
                    ? 'task_type'
                    : (\Schema::hasColumn('orders','delivery_installation_type') ? 'delivery_installation_type' : null);
    $deadlineCol = \Schema::hasColumn('orders','deadline') ? 'deadline' : 'created_at';
    $statusCol   = \Schema::hasColumn('orders','orderStatus') ? 'orderStatus' : 'status';

    $rows = Order::query()
        ->with([
            'artist',
            // 只拿最需要的列，避免负担
            'products:ProductID,OrderID'
        ])
        // 仅显示 Installation
        ->when($taskCol, fn($qb) => $qb->where($taskCol, 'installation'))
        // 搜索
        ->when($q !== '', function ($qb) use ($q) {
            $qb->where(function ($sub) use ($q) {
                $sub->where('order_number','like',"%{$q}%")
                    ->orWhere('orderTitle','like',"%{$q}%");
            });
        })
        ->when($artist !== '', fn($qb) => $qb->whereHas('artist', fn($w)=>$w->where('name','like',"%{$artist}%")))
        ->when($details !== '', function ($qb) use ($details) {
            $qb->where(function ($sub) use ($details) {
                $sub->where('orderTitle','like',"%{$details}%")
                    ->orWhere('description','like',"%{$details}%");
            });
        })
        ->when($status !== 'all' && $status !== '', fn($qb) => $qb->where($statusCol, $status))
        ->when($dateRange !== '', function ($qb) use ($deadlineCol,$dateRange) {
            $parts = preg_split('/\s*-\s*/', $dateRange);
            if (count($parts) === 2) {
                try {
                    $start = \Carbon\Carbon::createFromFormat('d/m/Y', trim($parts[0]))->startOfDay();
                    $end   = \Carbon\Carbon::createFromFormat('d/m/Y', trim($parts[1]))->endOfDay();
                    $qb->whereBetween($deadlineCol, [$start, $end]);
                } catch (\Exception $e) {}
            }
        })
        ->orderBy($deadlineCol, 'asc')
        ->paginate(20)
        ->appends($request->query());

    // ==== 批量取本页所有产品的 ProductID，并一次性查 product_permit ====
    $productIds = $rows->getCollection()
        ->flatMap(fn($o) => $o->products?->pluck('ProductID') ?? collect())
        ->filter()
        ->unique()
        ->values();

    $permitMap = ProductPermit::whereIn('product_id', $productIds)
        ->orderByDesc('uploaded_at')
        ->get()
        ->groupBy('product_id'); // product_id => [ProductPermit,...]

    // 统一映射到视图对象
    $rows->getCollection()->transform(function($o) use ($taskCol,$deadlineCol,$statusCol,$permitMap){
        $o->order_id       = $o->order_number ?? ('ORD-'.$o->id);
        $o->product_name   = $o->orderTitle ?? ($o->product_name ?? '');
        $o->task_type      = $taskCol ? $o->{$taskCol} : null;
        $o->deadline       = $o->{$deadlineCol} ? \Carbon\Carbon::parse($o->{$deadlineCol}) : null;
        $o->status         = $o->{$statusCol};

        // 本订单的所有产品 id
        $productIdList     = $o->products?->pluck('ProductID')->filter()->values() ?? collect();
        // 用于“上传”的主 product_id（取第一个）
        $o->product_primary_id = $productIdList->first();

        // 若任一产品有 permit，则认为此行 has_permit = true，并给一个可用的 permit 记录
        $firstPermit = null;
        foreach ($productIdList as $pid) {
            if (isset($permitMap[$pid]) && $permitMap[$pid]->isNotEmpty()) {
                $firstPermit = $permitMap[$pid]->first();
                break;
            }
        }
        $o->permit     = $firstPermit;                 // ProductPermit|null
        $o->has_permit = $firstPermit !== null;        // bool

        return $o;
    });

    return view('admin.installation', compact('rows'));
}
public function installationExport(Request $request)
{
    $q         = trim($request->get('q', ''));
    $artist    = trim($request->get('artist', ''));
    $details   = trim($request->get('details', ''));
    $status    = $request->get('status', 'all');
    $dateRange = trim($request->get('date_range', ''));

    $taskCol     = \Schema::hasColumn('orders','task_type')
                    ? 'task_type'
                    : (\Schema::hasColumn('orders','delivery_installation_type') ? 'delivery_installation_type' : null);
    $deadlineCol = \Schema::hasColumn('orders','deadline') ? 'deadline' : 'created_at';
    $statusCol   = \Schema::hasColumn('orders','orderStatus') ? 'orderStatus' : 'status';

    $query = Order::query()
        ->with(['artist','products:ProductID,OrderID'])
        ->when($taskCol, fn($qb) => $qb->where($taskCol, 'installation'))
        ->when($q !== '', function ($qb) use ($q) {
            $qb->where(function ($sub) use ($q) {
                $sub->where('order_number','like',"%{$q}%")
                    ->orWhere('orderTitle','like',"%{$q}%");
            });
        })
        ->when($artist !== '', fn($qb) => $qb->whereHas('artist', fn($w)=>$w->where('name','like',"%{$artist}%")))
        ->when($details !== '', function ($qb) use ($details) {
            $qb->where(function ($sub) use ($details) {
                $sub->where('orderTitle','like',"%{$details}%")
                    ->orWhere('description','like',"%{$details}%");
            });
        })
        ->when($status !== 'all' && $status !== '', fn($qb) => $qb->where($statusCol, $status))
        ->when($dateRange !== '', function ($qb) use ($deadlineCol,$dateRange) {
            $parts = preg_split('/\s*-\s*/', $dateRange);
            if (count($parts) === 2) {
                try {
                    $start = \Carbon\Carbon::createFromFormat('d/m/Y', trim($parts[0]))->startOfDay();
                    $end   = \Carbon\Carbon::createFromFormat('d/m/Y', trim($parts[1]))->endOfDay();
                    $qb->whereBetween($deadlineCol, [$start, $end]);
                } catch (\Exception $e) {}
            }
        })
        ->orderBy($deadlineCol, 'asc');

    $data = $query->get();

    // 批量查 permit
    $productIds = $data->flatMap(fn($o) => $o->products?->pluck('ProductID') ?? collect())
                       ->filter()->unique()->values();

    $permitMap = ProductPermit::whereIn('product_id', $productIds)
                  ->get()->groupBy('product_id');

    $headers = [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="installation_export.csv"',
    ];

    $callback = function () use ($data, $taskCol, $deadlineCol, $statusCol, $permitMap) {
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Product ID','Product Name','Task Type','Deadline','Status','Permit/Confirm']);

        foreach ($data as $o) {
            $orderId  = $o->order_number ?? ('ORD-'.$o->id);
            $name     = $o->orderTitle ?? ($o->product_name ?? '');
            $task     = $taskCol ? ($o->{$taskCol} ?? '') : '';
            $deadline = $o->{$deadlineCol} ? \Carbon\Carbon::parse($o->{$deadlineCol})->format('Y-m-d') : '';
            $status   = $o->{$statusCol} ?? '';

            // 任一产品有 permit 即 Yes
            $productIdList = $o->products?->pluck('ProductID')->filter()->values() ?? collect();
            $hasPermit = false;
            foreach ($productIdList as $pid) {
                if (isset($permitMap[$pid]) && $permitMap[$pid]->isNotEmpty()) {
                    $hasPermit = true; break;
                }
            }
            $permitVal = $hasPermit ? 'Yes' : '';

            fputcsv($out, [$orderId, $name, $task, $deadline, $status, $permitVal]);
        }
        fclose($out);
    };

    return response()->stream($callback, 200, $headers);
}
/**
 * 上传 Permit（保存到 product_permit 表 & storage/app/public/permits/{product_id}/）
 * 表单字段：product_id, file
 */
public function installationPermitStore(Request $request)
{
    // 权限按你项目需要自行限制
    // if (!Auth::user()->hasRole('admin')) abort(403, 'Unauthorized');

    $data = $request->validate([
        'product_id' => ['required','integer','exists:products,ProductID'],
        'file'       => ['required','file','mimetypes:image/jpeg,image/png,image/webp,image/gif,application/pdf','max:8192'], // 8MB
    ],[
        'file.mimetypes' => 'Only images (jpg/png/webp/gif) or PDF are allowed.',
        'file.max'       => 'File must be <= 8MB.',
    ]);

    $productId = (int)$data['product_id'];
    $userId    = auth()->id();

    // 目标目录：public/permits/{product_id}/
    $dir  = "permits/{$productId}";
    $ext  = $request->file('file')->getClientOriginalExtension();
    $name = 'permit_'.now()->format('Ymd_His').'_'.$productId.'.'.$ext;

    // 保存到 public 磁盘（确保已 php artisan storage:link）
    $path = $request->file('file')->storeAs($dir, $name, 'public');

    // 记录到 DB
    $permit = ProductPermit::create([
        'user_id'     => $userId,
        'product_id'  => $productId,
        'permit_file' => $path,          // 相对路径，前端用 Storage::url() 输出
        'uploaded_at' => now(),
    ]);

    return back()->with('success', 'Permit uploaded successfully.');
}

/** 下载 Permit（按记录 id 下载） */
public function installationPermitDownload(ProductPermit $permit)
{
    // if (!Auth::user()->hasRole('admin')) abort(403, 'Unauthorized');

    if (!$permit->permit_file || !Storage::disk('public')->exists($permit->permit_file)) {
        return back()->withErrors(['file' => 'File not found.']);
    }

    $absPath  = Storage::disk('public')->path($permit->permit_file);
    $download = 'permit_'.$permit->product_id.'_'.basename($permit->permit_file);
    return Response::download($absPath, $download);
}

/** 删除 Permit（删库 + 删文件） */
public function installationPermitDestroy(ProductPermit $permit)
{
    // if (!Auth::user()->hasRole('admin')) abort(403, 'Unauthorized');

    if ($permit->permit_file && Storage::disk('public')->exists($permit->permit_file)) {
        Storage::disk('public')->delete($permit->permit_file);
    }
    $permit->delete();

    return back()->with('success', 'Permit deleted.');
}


}