<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Reminder;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }
        return view('sales.calendar');
    }

   public function events(Request $request)
{
    $user = Auth::user();
    if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    Log::info('events called for user: ' . $user->email);

    $start = $request->input('start');
    $end = $request->input('end');

    // ----- Meetings -----
    $meetingsQuery = Meeting::query();

    if ($user->hasRole('salesperson') && !$user->hasRole('head-salesperson')) {
        $meetingsQuery->where('user_id', $user->id);
    }

    $meetings = $meetingsQuery
        ->whereBetween('start_time', [$start, $end])
        ->with(['lead', 'user'])
        ->get()
        ->map(function ($meeting) {
            $color = match($meeting->status) {
                'scheduled' => '#007bff',
                'canceled' => '#090a0bff',
                'postponed' => '#007bff',
                default => '#007bff'
            };
            $textColor = ($color === '#ffc107') ? '#000' : '#fff';
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
                    'lead_text' => $meeting->lead ? $meeting->lead->company_name . ' - ' . $meeting->lead->name : 'Unknown',
                    'note' => $meeting->note,
                    'created_by' => $meeting->user ? $meeting->user->name : 'Unknown',
                ],
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => $textColor,
            ];
        });

    // ----- Reminders -----
  $remindersQuery = Reminder::with(['lead.user'])->whereBetween('remind_at', [$start, $end]);

if ($user->hasRole('salesperson') && !$user->hasRole('head-salesperson')) {
    $remindersQuery->whereHas('lead', function ($q) use ($user) {
        $q->where('salesperson_id', $user->id);
    });
}

$reminders = $remindersQuery
    ->get()
    ->map(function ($reminder) {
        $color = $reminder->status === 'completed' ? '#6c757d' : ($reminder->remind_at->isPast() ? '#dc3545' : '#28a745');
        $textColor = ($color === '#ffc107') ? '#000' : '#fff';
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
                'lead_text' => $reminder->lead ? $reminder->lead->company_name . ' - ' . $reminder->lead->name : 'Unknown',
                'created_by' => $reminder->user ? $reminder->user->name : 'Unknown',
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

}