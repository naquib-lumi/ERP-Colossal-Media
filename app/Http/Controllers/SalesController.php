<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Order;
use App\Models\JobOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
class SalesController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

public function dashboard()
{
    $user = Auth::user();
    if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
        abort(403, 'Unauthorized');
    }

    $currentYear = Carbon::now()->year;
    $currentMonth = Carbon::now()->month;

    // ✅ Leads logic
    if ($user->hasRole('head-salesperson')) {
        // Head-salesperson sees all leads
        $leads = Lead::whereYear('created_at', $currentYear)->get();
    } else {
        // Normal salesperson only sees their own leads
        $leads = $user->leads()->whereYear('created_at', $currentYear)->get();
    }

    // ✅ Meetings logic
    if ($user->hasRole('head-salesperson')) {
        $meetings = Meeting::where('start_time', '>=', Carbon::now())
            ->orderBy('start_time')
            ->take(3)
            ->get();
    } else {
        $meetings = $user->meetings()
            ->where('start_time', '>=', Carbon::now())
            ->orderBy('start_time')
            ->take(3)
            ->get() ?? collect();
    }

    $orders = []; // Placeholder

    // ✅ Monthly stats
    $monthlyData = $leads->groupBy(function ($lead) {
        return Carbon::parse($lead->created_at)->format('M');
    })->map(function ($group) {
        return [
            'accept' => $group->where('status', 'accept')->count(),
            'reject' => $group->where('status', 'reject')->count(),
            'followup' => $group->where('status', 'followup')->count(),
        ];
    });

    $acceptCounts = array_fill(0, $currentMonth, 0);
    $rejectCounts = array_fill(0, $currentMonth, 0);
    $followupCounts = array_fill(0, $currentMonth, 0);

    foreach ($monthlyData as $month => $counts) {
        $monthIndex = Carbon::parse("$currentYear-$month-01")->month - 1;
        if ($monthIndex < $currentMonth) {
            $acceptCounts[$monthIndex] = $counts['accept'];
            $rejectCounts[$monthIndex] = $counts['reject'];
            $followupCounts[$monthIndex] = $counts['followup'];
        }
    }

    $acceptCount = $leads->where('status', 'accept')->count();
    $rejectCount = $leads->where('status', 'reject')->count();
    $followupCount = $leads->where('status', 'followup')->count();

    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    return view('sales.dashboard', compact(
        'leads',
        'meetings',
        'orders',
        'acceptCount',
        'rejectCount',
        'followupCount',
        'acceptCounts',
        'rejectCounts',
        'followupCounts',
        'currentYear',
        'currentMonth',
        'monthNames'
    ));
}


    public function leadManagement()
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        $leads = $user->leads;
        return view('sales.lead-management', compact('leads'));
    }

    public function addLead()
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        return view('sales.add-lead');
    }

    public function storeLead(Request $request)
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'notes' => 'nullable',
            'status' => 'required|in:accept,reject,followup', // Updated statuses
        ]);

        Lead::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'notes' => $request->notes,
            'status' => $request->status,
        ]);

        return redirect()->route('sales.lead-management')->with('success', 'Lead added successfully');
    }

    public function calendar()
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        $meetings = []; // Implement Meeting model and relationship if needed
        return view('sales.calendar', compact('meetings'));
    }

    public function scheduleMeeting()
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        return view('sales.schedule-meeting');
    }

    public function storeMeeting(Request $request)
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'title' => 'required',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'description' => 'nullable',
            'status' => 'required|in:scheduled,completed,cancelled',
        ]);

        // Implement Meeting model creation
        // Meeting::create([...]);

        return redirect()->route('sales.calendar')->with('success', 'Meeting scheduled successfully');
    }

    public function order()
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        $orders = []; // Implement Order model and relationship if needed
        return view('sales.order', compact('orders'));
    }

    public function jobOrderStatus()
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        $orders = []; // Implement Order model and relationship if needed
        $jobOrders = collect(); // Placeholder, implement JobOrder model if needed
        return view('sales.job-order-status', compact('jobOrders'));
    }
}