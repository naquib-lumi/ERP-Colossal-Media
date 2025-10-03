<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Meeting;
use App\Models\Material;
use App\Models\ProductRemark;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class DataEntryController extends Controller
{
    public function orders(Request $request)
    {
        $user = auth()->user();

        $base = Order::query()
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 0);
            })
            ->when($user && $user->role !== 'boss', fn($q) => $q->where('data_entry_id', $user->id));

        $query = clone $base;

        if ($s = trim($request->query('q', ''))) {
            $query->where(function ($q) use ($s) {
                $q->where('orderTitle',  'like', "%{$s}%")
                    ->orWhere('companyName', 'like', "%{$s}%")
                    ->orWhere('leadName',   'like', "%{$s}%");
            });
        }

        $query->select('orders.*')->addSelect([
            'ui_status' => DB::raw("
                CASE
                    WHEN data_entry_id IS NOT NULL AND COALESCE(pending,0)=1 THEN 'pending'
                    WHEN data_entry_id IS NOT NULL AND COALESCE(draft,0)  =1 THEN 'in_progress'
                    WHEN COALESCE(submit,0)=1 AND COALESCE(pending,0)=0    THEN 'completed'
                    ELSE '-'
                END
            "),
        ]);

        $orders = $query->with(['artist:id,name', 'salesperson:id,name'])
            ->orderByDesc('orderDate')
            ->paginate(10000)
            ->withQueryString();

        $orders->getCollection()->transform(function ($o) {
            if (!isset($o->ui_status) || $o->ui_status === null || $o->ui_status === '') {
                if ($o->data_entry_id && (int)$o->pending === 1) {
                    $o->ui_status = 'pending';
                } elseif ($o->data_entry_id && (int)$o->draft === 1) {
                    $o->ui_status = 'in_progress';
                } elseif ((int)$o->submit === 1 && (int)$o->pending === 0) {
                    $o->ui_status = 'completed';
                } else {
                    $o->ui_status = '-';
                }
            }
            return $o;
        });

        // Metrics that also ignore orderStatus (optional cards)
        $statsBase = (clone $base);
        $metrics = [
            'total'       => (clone $statsBase)->count(),
            'pending'     => (clone $statsBase)->where('submit', 1)->where('pending', 1)->count(),
            'in_progress' => (clone $statsBase)->whereNotNull('data_entry_id')->where('draft', 1)->count(),
            // If you want a “completed” meaning without orderStatus:
            'completed'   => (clone $statsBase)->where('submit', 1)->where('pending', 0)->where('draft', 0)->count(),
            'rejected'    => 0, // no flag defined; ignoring orderStatus as requested
            'to_assign'   => 0,
            'assigned'    => 0,
        ];

        $statusRaw = ''; // force “All statuses”

        if ($request->ajax()) {
            return view('data-entry.partials.orders-table', ['orders' => $orders])->render();
        }

        return view('data-entry.orders', compact('orders', 'metrics', 'statusRaw'));
    }

    public function show(Order $order)
    {
        $user = auth()->user();
        if ($user->role !== 'boss' && $order->data_entry_id !== $user->id) {
            abort(403);
        }

        $order->load(['artist:id,name', 'salesperson:id,name']);

        return view('data-entry.order.show', compact('order'));
    }

    public function edit(Order $order)
    {
        $user = Auth::user();

        if ($user->role !== 'boss' && $order->data_entry_id && $order->data_entry_id !== $user->id) {
            abort(403);
        }

        // Only move to "in progress" if it's not submitted AND not already draft
        if ((int)$order->submit === 0 && (int)$order->draft !== 1) {
            $update = [
                'draft'   => 1,
                'submit'  => 0,
                'pending' => 0,
                'orderStatus' => 'in_progress',
            ];
            if (empty($order->data_entry_id)) {
                $update['data_entry_id'] = $user->id;
            }
            Order::whereKey($order->getKey())->update($update);
            $order->refresh();
        }

        // Header bits
        $orderCode = sprintf('ORD-%04d', $order->id);
        $today     = now()->format('M d, Y');

        // Product + related
        $product = Product::with([
            'items.spec',
            'deliveryBreakdowns' => function ($q) {
                $q->select(
                    'BreakdownID',
                    'ProductID',
                    'method',
                    'deliver_install_type',
                    'outsource_cost',
                    'location',
                    'quantity',
                    'date',
                    'time'
                )->orderBy('BreakdownID');
            },
            'remarks',
        ])->where('OrderID', $order->id)->first();

        $deliveries = $product
            ? DeliveryBreakdown::where('ProductID', $product->ProductID)
            ->orderBy('BreakdownID')
            ->get([
                'BreakdownID as id',
                'method',
                'location',
                'quantity',
                'date',
                'time',
            ])
            : collect();

        // Items (with spec join)
        $items = DB::table('product_items as pi')
            ->join('products as p', 'p.ProductID', '=', 'pi.ProductID')
            ->leftJoin('specifications as s', 's.ItemID', '=', 'pi.ItemID')
            ->where('p.OrderID', $order->id)
            ->orderBy('pi.ItemID')
            ->selectRaw('
            pi.ItemID, pi.ProductID, pi.itemName, pi.quantity,
            pi.sizeWidth, pi.sizeHeight, pi.sizeUnit,
            pi.bleedTop, pi.bleedBottom, pi.bleedLeft, pi.bleedRight,
            pi.finishing, pi.material, pi.prime_centre,
            s.lamination, s.printer, s.cutter
        ')
            ->get()
            ->values();

        // Make material human-friendly when stored as JSON
        $items = $items->map(function ($row) {
            if (is_string($row->material) && str_starts_with($row->material, '[')) {
                $decoded = json_decode($row->material, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $row->material = implode(', ', $decoded);
                }
            }
            return $row;
        });

        // Materials lookups
        $materials     = Material::orderBy('materialName')->get(['materialName']);
        $allMaterials  = Material::orderBy('materialName')->pluck('materialName')->values()->all();

        // Attachments helpers
        $toPublicUrl = function (string $p): string {
            $p = ltrim($p, '/');
            if (\Illuminate\Support\Str::startsWith($p, 'storage/')) {
                return url($p);
            }
            return Storage::disk('public')->url($p);
        };

        // 1) Lead attachments (read-only)
        $leadAttachments = LeadAttachment::where('lead_id', $order->lead_id)
            ->orderBy('id')
            ->get()
            ->map(function ($row) use ($toPublicUrl) {
                $p = ltrim((string) $row->file_location, '/');
                $p = preg_replace('#^public/#', '', $p);
                $p = preg_replace('#^storage/#', '', $p);
                $web = 'storage/' . $p;

                return (object) [
                    'name' => basename($p),
                    'size' => (int) $row->file_size,
                    'ext'  => $row->file_extension,
                    'url'  => $toPublicUrl($web),
                ];
            });

        // 2) Order attachments (uploaded for the order)
        $rawPaths = method_exists($this, 'getOrderAttachments')
            ? (array) $this->getOrderAttachments($order)
            : (array) json_decode((string) $order->orderAttachment, true);

        $orderFiles = collect($rawPaths)->filter()->map(function ($p) use ($toPublicUrl) {
            $p = ltrim((string) $p, '/');
            return [
                'name' => basename($p),
                'ext'  => pathinfo($p, PATHINFO_EXTENSION),
                'url'  => $toPublicUrl($p),
                'path' => \Illuminate\Support\Str::startsWith($p, 'storage/') ? $p : 'storage/' . $p,
            ];
        });

        return view('data-entry.order.edit', compact(
            'order',
            'orderCode',
            'today',
            'product',
            'items',
            'deliveries',
            'materials',
            'allMaterials',
            'leadAttachments',
            'orderFiles'
        ));
    }

    public function begin(Order $order)
    {
        $user = Auth::user();

        // same guard you use elsewhere
        if ($user->role !== 'boss' && $order->data_entry_id && $order->data_entry_id !== $user->id) {
            abort(403);
        }

        // Only flip if not submitted yet
        if ((int)$order->submit === 1) {
            $order->fill([
                'pending'       => 0,                 // <-- set Pending -> 0
                'draft'         => 1,                 // mark as in progress (editable)
                'submit'        => 0,
                'data_entry_id' => $order->data_entry_id ?: $user->id,
            ])->save();
        }

        return redirect()->route('data-entry.orders.edit', $order);
    }

    public function update(Request $request, Order $order, Product $product = null)
    {
        // ----- AuthZ for data-entry -----
        $user = Auth::user();
        if ($user->role === 'data-entry') {
            if (!empty($order->data_entry_id) && (int)$order->data_entry_id !== (int)$user->id) {
                abort(403);
            }
        }

        // ----- Validation -----
        $rules = [
            'design_confirmed'  => ['required', 'boolean'],
            'is_draft'          => ['required', 'in:0,1'],

            // header (product shown at the top)
            'product.name'      => ['nullable', 'string', 'max:255'],
            'product.qty_total' => ['nullable', 'integer', 'min:0'],
            'product.material'  => ['nullable', 'string', 'max:255'],

            // nested products[*]
            'products'                          => ['array'],
            'products.*.product_id'             => ['nullable', 'integer'],

            // items
            'products.*.items'                  => ['array'],
            'products.*.items.*.id'             => ['nullable', 'integer'],
            'products.*.items.*.itemName'       => ['nullable', 'string', 'max:255'],
            'products.*.items.*.quantity'       => ['nullable', 'integer', 'min:0'],
            'products.*.items.*.sizeWidth'      => ['nullable', 'numeric'],
            'products.*.items.*.sizeHeight'     => ['nullable', 'numeric'],
            'products.*.items.*.sizeUnit'       => ['nullable', 'in:mm,cm,inch,ft'],
            'products.*.items.*.bleedTop'       => ['nullable', 'numeric'],
            'products.*.items.*.bleedBottom'    => ['nullable', 'numeric'],
            'products.*.items.*.bleedLeft'      => ['nullable', 'numeric'],
            'products.*.items.*.bleedRight'     => ['nullable', 'numeric'],
            'products.*.items.*.bleedUnit'      => ['nullable', 'in:mm,cm,inch,ft'],
            'products.*.items.*.finishing'      => ['nullable', 'string', 'max:255'],
            'products.*.items.*.renderTime'     => ['nullable', 'integer', 'min:0'],
            'products.*.items.*.material'       => ['nullable'],
            'products.*.items.*.material.*'     => ['nullable', 'string', 'max:255'],
            'products.*.items.*.lamination'     => ['nullable', 'string', 'max:255'],
            'products.*.items.*.printer'        => ['nullable', 'string', 'max:255'],
            'products.*.items.*.cutter'         => ['nullable', 'string', 'max:255'],
            'products.*.items.*.prime_centre'   => ['nullable', 'in:0,1'],

            // deliveries
            'products.*.deliveries'             => ['array'],
            'products.*.deliveries.*.id'        => ['nullable', 'integer'],
            'products.*.deliveries.*.method'    => ['nullable', 'string', 'max:255'],
            'products.*.deliveries.*.location'  => ['nullable', 'string', 'max:255'],
            'products.*.deliveries.*.quantity'  => ['nullable', 'numeric', 'min:0'],
            'products.*.deliveries.*.datetime'  => ['nullable', 'date'],
            'products.*.deliveries.*.date'      => ['nullable', 'date'],
            'products.*.deliveries.*.time'      => ['nullable', 'date_format:H:i'],
            'products.*.deliveries.*.deliver_install_type' => ['nullable', 'string', 'max:255'],
            'products.*.deliveries.*.outsource_cost'       => ['nullable', 'numeric', 'min:0'],

            // remarks (per product)
            'products.*.remarks'                => ['array'],
            'products.*.remarks.*.id'           => ['nullable', 'integer'],
            'products.*.remarks.*.operation'    => [
                'nullable',
                Rule::in(['printing', 'furnishing', 'installation', 'courier', 'self_pickup']),
            ],
            'products.*.remarks.*.remark'       => ['nullable', 'string'],
            'products.*.delete_remarks'         => ['array'],
            'products.*.delete_remarks.*'       => ['integer'],

            // attachments
            'attachments.*'                     => ['file', 'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,ppt,pptx', 'max:20480'],
            'delete_attachments'                => ['array'],
            'delete_attachments.*'              => ['string'],
        ];

        // Normalizations (prime_centre, remarks spelling)
        $payload = $request->all();
        if (!empty($payload['products']) && is_array($payload['products'])) {
            foreach ($payload['products'] as &$pg) {
                if (!empty($pg['items']) && is_array($pg['items'])) {
                    foreach ($pg['items'] as &$it) {
                        if (array_key_exists('prime_centre', $it)) {
                            $raw = $it['prime_centre'];
                            if ($raw === '' || $raw === null || $raw === '-') {
                                $it['prime_centre'] = null;
                            } else {
                                $v = strtolower((string)$raw);
                                $it['prime_centre'] = in_array($v, ['1', 'true', 'on', 'yes'], true) ? '1' : '0';
                            }
                        }
                    }
                    unset($it);
                }
                if (!empty($pg['remarks']) && is_array($pg['remarks'])) {
                    foreach ($pg['remarks'] as &$rk) {
                        if (array_key_exists('operation', $rk)) {
                            $op = strtolower(trim((string)$rk['operation']));
                            if ($op === '' || $op === '-' || $op === '—') {
                                $rk['operation'] = null;
                            } elseif ($op === 'self pickup') {
                                $rk['operation'] = 'self_pickup';
                            } elseif ($op === 'delivery') {
                                $rk['operation'] = 'courier';
                            } else {
                                $rk['operation'] = $op;
                            }
                        }
                    }
                    unset($rk);
                }
            }
            unset($pg);
        }
        $request->merge($payload);

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::transaction(function () use ($request, $order, $product, $user) {

                // ----- 1) Order core (flags for data-entry) -----
                $isSubmit = (string)$request->input('submit', '0') === '1';

                $order->submit  = $isSubmit ? 1 : 0;
                $order->draft   = $isSubmit ? 0 : 1;
                $order->pending = 0;
                $order->approval = $request->boolean('design_confirmed');
                $order->save();

                return response()->json([
                    'ok'       => true,
                    'message'  => $isSubmit ? 'Order submitted successfully.' : 'Draft saved successfully.',
                    // after submit, go to a read-only page or back to edit (both work with the guard)
                    'redirect' => $isSubmit
                        ? route('data-entry.orders.show', $order)   // recommended
                        : route('data-entry.orders.edit', $order),
                ]);

                // ----- 2) Attachments -----
                $existing = method_exists($this, 'getOrderAttachments')
                    ? collect((array) $this->getOrderAttachments($order))
                    : collect((array) json_decode((string) $order->orderAttachment, true));

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

                // write back using helper if it exists, otherwise persist to a column
                if (method_exists($this, 'putOrderAttachments')) {
                    $this->putOrderAttachments($order, $existing->values()->all());
                } else {
                    // keep compatibility with your edit() fallback
                    $order->orderAttachment = json_encode($existing->values()->all());
                    $order->save();
                }

                // ----- 3) Header product (if no per-product header posted) -----
                $postedProducts = collect($request->input('products', []))->values();
                $hasPerProductHeader = $postedProducts->contains(function ($g) {
                    return is_array($g) && (
                        array_key_exists('name', $g) ||
                        array_key_exists('qty_total', $g) ||
                        array_key_exists('material', $g)
                    );
                });

                if (!$hasPerProductHeader) {
                    $hdr = (array) $request->input('product', []);
                    $productHdr = Product::where('OrderID', $order->id)->first()
                        ?: new Product(['OrderID' => $order->id]);

                    if (array_key_exists('name', $hdr))      $productHdr->productName    = $hdr['name'];
                    if (array_key_exists('qty_total', $hdr)) $productHdr->totalQuantity  = $hdr['qty_total'];
                    if (array_key_exists('material', $hdr))  $productHdr->materialRemark = $hdr['material'];
                    $productHdr->save();
                }

                // ----- 4) Per-product payload (items, deliveries, remarks) -----
                $postedProducts = collect($request->input('products', []))->values();

                foreach ($postedProducts as $group) {
                    $pid = (int) ($group['product_id'] ?? 0);
                    if (!$pid) continue;

                    $productRow = Product::where('OrderID', $order->id)
                        ->where('ProductID', $pid)
                        ->first();
                    if (!$productRow) continue;

                    // per-product header
                    if (array_key_exists('name', $group)) {
                        $productRow->productName = $group['name'] === '' ? null : $group['name'];
                    }
                    if (array_key_exists('qty_total', $group)) {
                        $q = $group['qty_total'];
                        $productRow->totalQuantity = ($q === '' || $q === null) ? null : (int) $q;
                    }
                    if (array_key_exists('material', $group)) {
                        $productRow->materialRemark = $group['material'] === '' ? null : $group['material'];
                    }
                    $productRow->save();

                    // -------- Items --------
                    $keepItemIds = [];

                    collect($group['items'] ?? [])->filter(fn($row) => is_array($row))->each(function ($row) use ($productRow, &$keepItemIds) {
                        $isEmpty = collect($row)->except(['id', 'material'])
                            ->filter(fn($v) => $v !== '' && $v !== null)->isEmpty();
                        if ($isEmpty) return;

                        $item = null;
                        if (!empty($row['id'])) {
                            $item = ProductItem::where('ItemID', (int)$row['id'])
                                ->where('ProductID', $productRow->ProductID)
                                ->first();
                        }
                        if (!$item) {
                            $item = new ProductItem();
                            $item->ProductID = $productRow->ProductID;
                        }

                        foreach (
                            [
                                'itemName',
                                'quantity',
                                'sizeWidth',
                                'sizeHeight',
                                'sizeUnit',
                                'bleedTop',
                                'bleedBottom',
                                'bleedLeft',
                                'bleedRight',
                                'bleedUnit',
                                'finishing'
                            ] as $k
                        ) {
                            if (array_key_exists($k, $row)) {
                                $item->{$k} = $row[$k] === '' ? null : $row[$k];
                            }
                        }

                        if (array_key_exists('material', $row)) {
                            $vals = is_array($row['material'])
                                ? $row['material']
                                : array_map('trim', explode(',', (string)$row['material']));
                            $vals = array_values(array_filter($vals, fn($v) => $v !== ''));
                            if (method_exists($item, 'hasCast') && $item->hasCast('material', 'array')) {
                                $item->material = $vals ?: null;
                            } else {
                                $item->material = $vals ? json_encode($vals) : null;
                            }
                        }

                        if (array_key_exists('prime_centre', $row)) {
                            $v = strtolower((string)$row['prime_centre']);
                            $item->prime_centre = in_array($v, ['1', 'true', 'on', 'yes'], true) ? 1 : 0;
                        }

                        $item->save();
                        $keepItemIds[] = $item->ItemID;

                        if (array_key_exists('lamination', $row) || array_key_exists('printer', $row) || array_key_exists('cutter', $row)) {
                            $spec = Specification::firstOrNew(['ItemID' => $item->ItemID]);
                            $spec->lamination = $row['lamination'] ?? null;
                            $spec->printer    = $row['printer']    ?? null;
                            $spec->cutter     = $row['cutter']     ?? null;
                            $spec->save();
                        }
                    });

                    if (array_key_exists('items', $group)) {
                        ProductItem::where('ProductID', $productRow->ProductID)
                            ->when(count($keepItemIds) > 0, fn($q) => $q->whereNotIn('ItemID', $keepItemIds))
                            ->when(count($keepItemIds) === 0, fn($q) => $q) // delete all
                            ->delete();
                    }

                    // -------- Deliveries --------
                    $deliveries = collect($group['deliveries'] ?? [])
                        ->filter(fn($row) => is_array($row))
                        ->map(function ($row) {
                            $row = array_change_key_case($row, CASE_LOWER);

                            $date = $row['date'] ?? null;
                            $time = $row['time'] ?? null;
                            if ((!$date || !$time) && !empty($row['datetime'])) {
                                try {
                                    $dt   = \Carbon\Carbon::parse($row['datetime']);
                                    $date = $date ?: $dt->toDateString();
                                    $time = $time ?: $dt->format('H:i:s');
                                } catch (\Throwable $e) {
                                }
                            }

                            $method      = trim((string)($row['method'] ?? ''));
                            $location    = trim((string)($row['location'] ?? ''));
                            $qty         = $row['quantity'] ?? null;

                            $installType = strtolower(trim((string)($row['deliver_install_type'] ?? ''))) ?: null;
                            $costRaw     = $row['outsource_cost'] ?? null;
                            $cost        = ($costRaw === '' || $costRaw === null) ? null : (float)$costRaw;

                            if ($method === '' && $location === '' && ($qty === null || $qty === '') && !$date && !$time) {
                                return null;
                            }

                            return [
                                'id'                   => isset($row['id']) ? (int)$row['id'] : null,
                                'method'               => $method !== '' ? $method : null,
                                'location'             => $location !== '' ? $location : null,
                                'quantity'             => (int)($qty ?? 0),
                                'date'                 => $date ?: null,
                                'time'                 => $time ?: null,
                                'deliver_install_type' => $installType,
                                'outsource_cost'       => $cost,
                            ];
                        })
                        ->filter()
                        ->values();

                    $keepDeliveryIds = [];
                    foreach ($deliveries as $d) {
                        $bd = null;
                        if (!empty($d['id'])) {
                            $bd = DeliveryBreakdown::where('BreakdownID', $d['id'])
                                ->where('ProductID', $productRow->ProductID)
                                ->first();
                        }
                        if (!$bd) {
                            $bd = new DeliveryBreakdown();
                            $bd->ProductID = $productRow->ProductID;
                        }
                        $bd->method   = $d['method'];
                        $bd->location = $d['location'];
                        $bd->quantity = $d['quantity'];
                        $bd->date     = $d['date'];
                        $bd->time     = $d['time'];
                        $bd->deliver_install_type = $d['deliver_install_type'] ?? null;
                        $bd->outsource_cost       = array_key_exists('outsource_cost', $d) && $d['outsource_cost'] !== ''
                            ? (float)$d['outsource_cost']
                            : null;
                        $bd->save();

                        $keepDeliveryIds[] = $bd->BreakdownID;
                    }

                    if (array_key_exists('deliveries', $group)) {
                        DeliveryBreakdown::where('ProductID', $productRow->ProductID)
                            ->when(count($keepDeliveryIds) > 0, fn($q) => $q->whereNotIn('BreakdownID', $keepDeliveryIds))
                            ->when(count($keepDeliveryIds) === 0, fn($q) => $q) // delete all
                            ->delete();
                    }

                    // -------- Remarks --------
                    $keepRemarkIds = [];
                    foreach (collect($group['remarks'] ?? [])->filter(fn($row) => is_array($row)) as $row) {
                        $isEmpty = trim($row['operation'] ?? '') === '' && trim($row['remark'] ?? '') === '';
                        if ($isEmpty) continue;

                        $remark = null;
                        if (!empty($row['id'])) {
                            $remark = ProductRemark::where('RemarkID', (int)$row['id'])
                                ->where('ProductID', $productRow->ProductID)
                                ->first();
                        }
                        if (!$remark) {
                            $remark = new ProductRemark();
                            $remark->ProductID = $productRow->ProductID;
                        }
                        $remark->operation = $row['operation'] ?: null;
                        $remark->remark    = $row['remark'] ?: null;
                        $remark->save();

                        $keepRemarkIds[] = $remark->RemarkID;
                    }

                    $toDelete = collect($group['delete_remarks'] ?? [])
                        ->merge($request->input('delete_remarks', []))
                        ->map(fn($id) => (int)$id)
                        ->filter();

                    if ($toDelete->isNotEmpty()) {
                        ProductRemark::where('ProductID', $productRow->ProductID)
                            ->whereIn('RemarkID', $toDelete)
                            ->delete();
                    }

                    if (array_key_exists('remarks', $group)) {
                        ProductRemark::where('ProductID', $productRow->ProductID)
                            ->when(count($keepRemarkIds) > 0, fn($q) => $q->whereNotIn('RemarkID', $keepRemarkIds))
                            ->delete();
                    }

                    // optional: keep your existing helper
                    $productRow->syncTaskTypeFromSpecs();
                }
            });

            $message = $request->boolean('submit')
                ? 'Order submitted.'
                : ($request->input('is_draft') === '1' ? 'Draft saved.' : 'Order updated.');

            if ($request->expectsJson()) {
                return response()->json(['ok' => true, 'message' => $message]);
            }
            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Data-entry update failed', [
                'order_id' => $order->id,
                'err'      => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Failed to save. Please try again.'], 500);
            }
            return back()->with('error', 'Failed to save. Please try again.');
        }
    }

    public function destroyRemark(Request $request, Order $order, $remark)
    {
        $user = Auth::user();
        // Only enforce when user is data-entry
        if (
            $user->role === 'data-entry'
            && !empty($order->data_entry_id)
            && (int)$order->data_entry_id !== (int)$user->id
        ) {
            return response()->json(['ok' => false, 'message' => 'Forbidden'], 403);
        }

        // Load remark
        $row = ProductRemark::where('RemarkID', (int) $remark)->first();
        if (!$row) {
            return response()->json(['ok' => true]); // idempotent
        }

        // Remark's product must belong to THIS order
        $belongs = Product::where('ProductID', $row->ProductID)
            ->where('OrderID', $order->id)
            ->exists();

        if (!$belongs) {
            return response()->json(['ok' => false, 'message' => 'Remark does not belong to this order'], 422);
        }

        $row->delete();
        return response()->json(['ok' => true]);
    }

    public function destroyItem(Request $request, Order $order, ProductItem $item)
    {
        $user = Auth::user();
        if (
            $user->role === 'data-entry'
            && !empty($order->data_entry_id)
            && (int)$order->data_entry_id !== (int)$user->id
        ) {
            return response()->json(['ok' => false, 'msg' => 'Forbidden'], 403);
        }

        // Ensure item belongs to this order
        $item->loadMissing('product');
        if (!$item->product || (int)$item->product->OrderID !== (int)$order->id) {
            return response()->json(['ok' => false, 'msg' => 'Not found'], 404);
        }

        $item->delete(); // FK cascade will remove specification if set up
        return response()->json(['ok' => true]);
    }

    public function deleteDelivery(Request $request, Order $order, int $delivery)
    {
        $user = Auth::user();
        if (
            $user->role === 'data-entry'
            && !empty($order->data_entry_id)
            && (int)$order->data_entry_id !== (int)$user->id
        ) {
            return response()->json(['ok' => false, 'message' => 'Forbidden'], 403);
        }

        // Delivery must be for a product under this order
        $productIds = Product::where('OrderID', $order->id)->pluck('ProductID');

        $row = DeliveryBreakdown::where('BreakdownID', $delivery)
            ->whereIn('ProductID', $productIds)
            ->first();

        if (!$row) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        $row->delete();
        return response()->json(['ok' => true]);
    }

    public function deleteAttachment(Request $request, Order $order)
    {
        $user = Auth::user();
        if (
            $user->role === 'data-entry'
            && !empty($order->data_entry_id)
            && (int)$order->data_entry_id !== (int)$user->id
        ) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate(['path' => 'required|string']);
        $path = trim($data['path']);

        // Delete physical file if exists
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        // Remove from DB list
        $list = collect($this->getOrderAttachments($order))
            ->reject(fn($p) => trim((string)$p) === $path)
            ->values()
            ->all();

        $this->putOrderAttachments($order, $list);

        return response()->json(['ok' => true]);
    }
}
