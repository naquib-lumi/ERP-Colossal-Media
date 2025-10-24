<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Lead;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class AdminController extends Controller
{
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

        $ordersToAssign = Order::where('orderStatus', 'to_assign')
            ->whereBetween('orderDate', [$start, $end])
            ->whereNotIn('id', $excludedOrderIds)
            ->count();

        $totalOrders = Order::whereBetween('orderDate', [$start, $end])
            ->whereNotIn('id', $excludedOrderIds)
            ->count();

        $inProgressCount = Order::where('orderStatus', 'in_progress')
            ->whereBetween('orderDate', [$start, $end])
            ->whereNotIn('id', $excludedOrderIds)
            ->count();

        $completedCount = Order::where('orderStatus', 'completed')
            ->whereBetween('orderDate', [$start, $end])
            ->whereNotIn('id', $excludedOrderIds)
            ->count();

        $inProgressOrders = Order::where('orderStatus', 'in_progress')
            ->with('artist')
            ->whereNotIn('id', $excludedOrderIds)
            ->orderBy('deadline', 'asc')
            ->take(3)
            ->get();

        $completedOrders = Order::where('orderStatus', 'completed')
            ->with('artist')
            ->whereNotIn('id', $excludedOrderIds)
            ->orderBy('updated_at', 'asc')
            ->take(3)
            ->get();

        $lastUpdated = $now->format('M d, Y');

        return view('admin.dashboard', compact(
            'ordersToAssign', 'totalOrders', 'inProgressCount', 'completedCount', 'period',
            'inProgressOrders', 'completedOrders', 'lastUpdated'
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

    /* ======================================================================
     *                          Manage Users
     * ====================================================================== */

    private function allowedRoles(): array
    {
        return [
            'admin',
            'boss',
            'salesperson',
            'head-artist',
            'artist',
            'operations-printing',
            'operations-furnishing',
            'operations-dispatch-control',
            'operations-delivery-installation',
            'data-entry',
            'installation', // 如果你在前端下拉里用到了
            'head-salesperson',
        ];
    }

    /** 页面入口：服务端分页 + 搜索筛选 */
    public function manageUser(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) abort(403, 'Unauthorized');

        $q      = trim((string)$request->query('q', ''));
        $role   = $request->query('role', 'all');
        $status = $request->query('status', 'all');

        $users = User::query()
            ->when($q !== '', function ($qbuilder) use ($q) {
                $qbuilder->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                      ->orWhere('contact_number', 'like', "%{$q}%");
                });
            })
            ->when($role !== 'all', fn($qb) => $qb->where('role', $role))
            ->when($status !== 'all', fn($qb) => $qb->where('status', strtolower($status)))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.manageuser', [
            'users'       => $users,
            'roleOptions' => $this->allowedRoles(),
        ]);
    }

    /** 若你仍用到 DataTables */
    public function user(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $q      = trim((string)$request->get('q',''));
        $role   = $request->get('role');
        $status = $request->get('status');

        $query = User::query()
            ->select(['id','name','email','contact_number','status','role','created_at','updated_at']);

        if ($q !== '') {
            $query->where(function($w) use ($q){
                $w->where('name','like',"%{$q}%")
                  ->orWhere('email','like',"%{$q}%")
                  ->orWhere('contact_number','like',"%{$q}%");
            });
        }
        if ($role && $role !== 'all')     $query->where('role', $role);
        if ($status && $status !== 'all') $query->where('status', strtolower($status));

        return DataTables::of($query)
            ->addColumn('actions', function(User $u){
                return [
                    'update' => route('admin.user.update', $u),
                    'toggle' => route('admin.user.disable', $u),
                ];
            })
            ->toJson();
    }

    /** 创建用户（状态统一写小写） */
    public function storeUser(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) abort(403, 'Unauthorized');

        $roles = implode(',', $this->allowedRoles());

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255', 'unique:users,email'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'role'           => ["required","in:$roles"],
            'password'       => [ 'required', Password::min(8)->mixedCase()->numbers()->symbols() ],
            'status'         => ['nullable','in:active,inactive'],
        ]);

        $user = User::create([
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'contact_number' => $validated['contact_number'] ?? null,
            'role'           => $validated['role'],
            'password'       => Hash::make($validated['password']),
            'status'         => strtolower($validated['status'] ?? 'active'),
        ]);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'User created successfully.', 'user' => $user], 201)
            : back()->with('success', 'User created successfully.');
    }

    /** 更新用户（状态统一写小写） */
    public function updateUser(Request $request, User $user)
    {
        if (!Auth::user()->hasRole('admin')) abort(403, 'Unauthorized');

        $roles = implode(',', $this->allowedRoles());

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'role'           => ["required","in:$roles"],
            'password'       => ['nullable', Password::min(8)->mixedCase()->numbers()->symbols()],
            'status'         => ['required','in:active,inactive'],
        ]);

        $user->fill([
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'contact_number' => $validated['contact_number'] ?? null,
            'role'           => $validated['role'],
            'status'         => strtolower($validated['status']),
        ]);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return $request->expectsJson()
            ? response()->json(['success'=>true,'message'=>'User updated successfully.','user'=>$user])
            : redirect()->route('admin.manageuser', $request->only('q','role','status'))
                        ->with('success', 'User updated successfully.');
    }

    /** 启/停用切换（大小写安全） */
    public function disableUser(Request $request, User $user)
    {
        if (!Auth::user()->hasRole('admin')) abort(403, 'Unauthorized');

        $current = strtolower((string)$user->status);
        $user->status = $current === 'active' ? 'inactive' : 'active';
        $user->save();

        return $request->expectsJson()
            ? response()->json(['success'=>true,'status'=>$user->status,'message'=>'User status updated successfully.'])
            : redirect()->route('admin.manageuser', $request->only('q','role','status'))
                        ->with('success', 'User status updated successfully.');
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

    public function coasingData()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) abort(403, 'Unauthorized');
        return view('admin.coasing-data');
    }

    public function calendar()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) abort(403, 'Unauthorized');
        return view('admin.calendar');
    }

    public function reports()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) abort(403, 'Unauthorized');

        $leads  = Lead::latest()->get();
        $orders = Order::latest()->get();
        return view('admin.reports', compact('leads', 'orders'));
    }

    /* -------------------- Fulfillment -------------------- */

    public function fulfillment(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) abort(403, 'Unauthorized');

        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 50]) ? $perPage : 10;

        $excludedOrderIds = Order::whereNotNull('redo')->pluck('redo')->all();

        $needPermit = Order::whereNotIn('id', $excludedOrderIds)
            ->when(Schema::hasColumn('orders','need_permit'), fn($q)=>$q->where('need_permit', true),
                   fn($q)=>$q->where('orderStatus','pending'))
            ->when(Schema::hasColumn('orders','permit_file'), fn($q)=>$q->whereNull('permit_file'))
            ->count();

        $dispatchCount = Order::whereNotIn('id', $excludedOrderIds)
            ->whereIn('orderStatus', ['pending','in_progress'])
            ->count();

        $q = Order::query()->with('artist')->whereNotIn('id', $excludedOrderIds);

        if ($idOrTitle = $request->input('order_id')) {
            $q->where(fn($qq)=>$qq->where('order_number','like',"%{$idOrTitle}%")
                                  ->orWhere('orderTitle','like',"%{$idOrTitle}%"));
        }
        if ($artist = $request->input('artist')) {
            $q->whereHas('artist', fn($qq)=>$qq->where('name','like',"%{$artist}%"));
        }
        if ($kw = $request->input('q')) {
            $q->where(fn($qq)=>$qq->where('orderTitle','like',"%{$kw}%")
                                  ->orWhere('description','like',"%{$kw}%"));
        }
        if ($task = $request->input('task')) {
            $q->where(fn($qq)=>$qq->where('task_type',$task)
                                  ->orWhere('delivery_installation_type',$task));
        }
        if ($status = $request->input('status')) {
            $q->where('orderStatus', $status);
        }
        if ($range = $request->input('date_range')) {
            $parts = array_map('trim', explode(' - ', $range));
            if (count($parts) === 2) {
                try {
                    $from = Carbon::createFromFormat('d/m/Y', $parts[0])->startOfDay();
                    $to   = Carbon::createFromFormat('d/m/Y', $parts[1])->endOfDay();
                    $q->whereBetween('deadline', [$from, $to]);
                } catch (\Exception $e) {}
            }
        }

        $q->orderByDesc('created_at');

        if ($request->boolean('export')) {
            $rows = $q->get();
            $csv  = [];
            $csv[] = ['Product ID','Product Name','Task Type','Deadline','Status','Delivery Date','Delivery Location','Installation Type','Cost'];
            foreach ($rows as $o) {
                $taskType  = $o->task_type ?? $o->delivery_installation_type ?? null;
                $deadline  = $o->deadline ? Carbon::parse($o->deadline)->format('Y-m-d') : '';
                $delivDate = $o->delivery_date ? Carbon::parse($o->delivery_date)->format('Y-m-d') : '';
                $csv[] = [
                    $o->order_number ?? ('ORD-'.$o->id),
                    $o->orderTitle ?? '-',
                    $taskType ? ucfirst(str_replace('_',' ',$taskType)) : 'N/A',
                    $deadline,
                    ucfirst(str_replace('_',' ', $o->orderStatus ?? 'pending')),
                    $delivDate,
                    $o->delivery_address ?? '',
                    $o->installation_type ?? 'N/A',
                    isset($o->delivery_cost) ? ('RM '.number_format($o->delivery_cost, 0)) : 'N/A',
                ];
            }
            $filename = 'fulfillment-'.now()->format('Ymd_His').'.csv';
            $fh = fopen('php://temp', 'w+');
            foreach ($csv as $line) fputcsv($fh, $line);
            rewind($fh);
            return Response::stream(function() use ($fh){ fpassthru($fh); fclose($fh); }, 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ]);
        }

        $fulfillments = $q->paginate($perPage)->withQueryString();

        $fulfillments->getCollection()->transform(function($o){
            $o->product_code       = $o->order_number ?? ('ORD-'.$o->id);
            $o->product_name       = $o->orderTitle ?? ($o->product_name ?? null);
            $o->task_type          = $o->task_type ?? $o->delivery_installation_type ?? null;
            $o->task_type_label    = $o->task_type ? ucfirst(str_replace('_',' ',$o->task_type)) : 'N/A';
            $o->status             = $o->orderStatus;
            $o->delivery_location  = $o->delivery_address ?? $o->shipping_address ?? null;
            $o->installation_type  = $o->installation_type ?? ($o->is_outsource ? 'Outsource' : 'Inhouse');
            $o->cost               = $o->delivery_cost ?? null;
            return $o;
        });

        return view('admin.fulfillment', [
            'fulfillments'  => $fulfillments,
            'needPermit'    => $needPermit,
            'dispatchCount' => $dispatchCount,
        ]);
    }

    public function fulfillmentShow($id)
    {
        $order = Order::with('artist')->findOrFail($id);
        return view('admin.fulfillment-show', compact('order'));
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
}
