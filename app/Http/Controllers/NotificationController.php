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




    public function testSelf(Request $request) {
    Helpers::notify(auth()->user(), 'Test notification to self', url('/'), ['database','mail']);
    return response()->json(['success' => true]);
}

    public function testAll(Request $request) {
        User::all()->each(function($user) {
            Helpers::notify($user, 'Test notification to all users', url('/'), ['database']);
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
