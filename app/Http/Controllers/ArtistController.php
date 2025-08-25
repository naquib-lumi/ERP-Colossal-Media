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
use App\Models\LeadAttachment;
use App\Models\DeliveryBreakdown;
use App\Models\Specification;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class ArtistController extends Controller
{
    public function dashboard(Request $request)
    {
        $user  = Auth::user();
        $isHead  = $this->isHeadArtist($user);
        $base  = $this->visibleOrders();

        $raw = strtolower(preg_replace('/[^a-z]/', '', (string) $request->query('status', '')));

        $map = [
            'toassign'   => 'to_assign',
            'assigned'   => 'assigned',
            'inprogress' => 'in_progress',
            'completed'  => 'completed',
            'rejected'   => 'rejected',
        ];

        $query = clone $base;

        if ($raw !== '') {
            if ($raw === 'pending') {
                $query->where('orderStatus', 'assigned')
                    ->where('pending', 1);

                if ($isHead) {
                    $query->whereRaw('1=0');
                }
            } else {
                $db = $map[$raw] ?? $raw; 
                $query->where('orderStatus', $db);

                if (!$isHead && $db === 'in_progress') {
                    $query->where('artist_id', $user->id)
                        ->where('pending', 0);
                }
            }
        }

        if ($s = trim($request->query('q', ''))) {
            $query->where(function ($q) use ($s) {
                $q->where('orderTitle', 'like', "%{$s}%")
                ->orWhere('companyName', 'like', "%{$s}%")
                ->orWhere('leadName', 'like', "%{$s}%");
            });
        }

        // 4) Metrics from the same base visibility
        $metrics = [
            'total'       => (clone $base)->count(),
            'pending'     => (clone $base)->where('orderStatus', 'assigned')->where('pending', 1)->count(),
            'in_progress' => (clone $base)->where('orderStatus', 'in_progress')->count(),
            'completed'   => (clone $base)->where('orderStatus', 'completed')->count(),
            'rejected'    => (clone $base)->where('orderStatus', 'rejected')->count(),
        ];
        if ($isHead) {
            $metrics['to_assign'] = (clone $base)->where('orderStatus', 'to_assign')->count();
            $metrics['assigned']  = (clone $base)->where('orderStatus', 'assigned')->count();
        }

        // 5) Rows
        $orders = $query->with(['artist:id,name', 'salesperson:id,name'])
                        ->latest('orderDate')
                        ->paginate(20)
                        ->withQueryString();

        // Pass the *raw UI token* back so the dropdown can mark "selected"
        $statusRaw = $raw;

        if ($request->ajax()) {
            return view('artist.partials.orders-table', compact('orders', 'isHead'))->render();
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
            'isHead'        => $isHead,     
            'statusRaw'     => $statusRaw,  
            'salespersons'  => $salespersons,
            'initialCounts' => [
                'scheduled' => (int) ($initial->scheduled ?? 0),
                'canceled'  => (int) ($initial->canceled  ?? 0),
            ],
        ]);

        return view('artist.dashboard', compact('orders', 'metrics', 'isHead', 'statusRaw'));
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

    public function orders(Request $request)
    {
        $user    = Auth::user();
        $isHead  = $this->isHeadArtist($user);

        $base = $this->visibleOrders(); 

        $raw = strtolower(preg_replace('/[^a-z]/', '', (string) $request->query('status', '')));

        $map = [
            'toassign'   => 'to_assign',
            'assigned'   => 'assigned',
            'inprogress' => 'in_progress',
            'completed'  => 'completed',
            'rejected'   => 'rejected',
        ];

        $query = clone $base;

        if ($raw !== '') {
            if ($raw === 'pending') {
                $query->where('orderStatus', 'assigned')
                    ->where('pending', 1);

                if ($isHead) {
                    $query->whereRaw('1=0');
                }
            } else {
                $db = $map[$raw] ?? $raw; 
                $query->where('orderStatus', $db);

                if (!$isHead && $db === 'in_progress') {
                    $query->where('artist_id', $user->id)
                        ->where('pending', 0);
                }
            }
        }

        if ($s = trim($request->query('q', ''))) {
            $query->where(function ($q) use ($s) {
                $q->where('orderTitle', 'like', "%{$s}%")
                ->orWhere('companyName', 'like', "%{$s}%")
                ->orWhere('leadName', 'like', "%{$s}%");
            });
        }

        // 4) Metrics from the same base visibility
        $metrics = [
            'total'       => (clone $base)->count(),
            'pending'     => (clone $base)->where('orderStatus', 'assigned')->where('pending', 1)->count(),
            'in_progress' => (clone $base)->where('orderStatus', 'in_progress')->count(),
            'completed'   => (clone $base)->where('orderStatus', 'completed')->count(),
            'rejected'    => (clone $base)->where('orderStatus', 'rejected')->count(),
        ];
        if ($isHead) {
            $metrics['to_assign'] = (clone $base)->where('orderStatus', 'to_assign')->count();
            $metrics['assigned']  = (clone $base)->where('orderStatus', 'assigned')->count();
        }

        // 5) Rows
        $orders = $query->with(['artist:id,name', 'salesperson:id,name'])
                        ->latest('orderDate')
                        ->paginate(20)
                        ->withQueryString();

        // Pass the *raw UI token* back so the dropdown can mark "selected"
        $statusRaw = $raw;

        if ($request->ajax()) {
            return view('artist.partials.orders-table', compact('orders'))->render();
        }

        return view('artist.orders', compact('orders', 'metrics', 'isHead', 'statusRaw'));
    }

    public function showAssign(Order $order)
    {

        // Only head artists should be here (guard with middleware)
        $order->load([
            'salesperson:id,name',
            'artist:id,name',
            'products.deliveryBreakdowns',               // if you render product info
        ]);

        // Normal artists to assign to
        $artists = User::where('role', 'artist')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('artist.orders.assign', compact('order', 'artists'));
    }

    public function storeAssign(Request $request, Order $order)
    {
        $data = $request->validate([
            'artist_id' => ['required', 'exists:users,id'],
        ]);

        $order->artist_id   = $data['artist_id'];
        $order->orderStatus = 'assigned'; // becomes “Pending” for normal artists
        $order->pending     = 1;          // your business rule
        $order->save();

        return redirect()
        ->route('artist.orders.assign.show', $order)
        ->with('ok', 'Artist assigned successfully.');
    }

    public function show(Order $order)
    {
        // keep what you already load here (products, items, deliveryBreakdowns, etc.)
        $order->loadMissing([
            'salesperson:id,name',
            'artist:id,name',
            'products'        => fn ($q) => $q->orderBy('ProductID'),
            'products.items'  => fn ($q) => $q->orderBy('ItemID'),
            'products.items.spec',
            'leadAttachments',
            'deliveryBreakdowns' => fn ($q) => $q->orderBy('BreakdownID'),
        ]);

        // Attachments: prefer orderAttachment CSV; otherwise fallback to lead_attachments
        $attachments = $order->attachment_paths->isNotEmpty()
            ? $order->attachment_paths->map(fn ($path) => $this->fileInfoFromPath($path))
            : LeadAttachment::where('lead_id', $order->lead_id)
                ->latest()
                ->get()
                ->map(fn ($a) => [
                    'name' => basename($a->file_location),
                    'url'  => Storage::disk('public')->url($a->file_location),
                    'size' => (int) $a->file_size,
                    'ext'  => $a->file_extension,
                ]);

        return view('artist.orders.show', compact('order', 'attachments'));
    }

    private function fileInfoFromPath(string $relPath): array
    {
        // adjust disk if needed
        $url  = Storage::disk('public')->url($relPath);
        $ext  = pathinfo($relPath, PATHINFO_EXTENSION);

        return [
            'name' => basename($relPath),
            'url'  => $url,
            'size' => null, // unknown for order CSV; can be resolved if you want
            'ext'  => $ext,
        ];
    }

    /** Optional ajax search if you want Select2 remote search */
    public function searchArtists(Request $request)
    {
        $q = trim($request->query('q',''));
        $rows = User::where('role','artist')
            ->when($q !== '', fn($w)=>$w->where('name','like',"%{$q}%"))
            ->orderBy('name')
            ->limit(20)
            ->get(['id','name']);

        return response()->json(
            $rows->map(fn($u)=>['id'=>$u->id, 'text'=>$u->name])
        );
    }

    public function edit(Order $order)
    {
        $user = Auth::user();
        $isHead = $this->isHeadArtist($user);
        if (!$this->isHeadArtist($user) && $order->artist_id !== $user->id) abort(403);

        if (!$this->isHeadArtist(Auth::user())
            && $order->orderStatus === 'assigned'
            && (int) $order->pending === 1) {

            $order->orderStatus = 'in_progress';
            $order->pending     = 0;

            // make sure the order is owned by this artist from now on
            if (!$order->artist_id) {
                $order->artist_id = Auth::id();
            }

            $order->loadMissing([
                'products' => fn ($q) => $q->orderBy('ProductID'),
                'products.items' => fn ($q) => $q->orderBy('ItemID'), // relation on Product model
                'products.deliveryBreakdowns' => fn ($q) => $q->orderBy('BreakdownID'),
            ]);

            $order->save();
        }

        $orderCode = sprintf('ORD-%04d', $order->id);
        $today     = now()->format('M d, Y');

        $product = Product::with([
            'items.spec','deliveryBreakdowns' => function ($q) {
                // select only real columns – no "id" here
                $q->select('BreakdownID', 'ProductID', 'method', 'location', 'quantity', 'date', 'time')
                ->orderBy('BreakdownID');
            },
        ])->where('OrderID', $order->id)->first();

        $deliveries = $product
            ? DeliveryBreakdown::where('ProductID', $product->ProductID)
                ->orderBy('BreakdownID')
                ->get(['BreakdownID as id','method','location','quantity','date','time'])
            : collect();

        if ($product) {
        $deliveries = DeliveryBreakdown::where('ProductID', $product->ProductID)
            ->orderBy('BreakdownID')
            ->get([
                'BreakdownID as id',
                'method',
                'location',
                'quantity',
                'date',
                'time',
            ]);
        }

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
            'order','orderCode','today','attachments','product','items', 'materials', 'allMaterials', 'deliveries'
        ));

        return view('artist.orders.edit', [
            'order'       => $order,
            'materials' => $materials,
            'product'     => $product,
            'deliveries'  => $deliveries,
        ]);
    }

    public function update(Request $request, Order $order)
{
    // ----- AuthZ -----
    $user = Auth::user();
    if (!$this->isHeadArtist($user) && $order->artist_id !== $user->id) {
        abort(403);
    }

    // ----- Validation (matches Blade names) -----
    $rules = [
        'design_confirmed'  => ['required','boolean'],
        'is_draft'          => ['required','in:0,1'],

        // product header (your Blade uses singular "product[...]")
        'product.name'      => ['nullable','string','max:255'],
        'product.qty_total' => ['nullable','integer','min:0'],
        'product.material'  => ['nullable','string','max:255'],
        'product.remarks'   => ['nullable','string'],

        // nested products[*].items[*]
        'products'                               => ['array'],
        'products.*.items'                       => ['array'],
        'products.*.items.*.id'                  => ['nullable','integer'],
        'products.*.items.*.itemName'            => ['nullable','string','max:255'],
        'products.*.items.*.quantity'            => ['nullable','integer','min:0'],
        'products.*.items.*.sizeWidth'           => ['nullable','numeric'],
        'products.*.items.*.sizeHeight'          => ['nullable','numeric'],
        'products.*.items.*.sizeLength'          => ['nullable','numeric'],
        'products.*.items.*.bleedTop'            => ['nullable','numeric'],
        'products.*.items.*.bleedBottom'         => ['nullable','numeric'],
        'products.*.items.*.bleedLeft'           => ['nullable','numeric'],
        'products.*.items.*.bleedRight'          => ['nullable','numeric'],
        'products.*.items.*.finishing'           => ['nullable','string','max:255'],
        'products.*.items.*.renderTime'          => ['nullable','integer','min:0'],
        'products.*.items.*.material'            => ['nullable'],
        'products.*.items.*.material.*'          => ['nullable','string','max:255'],
        'products.*.items.*.lamination'          => ['nullable','string','max:255'],
        'products.*.items.*.printer'             => ['nullable','string','max:255'],
        'products.*.items.*.cutter'              => ['nullable','string','max:255'],

        // nested products[*].deliveries[*]
        'products.*.deliveries'                  => ['array'],
        'products.*.deliveries.*.id'             => ['nullable','integer'],
        'products.*.deliveries.*.method'         => ['nullable','string','max:255'],
        'products.*.deliveries.*.location'       => ['nullable','string','max:255'],
        'products.*.deliveries.*.quantity'       => ['nullable','numeric','min:0'],
        'products.*.deliveries.*.datetime'       => ['nullable','date'],
        'products.*.deliveries.*.date'           => ['nullable','date'],
        'products.*.deliveries.*.time'           => ['nullable','date_format:H:i'],

        // attachments
        'attachments.*'                          => ['file','mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,ppt,pptx','max:20480'],
        'delete_attachments'                     => ['array'],
        'delete_attachments.*'                   => ['string'],
    ];

    $validator = Validator::make($request->all(), $rules);

    // Sum checks (items & deliveries must not exceed product total)
    $validator->after(function($v) use ($request, $order) {
        $qtyTotal = (int) $request->input('product.qty_total', 0);
        if (!$qtyTotal) {
            $firstProduct = Product::where('OrderID', $order->id)->first();
            $qtyTotal = (int) ($firstProduct->totalQuantity ?? 0);
        }

        $sumItems = collect($request->input('products', []))
            ->flatMap(fn($p) => (array)($p['items'] ?? []))
            ->sum(fn($r) => (int)($r['quantity'] ?? 0));

        if ($sumItems > $qtyTotal) {
            $v->errors()->add('products.0.items', "Item quantities ($sumItems) exceed Total Quantity ($qtyTotal).");
        }

        $sumDeliveries = collect($request->input('products', []))
            ->flatMap(fn($p) => (array)($p['deliveries'] ?? []))
            ->sum(fn($r) => (int)($r['quantity'] ?? 0));

        if ($sumDeliveries > $qtyTotal) {
            $v->errors()->add('products.0.deliveries', "Delivery quantities ($sumDeliveries) exceed Total Quantity ($qtyTotal).");
        }
    });

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput();
    }

    try {
        DB::transaction(function() use ($request, $order) {

            // ----- 1) Order core -----
            $order->draft       = (int) $request->input('is_draft', 0);
            $order->approval    = $request->boolean('design_confirmed');
            $order->orderStatus = 'in_progress';
            $order->save();

            // ----- 2) Attachments -----
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

            // ----- 3) Update the first product’s header (your Blade posts singular "product[...]") -----
            $productHdr = $request->input('product', []);
            $product    = Product::where('OrderID', $order->id)->first() ?: new Product(['OrderID' => $order->id]);

            if (array_key_exists('name', $productHdr))      $product->productName    = $productHdr['name'];
            if (array_key_exists('qty_total', $productHdr)) $product->totalQuantity  = $productHdr['qty_total'];
            if (array_key_exists('material', $productHdr))  $product->materialRemark = $productHdr['material'];
            if (array_key_exists('remarks', $productHdr))   $product->productRemark  = $productHdr['remarks'];
            $product->OrderID = $order->id;
            $product->save();

            // ----- 4) Upsert items + specifications, and 5) deliveries -----
            $postedProducts = collect($request->input('products', []))->values();

            // Map each posted pIndex to the actual product row we edit.
            // If you truly have multiple editable products, pass product_id in the form and use it here.
            $targetProduct = $product; // current implementation edits the first/only product

            // Items
            $keepItemIds = [];
            $postedProducts->flatMap(fn($p) => (array)($p['items'] ?? []))
                ->filter(fn($row) => is_array($row))
                ->each(function($row) use (&$keepItemIds, $targetProduct) {

                    // skip empty rows
                    $isEmpty = collect($row)->except(['id','material'])->filter(fn($v) => $v !== null && $v !== '')->isEmpty();
                    if ($isEmpty) return;

                    // locate/create
                    $item = null;
                    if (!empty($row['id'])) {
                        $item = ProductItem::where('ItemID', (int)$row['id'])
                            ->where('ProductID', $targetProduct->ProductID)->first();
                    }
                    if (!$item) {
                        $item = new ProductItem();
                        $item->ProductID = $targetProduct->ProductID;
                    }

                    foreach ([
                        'itemName','quantity',
                        'sizeWidth','sizeHeight','sizeLength',
                        'bleedTop','bleedBottom','bleedLeft','bleedRight',
                        'finishing','renderTime'
                    ] as $k) {
                        if (array_key_exists($k, $row)) {
                            $item->{$k} = ($row[$k] === '') ? null : $row[$k];
                        }
                    }

                    // materials (array or CSV)
                    if (array_key_exists('material', $row)) {
                        $vals = is_array($row['material'])
                            ? $row['material']
                            : array_map('trim', explode(',', (string)$row['material']));
                        $vals = array_values(array_filter($vals, fn($v) => $v !== ''));
                        $item->material = $vals ?: null;
                    }

                    $item->save();
                    $keepItemIds[] = $item->ItemID;

                    // spec
                    if (array_key_exists('lamination',$row) || array_key_exists('printer',$row) || array_key_exists('cutter',$row)) {
                        $spec = Specification::firstOrNew(['ItemID' => $item->ItemID]);
                        $spec->lamination = $row['lamination'] ?? null;
                        $spec->printer    = $row['printer']    ?? null;
                        $spec->cutter     = $row['cutter']     ?? null;
                        $spec->save();
                    }
                });

            // remove items that were deleted
            if (!empty($keepItemIds)) {
                ProductItem::where('ProductID', $targetProduct->ProductID)
                    ->whereNotIn('ItemID', $keepItemIds)
                    ->delete();
            }

            // Deliveries
            $deliveries = $postedProducts->flatMap(fn($p) => (array)($p['deliveries'] ?? []))
                ->filter(fn($row) => is_array($row))
                ->map(function($row) {
                    $row = array_change_key_case($row, CASE_LOWER);
                    $date = $row['date'] ?? null;
                    $time = $row['time'] ?? null;

                    if ((!$date || !$time) && !empty($row['datetime'])) {
                        try {
                            $dt   = \Carbon\Carbon::parse($row['datetime']);
                            $date = $date ?: $dt->toDateString();
                            $time = $time ?: $dt->format('H:i:s');
                        } catch (\Throwable $e) {}
                    }

                    $method   = trim($row['method']   ?? '');
                    $location = trim($row['location'] ?? '');

                    // skip empty
                    if ($method === '' && $location === '' && ($row['quantity'] ?? null) === null && !$date && !$time) {
                        return null;
                    }

                    return [
                        'id'       => isset($row['id']) ? (int)$row['id'] : null,
                        'method'   => $method ?: null,
                        'location' => $location ?: null,
                        'quantity' => (int)($row['quantity'] ?? 0),
                        'date'     => $date ?: null,
                        'time'     => $time ?: null,
                    ];
                })
                ->filter();

            // business rule: sum ≤ product total
            $qtyTotal = (int)($request->input('product.qty_total') ?? $targetProduct->totalQuantity ?? 0);
            $sumQty   = $deliveries->sum('quantity');
            if ($sumQty > $qtyTotal) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'products.0.deliveries' => ["Delivery quantities ($sumQty) exceed Total Quantity ($qtyTotal)."],
                ]);
            }

            $keepDeliveryIds = [];
            foreach ($deliveries as $d) {
                $bd = null;
                if (!empty($d['id'])) {
                    $bd = DeliveryBreakdown::where('BreakdownID', $d['id'])
                        ->where('ProductID', $targetProduct->ProductID)->first();
                }
                if (!$bd) {
                    $bd = new DeliveryBreakdown();
                    $bd->ProductID = $targetProduct->ProductID;
                }
                $bd->method   = $d['method'];
                $bd->location = $d['location'];
                $bd->quantity = $d['quantity'];
                $bd->date     = $d['date'];
                $bd->time     = $d['time'];
                $bd->save();

                $keepDeliveryIds[] = $bd->BreakdownID;
            }

            // (optional) delete removed deliveries:
            if (!empty($keepDeliveryIds)) {
                DeliveryBreakdown::where('ProductID', $targetProduct->ProductID)
                    ->whereNotIn('BreakdownID', $keepDeliveryIds)
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
            ->whereNotIn('orderStatus', ['to_assign']);
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
    private function extractDeliveries(Request $request): array
    {
        $posted = $request->input('deliveries', []);
        if (!is_array($posted)) return [];

        $rows = [];
        foreach ($posted as $row) {
            if (!is_array($row)) continue;

            // normalize keys
            $row = array_change_key_case($row, CASE_LOWER);

            $method   = trim((string)($row['method']   ?? ''));
            $location = trim((string)($row['location'] ?? ''));
            $qty      = $row['quantity'] ?? null;
            $date     = $row['date']     ?? null;
            $time     = $row['time']     ?? null;
            $id       = isset($row['id']) ? (int)$row['id'] : null;

            // Support a single datetime-local input
            // e.g. "2025-08-19T13:55"
            if ((!$date || !$time) && !empty($row['datetime'])) {
                try {
                    $dt   = \Carbon\Carbon::parse($row['datetime']);
                    $date = $date ?: $dt->toDateString();   // "YYYY-MM-DD"
                    $time = $time ?: $dt->format('H:i');    // "HH:mm"
                } catch (\Throwable $e) {
                    // leave as null; optional fields anyway
                }
            }

            // empty-card guard (everything blank)
            $isEmpty = ($method === '' && $location === '' && ($qty === null || $qty === '') && !$date && !$time);
            if ($isEmpty) continue;

            $rows[] = [
                'id'       => $id,
                'method'   => $method ?: null,
                'location' => $location ?: null,
                'quantity' => is_numeric($qty) ? (int)$qty : 0,
                'date'     => $date ?: null,
                'time'     => $time ?: null,
            ];
        }

        return $rows;
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

    public function deleteDelivery(Request $request, Order $order, int $delivery)
    {
        $user = Auth::user();
        if (!$this->isHeadArtist($user) && $order->artist_id !== $user->id) {
            return response()->json(['ok' => false, 'message' => 'Forbidden'], 403);
        }

        // Find product for this order
        $product = Product::where('OrderID', $order->id)->first();
        if (!$product) {
            return response()->json(['ok' => false, 'message' => 'No product for order'], 404);
        }

        // Only delete rows that belong to this product
        $row = DeliveryBreakdown::where('BreakdownID', $delivery)
            ->where('ProductID', $product->ProductID)
            ->first();

        if (!$row) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        $row->delete();
        return response()->json(['ok' => true]);
    }
}
