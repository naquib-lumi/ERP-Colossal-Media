<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Reminder;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ArtistCalendarController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->hasRole('artist')) {
            abort(403, 'Unauthorized');
        }
        return view('artist.calendar');
    }

    public function events(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('artist')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        Log::info('events called for user: ' . $user->email);

        $start = $request->input('start');
        $end = $request->input('end');

        $meetings = Meeting::where('user_id', $user->id)
            ->whereBetween('start_time', [$start, $end])
            ->with('lead')
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
                    ],
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'textColor' => $textColor,
                ];
            });

        $reminders = Reminder::whereHas('lead', function ($q) use ($user) {
                $q->where('salesperson_id', $user->id);
            })
            ->whereBetween('due_date', [$start, $end])
            ->with('lead')
            ->get()
            ->map(function ($reminder) {
                $color = $reminder->status === 'completed' ? '#6c757d' : ($reminder->due_date->isPast() ? '#dc3545' : '#28a745');
                $textColor = ($color === '#ffc107') ? '#000' : '#fff';
                return [
                    'id' => 'reminder-' . $reminder->id,
                    'title' => $reminder->title,
                    'start' => $reminder->due_date->toIso8601String(),
                    'allDay' => true,
                    'extendedProps' => [
                        'calendar' => 'Reminder',
                        'type' => 'reminder',
                        'status' => $reminder->status,
                        'lead_id' => $reminder->lead_id,
                        'lead_text' => $reminder->lead ? $reminder->lead->company_name . ' - ' . $reminder->lead->name : 'Unknown',
                        'recurrence_type' => $reminder->recurrence_type,
                        'recurrence_time' => $reminder->recurrence_time,
                    ],
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'textColor' => $textColor,
                ];
            });

        return response()->json($meetings->merge($reminders));
    }
}