<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
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
            ->paginate(1000);

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
            ->paginate(1000);

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

        $items = [];
        if (!empty($order->order_items_json)) {
            $items = json_decode($order->order_items_json, true) ?: [];
        }

        // Guarantee at least one empty item so the UI shows something
        if (empty($items)) {
            $items = [[
                'name' => '', 'qty' => '', 'material' => '',
                'size' => ['w'=>'','h'=>'','l'=>'','top'=>'','bottom'=>'','left'=>'','right'=>''],
                'lamination' => '', 'printer' => '', 'cutter' => '', 'finishing' => ''
            ]];
        }

        $attachments = !empty($order->orderAttachment)
            ? array_filter(array_map('trim', explode(',', $order->orderAttachment)))
            : [];

        $attachments = $this->getOrderAttachments($order);

        return view('artist.orders.edit', compact('order','orderCode','today','attachments','items'));
    }

    public function update(Request $request, Order $order)
    {
        // Only “Design Confirmation Required?” is editable here
        $request->validate(['design_confirmed' => 'required|boolean']);

        $order->approval = $request->boolean('design_confirmed');
        $order->save();

        $validated = $request->validate([
            'design_confirmed'     => ['required','boolean'],
            'attachments.*'        => ['file','max:10240','mimes:pdf,jpg,jpeg,png,doc,docx'],
            'delete_attachments'   => ['array'],
            'delete_attachments.*' => ['string'],
        ]);

        // Start with existing list (always array because of $casts)
        $existing = collect($order->attachments ?? []);

        // 1) Delete checked files
        $toDelete = collect($request->input('delete_attachments', []));
        if ($toDelete->isNotEmpty()) {
            $toDelete->each(function ($path) {
                Storage::disk('public')->delete($path);
            });
            $existing = $existing->reject(fn ($p) => $toDelete->contains($p));
        }

        // 2) Upload new files
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if (!$file->isValid()) continue;
                $path = $file->store("orders/{$order->id}", 'public'); // storage/app/public/orders/{id}
                $existing->push($path);
            }
        }

        // 3) Save back
        $order->attachments = $existing->values()->all();
        $order->approval    = $request->boolean('design_confirmed');
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

    // Helper to read/combine either JSON or comma string
    private function getOrderAttachments(Order $order): array
    {
        $raw = $order->orderAttachment ?? '';
        if ($raw === '') return [];
        if (str_starts_with(trim($raw), '[')) {
            return array_values(array_filter((array) json_decode($raw, true)));
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    private function putOrderAttachments(Order $order, array $paths): void
    {
        // keep as comma separated (or switch to json_encode($paths))
        $order->orderAttachment = implode(',', $paths);
        $order->save();
    }

    // Upload endpoint (Dropzone)
    public function uploadAttachments(Request $request, Order $order)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // 20MB
        ]);

        // Store under public disk; adjust path as you like
        $path = $request->file('file')->store("orders/{$order->id}/attachments", 'public');

        // You can also persist this path on the order if you track them in DB
        // e.g., $order->addAttachment($path);

        return response()->json([
            'path' => $path,
            'url'  => Storage::disk('public')->url($path),
        ]);
    }

    // Remove from order + delete file (optional)
    public function deleteAttachment(Request $request, Order $order)
    {
        $request->validate(['path' => 'required|string']);

        if (Storage::disk('public')->exists($request->path)) {
            Storage::disk('public')->delete($request->path);
        }

        // Also remove from DB if you track it
        return response()->json(['ok' => true]);
    }

}
