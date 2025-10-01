<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReminderController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
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

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
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

    public function complete(Request $request, $id)
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
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

    public function destroy($id)
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
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

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate(['status' => 'required|in:upcoming,completed']);
        $reminder = Reminder::findOrFail($id);
        $reminder->update(['status' => $validated['status']]);
        return response()->json(['success' => true]);
    }
}