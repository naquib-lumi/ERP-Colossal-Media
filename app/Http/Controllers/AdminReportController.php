<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class AdminReportController extends Controller
{
    public function index()
    {
        $salespeople = User::whereIn('role', ['salesperson', 'head-salesperson'])
            ->orderBy('name')
            ->pluck('name', 'id');
        return view('admin.reports', compact('salespeople'));
    }

    public function reportstest()
    {
        $leads  = Lead::latest()->get();
        $orders = Order::latest()->get();
        return view('admin.reports-bk20251027', compact('leads', 'orders'));
    }

    public function salesKpis(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin')) abort(403, 'Unauthorized');

        $salesperson = $request->salesperson === 'All Salespersons' ? null : $request->salesperson;
        $period = strtolower($request->period ?? 'monthly');
        $start = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $end = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        if ($period === 'yearly') {
            $start = Carbon::now()->startOfYear();
            $end = Carbon::now()->endOfYear();
        } elseif ($period === 'quarterly') {
            $start = Carbon::now()->startOfQuarter();
            $end = Carbon::now()->endOfQuarter();
        }

        $q = Lead::whereBetween('created_at', [$start, $end]);
        if ($salesperson) $q->where('salesperson_id', $salesperson);

        $totalLeads = $q->count();

        $mq = Meeting::whereBetween('start_time', [$start, $end]);
        if ($salesperson) $mq->where('salesperson_id', $salesperson);

        $totalMeetings = $mq->count();
        $accepted = $q->clone()->whereHas('orders')->count();
        $rejected = $q->clone()->whereDoesntHave('orders')->count();

        $prevStart = $start->copy()->subMonth();
        $prevEnd = $end->copy()->subMonth();
        $prevLeads = Lead::whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $prevMeetings = Meeting::whereBetween('start_time', [$prevStart, $prevEnd])->count();

        $leadsDelta = $prevLeads > 0 ? round(($totalLeads - $prevLeads) / $prevLeads * 100) : 0;
        $meetingsDelta = $prevMeetings > 0 ? round(($totalMeetings - $prevMeetings) / $prevMeetings * 100) : 0;

        return response()->json([
            'total_leads' => $totalLeads,
            'total_meetings' => $totalMeetings,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'leads_delta' => $leadsDelta,
            'meetings_delta' => $meetingsDelta,
            'acceptance_rate' => $totalLeads > 0 ? round($accepted / $totalLeads * 100, 1) : 0,
        ]);
    }

    public function salesMonthlyPerformance(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin')) abort(403, 'Unauthorized');

        $salesperson = $request->salesperson === 'All Salespersons' ? null : $request->salesperson;
        $period = strtolower($request->period ?? 'monthly');
        $start = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $end = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        if ($period === 'yearly') {
            $start = Carbon::now()->startOfYear();
            $end = Carbon::now()->endOfYear();
        } elseif ($period === 'quarterly') {
            $start = Carbon::now()->startOfQuarter();
            $end = Carbon::now()->endOfQuarter();
        }

        $lq = Lead::whereBetween('created_at', [$start, $end]);
        if ($salesperson) $lq->where('salesperson_id', $salesperson);
        $leadsAdded = $lq->count();
        $accepted = $lq->clone()->where('status', 'accept')->count();
        $rejected = $lq->clone()->where('status', 'reject')->count();
        $fiftyFifty = $lq->clone()->where('opportunity', '50/50')->count();
        $lowChance = $lq->clone()->where('opportunity', 'Low Chance')->count();

        return response()->json([
            'leads_added' => $leadsAdded,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'fifty_fifty' => $fiftyFifty,
            'low_chance' => $lowChance,
        ]);
    }

    public function salesOutcomes(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin')) abort(403, 'Unauthorized');

        $salesperson = $request->salesperson === 'All Salespersons' ? null : $request->salesperson;
        $period = strtolower($request->period ?? 'monthly');
        $start = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $end = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        if ($period === 'yearly') {
            $start = Carbon::now()->startOfYear();
            $end = Carbon::now()->endOfYear();
        } elseif ($period === 'quarterly') {
            $start = Carbon::now()->startOfQuarter();
            $end = Carbon::now()->endOfQuarter();
        }

        $q = Lead::whereBetween('created_at', [$start, $end]);
        if ($salesperson) $q->where('salesperson_id', $salesperson);

        $accepted = $q->clone()->whereHas('orders')->count();
        $rejected = $q->clone()->whereDoesntHave('orders')->count();

        return response()->json([
            'accepted' => $accepted,
            'rejected' => $rejected,
        ]);
    }

    public function orderFulfillment(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin')) abort(403, 'Unauthorized');

        $period = strtolower($request->period ?? 'monthly');
        $start = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $end = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        if ($period === 'yearly') {
            $start = Carbon::now()->startOfYear();
            $end = Carbon::now()->endOfYear();
        } elseif ($period === 'quarterly') {
            $start = Carbon::now()->startOfQuarter();
            $end = Carbon::now()->endOfQuarter();
        }

        $excluded = Order::whereNotNull('redo')->pluck('redo')->toArray();

        $q = Order::whereBetween('orderDate', [$start, $end])->whereNotIn('id', $excluded);

        $newOrders = $q->clone()->where('orderStatus', 'to_assign')->count();
        $inProgress = $q->clone()->where('orderStatus', 'in_progress')->count();
        $completed = $q->clone()->where('orderStatus', 'completed')->count();
        $overdue = $q->clone()->where('orderStatus', 'in_progress')->where('deadline', '<', Carbon::now())->count();

        return response()->json([
            'new_orders' => $newOrders,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'overdue' => $overdue,
        ]);
    }

    public function exportSales(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin')) abort(403, 'Unauthorized');

        $salesperson = $request->salesperson === 'All Salespersons' ? null : $request->salesperson;
        $period = strtolower($request->period ?? 'monthly');
        $start = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $end = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        if ($period === 'yearly') {
            $start = Carbon::now()->startOfYear();
            $end = Carbon::now()->endOfYear();
        } elseif ($period === 'quarterly') {
            $start = Carbon::now()->startOfQuarter();
            $end = Carbon::now()->endOfQuarter();
        }

        $leads = Lead::with('user')
            ->whereBetween('created_at', [$start, $end])
            ->when($salesperson, fn($q) => $q->where('salesperson_id', $salesperson))
            ->get();

        $meetings = Meeting::with('user', 'lead')
            ->whereBetween('start_time', [$start, $end])
            ->when($salesperson, fn($q) => $q->where('salesperson_id', $salesperson))
            ->get();

        $filename = 'sales_report_' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return Response::stream(function () use ($leads, $meetings) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Type', 'ID', 'Salesperson', 'Company Name', 'Lead Name', 'Status', 'Opportunity', 'Created At', 'Start Time']);

            foreach ($leads as $lead) {
                fputcsv($handle, [
                    'Lead',
                    $lead->id,
                    $lead->user->name ?? 'N/A',
                    $lead->company_name ?? 'N/A',
                    $lead->name ?? 'N/A',
                    $lead->status ?? 'N/A',
                    $lead->opportunity ?? 'N/A',
                    $lead->created_at->format('Y-m-d H:i:s'),
                    '',
                ]);
            }

            foreach ($meetings as $meeting) {
                fputcsv($handle, [
                    'Meeting',
                    $meeting->id,
                    $meeting->user->name ?? 'N/A',
                    $meeting->lead->company_name ?? 'N/A',
                    $meeting->lead->name ?? 'N/A',
                    $meeting->status ?? 'N/A',
                    '',
                    $meeting->start_time->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function exportOrders(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin')) abort(403, 'Unauthorized');

        $period = strtolower($request->period ?? 'monthly');
        $start = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $end = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        if ($period === 'yearly') {
            $start = Carbon::now()->startOfYear();
            $end = Carbon::now()->endOfYear();
        } elseif ($period === 'quarterly') {
            $start = Carbon::now()->startOfQuarter();
            $end = Carbon::now()->endOfQuarter();
        }

        $excluded = Order::whereNotNull('redo')->pluck('redo')->toArray();

        $orders = Order::with('lead', 'salesperson', 'originalOrder')
            ->whereBetween('orderDate', [$start, $end])
            ->whereNotIn('id', $excluded)
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'orders_' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return Response::stream(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Order ID', 'Order Name', 'Company Name', 'Lead Name', 'Lead Phone', 'Status', 'Salesperson', 'Created At', 'Deadline']);

            foreach ($orders as $order) {
                $orderId = $order->originalOrder ? $order->originalOrder->order_number . 'R' : ($order->order_number ?? $order->id);
                fputcsv($handle, [
                    $orderId,
                    $order->orderTitle,
                    $order->lead->company_name ?? 'N/A',
                    $order->lead->name ?? 'N/A',
                    $order->lead->phone ?? 'N/A',
                    $order->orderStatus,
                    $order->salesperson->name ?? 'N/A',
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->deadline ? $order->deadline->format('Y-m-d') : 'N/A',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}