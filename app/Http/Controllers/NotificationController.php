<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Helpers\Helpers;

class NotificationController extends Controller
{
    public function markAsRead(Request $request)
    {
        $n = Auth::user()->notifications()->find($request->id);
        if ($n) {
            if (is_null($n->read_at)) {
                $n->markAsRead();
            }
            // return a minimal payload the UI expects
            return response()->json(['ok' => true, 'id' => $n->id]);
        }
        return response()->json(['ok' => false, 'message' => 'Notification not found'], 404);
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }

    public function archive(Request $request, string $id)
    {
        $n = Auth::user()->notifications()->find($id);
        if ($n) {
            $n->delete();
            return response()->json(['success' => true]);
        }
        return response()->json(['error' => 'Notification not found'], 404);
    }

    public function archiveAll()
    {
        Auth::user()->notifications()->delete();
        return response()->json(['success' => true]);
    }

    public function index()
    {
        // full page – leave as all notifications (or change to unread() if you prefer)
        $notifications = Auth::user()->notifications()->latest()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    public function count()
    {
        return Auth::user()->unreadNotifications->count();
    }

    // --- optional: tiny endpoint to return the latest unread for the dropdown
    public function latestUnread()
    {
        $items = Auth::user()
            ->unreadNotifications()
            ->latest()
            ->take(10)
            ->get();

        return response()->json($items);
    }

    public function testSelf(Request $request) {
        Helpers::notify(auth()->user(), 'Test notification to self', url('/'), ['database','mail']);
        return response()->json(['success' => true]);
    }

    public function testAll(Request $request) {
        User::all()->each(function($user) {
            Helpers::notify($user, 'Test notification to all users', url('/leads/37/edit'), ['database']);
        });
        return response()->json(['success' => true]);
    }

    public function testSales(Request $request) {
        User::where('role', 'salesperson')->each(function($user) {
            Helpers::notify($user, 'Test notification to all salespeople', url('/'), ['database']);
        });
        return response()->json(['success' => true]);
    }

}