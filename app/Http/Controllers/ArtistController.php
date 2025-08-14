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

        // eager-load products so we can use them in the view
        $order->load(['products']); // requires Order::products() relationship

        $orderCode = sprintf('ORD-%04d', $order->id);
        $today     = now()->format('M d, Y');

        // Build items array from JSON (kept as-is)
        $items = [];
        if (!empty($order->order_items_json)) {
            $items = json_decode($order->order_items_json, true) ?: [];
        }
        if (empty($items)) {
            $items = [[
                'name' => '', 'qty' => '', 'material' => '',
                'size' => ['w'=>'','h'=>'','l'=>'','top'=>'','bottom'=>'','left'=>'','right'=>''],
                'lamination' => '', 'printer' => '', 'cutter' => '', 'finishing' => ''
            ]];
        }

        // Attachments (kept as-is, using helper)
        $attachments = $this->getOrderAttachments($order);

        // Products from DB
        $products = $order->products;          // collection (may be empty)
        $product  = $products->first();         // first product for single-block UI

         return view('artist.orders.edit', compact(
            'order', 'orderCode', 'today', 'attachments', 'items', 'products', 'product'
        ));
    }

    public function update(Request $request, Order $order)
    {
        try {
            // 1) Validate once
            $data = $request->validate([
                'design_confirmed'       => ['required','boolean'],
                'is_draft'               => ['required','in:0,1'],

                // form fields (add/remove as needed)
                'leadName'               => ['nullable','string','max:255'],
                'leadPhone'              => ['nullable','string','max:30'],
                'companyName'            => ['nullable','string','max:255'],
                'deadline'               => ['nullable','date'],
                'leadEmail'              => ['nullable','email','max:255'],
                'orderTitle'             => ['nullable','string','max:255'],
                'orderDetail'            => ['nullable','string'],

                // attachments
                'attachments.*'          => ['file','mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,ppt,pptx'],
                'delete_attachments'     => ['array'],
                'delete_attachments.*'   => ['string'],
            ]);

            // 2) Only patch fields that are present in the request (prevents wiping)
            $updatable = [
                'leadName','leadPhone','companyName','deadline',
                'leadEmail','orderTitle','orderDetail',
            ];
            foreach ($updatable as $key) {
                if ($request->has($key)) {
                    // use ->input so empty string is a valid update if user cleared it on purpose
                    $order->{$key} = $request->input($key);
                }
            }

            // 3) Flags
            $order->draft       = (int) $data['is_draft'];     // 1 = draft, 0 = submit
            $order->orderStatus = 'in_progress';
            $order->approval    = (bool) $data['design_confirmed'];

            // 4) Attachments (consistent path + same column)
            $paths = collect($this->getOrderAttachments($order)); // reads orderAttachment (comma list)

            // deletions (optional)
            foreach ($request->input('delete_attachments', []) as $delPath) {
                Storage::disk('public')->delete($delPath);
                $paths = $paths->reject(fn ($p) => trim($p) === trim($delPath));
            }

            // new files (store to SAME place as before)
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if ($file && $file->isValid()) {
                        $stored = $file->store("orders/{$order->id}/attachments", 'public');
                        $paths->push($stored);
                    }
                }
            }

            // write list back to the SAME column
            $this->putOrderAttachments($order, $paths->values()->all());

            // 5) Save once
            $order->save();

            $msg = $order->draft ? 'Draft saved successfully.' : 'Order submitted successfully.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => true, 'message' => $msg], 200);
            }
            return back()->with('success', $msg);

        } catch (\Throwable $e) {
            $err = 'Save failed: ' . $e->getMessage();
            if ($request->ajax() || $request->wantsJson()) {
                // 422 if it’s a validation-like issue, 500 otherwise — your call
                return response()->json(['ok' => false, 'message' => $err], 500);
            }
            return back()->with('error', $err)->withInput();
        }
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
        $user = Auth::user();
        if (!$this->isHeadArtist($user) && $order->artist_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,ppt,pptx'
        ]);

        // store file
        $path = $request->file('file')->store("orders/{$order->id}/attachments", 'public');

        // append to DB (comma-separated list)
        $list = $this->getOrderAttachments($order);
        $list[] = $path;
        $this->putOrderAttachments($order, $list);

        return response()->json(['path' => $path, 'url' => Storage::disk('public')->url($path)], 201);
    }

    // Remove from order + delete file (optional)
    public function deleteAttachment(Request $request, Order $order)
    {
        $user = Auth::user();
        if (!$this->isHeadArtist($user) && $order->artist_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate(['path' => 'required|string']);
        $path = trim($data['path']);

        // delete physical file (ignore if missing)
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        // remove from DB list
        $list = collect($this->getOrderAttachments($order))
            ->reject(fn ($p) => trim($p) === $path)
            ->values()->all();
        $this->putOrderAttachments($order, $list);

        return response()->json(['ok' => true]);
    }

}
