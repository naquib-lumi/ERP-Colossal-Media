<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Meeting;
use App\Models\Material;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;  
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\DeliveryBreakdown;
use App\Models\Specification;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Arr;

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
        $product   = Product::with('items.spec','breakdowns')
                    ->where('OrderID', $order->id)->first();

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

        $materials = Material::orderBy('materialName')->get(['materialName']);

        $allMaterials = Material::orderBy('materialName')->pluck('materialName')->values()->all();

        $attachments = $this->getOrderAttachments($order);

        return view('artist.orders.edit', compact(
            'order','orderCode','today','attachments','product','items', 'materials', 'allMaterials'
        ));

        return view('artist.orders.edit', [
            'materials' => $materials,
        ]);
    }

    public function update(Request $request, Order $order)
    {
        // AuthZ
        $user = Auth::user();
        if (!$this->isHeadArtist($user) && $order->artist_id !== $user->id) {
            abort(403);
        }

        // -------------------- VALIDATION --------------------
        // NOTE: Use "breakdowns.*" everywhere (you POST/handle "breakdowns", not "deliveries")
        $rules = [
            'design_confirmed'  => ['required', 'boolean'],
            'is_draft'          => ['required', 'in:0,1'],

            // Product (optional)
            'product.id'        => ['nullable', 'integer'],
            'product.name'      => ['nullable', 'string', 'max:255'],
            'product.qty_total' => ['nullable', 'integer', 'min:0'],
            'product.material'  => ['nullable', 'string', 'max:255'],
            'product.remarks'   => ['nullable', 'string'],

            // Items
            'items'                    => ['array'],
            'items.*.id'               => ['nullable', 'integer'],
            'items.*.itemName'         => ['nullable', 'string', 'max:255'],
            'items.*.quantity'         => ['nullable', 'integer', 'min:0'],

            'items.*.sizeWidth'        => ['nullable', 'numeric'],
            'items.*.sizeHeight'       => ['nullable', 'numeric'],
            'items.*.sizeLength'       => ['nullable', 'numeric'],

            'items.*.bleedTop'         => ['nullable','numeric'],
            'items.*.bleedBottom'      => ['nullable','numeric'],
            'items.*.bleedLeft'        => ['nullable','numeric'],
            'items.*.bleedRight'       => ['nullable','numeric'],

            'items.*.finishing'        => ['nullable', 'string', 'max:255'],
            'items.*.renderTime'       => ['nullable', 'integer', 'min:0'],

            'items.*.material'         => ['nullable'],
            'items.*.material.*'       => ['nullable', 'string', 'max:255'],

            // spec (optional)
            'items.*.lamination'       => ['nullable', 'string', 'max:255'],
            'items.*.printer'          => ['nullable', 'string', 'max:255'],
            'items.*.cutter'           => ['nullable', 'string', 'max:255'],

            // ✅ Delivery breakdowns — use the key you actually post: "breakdowns"
            'breakdowns'                 => ['array'],
            'breakdowns.*.id'            => ['nullable','integer'],
            'breakdowns.*.method'        => ['nullable','string','max:255'],
            'breakdowns.*.location'      => ['nullable','string','max:255'],
            'breakdowns.*.quantity'      => ['nullable','numeric','min:0'],
            'breakdowns.*.date'          => ['nullable','date'],
            'breakdowns.*.time'          => ['nullable','date_format:H:i'],

            // Attachments
            'attachments.*'            => ['file','mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,ppt,pptx','max:20480'],
            'delete_attachments'       => ['array'],
            'delete_attachments.*'     => ['string'],
        ];

        // We need a custom “sum ≤ total” check, so build a validator:
        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($v) use ($request, $order) {
            // Read intended total from the form (product.qty_total) with fallback
            $formTotal = (int) data_get($request->input('product', []), 'qty_total', 0);

            // If not posted (readonly), fallback to current product row:
            $prodRow = Product::where('OrderID', $order->id)->first();
            $productTotalQty = $formTotal > 0
                ? $formTotal
                : (int) ($prodRow->totalQuantity ?? 0);

            $sumBreakdowns = collect($request->input('breakdowns', []))
                ->sum(fn($r) => (int) ($r['quantity'] ?? 0));

            if ($sumBreakdowns > $productTotalQty) {
                $v->errors()->add('breakdowns', "Delivery quantities ($sumBreakdowns) exceed Total Quantity ($productTotalQty).");
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        try {
            DB::transaction(function () use ($request, $order) {

                // 1) Order core
                $order->leadName    = $request->input('leadName',    $order->leadName);
                $order->leadPhone   = $request->input('leadPhone',   $order->leadPhone);
                $order->companyName = $request->input('companyName', $order->companyName);
                $order->deadline    = $request->input('deadline',    $order->deadline);
                $order->leadEmail   = $request->input('leadEmail',   $order->leadEmail);
                $order->orderTitle  = $request->input('orderTitle',  $order->orderTitle);
                $order->orderDetail = $request->input('orderDetail', $order->orderDetail);

                $order->draft       = (int) $request->input('is_draft', 0);
                $order->orderStatus = 'in_progress';
                $order->approval    = $request->boolean('design_confirmed');

                // 2) Attachments
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

                // 3) Upsert product (reuse existing product row)
                $product = Product::where('OrderID', $order->id)->first();

                $p = $request->input('product', []);
                $hasProduct    = isset($p['name']) || isset($p['qty_total']) || isset($p['material']) || isset($p['remarks']);
                $hasItems      = filled($request->input('items', []));
                $hasBreakdowns = filled($request->input('breakdowns', []));

                if (!$product && ($hasProduct || $hasItems || $hasBreakdowns)) {
                    $product = new Product();
                    $product->OrderID = $order->id;
                }

                if ($product) {
                    if (array_key_exists('name', $p))       $product->productName    = $p['name'];
                    if (array_key_exists('qty_total', $p))  $product->totalQuantity  = $p['qty_total'];
                    if (array_key_exists('material', $p))   $product->materialRemark = $p['material'];
                    if (array_key_exists('remarks', $p))    $product->productRemark  = $p['remarks'];
                    $product->save();
                }

                if (!$product) return;

                // 4) Upsert items (+spec)
                $postedItems = collect($request->input('items', []))
                    ->filter(fn($row) => is_array($row));

                $keepItemIds = [];
                foreach ($postedItems as $row) {
                    // ignore fully empty rows
                    $isEmpty = collect($row)->filter(fn($v, $k) => $k !== 'id' && $v !== null && $v !== '')->isEmpty();
                    if ($isEmpty) continue;

                    // locate or create item
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

                    // simple columns
                    foreach ([
                        'itemName','quantity',
                        'sizeWidth','sizeHeight','sizeLength',
                        'bleedTop','bleedBottom','bleedLeft','bleedRight',
                        'finishing','renderTime'
                    ] as $key) {
                        if (array_key_exists($key, $row)) {
                            $item->{$key} = $row[$key] === '' ? null : $row[$key];
                        }
                    }

                    // ✅ MATERIALS (multi-select or string)
                    if (array_key_exists('material', $row)) {
                        if (is_array($row['material'])) {
                            $clean = array_values(array_filter(array_map('trim', $row['material']), fn($v) => $v !== ''));
                            $item->material = $clean ?: null;
                        } else {
                            $clean = array_values(array_filter(array_map('trim', explode(',', (string) $row['material'])), fn($v) => $v !== ''));
                            $item->material = $clean ?: null;
                        }
                    }

                    $item->save();
                    $keepItemIds[] = $item->ItemID;

                    // One-to-one spec (optional)
                    if (
                        array_key_exists('lamination', $row) ||
                        array_key_exists('printer',   $row) ||
                        array_key_exists('cutter',    $row)
                    ) {
                        $spec = Specification::firstOrNew(['ItemID' => $item->ItemID]);
                        if (array_key_exists('lamination', $row)) $spec->lamination = $row['lamination'] ?: null;
                        if (array_key_exists('printer',   $row)) $spec->printer    = $row['printer'] ?: null;
                        if (array_key_exists('cutter',    $row)) $spec->cutter     = $row['cutter'] ?: null;
                        $spec->save();
                    }
                }

                // delete removed items (only when some were posted)
                if (count($keepItemIds)) {
                    ProductItem::where('ProductID', $product->ProductID)
                        ->whereNotIn('ItemID', $keepItemIds)
                        ->delete();
                }

                // 5) Upsert delivery breakdowns
                $postedBreakdowns = collect($request->input('breakdowns', []))
                    ->filter(fn($row) => is_array($row));

                $sumBreakdowns = (int) $postedBreakdowns->sum(fn($r) => (int)($r['quantity'] ?? 0));
                $totalAllowed  = (int) ($product->totalQuantity ?? 0);
                if ($sumBreakdowns > $totalAllowed) {
                    // Throwing here makes the whole txn roll back
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'breakdowns' => ["Delivery quantities ($sumBreakdowns) exceed Total Quantity ($totalAllowed)."]
                    ]);
                }

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
                            $bd->{$key} = $row[$key] === '' ? null : $row[$key];
                        }
                    }
                    $bd->save();
                    $keepBreakIds[] = $bd->BreakdownID;
                }

                if (count($keepBreakIds)) {
                    DeliveryBreakdown::where('ProductID', $product->ProductID)
                        ->whereNotIn('BreakdownID', $keepBreakIds)
                        ->delete();
                } else {
                    // if no rows posted at all, you can choose to delete all or keep old
                    // DeliveryBreakdown::where('ProductID', $product->ProductID)->delete();
                }
            });

            // -- DELIVERY BREAKDOWNS -------------------------------------------------
            $productTotalQty = (int) ($request->input('qty_total') ?? $order->TotalQuantity ?? 0);
            $deliveries = $this->extractDeliveries($request);
            [$deliveries, $sum] = $this->validateDeliveries($deliveries, $productTotalQty);
            
            DB::table('delivery_breakdowns')->where('ProductID', $order->ProductID ?? $order->id)->delete();

            if (!empty($deliveries)) {
                $now = now();
                $rows = collect($deliveries)->map(function ($r) use ($order, $now) {
                    return [
                        'ProductID'  => $order->ProductID ?? $order->id, // <- FK to your order/product
                        'method'     => $r['method'],
                        'location'   => $r['location'],
                        'quantity'   => $r['quantity'],
                        'date'       => $r['date'],  // DATE column
                        'time'       => $r['time'],  // TIME column
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->all();

                DB::table('delivery_breakdowns')->insert($rows);
            }

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

    /**
     * Normalize delivery rows from the request into a clean array.
     * Accepts either a single datetime string or separate date/time.
     */
    private function extractDeliveries(\Illuminate\Http\Request $request): array
    {
        // Expecting names like: deliveries[0][method], deliveries[0][location], deliveries[0][quantity], deliveries[0][datetime]
        // or deliveries[0][date] + deliveries[0][time]
        $rows = $request->input('deliveries', []);
        if (!is_array($rows)) return [];

        // Keep only non-empty rows (at least quantity or method present)
        return collect($rows)->map(function ($row) {
            $row = is_array($row) ? $row : [];

            // handle datetime in either format
            $date = trim((string)Arr::get($row, 'date', ''));
            $time = trim((string)Arr::get($row, 'time', ''));
            $dt   = trim((string)Arr::get($row, 'datetime', '')); // in case your input is a single control

            if ($dt !== '') {
                try {
                    $c = Carbon::parse($dt);
                    $date = $c->toDateString();
                    $time = $c->format('H:i:s');
                } catch (\Throwable $e) {}
            }

            return [
                'method'   => trim((string)Arr::get($row, 'method', '')),
                'location' => trim((string)Arr::get($row, 'location', '')),
                'quantity' => (int)Arr::get($row, 'quantity', 0),
                'date'     => $date ?: null,
                'time'     => $time ?: null,
            ];
        })
        // filter out rows that have nothing at all
        ->filter(fn ($r) => $r['method'] !== '' || $r['location'] !== '' || $r['quantity'] > 0)
        ->values()
        ->all();
    }

    /**
     * Validate deliveries and enforce the total ≤ product quantity rule.
     * Returns an array [deliveries, totalQty] or throws \Illuminate\Validation\ValidationException.
     */
    private function validateDeliveries(array $deliveries, int $maxQty): array
    {
        // Per-row validation
        $v = Validator::make(['deliveries' => $deliveries], [
            'deliveries'               => ['array'],
            'deliveries.*.method'      => ['required','string','max:255'],
            'deliveries.*.location'    => ['nullable','string','max:255'],
            'deliveries.*.quantity'    => ['required','integer','min:1'],
            'deliveries.*.date'        => ['nullable','date'],
            'deliveries.*.time'        => ['nullable'],
        ]);

        $v->after(function ($v) use ($deliveries, $maxQty) {
            $sum = collect($deliveries)->sum('quantity');
            if ($sum > $maxQty) {
                $v->errors()->add('deliveries', 'Total delivery quantity ('.$sum.') cannot exceed product total ('.$maxQty.').');
            }
        });

        $v->validate();

        return [$deliveries, collect($deliveries)->sum('quantity')];
    }
}
