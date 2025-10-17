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

 
    public function manageUser()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }
        $users = User::paginate(10);
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

        return response()->json(['success' => 'User updated successfully.']);
    }

    public function disableUser(Request $request, User $user)
    {
        $authUser = Auth::user();
        if (!$authUser->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        return response()->json(['success' => 'User status updated successfully.']);
    }




    public function orders()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }
        $orders = Order::latest()->get();
        return view('admin.orders', compact('orders'));
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