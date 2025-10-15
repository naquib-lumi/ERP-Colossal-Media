<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Lead;
use App\Models\Order;
use Carbon\Carbon;

class AdminController extends Controller
{
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

    public function settings()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $users = User::all();
        return view('admin.settings', compact('users'));
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
}