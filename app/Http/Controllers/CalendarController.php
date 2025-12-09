<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Reminder;
use App\Models\Lead;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\DeliveryBreakdown;

class CalendarController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        $isHead = $user->hasRole('head-salesperson');
        $salespeople = $isHead
            ? User::whereIn('role', ['salesperson', 'head-salesperson'])
                  ->orderBy('name')
                  ->get(['id', 'name'])
            : collect([$user->only(['id', 'name'])]);

        return view('sales.calendar', compact('salespeople'));
    }

    public function adminIndex()
    {
        $user = Auth::user();
      if (!($user->hasRole('admin') || $user->hasRole('boss'))) {
    return response()->json(['error' => 'Unauthorized'], 403);
}

        $salespeople = User::whereIn('role', ['salesperson', 'head-salesperson'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.calendar', compact('salespeople'));
    }

  public function events(Request $request)
{
    $user = Auth::user();
  if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson') || $user->hasRole('admin') || $user->hasRole('boss'))) {
    return response()->json(['error' => 'Unauthorized'], 403);
}

    Log::info('events called for user: ' . $user->email);

    $start = Carbon::parse($request->input('start', now()->startOfMonth()));
    $end = Carbon::parse($request->input('end', now()->endOfMonth()));
    $salespersonId = (int) $request->input('salesperson_id', 0);
    $q = trim($request->input('q', ''));

    if ($user->hasRole('salesperson') && !$user->hasRole('head-salesperson') && !$user->hasRole('admin')) {
        $salespersonId = $user->id;
    }

    $statusColors = [
        'scheduled' => '#4e73df',
        'postponed' => '#f6c23e',
        'canceled' => '#000a0b',
        'default' => '#6c757d'
    ];

    $isHeadSalesperson = $user->hasRole('head-salesperson');

    // ----- Meetings -----
    $meetingsQuery = Meeting::leftJoin('leads', 'meetings.lead_id', '=', 'leads.id')
        ->leftJoin('users', 'meetings.user_id', '=', 'users.id')
        ->select([
            'meetings.*',
            'users.name as user_name',
            'leads.company_name',
            'leads.name as lead_name'
        ])
        ->where(function ($query) use ($start, $end) {
            $query->where(function ($q1) use ($start, $end) {
                $q1->whereNotNull('meetings.start_time')
                   ->where(function ($qq) use ($start, $end) {
                       $qq->whereBetween('meetings.start_time', [$start, $end])
                          ->orWhereBetween('meetings.end_time', [$start, $end])
                          ->orWhere(function ($q4) use ($start, $end) {
                              $q4->where('meetings.start_time', '<=', $start)
                                 ->where('meetings.end_time', '>=', $start);
                          });
                   });
            })->orWhere(function ($qq) use ($start, $end) {
                $qq->whereNotNull('meetings.new_start_time')
                   ->where(function ($qqq) use ($start, $end) {
                       $qqq->whereBetween('meetings.new_start_time', [$start, $end])
                          ->orWhereBetween('meetings.new_end_time', [$start, $end])
                          ->orWhere(function ($q4) use ($start, $end) {
                              $q4->where('meetings.new_start_time', '<=', $start)
                                 ->where('meetings.new_end_time', '>=', $start);
                          });
                   });
            });
        })
        ->when($q !== '', function ($query) use ($q) {
            $query->where(function ($q2) use ($q) {
                $q2->where('meetings.title', 'like', "%{$q}%")
                   ->orWhere('leads.name', 'like', "%{$q}%")
                   ->orWhere('leads.company_name', 'like', "%{$q}%");
            });
        })
        ->when($salespersonId > 0, function ($query) use ($salespersonId) {
            // CHANGED: Filter by lead's salesperson_id instead of meeting's user_id
            $query->where('leads.salesperson_id', $salespersonId);
        })
        ->when($user->hasRole('salesperson') && !$user->hasRole('head-salesperson') && !$user->hasRole('admin'), function ($query) use ($user) {
            // CHANGED: Force filter by user's id on lead's salesperson_id for non-head salespeople
            $query->where('leads.salesperson_id', $user->id);
        });

    $meetings = $meetingsQuery->get();

    $meetings = $meetings->map(function ($meeting) use ($statusColors, $isHeadSalesperson) {
        $color = $statusColors[$meeting->status] ?? $statusColors['default'];
        $textColor = '#fff';
        $leadText = $meeting->lead_name && $meeting->company_name ? $meeting->company_name . ' - ' . $meeting->lead_name : 'Unknown';
        return [
            'id' => 'meeting-' . $meeting->id,
            'title' => $meeting->title,
            'start' => $meeting->start_time->toIso8601String(),
            'end' => $meeting->end_time ? $meeting->end_time->toIso8601String() : null,
            'allDay' => false,
            'extendedProps' => [
                'calendar' => 'Meeting',
                'type' => 'meeting',
                'meeting_type' => $meeting->type,
                'location' => $meeting->location,
                'url' => $meeting->url,
                'status' => $meeting->status,
                'lead_id' => $meeting->lead_id,
                'lead_text' => $leadText,
                'note' => $meeting->note,
                'created_by' => $meeting->user_name ?? 'Unknown',
                'is_head_salesperson' => $isHeadSalesperson,
            ],
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => $textColor,
        ];
    });

    // ----- Reminders -----
    $remindersQuery = Reminder::leftJoin('leads', 'reminders.lead_id', '=', 'leads.id')
        ->leftJoin('users', 'reminders.created_by', '=', 'users.id')
        ->select([
            'reminders.*',
            'users.name as user_name',
            'leads.company_name',
            'leads.name as lead_name'
        ])
        ->whereBetween('reminders.remind_at', [$start, $end])
        ->when($q !== '', function ($query) use ($q) {
            $query->where(function ($q2) use ($q) {
                $q2->where('reminders.title', 'like', "%{$q}%")
                   ->orWhere('leads.name', 'like', "%{$q}%")
                   ->orWhere('leads.company_name', 'like', "%{$q}%");
            });
        })
        ->when($salespersonId > 0, function ($query) use ($salespersonId) {
            $query->where('reminders.created_by', $salespersonId);
        })
        ->when(!($user->hasRole('admin') || $user->hasRole('boss')), function ($query) use ($user) {
            $query->where('reminders.created_by', $user->id);
        });

    $reminders = $remindersQuery->get();

    $reminders = $reminders->map(function ($reminder) use ($isHeadSalesperson) {
        $status = $reminder->status;
        $displayStatus = $status;
        $color = match($status) {
            'completed' => '#28a745',
            'upcoming' => '#6c757d',
            'overdue' => '#dc3545',
            default => '#6c757d'
        };
        $textColor = '#fff';
        $leadText = $reminder->lead_name && $reminder->company_name ? $reminder->company_name . ' - ' . $reminder->lead_name : 'Unknown';
        return [
            'id' => 'reminder-' . $reminder->id,
            'title' => $reminder->title,
            'start' => $reminder->remind_at->toIso8601String(),
            'allDay' => true,
            'extendedProps' => [
                'calendar' => 'Reminder',
                'type' => 'reminder',
                'status' => $displayStatus,
                'lead_id' => $reminder->lead_id,
                'lead_text' => $leadText,
                'created_by' => $reminder->user_name ?? 'Unknown',
                'due_date' => $reminder->due_date ? $reminder->due_date->toIso8601String() : null,
                'remind_at' => $reminder->remind_at->toIso8601String(),
                'note' => $reminder->description ?? '',
                'recurrence_type' => $reminder->recurrence_type ?? '',
                'recurrence_time' => $reminder->recurrence_time ?? '',
                'is_head_salesperson' => $isHeadSalesperson,
            ],
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => $textColor,
        ];
    });

    $allEvents = $meetings->toArray();
    $allEvents = array_merge($allEvents, $reminders->toArray());
    return response()->json($allEvents);
}

  public function orderEvents(Request $request)
    {
        $user = Auth::user();
        if (!($user->hasRole('admin') || $user->hasRole('boss'))) {
    return response()->json(['error' => 'Unauthorized'], 403);
}

        Log::info('orderEvents called for user: ' . $user->email);

        $start = Carbon::parse($request->input('start', now()->startOfMonth()));
        $end = Carbon::parse($request->input('end', now()->endOfMonth()));

        $statusColors = [
            'self_pickup' => '#007bff',
            'courier' => '#28a745',
            'delivery' => '#0dcaf0',
            'installation' => '#dc3545',
            'default' => '#6c757d'
        ];

        $deliveriesQuery = DeliveryBreakdown::leftJoin('products', 'delivery_breakdowns.ProductID', '=', 'products.ProductID')
            ->leftJoin('orders', 'products.OrderID', '=', 'orders.id')
            ->leftJoin('leads', 'orders.lead_id', '=', 'leads.id')
            ->leftJoin('users', 'orders.salesperson_id', '=', 'users.id')
            ->select([
                'delivery_breakdowns.*',
                'products.productName',
                'orders.order_number',
                'orders.id as order_id',
                'orders.orderTitle',
                'orders.deadline as job_order_deadline',
                DB::raw('(SELECT pp.permit_file FROM product_permit pp WHERE pp.product_id = products.ProductID ORDER BY pp.uploaded_at DESC LIMIT 1) as permit_attachment'),
                'users.name as salesperson_name',
                'leads.company_name',
                'leads.name as lead_name'
            ])
            ->whereNotNull('delivery_breakdowns.date')
            ->whereBetween('delivery_breakdowns.date', [$start, $end]);

        $deliveries = $deliveriesQuery->get();

        $deliveries = $deliveries->map(function ($delivery) use ($statusColors) {
            $when = $delivery->when;
            $isAllDay = $delivery->time === null;
            $color = $statusColors[$delivery->method] ?? $statusColors['default'];
            $textColor = '#fff';
            $leadText = $delivery->lead_name && $delivery->company_name ? $delivery->company_name . ' - ' . $delivery->lead_name : 'Unknown';
            $methodDisplay = match($delivery->method) {
            'courier' => 'Courier',
            'self_pickup' => 'Self Pickup',
            'delivery' => 'Delivery', 
            'installation' => 'Installation',
            default => ucwords(str_replace('_', ' ', $delivery->method))
            };
            $title = $methodDisplay . ' ' . $delivery->order_number . ' - ' . $delivery->productName;
            return [
                'id' => 'delivery-' . $delivery->BreakdownID,
                'title' => $title,
                'start' => $when->toIso8601String(),
                'end' => $when->copy()->addMinutes(30)->toIso8601String(), // Assume 30 min duration if time-based
                'allDay' => $isAllDay,
                'extendedProps' => [
                    'calendar' => 'Delivery',
                    'type' => 'delivery',
                    'method' => $delivery->method,
                    'quantity' => $delivery->quantity,
                    'location' => $delivery->location,
                    'deliver_install_type' => $delivery->deliver_install_type,
                    'outsource_cost' => $delivery->outsource_cost,
                    'lead_id' => $delivery->lead_id ?? null,
                    'lead_text' => $leadText,
                    'assigned_to' => $delivery->salesperson_name ?? 'Unknown',
                    'description' => 'Delivery for ' . $delivery->productName . ' (Qty: ' . $delivery->quantity . ')',
                    'order_number' => $delivery->order_number,
                    'product_name' => $delivery->productName,
                    'order_date' => $delivery->order_date ?? null,
                    'job_order_deadline' => $delivery->job_order_deadline,
                    'job_order_name' => $delivery->orderTitle, 
                    'permit_attachment' => $delivery->permit_attachment !== null,
                    'product_id'        => $delivery->ProductID,
                ],
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => $textColor,
            ];
        });

        return response()->json($deliveries->toArray());
    }



}