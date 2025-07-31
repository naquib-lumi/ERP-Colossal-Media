<?php

namespace App\Http\Controllers;

use App\Models\SalesPerson;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Order;
use App\Models\JobOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalesController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function dashboard()
    {
        $salesPerson = Auth::user()->salesPerson;
        $leads = $salesPerson ? $salesPerson->leads : [];
        $meetings = $salesPerson ? $salesPerson->meetings : [];
        $orders = $salesPerson ? $salesPerson->orders : [];
        return view('sales.dashboard', compact('leads', 'meetings', 'orders'));
    }

    public function leadManagement()
    {
        $salesPerson = Auth::user()->salesPerson;
        $leads = $salesPerson ? $salesPerson->leads : [];
        return view('sales.lead-management', compact('leads'));
    }

    public function addLead()
    {
        return view('sales.add-lead');
    }

    public function storeLead(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'notes' => 'nullable',
            'status' => 'required|in:new,contacted,in_progress,converted,lost',
        ]);

        $salesPerson = Auth::user()->salesPerson;
        Lead::create([
            'sales_person_id' => $salesPerson->id,
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
        $salesPerson = Auth::user()->salesPerson;
        $meetings = $salesPerson ? $salesPerson->meetings : [];
        return view('sales.calendar', compact('meetings'));
    }

    public function scheduleMeeting()
    {
        return view('sales.schedule-meeting');
    }

    public function storeMeeting(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'description' => 'nullable',
            'status' => 'required|in:scheduled,completed,cancelled',
        ]);

        $salesPerson = Auth::user()->salesPerson;
        Meeting::create([
            'sales_person_id' => $salesPerson->id,
            'title' => $request->title,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return redirect()->route('sales.calendar')->with('success', 'Meeting scheduled successfully');
    }

    public function order()
    {
        $salesPerson = Auth::user()->salesPerson;
        $orders = $salesPerson ? $salesPerson->orders : [];
        return view('sales.order', compact('orders'));
    }

    public function jobOrderStatus()
    {
        $salesPerson = Auth::user()->salesPerson;
        $orders = $salesPerson ? $salesPerson->orders : [];
        $jobOrders = $orders->flatMap->jobOrders;
        return view('sales.job-order-status', compact('jobOrders'));
    }
}