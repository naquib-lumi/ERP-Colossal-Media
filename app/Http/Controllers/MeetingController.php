<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MeetingController extends Controller
{
    public function store(Request $request, $leadId)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'duration' => 'required|integer|min:1',
            'type' => 'required|in:online,offline',
            'url' => 'nullable|url|required_if:type,online',
            'location' => 'nullable|string|required_if:type,offline',
            'note' => 'nullable|string',
        ]);

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

        return response()->json(['success' => true, 'message' => 'Meeting created successfully']);
    }

  public function index()
{
    $user = Auth::user();
    if (in_array($user->role, ['head-artist', 'head-salesperson'])) {
        $meetings = Meeting::with('lead', 'user')->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END, start_time ASC")->get();
    } else {
        $meetings = Meeting::with('lead', 'user')->where('user_id', $user->id)->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END, start_time ASC")->get();
    }
    return response()->json($meetings);
}

    public function updateStatus(Request $request, $id)
    {
        $meeting = Meeting::findOrFail($id);
        if ($meeting->user_id != Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'status' => 'required|in:scheduled,completed,cancelled,missed',
        ]);
        $meeting->status = $validated['status'];
        $meeting->save();
        return response()->json(['success' => true]);
    }
}