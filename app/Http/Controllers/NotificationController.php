<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function markAsRead(Request $request)
    {
        $notification = Auth::user()->notifications()->find($request->id);
        if ($notification) {
            $notification->markAsRead();
            return response()->json(['success' => true]);
        }
        return response()->json(['error' => 'Notification not found'], 404);
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }

    public function archive(Request $request)
    {
        $notification = Auth::user()->notifications()->find($request->id);
        if ($notification) {
            $notification->delete();
            return response()->json(['success' => true]);
        }
        return response()->json(['error' => 'Notification not found'], 404);
    }

    public function index()
    {
        $notifications = Auth::user()->notifications()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    public function count()
    {
        return Auth::user()->unreadNotifications->count();
    }
}
