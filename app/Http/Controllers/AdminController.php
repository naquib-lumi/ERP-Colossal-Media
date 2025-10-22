<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Lead;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AdminController extends Controller
{
    public function ProfileShow(Request $request)
    {
        $user = $request->user();
        return view('admin.profile.show', compact('user'));
    }

    public function ProfileUpdate(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password'         => ['nullable', Password::min(8)->mixedCase()->numbers()->symbols(), 'confirmed'],
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
    
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $period = $request->get('period', 'this_month');
        $now = Carbon::now();

        switch ($period) {
            case 'this_month':
                $start = $now->startOfMonth()->toDateString();
                $end = $now->endOfMonth()->toDateString();
                break;
            case 'this_year':
                $start = $now->startOfYear()->toDateString();
                $end = $now->endOfYear()->toDateString();
                break;
            case 'last_month':
                $start = $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
                $end = $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
                break;
            case '3_months':
                $start = $now->copy()->subMonthsNoOverflow(3)->startOfMonth()->toDateString();
                $end = $now->endOfMonth()->toDateString();
                break;
            default:
                $start = $now->startOfMonth()->toDateString();
                $end = $now->endOfMonth()->toDateString();
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
            ->orderBy('updated_at', 'desc')
            ->take(3)
            ->get();

        $lastUpdated = $now->format('M d, Y');
        return view('admin.dashboard', compact(
            'ordersToAssign', 'totalOrders', 'inProgressCount', 'completedCount', 'period',
            'inProgressOrders', 'completedOrders', 'lastUpdated'
        ));
    }

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
            case 'this_month':
                $start = $now->startOfMonth()->toDateString();
                $end = $now->endOfMonth()->toDateString();
                break;
            case 'this_year':
                $start = $now->startOfYear()->toDateString();
                $end = $now->endOfYear()->toDateString();
                break;
            case 'last_month':
                $start = $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
                $end = $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
                break;
            case '3_months':
                $start = $now->copy()->subMonthsNoOverflow(3)->startOfMonth()->toDateString();
                $end = $now->endOfMonth()->toDateString();
                break;
            default:
                $start = $now->startOfMonth()->toDateString();
                $end = $now->endOfMonth()->toDateString();
        }

        $added = Lead::whereBetween('created_at', [$start, $end])->where('status', 'new')->count();
        $accepted = Lead::whereBetween('created_at', [$start, $end])->where('status', 'accept')->count();
        $rejected = Lead::whereBetween('created_at', [$start, $end])->where('status', 'reject')->count();
        $fiftyFifty = Lead::whereBetween('created_at', [$start, $end])->where('opportunity', '50/50')->count();
        $lowChance = Lead::whereBetween('created_at', [$start, $end])->where('opportunity', 'Low Chance')->count();

        return response()->json([
            'added' => $added,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'fifty_fifty' => $fiftyFifty,
            'low_chance' => $lowChance
        ]);
    }

    public function fulfillmentCounts(Request $request)
    {
        $period = $request->get('period', 'this_month');
        $now = Carbon::now();

        switch ($period) {
            case 'this_month':
                $start = $now->startOfMonth()->toDateString();
                $end = $now->endOfMonth()->toDateString();
                break;
            case 'this_year':
                $start = $now->startOfYear()->toDateString();
                $end = $now->endOfYear()->toDateString();
                break;
            case 'last_month':
                $start = $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
                $end = $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
                break;
            case '3_months':
                $start = $now->copy()->subMonthsNoOverflow(3)->startOfMonth()->toDateString();
                $end = $now->endOfMonth()->toDateString();
                break;
            default:
                $start = $now->startOfMonth()->toDateString();
                $end = $now->endOfMonth()->toDateString();
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
            'new_orders' => $newOrders,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'overdue' => $overdue
        ]);
    }

 
   public function manageUser(Request $request)
{
    $user = Auth::user();
    if (!$user->hasRole('admin')) {
        abort(403, 'Unauthorized');
    }
    
    $query = User::query();
    
    if ($request->filled('search')) {
        $query->where('name', 'like', '%' . $request->search . '%')
              ->orWhere('email', 'like', '%' . $request->search . '%');
    }
    
    if ($request->filled('role')) {
        $query->where('role', $request->role);
    }
    
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
    
    $users = $query->paginate(10);
    
    return view('admin.manageuser', compact('users'));
}
    
       public function manageUserTable(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $query = User::query();
        if ($request->has('search')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }
        if ($request->has('role')) {
            $role = $request->input('role');
            $query->where('role', $role);
        }

        $users = $query->select('id', 'name', 'role', 'email', 'contact_number', 'status', 'created_at')->paginate(10);
        

        return response()->json([
            'data' => $users->items(),
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $users->total(),
            'recordsFiltered' => $users->total(),
        ]);
    }

    public function storeUser(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'in:admin,salesperson,head-salesperson,head-artist,artist,installation'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'contact_number' => $validated['contact_number'] ?? null,
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'status' => 'active',
        ]);

        return response()->json(['success' => 'User created successfully.']);
    }

    public function updateUser(Request $request, User $user)
    {
        $authUser = Auth::user();
        if (!$authUser->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'in:admin,salesperson,head-salesperson,head-artist,artist,installation'],
            'password' => ['nullable', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'contact_number' => $validated['contact_number'] ?? null,
            'role' => $validated['role'],
        ]);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'User updated successfully.');

    }

    public function disableUser(Request $request, User $user)
    {
        $authUser = Auth::user();
        if (!$authUser->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        return back()->with('success', 'User status updated successfully.');
    }




    public function orders()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }
        $orders = Order::latest()->get();
        return view('admin.order.index');
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
                $editRoute = route('orders.edit', $order->id);
                $leadViewRoute = route('admin.orders.show', $order->id);
                $html = '<div class="actions-cell d-flex gap-2">';
                // if ($order->orderStatus == 'to_assign') {
                //     $html .= '<a href="' . $editRoute . '" class="btn" title="Edit"><i class="bx bxs-edit me-2" style="font-size: 1.5em;"></i></a>';
                // }
                $html .= '<a href="' . $leadViewRoute . '" class="btn" title="View Lead"><i class="bx bxs-show me-2" style="font-size: 1.5em;"></i></a>';
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['company_info', 'lead_details', 'status', 'products', 'actions'])
            ->toJson();
    }

    public function showOrder($id)
{
    $order = Order::with('lead.attachments', 'salesperson', 'products', 'artist')->findOrFail($id);
    $attachments = $order->getAttachmentPathsAttribute()->map(function ($path) {
        return ['url' => Storage::url($path), 'name' => basename($path), 'size' => Storage::size($path)];
    });
    $leadAttachments = $order->lead ? $order->lead->attachments->map(function ($attachment) {
        return [
            'url' => asset('storage/' . $attachment->file_location),
            'name' => basename($attachment->file_location),
            'size' => $attachment->file_size
        ];
    }) : collect();
    return view('admin.order.view', compact('order', 'attachments', 'leadAttachments'));
}

    public function coasingData()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }
        return view('admin.coasing-data');
    }

    public function calendar()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }
        return view('admin.calendar');
    }

    public function reports()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $leads = Lead::latest()->get();
        $orders = Order::latest()->get();
        return view('admin.reports', compact('leads', 'orders'));
    }

    public function fulfillment()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }
        $orders = Order::where('orderStatus', 'completed')->get();
        return view('admin.fulfillment', compact('orders'));
    }

    public function user()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }
        $users = User::all();
        return view('admin.user', compact('users'));
    }

    public function dataKeyIn()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }
        return view('admin.data-key-in');
    }

    public function settings()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $users = User::all();
        return view('admin.settings', compact('users'));
    }
}