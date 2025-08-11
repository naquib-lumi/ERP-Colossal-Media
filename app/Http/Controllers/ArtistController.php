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
        $user  = Auth::user();
        $base  = $this->visibleOrders();
        $isTop = $this->isHeadArtist($user);

        // KPI metrics (respect visible scope)
        $metrics = [
            'total'       => (clone $base)->count(),
            'pending'     => (clone $base)->where('orderStatus', 'pending')->count(),
            'in_progress' => (clone $base)->where('orderStatus', 'in_progress')->count(),
            'completed'   => (clone $base)->where('orderStatus', 'completed')->count(),
            'rejected'    => (clone $base)->where('orderStatus', 'rejected')->count(),
        ];

        // Head-artist can also see these two buckets
        if ($isTop) {
            $metrics['to_assign'] = (clone $base)->where('orderStatus', 'to_assign')->count();
            $metrics['assigned']  = (clone $base)->where('orderStatus', 'assigned')->count();
        }

        // Orders list (respect scope)
        $orders = $this->visibleOrders()
            ->with(['artist:id,name', 'salesperson:id,name'])
            ->latest('orderDate')
            ->paginate(10);

        // Salesperson filter for the donut chart
        $salespersons = User::where('role', 'salesperson')
            ->orderBy('name')
            ->get(['id','name']);

        // Initial donut counts (all salespersons)
        $initial = Meeting::selectRaw("
            SUM(CASE WHEN status='scheduled' THEN 1 ELSE 0 END) AS scheduled,
            SUM(CASE WHEN status='canceled'  THEN 1 ELSE 0 END) AS canceled
        ")->first();

        return view('artist.dashboard', [
            'orders'        => $orders,
            'metrics'       => $metrics,
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

    public function orders()
    {
        $user  = Auth::user();
        $base  = $this->visibleOrders();
        $isTop = $this->isHeadArtist($user);

        $metrics = [
            'total'       => (clone $base)->count(),
            'pending'     => (clone $base)->where('orderStatus', 'pending')->count(),
            'in_progress' => (clone $base)->where('orderStatus', 'in_progress')->count(),
            'completed'   => (clone $base)->where('orderStatus', 'completed')->count(),
            'rejected'    => (clone $base)->where('orderStatus', 'rejected')->count(),
        ];

        if ($isTop) {
            $metrics['to_assign'] = (clone $base)->where('orderStatus', 'to_assign')->count();
            $metrics['assigned']  = (clone $base)->where('orderStatus', 'assigned')->count();
        }

        $orders = (clone $base)
            ->with(['artist:id,name', 'salesperson:id,name'])
            ->latest('orderDate')
            ->paginate(10);

        return view('artist.orders', compact('orders','metrics'));
    }

    public function edit(Order $order)
    {
        $user = Auth::user();

        // Normal artist can only open orders assigned to them
        if (!$this->isHeadArtist($user) && $order->artist_id !== $user->id) {
            abort(403);
        }

        $orderCode   = sprintf('ORD-%04d', $order->id);
        $today       = now()->format('M d, Y');
        $attachments = !empty($order->orderAttachment)
            ? array_filter(array_map('trim', explode(',', $order->orderAttachment)))
            : [];

        return view('artist.orders.edit', compact('order','orderCode','today','attachments'));
    }

    public function update(Request $request, Order $order)
    {
        // Only “Design Confirmation Required?” is editable here
        $request->validate(['design_confirmed' => 'required|boolean']);

        $order->approval = $request->boolean('design_confirmed');
        $order->save();

        return back()->with('success','Order updated.');
    }

    /**
     * Visible orders for the current user:
     * - head-artist: sees everything
     * - artist: only orders assigned to them; hide "to_assign" / "assigned"
     */
    private function visibleOrders()
    {
        $user = Auth::user();
        $q    = Order::query();

        if ($this->isHeadArtist($user)) {
            return $q; // no restrictions
        }

        // Normal artist
        return $q->where('artist_id', $user->id)
                 ->whereNotIn('orderStatus', ['to_assign', 'assigned']);
    }

    private function isHeadArtist($user): bool
    {
        return $user && $user->role === 'head-artist';
    }
}
