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
                $start = $now->startOfMonth();
                $end = $now->endOfMonth();
                break;
            case 'this_year':
                $start = $now->startOfYear();
                $end = $now->endOfYear();
                break;
            case 'last_month':
                $start = $now->subMonthNoOverflow()->startOfMonth();
                $end = $now->subMonthNoOverflow()->endOfMonth();
                break;
            case '3_months':
                $start = $now->subMonthsNoOverflow(3)->startOfMonth();
                $end = $now->endOfMonth();
                break;
            default:
                $start = $now->startOfMonth();
                $end = $now->endOfMonth();
        }

        $inProgressCount = Order::where('orderStatus', 'in_progress')
            ->whereBetween('orderDate', [$start, $end])
            ->count();

        $completedCount = Order::where('orderStatus', 'completed')
            ->whereBetween('orderDate', [$start, $end])
            ->count();

        $inProgressOrders = Order::where('orderStatus', 'in_progress')
            ->orderBy('deadline', 'asc')
            ->take(3)
            ->get();

        $completedOrders = Order::where('orderStatus', 'completed')
            ->orderBy('orderDate', 'desc')
            ->take(3)
            ->get();

        $lastUpdated = $now->format('M d, Y');

        return view('admin.dashboard', compact(
            'inProgressCount', 'completedCount', 'period',
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