<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;   // ← add this
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\DeliveryBreakdown;
use App\Models\Specification;


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
            ->get(['id', 'name']);

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

        return view('artist.orders', compact('orders', 'metrics'));
    }

    public function edit(Order $order)
    {
        $user = Auth::user();
        if (!$this->isHeadArtist($user) && $order->artist_id !== $user->id) abort(403);

        $orderCode = sprintf('ORD-%04d', $order->id);
        $today     = now()->format('M d, Y');

        // Product (unchanged)
        $product = \App\Models\Product::with('breakdowns')
            ->where('OrderID', $order->id)
            ->first();

        // ✅ Items via JOIN: product_items + specifications, filtered by this order’s product(s)
        $items = DB::table('product_items as pi')
            ->join('products as p', 'p.ProductID', '=', 'pi.ProductID')
            ->leftJoin('specifications as s', 's.ItemID', '=', 'pi.ItemID')
            ->where('p.OrderID', $order->id)
            ->orderBy('pi.ItemID')
            ->selectRaw('
                pi.ItemID, pi.ProductID, pi.itemName, pi.quantity,
                pi.sizeWidth, pi.sizeHeight, pi.sizeLength,
                pi.bleedTop, pi.bleedBottom, pi.bleedLeft, pi.bleedRight,
                pi.finishing, pi.renderTime, pi.material,
                s.lamination, s.printer, s.cutter
            ')
            ->get()
            ->values(); // ensure indexes start at 0 (for your items[{{ $i }}] names)

        // If you want material shown as a string instead of JSON
        $items = $items->map(function ($row) {
            if (is_string($row->material) && str_starts_with($row->material, '[')) {
                $decoded = json_decode($row->material, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $row->material = implode(', ', $decoded);
                }
            }
            return $row;
        });

        $attachments = $this->getOrderAttachments($order);
        return view('artist.orders.edit', compact(
            'order','orderCode','today','attachments','product','items'
        ));
    }

    public function update(Request $request, Order $order)
    {
        // AuthZ: artist can only edit own orders unless head-artist
        $user = Auth::user();
        if (!$this->isHeadArtist($user) && $order->artist_id !== $user->id) {
            abort(403);
        }

        // Validate payload
        $validated = $request->validate([
            'design_confirmed'  => ['required', 'boolean'],
            'is_draft'          => ['required', 'in:0,1'],

            // Product block (optional)
            'product.id'        => ['nullable', 'integer'],
            'product.name'      => ['nullable', 'string', 'max:255'],
            'product.qty_total' => ['nullable', 'integer', 'min:0'],
            'product.material'  => ['nullable', 'string', 'max:255'],
            'product.remarks'   => ['nullable', 'string'],

            // Items
            'items'                          => ['array'],
            'items.*.id'                     => ['nullable', 'integer'],
            'items.*.itemName'               => ['nullable', 'string', 'max:255'],
            'items.*.quantity'               => ['nullable', 'integer', 'min:0'],

            'items.*.sizeWidth'              => ['nullable', 'numeric'],
            'items.*.sizeHeight'             => ['nullable', 'numeric'],
            'items.*.sizeLength'             => ['nullable', 'numeric'],

            'items.*.bleedTop'             => ['nullable','numeric'],
            'items.*.bleedBottom'          => ['nullable','numeric'],
            'items.*.bleedLeft'            => ['nullable','numeric'],
            'items.*.bleedRight'           => ['nullable','numeric'],

            'items.*.finishing'              => ['nullable', 'string', 'max:255'],
            'items.*.renderTime'             => ['nullable', 'integer', 'min:0'],
            'items.*.material'           => ['nullable'],
            'items.*.material.*'         => ['nullable','string','max:255'],

            // spec (optional)
            'items.*.lamination'             => ['nullable', 'string', 'max:255'],
            'items.*.printer'                => ['nullable', 'string', 'max:255'],
            'items.*.cutter'                 => ['nullable', 'string', 'max:255'],

            // Delivery breakdowns
            'breakdowns'                     => ['array'],
            'breakdowns.*.id'                => ['nullable', 'integer'],
            'breakdowns.*.method'            => ['nullable', 'string', 'max:255'],
            'breakdowns.*.quantity'          => ['nullable', 'integer', 'min:0'],
            'breakdowns.*.date'              => ['nullable', 'date'],
            'breakdowns.*.time'              => ['nullable', 'date_format:H:i'],
            'breakdowns.*.location'          => ['nullable', 'string', 'max:255'],

            // Attachments (kept from your old flow)
            'attachments.*'        => ['file', 'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,ppt,pptx', 'max:20480'],
            'delete_attachments'   => ['array'],
            'delete_attachments.*' => ['string'],
        ]);

        try {
            DB::transaction(function () use ($request, $order) {

                // ---------------- 1) Update order core fields you allow here ----------------
                // Keep existing values as default so we don't blank-out columns
                $order->leadName    = $request->input('leadName',    $order->leadName);
                $order->leadPhone   = $request->input('leadPhone',   $order->leadPhone);
                $order->companyName = $request->input('companyName', $order->companyName);
                $order->deadline    = $request->input('deadline',    $order->deadline);
                $order->leadEmail   = $request->input('leadEmail',   $order->leadEmail);
                $order->orderTitle  = $request->input('orderTitle',  $order->orderTitle);
                $order->orderDetail = $request->input('orderDetail', $order->orderDetail);

                $order->draft       = (int) $request->input('is_draft', 0); // from submit buttons
                $order->orderStatus = 'in_progress';
                $order->approval    = $request->boolean('design_confirmed');

                // ---------------- 2) Attachments: delete selected + add newly uploaded ------
                $existing = collect($this->getOrderAttachments($order));

                $toDelete = collect($request->input('delete_attachments', []));
                if ($toDelete->isNotEmpty()) {
                    $toDelete->each(fn($p) => Storage::disk('public')->delete($p));
                    $existing = $existing->reject(fn($p) => $toDelete->contains($p));
                }
                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        if (!$file->isValid()) continue;
                        $path = $file->store("orders/{$order->id}/attachments", 'public');
                        $existing->push($path);
                    }
                }
                $this->putOrderAttachments($order, $existing->values()->all());
                $order->save();

                // ---------------- 3) Upsert Product (create stub if children exist) ----------
                $product = Product::where('OrderID', $order->id)->first();

                $p = $request->input('product', []);
                $hasProduct    = isset($p['name']) || isset($p['qty_total']) || isset($p['material']) || isset($p['remarks']);
                $hasItems      = filled($request->input('items', []));
                $hasBreakdowns = filled($request->input('breakdowns', []));

                if (!$product && ($hasProduct || $hasItems || $hasBreakdowns)) {
                    $product = new Product();
                    $product->OrderID = $order->id;
                }

                // Only touch fields that were posted
                if ($product) {
                    if (array_key_exists('name', $p))       $product->productName    = $p['name'];
                    if (array_key_exists('qty_total', $p))  $product->totalQuantity  = $p['qty_total'];
                    if (array_key_exists('material', $p))   $product->materialRemark = $p['material'];
                    if (array_key_exists('remarks', $p))    $product->productRemark  = $p['remarks'];
                    $product->save();
                }

                if (!$product) return; // nothing else to do

                // ---------------- 4) Upsert Items (+ optional Specification) -----------------
                $postedItems = collect($request->input('items', []))
                    ->filter(fn($row) => is_array($row));

                $keepItemIds = [];
                foreach ($postedItems as $row) {
                    // completely empty rows are ignored
                    $isEmpty = collect($row)->filter(fn($v, $k) => $k !== 'id' && $v !== null && $v !== '')->isEmpty();
                    if ($isEmpty) continue;

                    $item = null;
                    if (!empty($row['id'])) {
                        $item = ProductItem::where('ItemID', (int)$row['id'])
                            ->where('ProductID', $product->ProductID)
                            ->first();
                    }
                    if (!$item) {
                        $item = new ProductItem();
                        $item->ProductID = $product->ProductID;
                    }

                    foreach (
                        [
                            'itemName','quantity',
                            'sizeWidth','sizeHeight','sizeLength',
                            'bleedTop','bleedBottom','bleedLeft','bleedRight',
                            'finishing','renderTime'
                        ] as $key
                    ) {
                        if (array_key_exists($key, $row)) {
                            $item->{$key} = $row[$key];
                        }
                    }

                    if (array_key_exists('material', $row)) {
                        if (is_array($row['material'])) {
                            $clean = array_values(array_filter(array_map('trim', $row['material']), fn($v) => $v !== ''));
                            $item->material = $clean ?: null;
                        } else {
                            $clean = array_values(array_filter(array_map('trim', explode(',', (string)$row['material'])), fn($v) => $v !== ''));
                            $item->material = $clean ?: null;
                        }
                    }

                    $item->save();
                    $keepItemIds[] = $item->ItemID;

                    // Optional: one-to-one specification row for lamination/printer/cutter
                    if (
                        array_key_exists('lamination', $row) ||
                        array_key_exists('printer', $row) ||
                        array_key_exists('cutter', $row)
                    ) {

                        $spec = Specification::firstOrNew(['ItemID' => $item->ItemID]);
                        if (array_key_exists('lamination', $row)) $spec->lamination = $row['lamination'];
                        if (array_key_exists('printer',   $row)) $spec->printer    = $row['printer'];
                        if (array_key_exists('cutter',    $row)) $spec->cutter     = $row['cutter'];
                        $spec->save();
                    }
                }

                // delete items removed in UI (optional — comment to keep old ones)
                if (count($keepItemIds)) {
                    ProductItem::where('ProductID', $product->ProductID)
                        ->whereNotIn('ItemID', $keepItemIds)
                        ->delete();
                }

                // ---------------- 5) Upsert Delivery Breakdowns ------------------------------
                $postedBreakdowns = collect($request->input('breakdowns', []))
                    ->filter(fn($row) => is_array($row));

                $keepBreakIds = [];
                foreach ($postedBreakdowns as $row) {
                    $isEmpty = collect($row)->filter(fn($v, $k) => $k !== 'id' && $v !== null && $v !== '')->isEmpty();
                    if ($isEmpty) continue;

                    $bd = null;
                    if (!empty($row['id'])) {
                        $bd = DeliveryBreakdown::where('BreakdownID', (int)$row['id'])
                            ->where('ProductID', $product->ProductID)
                            ->first();
                    }
                    if (!$bd) {
                        $bd = new DeliveryBreakdown();
                        $bd->ProductID = $product->ProductID;
                    }

                    foreach (['method', 'quantity', 'date', 'time', 'location'] as $key) {
                        if (array_key_exists($key, $row)) {
                            $bd->{$key} = $row[$key];
                        }
                    }
                    $bd->save();
                    $keepBreakIds[] = $bd->BreakdownID;
                }

                if (count($keepBreakIds)) {
                    DeliveryBreakdown::where('ProductID', $product->ProductID)
                        ->whereNotIn('BreakdownID', $keepBreakIds)
                        ->delete();
                }
            });

            return back()->with('success', $request->input('is_draft') === '1' ? 'Draft saved.' : 'Order updated.');
        } catch (\Throwable $e) {
            Log::error('Artist update failed', ['order_id' => $order->id, 'err' => $e]);
            return back()->with('error', 'Failed to save. Please try again.');
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
            ->reject(fn($p) => trim($p) === $path)
            ->values()->all();
        $this->putOrderAttachments($order, $list);

        return response()->json(['ok' => true]);
    }

    public function destroyItem(Request $request, Order $order, ProductItem $item)
    {
        // Permission: artist must be assigned OR head-artist
        $user = Auth::user();
        if (!$this->isHeadArtist($user) && $order->artist_id !== $user->id) {
            return response()->json(['ok' => false, 'msg' => 'Forbidden'], 403);
        }

        // Safety: ensure the item belongs to this order
        $item->loadMissing('product'); // product relation must exist on ProductItem
        if (!$item->product || $item->product->OrderID !== $order->id) {
            return response()->json(['ok' => false, 'msg' => 'Not found'], 404);
        }

        // Delete the item; FK ON DELETE CASCADE will remove the specification row
        $item->delete();

        return response()->json(['ok' => true]);
    }
}
