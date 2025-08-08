<?php

namespace App\Http\Controllers;


use App\Models\Order;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ArtistController extends Controller
{
    public function dashboard()
    {
        // eager-load the user (artist) relation so name is available
        $orders = Order::with('user')
            ->latest('orderDate')   // or ->orderByDesc('orderDate')
            ->take(50)
            ->get();

        // 👈 Needed by the “Salesperson” filter in Blade
        $salespersons = User::query()
            ->where('role', 'salesperson')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Optional: initial counts for the donut (all salespersons)
        $initial = Meeting::selectRaw("
                SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) AS scheduled,
                SUM(CASE WHEN status = 'canceled'  THEN 1 ELSE 0 END) AS canceled
            ")->first();

        return view('artist.dashboard', [
            'orders'        => $orders,
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
        if ($userId) {
            $q->where('user_id', $userId);
        }

        $scheduled = (clone $q)->where('status', 'scheduled')->count();
        $canceled  = (clone $q)->where('status', 'canceled')->count();

        return response()->json(['scheduled' => $scheduled, 'canceled' => $canceled]);
    }
}