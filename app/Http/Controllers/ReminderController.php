<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Models\Lead;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        'remind_at' => 'required|date',
    ]);

    $lead = Lead::findOrFail($validated['lead_id']);
    if ($lead->salesperson_id !== $user->id && !$user->hasRole('head-salesperson')) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $validated['salesperson_id'] = $user->id;
    $validated['created_by'] = $user->id;
    $validated['is_auto'] = false;
    $validated['status'] = 'upcoming';

    try {
        $reminder = Reminder::create($validated);
        Log::info("Reminder ID {$reminder->id} created for lead ID {$validated['lead_id']}");
        return response()->json(['success' => true, 'reminder' => $reminder]);
    } catch (\Exception $e) {
        Log::error("Error creating reminder: " . $e->getMessage());
        return response()->json(['error' => 'Failed to create reminder'], 500);
    }
}

public function completeFromNotification(Reminder $reminder)
{
    if (Auth::id() !== $reminder->created_by) {
        abort(403);
    }

    $reminder->update(['status' => 'completed']);

    return redirect("/leads/{$reminder->lead_id}")->with('success', 'Reminder completed.');
}

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $reminder = Reminder::findOrFail($id);
        if ($reminder->lead && $reminder->lead->salesperson_id != $user->id && !$user->hasRole('head-salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'title' => 'required|string|max:255',
            'remind_at' => 'required|date',
        ]);

        $lead = Lead::findOrFail($validated['lead_id']);
        if ($lead->salesperson_id !== $user->id && !$user->hasRole('head-salesperson')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $reminder->update($validated);
            Log::info("Reminder ID {$id} updated for lead ID {$validated['lead_id']}");
            return response()->json(['success' => true, 'reminder' => $reminder->fresh()]);
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
        if ($reminder->lead && $reminder->lead->salesperson_id != $user->id && !$user->hasRole('head-salesperson')) {
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
        if ($reminder->lead && $reminder->lead->salesperson_id != $user->id && !$user->hasRole('head-salesperson')) {
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