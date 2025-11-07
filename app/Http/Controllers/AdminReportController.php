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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        if ($salesperson) $mq->where('user_id', $salesperson);

        $totalMeetings = $mq->count();
        $accepted = $q->clone()->where('status', 'accept')->count();
        $rejected = $q->clone()->where('status', 'reject')->count();

        $prevStart = $start->copy()->subMonth();
        $prevEnd = $end->copy()->subMonth();
        $prevQ = Lead::whereBetween('created_at', [$prevStart, $prevEnd]);
        if ($salesperson) $prevQ->where('salesperson_id', $salesperson);
        $prevLeads = $prevQ->count();

        $prevMq = Meeting::whereBetween('start_time', [$prevStart, $prevEnd]);
        if ($salesperson) $prevMq->where('user_id', $salesperson);
        $prevMeetings = $prevMq->count();

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

        $accepted = $q->clone()->where('status', 'accept')->count();
        $rejected = $q->clone()->where('status', 'reject')->count();

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
            ->when($salesperson, fn($q) => $q->where('user_id', $salesperson))
            ->get();

        $spreadsheet = new Spreadsheet();

        // Leads sheet
        $leadsSheet = $spreadsheet->createSheet();
        $leadsSheet->setTitle('Leads');
        $leadsSheet->setCellValue('A1', 'ID');
        $leadsSheet->setCellValue('B1', 'Salesperson');
        $leadsSheet->setCellValue('C1', 'Company Name');
        $leadsSheet->setCellValue('D1', 'Lead Name');
        $leadsSheet->setCellValue('E1', 'Status');
        $leadsSheet->setCellValue('F1', 'Opportunity');
        $leadsSheet->setCellValue('G1', 'Created At');

        $row = 2;
        foreach ($leads as $lead) {
            $leadsSheet->setCellValue('A' . $row, $lead->id);
            $leadsSheet->setCellValue('B' . $row, $lead->user->name ?? 'N/A');
            $leadsSheet->setCellValue('C' . $row, $lead->company_name ?? 'N/A');
            $leadsSheet->setCellValue('D' . $row, $lead->name ?? 'N/A');
            $leadsSheet->setCellValue('E' . $row, $lead->status ?? 'N/A');
            $leadsSheet->setCellValue('F' . $row, $lead->opportunity ?? 'N/A');
            $leadsSheet->setCellValue('G' . $row, $lead->created_at->format('Y-m-d H:i:s'));
            $row++;
        }

        // Meetings sheet
        $meetingsSheet = $spreadsheet->createSheet();
        $meetingsSheet->setTitle('Meetings');
        $meetingsSheet->setCellValue('A1', 'ID');
        $meetingsSheet->setCellValue('B1', 'Salesperson');
        $meetingsSheet->setCellValue('C1', 'Company Name');
        $meetingsSheet->setCellValue('D1', 'Lead Name');
        $meetingsSheet->setCellValue('E1', 'Status');
        $meetingsSheet->setCellValue('F1', 'Start Time');

        $row = 2;
        foreach ($meetings as $meeting) {
            $meetingsSheet->setCellValue('A' . $row, $meeting->id);
            $meetingsSheet->setCellValue('B' . $row, $meeting->user->name ?? 'N/A');
            $meetingsSheet->setCellValue('C' . $row, $meeting->lead->company_name ?? 'N/A');
            $meetingsSheet->setCellValue('D' . $row, $meeting->lead->name ?? 'N/A');
            $meetingsSheet->setCellValue('E' . $row, $meeting->status ?? 'N/A');
            $meetingsSheet->setCellValue('F' . $row, $meeting->start_time->format('Y-m-d H:i:s'));
            $row++;
        }

        // Remove default sheet
        $spreadsheet->removeSheetByIndex(0);

        $filename = 'sales_report_' . now()->format('Y-m-d') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
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

    $spreadsheet = new Spreadsheet();

    // Orders sheet
    $ordersSheet = $spreadsheet->getActiveSheet();
    $ordersSheet->setTitle('Orders');
    $ordersSheet->setCellValue('A1', 'Order ID');
    $ordersSheet->setCellValue('B1', 'Order Name');
    $ordersSheet->setCellValue('C1', 'Company Name');
    $ordersSheet->setCellValue('D1', 'Lead Name');
    $ordersSheet->setCellValue('E1', 'Lead Phone');
    $ordersSheet->setCellValue('F1', 'Status');
    $ordersSheet->setCellValue('G1', 'Salesperson');
    $ordersSheet->setCellValue('H1', 'Created At');
    $ordersSheet->setCellValue('I1', 'Deadline');

    $row = 2;
    foreach ($orders as $order) {
        $orderId = $order->originalOrder ? $order->originalOrder->order_number . 'R' : ($order->order_number ?? $order->id);
        $ordersSheet->setCellValue('A' . $row, $orderId);
        $ordersSheet->setCellValue('B' . $row, $order->orderTitle);
        $ordersSheet->setCellValue('C' . $row, $order->lead->company_name ?? 'N/A');
        $ordersSheet->setCellValue('D' . $row, $order->lead->name ?? 'N/A');
        $ordersSheet->setCellValue('E' . $row, $order->lead->phone ?? 'N/A');
        $ordersSheet->setCellValue('F' . $row, $order->orderStatus);
        $ordersSheet->setCellValue('G' . $row, $order->salesperson->name ?? 'N/A');
        $ordersSheet->setCellValue('H' . $row, $order->created_at->format('Y-m-d H:i:s'));
        $ordersSheet->setCellValue('I' . $row, $order->deadline ? $order->deadline->format('Y-m-d') : 'N/A');
        $row++;
    }

    $filename = 'orders_report_' . now()->format('Y-m-d') . '.xlsx';

    $writer = new Xlsx($spreadsheet);

    return response()->stream(function () use ($writer) {
        $writer->save('php://output');
    }, 200, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);
}
}