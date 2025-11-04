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
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $salespeople = User::whereIn('role', ['salesperson', 'head-salesperson'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.calendar', compact('salespeople'));
    }

    public function events(Request $request)
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson') || $user->hasRole('admin'))) {
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
                $query->where('meetings.user_id', $salespersonId);
            });

        $meetings = $meetingsQuery->get();

        $meetings = $meetings->map(function ($meeting) use ($statusColors) {
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
                $query->whereHas('lead', function ($q) use ($salespersonId) {
                    $q->where('salesperson_id', $salespersonId);
                });
            });

        $reminders = $remindersQuery->get();

        $reminders = $reminders->map(function ($reminder) {
            $color = $reminder->status === 'completed' ? '#6c757d' : ($reminder->remind_at->isPast() ? '#dc3545' : '#28a745');
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
                    'status' => $reminder->status,
                    'lead_id' => $reminder->lead_id,
                    'lead_text' => $leadText,
                    'created_by' => $reminder->user_name ?? 'Unknown',
                    'due_date' => $reminder->due_date ? $reminder->due_date->toIso8601String() : null,
                    'remind_at' => $reminder->remind_at->toIso8601String(),
                    'note' => $reminder->description ?? '',
                    'recurrence_type' => $reminder->recurrence_type ?? '',
                    'recurrence_time' => $reminder->recurrence_time ?? '',
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
        if (!$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        Log::info('orderEvents called for user: ' . $user->email);

        $start = Carbon::parse($request->input('start', now()->startOfMonth()));
        $end = Carbon::parse($request->input('end', now()->endOfMonth()));

        $statusColors = [
            'to_assign' => '#007bff',
            'assigned' => '#17a2b8',
            'in_progress' => '#ffc107',
            'pending' => '#6c757d',
            'completed' => '#28a745',
            'rejected' => '#dc3545',
            'default' => '#6c757d'
        ];

        $ordersQuery = Order::leftJoin('leads', 'orders.lead_id', '=', 'leads.id')
            ->leftJoin('users', 'orders.salesperson_id', '=', 'users.id')
            ->select([
                'orders.*',
                'users.name as user_name',
                'leads.company_name',
                'leads.name as lead_name'
            ])
            ->whereNotNull('orders.deadline')
            ->whereBetween('orders.deadline', [$start, $end]);

        $orders = $ordersQuery->get();

        $orders = $orders->map(function ($order) use ($statusColors) {
            $color = $statusColors[$order->effective_status] ?? $statusColors['default'];
            $textColor = '#fff';
            $leadText = $order->lead_name && $order->company_name ? $order->company_name . ' - ' . $order->lead_name : 'Unknown';
            return [
                'id' => 'order-' . $order->id,
                'title' => $order->orderTitle,
                'start' => $order->deadline->toIso8601String(),
                'end' => $order->deadline->toIso8601String(),
                'allDay' => true,
                'extendedProps' => [
                    'calendar' => 'Order',
                    'type' => 'order',
                    'status' => $order->effective_status,
                    'lead_id' => $order->lead_id,
                    'lead_text' => $leadText,
                    'assigned_to' => $order->user_name ?? 'Unknown',
                    'description' => $order->orderDetail ?? '',
                    'order_number' => $order->order_number,
                    'approval' => $order->approval,
                    'draft' => $order->draft,
                    'pending' => $order->pending,
                ],
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => $textColor,
            ];
        });

        return response()->json($orders->toArray());
    }
}