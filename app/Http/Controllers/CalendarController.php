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
        if (!$user->hasRole('salesperson')) {
            abort(403, 'Unauthorized');
        }
        return view('sales.calendar');
    }

    public function events(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('salesperson')) {
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

    public function updateMeetingStatus(Request $request, $id)
{
    $validated = $request->validate(['status' => 'required|in:scheduled,canceled,postponed']);
    $meeting = Meeting::findOrFail($id);
    $meeting->update(['status' => $validated['status']]);
    return response()->json(['success' => true]);
}

public function updateReminderStatus(Request $request, $id)
{
    $validated = $request->validate(['status' => 'required|in:upcoming,completed']);
    $reminder = Reminder::findOrFail($id);
    $reminder->update(['status' => $validated['status']]);
    return response()->json(['success' => true]);
}
    

    public function storeReminder(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'title' => 'required|string|max:255',
            'due_date' => 'required|date',
            'recurrence_type' => 'nullable|in:none,daily,weekly,monthly',
            'recurrence_time' => 'nullable|date_format:H:i',
        ]);

        $lead = Lead::findOrFail($validated['lead_id']);
        if ($lead->salesperson_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated['salesperson_id'] = $user->id;
        $validated['is_auto'] = false;
        $validated['end_date'] = Carbon::parse($validated['due_date'])->addDays(3);
        $validated['last_notify_time'] = null;
        $validated['status'] = 'upcoming';

        try {
            $reminder = Reminder::create($validated);
            $reminder->notifyUser();
            Log::info("Reminder ID {$reminder->id} created for lead ID {$validated['lead_id']}");
            return response()->json(['success' => true, 'reminder' => $reminder]);
        } catch (\Exception $e) {
            Log::error("Error creating reminder: " . $e->getMessage());
            return response()->json(['error' => 'Failed to create reminder'], 500);
        }
    }

    public function updateReminder(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasRole('salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $reminder = Reminder::findOrFail($id);
        if ($reminder->lead && $reminder->lead->salesperson_id != $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'title' => 'required|string|max:255',
            'due_date' => 'required|date',
            'recurrence_type' => 'nullable|in:none,daily,weekly,monthly',
            'recurrence_time' => 'nullable|date_format:H:i',
        ]);

        $lead = Lead::findOrFail($validated['lead_id']);
        if ($lead->salesperson_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated['end_date'] = Carbon::parse($validated['due_date'])->addDays(3);

        try {
            $reminder->update($validated);
            Log::info("Reminder ID {$id} updated for lead ID {$validated['lead_id']}");
            return response()->json(['success' => true, 'reminder' => $reminder]);
        } catch (\Exception $e) {
            Log::error("Error updating reminder: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update reminder'], 500);
        }
    }

    public function completeReminder(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasRole('salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $reminder = Reminder::findOrFail($id);
        if ($reminder->lead && $reminder->lead->salesperson_id != $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($request->input('confirm') === 'yes') {
            $reminder->update(['status' => 'completed']);
            Log::info("Reminder ID {$id} marked as completed");
            return response()->json(['success' => true, 'message' => 'Reminder marked as completed']);
        }

        return response()->json(['error' => 'Confirmation not provided'], 400);
    }

    public function storeMeeting(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'duration' => 'required|integer|min:1',
            'type' => 'required|in:online,offline',
            'url' => 'nullable|url|required_if:type,online',
            'location' => 'nullable|string|required_if:type,offline',
            'note' => 'nullable|string',
        ]);

        $lead = Lead::findOrFail($validated['lead_id']);
        if ($lead->salesperson_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated['user_id'] = $user->id;
        $validated['end_time'] = Carbon::parse($validated['start_time'])->addMinutes((int) $validated['duration']);
        unset($validated['duration']);
        if ($validated['type'] === 'online') {
            $validated['location'] = null;
        } else {
            $validated['url'] = null;
        }

        try {
            $meeting = Meeting::create($validated);
            Log::info("Meeting ID {$meeting->id} created for lead ID {$validated['lead_id']}");
            return response()->json(['success' => true, 'meeting' => $meeting]);
        } catch (\Exception $e) {
            Log::error("Error creating meeting: " . $e->getMessage());
            return response()->json(['error' => 'Failed to create meeting'], 500);
        }
    }

    public function updateMeeting(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasRole('salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $meeting = Meeting::findOrFail($id);
        if ($meeting->user_id != $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        Log::info('updateMeeting FormData:', $request->all());

        $validated = $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'duration' => 'required|integer|min:1',
            'type' => 'required|in:online,offline',
            'url' => 'nullable|url|required_if:type,online',
            'location' => 'nullable|string|required_if:type,offline',
            'note' => 'nullable|string',
        ]);

        $lead = Lead::findOrFail($validated['lead_id']);
        if ($lead->salesperson_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated['end_time'] = Carbon::parse($validated['start_time'])->addMinutes((int) $validated['duration']);
        unset($validated['duration']);
        if ($validated['type'] === 'online') {
            $validated['location'] = null;
        } else {
            $validated['url'] = null;
        }

        try {
            $meeting->update($validated);
            Log::info("Meeting ID {$id} updated for lead ID {$validated['lead_id']}");
            return response()->json(['success' => true, 'meeting' => $meeting]);
        } catch (\Exception $e) {
            Log::error("Error updating meeting: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update meeting'], 500);
        }
    }

    public function destroyReminder($id)
    {
        $user = Auth::user();
        if (!$user->hasRole('salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $reminder = Reminder::findOrFail($id);
        if ($reminder->lead && $reminder->lead->salesperson_id != $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $reminder->delete();
        Log::info("Reminder ID {$id} deleted");
        return response()->json(['success' => true]);
    }

    public function destroyMeeting($id)
    {
        $user = Auth::user();
        if (!$user->hasRole('salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $meeting = Meeting::findOrFail($id);
        if ($meeting->user_id != $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $meeting->delete();
        Log::info("Meeting ID {$id} deleted");
        return response()->json(['success' => true]);
    }
}