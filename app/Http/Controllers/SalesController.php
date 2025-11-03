<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Order;
use App\Models\JobOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

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

    $currentYear  = Carbon::now()->year;
    $currentMonth = Carbon::now()->month;

    // ✅ Leads scope
    if ($user->hasRole('head-salesperson')) {
        $leads = Lead::whereYear('created_at', $currentYear)->get();
    } else {
        $leads = $user->leads()->whereYear('created_at', $currentYear)->get();
    }

    // ✅ Meetings scope
   
       $meetings = $user->meetings()
    ->where('status', 'scheduled')
    ->where('start_time', '>=', Carbon::now())
    ->orderBy('start_time', 'asc') // earliest first
    ->limit(3)
    ->get() ?? collect();

    $orders = []; // Placeholder

    /**
     * ✅ 月度统计（含 Meeting）
     * 你 leads 表的 status enum: 'accept','reject','followup','new','meeting'
     * 这里把 meeting 一并统计
     */
    $monthlyData = $leads->groupBy(function ($lead) {
        return Carbon::parse($lead->created_at)->format('M'); // Jan, Feb, ...
    })->map(function ($group) {
        return [
            'accept'   => $group->where('status', 'accept')->count(),
            'reject'   => $group->where('status', 'reject')->count(),
            'followup' => $group->where('status', 'followup')->count(),
            'meeting'  => $group->where('status', 'meeting')->count(),
        ];
    });

    // 保证到当前月都有占位
    $acceptCounts   = array_fill(0, $currentMonth, 0);
    $rejectCounts   = array_fill(0, $currentMonth, 0);
    $followupCounts = array_fill(0, $currentMonth, 0);
    $meetingCounts  = array_fill(0, $currentMonth, 0);

    foreach ($monthlyData as $month => $counts) {
        // $month 是 'Jan' 这类字符串，Carbon 能解析
        $monthIndex = Carbon::parse("$currentYear-$month-01")->month - 1;
        if ($monthIndex < $currentMonth) {
            $acceptCounts[$monthIndex]   = $counts['accept']   ?? 0;
            $rejectCounts[$monthIndex]   = $counts['reject']   ?? 0;
            $followupCounts[$monthIndex] = $counts['followup'] ?? 0;
            $meetingCounts[$monthIndex]  = $counts['meeting']  ?? 0;
        }
    }

    // 汇总（右侧 legend 数字）
    $acceptCount   = $leads->where('status', 'accept')->count();
    $rejectCount   = $leads->where('status', 'reject')->count();
    $followupCount = $leads->where('status', 'followup')->count();
    $meetingCount  = $leads->where('status', 'meeting')->count();

    $monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    return view('sales.dashboard', compact(
        'leads',
        'meetings',
        'orders',
        // 右侧统计
        'acceptCount',
        'rejectCount',
        'followupCount',
        'meetingCount',
        // 图表序列
        'acceptCounts',
        'rejectCounts',
        'followupCounts',
        'meetingCounts',
        // 其它
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

        // ✅ 允许 meeting 作为线索状态
        $request->validate([
            'name'   => 'required',
            'email'  => 'required|email',
            'phone'  => 'required',
            'notes'  => 'nullable',
            'status' => 'required|in:accept,reject,followup,meeting',
        ]);

        Lead::create([
            'user_id' => $user->id,
            'name'    => $request->name,
            'email'   => $request->email,
            'phone'   => $request->phone,
            'notes'   => $request->notes,
            'status'  => $request->status,
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
            'title'       => 'required',
            'start_time'  => 'required|date',
            'end_time'    => 'required|date|after:start_time',
            'description' => 'nullable',
            'status'      => 'required|in:scheduled,completed,cancelled',
        ]);

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
        $jobOrders = collect(); // Placeholder
        return view('sales.job-order-status', compact('jobOrders'));
    }

    // ===== Profile =====
    public function ProfileShow(Request $request)
    {
        $user = $request->user();
        return view('sales.profile.show', compact('user'));
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
}
