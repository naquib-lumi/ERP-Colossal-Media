<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;   // ← add this
use Illuminate\Support\Facades\DB;

class ArtistController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();

        // Base scope — for artists, only their own orders
        $base = Order::query();
        if ($user->role === 'artist') {
            $base->where('user_id', $user->id);
        }

        // KPI metrics
        $metrics = [
            'total'       => (clone $base)->count(),
            'pending'     => (clone $base)->where('orderStatus', 'pending')->count(),
            'in_progress' => (clone $base)->where('orderStatus', 'in_progress')->count(),
            'completed'   => (clone $base)->where('orderStatus', 'completed')->count(),
            'rejected'    => (clone $base)->where('orderStatus', 'rejected')->count(),
            // 'to_assign' => (clone $base)->where('orderStatus','to_assign')->count(),
            // 'assigned'  => (clone $base)->where('orderStatus','assigned')->count(),
        ];

        // Orders list (respect the same scope)
        $orders = (clone $base)
            ->with('user')                // so {{ optional($order->user)->name }} works
            ->latest('orderDate')
            ->limit(50)
            ->get();

        // Salesperson dropdown
        $salespersons = User::query()
            ->where('role', 'salesperson')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Initial donut counts (all salespersons)
        $initial = Meeting::selectRaw("
            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) AS scheduled,
            SUM(CASE WHEN status = 'canceled'  THEN 1 ELSE 0 END) AS canceled
        ")->first();

        return view('artist.dashboard', [
            'orders'        => $orders,
            'metrics'       => $metrics, // ← pass to Blade
            'salespersons'  => $salespersons,
            'initialCounts' => [
                'scheduled' => (int) ($initial->scheduled ?? 0),
                'canceled'  => (int) ($initial->canceled  ?? 0),
            ],
        ]);
    }

    // AJAX for the donut chart
    public function meetingStatusCounts(Request $request)
    {
        $userId = $request->query('salesperson_id');

        $q = DB::table('meetings');
        if (!empty($userId)) {
            $q->where('user_id', $userId);
        }

        $scheduled = (clone $q)->where('status', 'scheduled')->count();
        $canceled  = (clone $q)->where('status', 'canceled')->count();

        return response()->json([
            'scheduled' => $scheduled,
            'canceled'  => $canceled,
        ]);
    }
}
