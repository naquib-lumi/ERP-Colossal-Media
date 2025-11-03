<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;


class MeetingController extends Controller
{

    public function show($id)
{
    $meeting = Meeting::with('lead')->findOrFail($id);
    if ($meeting->lead->salesperson_id !== Auth::id() && !Auth::user()->hasRole('head-salesperson')) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    return response()->json($meeting);
}

    public function storeFromLead(Request $request, $leadId)
    {
     $validated = $request->validate([
        // 'lead_id' => 'required|exists:leads,id',
        'title' => 'required|string|max:255',
        'start_time' => 'required|date',
        'duration' => 'required|integer|min:1',
        'type' => 'nullable|in:online,offline',
        'url' => 'nullable|url',
        'location' => 'nullable|string',
        'note' => 'nullable|string',
    ]);
        // Check for existing meeting with same lead, title, and start time
        $existingMeeting = Meeting::where('lead_id', $leadId)
            ->where('title', $validated['title'])
            ->where('start_time', Carbon::parse($validated['start_time']))
            ->first();

        if ($existingMeeting) {
            return response()->json(['error' => 'A meeting with this title and time already exists for this lead.'], 422);
        }

        $meeting = Meeting::create([
            'lead_id' => $leadId,
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'start_time' => Carbon::parse($validated['start_time']),
            'end_time' => Carbon::parse($validated['start_time'])->addMinutes((int) $validated['duration']),
            'type' => $validated['type'],
            'location' => $validated['type'] === 'offline' ? $validated['location'] : null,
            'url' => $validated['type'] === 'online' ? $validated['url'] : null,
            'note' => $validated['note'],
            'status' => 'scheduled',
        ]);

        return response()->json(['success' => true, 'message' => 'Meeting created successfully', 'meeting' => $meeting]);
    }



  public function index()
{
    $user = Auth::user();
    $now = Carbon::now();
    $start = $now->copy()->startOfMonth();
    $end = $now->copy()->endOfMonth();

    if (in_array($user->role, ['head-artist', 'head-salesperson'])) {
        $meetings = Meeting::with('lead', 'user')
            ->where('start_time', '>=', $start)
            ->where('start_time', '<=', $end)
            ->orderBy('start_time', 'asc')
            ->get();
    } else {
        $meetings = Meeting::with('lead', 'user')
            ->where('user_id', $user->id)
            ->where('start_time', '>=', $start)
            ->where('start_time', '<=', $end)
            ->orderBy('start_time', 'asc')
            ->get();
    }

    return response()->json($meetings);
}



    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate(['status' => 'required|in:scheduled,canceled,postponed']);
        $meeting = Meeting::findOrFail($id);
        $meeting->update(['status' => $validated['status']]);
        return response()->json(['success' => true]);
    }

    public function storeFromCalendar(Request $request)
{
    $user = Auth::user();
    if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $validated = $request->validate([
        'lead_id' => 'required|exists:leads,id',
        'title' => 'required|string|max:255',
        'start_time' => 'required|date',
        'duration' => 'required|integer|min:1',
        'type' => 'nullable|in:online,offline',
        'url' => 'nullable|url',
        'location' => 'nullable|string',
        'note' => 'nullable|string',
    ]);

    $lead = Lead::findOrFail($validated['lead_id']);
    if ($lead->salesperson_id !== $user->id && !$user->hasRole('head-salesperson')) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    if ($user->hasRole('head-salesperson')) {
        $validated['user_id'] = $lead->salesperson_id;
    } else {
        $validated['user_id'] = $user->id;
    }

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

    public function updateFromCalendar(Request $request, $id)
{
    $user = Auth::user();
    if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $meeting = Meeting::findOrFail($id);
    if ($meeting->user_id != $user->id && !$user->hasRole('head-salesperson')) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    Log::info('updateMeeting FormData:', $request->all());

    $validated = $request->validate([
        'lead_id' => 'required|exists:leads,id',
        'title' => 'required|string|max:255',
        'start_time' => 'required|date',
        'duration' => 'required|integer|min:1',
        'type' => 'nullable|in:online,offline',
        'url' => 'nullable|url',
        'location' => 'nullable|string',
        'note' => 'nullable|string',
    ]);

    $lead = Lead::findOrFail($validated['lead_id']);
    if ($lead->salesperson_id !== $user->id && !$user->hasRole('head-salesperson')) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $validated['end_time'] = Carbon::parse($validated['start_time'])->addMinutes((int) $validated['duration']);
    unset($validated['duration']);
    $type = $validated['type'] ?? null;
    if ($type === 'online') {
        $validated['location'] = null;
    } elseif ($type === 'offline') {
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

    public function updateCalendarStatus(Request $request, $id)
    {
        $validated = $request->validate(['status' => 'required|in:scheduled,canceled,postponed']);
        $meeting = Meeting::findOrFail($id);
        $meeting->update(['status' => $validated['status']]);
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $meeting = Meeting::findOrFail($id);
        if ($meeting->user_id != $user->id && !$user->hasRole('head-salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $meeting->delete();
        Log::info("Meeting ID {$id} deleted");
        return response()->json(['success' => true]);
    }
}