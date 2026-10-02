<?php

namespace App\Http\Controllers;

use App\Services\OrderProductService;
use App\Rules\KnownMaterial;
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
use App\Helpers\Helpers;
use App\Notifications\GenericNotification;
use App\Models\OrderAttachment;
use App\Models\OrderRecord;

class DataEntryController extends Controller
{
    public function orders(Request $request)
    {
        $user = auth()->user();

        // --- read status from query (for KPI / dropdown) ---
        $statusParam = strtolower(trim((string) $request->query('status', '')));
        $allowedStatuses = ['awaiting_keyin', 'completed'];
        $selectedStatus  = in_array($statusParam, $allowedStatuses, true) ? $statusParam : '';

        // Base scope: hide rows with status = 1 (only NULL or 0 are visible)
        $base = Order::query()
            ->where(function ($q) {
                $q->whereNull('status')
                ->orWhere('status', 0);
            })
            ->when($user && $user->role !== 'boss', fn ($q) => $q->where('data_entry_id', $user->id));

        // ---------- KPI COUNTS (also exclude status = 1 via $base) ----------
        $metrics = [
            'total'          => (clone $base)->count(),
            'awaiting_keyin' => (clone $base)->where('orderStatus', 'awaiting_keyin')->count(),
            'completed'      => (clone $base)->where('orderStatus', 'completed')->count(),
        ];

        // ---------- TABLE DATA: awaiting_keyin + completed ----------
        $table = (clone $base);
        if ($selectedStatus !== '') {
            $table->where('orderStatus', $selectedStatus);
        } else {
            $table->whereIn('orderStatus', ['awaiting_keyin', 'completed']);
        }

        if ($s = trim((string) $request->query('q', ''))) {
            $table->where(function ($w) use ($s) {
                $w->where('orderTitle',   'like', "%{$s}%")
                ->orWhere('companyName','like', "%{$s}%")
                ->orWhere('leadName',   'like', "%{$s}%")
                ->orWhere('order_number','like', "%{$s}%");
            });
        }

        // expose a consistent ui_status to the blade (use actual orderStatus)
        $table->select('orders.*')->addSelect([
            'ui_status' => DB::raw('orderStatus'),
        ]);

        // Optional: put awaiting_keyin before completed in the list, then by newest date
        $orders = $table->with(['artist:id,name', 'salesperson:id,name'])
            ->orderByRaw("FIELD(orderStatus, 'awaiting_keyin','completed')") // awaiting first
            ->orderByDesc('orderDate')
            ->paginate(30)
            ->withQueryString();

        // keep compatibility if blade relies on ui_status
        $orders->getCollection()->transform(function ($o) {
            $o->ui_status = $o->orderStatus; 
            return $o;
        });

        $statusRaw = $selectedStatus;

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

        $order->loadMissing([
            'salesperson:id,name',
            'artist:id,name',
            'products'        => fn ($q) => $q->orderBy('ProductID'),
            'products.items'  => fn ($q) => $q->orderBy('ItemID'),
            'products.items.spec',
            // 'leadAttachments',
            'deliveryBreakdowns' => fn ($q) => $q->orderBy('BreakdownID'),
        ]);

        // helper to turn a storage path into a public URL
        $toPublicUrl = function (string $p): string {
            $p = ltrim($p, '/');

            if (Str::startsWith($p, 'storage/')) {
                return url($p);
            }

            return Storage::disk('public')->url($p);
        };

        // roles considered "artist-side"
        $artistRoles = ['artist', 'head-artist', 'data-entry'];

        // 1) Try order_attachments table first
        $rows = OrderAttachment::with('uploader:id,name,role')
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get();

        $mapRow = function (OrderAttachment $att) use ($toPublicUrl) {
            $p = ltrim((string) $att->file_path, '/');
            $web = \Illuminate\Support\Str::startsWith($p, 'storage/') ? $p : 'storage/' . $p;

            return [
                'id'            => $att->id,
                'name'          => $att->original_name ?: basename($p),
                'url'           => $toPublicUrl($web),
                'size'          => (int) $att->size,
                'ext'           => pathinfo($p, PATHINFO_EXTENSION),
                'uploaded_by'   => optional($att->uploader)->name,
                'uploader_role' => optional($att->uploader)->role,
                'uploaded_at'   => optional($att->created_at)->format('d M Y'),
            ];
        };

        if ($rows->isNotEmpty()) {
            // split by uploader role
            $headerAttachments = $rows
                ->filter(function ($att) use ($artistRoles) {
                    return !in_array(optional($att->uploader)->role, $artistRoles, true);
                })
                ->map($mapRow)
                ->values();

            $attachments = $rows
                ->filter(function ($att) use ($artistRoles) {
                    return in_array(optional($att->uploader)->role, $artistRoles, true);
                })
                ->map($mapRow)
                ->values();
        } else {
            // 2) Fallback to lead_attachments for legacy orders
            $headerAttachments = LeadAttachment::where('lead_id', $order->lead_id)
                ->latest()
                ->get()
                ->map(function ($a) use ($toPublicUrl) {
                    $p = ltrim((string) $a->file_location, '/');
                    $p = preg_replace('#^public/#', '', $p);
                    $p = preg_replace('#^storage/#', '', $p);
                    $web = 'storage/' . $p;

                    return [
                        'id'            => null,
                        'name'          => basename($p),
                        'url'           => $toPublicUrl($web),
                        'size'          => (int) $a->file_size,
                        'ext'           => $a->file_extension,
                        'uploaded_by'   => null,  // unknown for legacy files
                        'uploader_role' => null,
                        'uploaded_at'   => null,
                    ];
                });

            $attachments = collect(); // none from artist/head-artist in this legacy path
        }

        return view('data-entry.order.show', compact('order', 'attachments', 'headerAttachments'));
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

    private function getOrderAttachments(Order $order): array
    {
        // $raw = $order->orderAttachment ?? '';
        // if ($raw === '') return [];
        // if (str_starts_with(trim($raw), '[')) {
        //     return array_values(array_filter((array) json_decode($raw, true)));
        // }
        // return array_values(array_filter(array_map('trim', explode(',', $raw))));

        return OrderAttachment::where('order_id', $order->id)
            ->orderBy('id')
            ->pluck('file_path')
            ->filter()
            ->values()
            ->all();
    }

    private function putOrderAttachments(Order $order, array $paths): void
    {
        // keep as comma separated (or switch to json_encode($paths))
        // $order->orderAttachment = implode(',', $paths);
        // $order->save();
        $paths = array_values(array_filter($paths));

        $existing = OrderAttachment::where('order_id', $order->id)->get();
        $existingPaths = $existing->pluck('file_path')->all();

        // delete rows that are no longer in the list
        foreach ($existing as $att) {
            if (!in_array($att->file_path, $paths, true)) {
                $att->delete();
            }
        }

        // insert minimal rows for new paths (if any)
        $userId = Auth::id();
        foreach ($paths as $p) {
            if (!in_array($p, $existingPaths, true)) {
                OrderAttachment::create([
                    'order_id'      => $order->id,
                    'user_id'       => $userId,
                    'file_path'     => $p,
                    'original_name' => basename($p),
                ]);
            }
        }
    }

    public function uploadAttachments(Request $request, Order $order)
    {
        $user = Auth::user();
        $request->validate([
            'file' => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,ppt,pptx,ps,ai',
        ]);

        $file = $request->file('file');
        $dir  = "orders/{$order->id}/attachments";

        $orig = $file->getClientOriginalName();
        $base = pathinfo($orig, PATHINFO_FILENAME);
        $ext  = strtolower($file->getClientOriginalExtension());
        $baseSlug = Str::slug($base) ?: 'file';

        $candidate = "{$baseSlug}.{$ext}";
        $i = 1;
        while (Storage::disk('public')->exists("$dir/$candidate")) {
            $candidate = "{$baseSlug} ({$i}).{$ext}";
            $i++;
        }

        $path = $file->storeAs($dir, $candidate, 'public');

        $attachment = OrderAttachment::create([
            'order_id'      => $order->id,
            'user_id'       => $user->id,
            'file_path'     => $path,
            'original_name' => $orig,
            'mime_type'     => $file->getClientMimeType(),
            'size'          => $file->getSize(),
        ]);

        return response()->json([
            'id'   => $attachment->id,
            'path' => $path,
            'url'  => Storage::disk('public')->url($path),
        ], 201);
    }

    public function destroyAttachment(Request $request, Order $order)
    {
        // Only allow delete while in draft
        if ((int)$order->draft !== 1 || (int)$order->submit === 1) {
            return response()->json(['ok' => false, 'message' => 'Not allowed.'], 403);
        }

        // "path" comes from the button data attribute (see Blade below)
        $path = (string) $request->input('path', '');
        if ($path === '') {
            return response()->json(['ok' => false, 'message' => 'Missing file path.'], 422);
        }

        // Normalize to disk path (strip leading storage/)
        $diskPath = ltrim($path, '/');
        $diskPath = preg_replace('#^storage/#', '', $diskPath); // public disk path

        // Read current attachments (use your helpers if present)
        $attachments = method_exists($this, 'getOrderAttachments')
            ? (array) $this->getOrderAttachments($order)
            : (array) (json_decode((string) $order->orderAttachment, true) ?: []);

        // Remove from array (match either raw or with "storage/" prefix)
        $attachments = collect($attachments)->reject(function ($p) use ($diskPath) {
            $p = ltrim((string)$p, '/');
            $pNoStorage = preg_replace('#^storage/#', '', $p);
            return $pNoStorage === $diskPath;
        })->values()->all();

        // Delete physical file (best-effort)
        try { Storage::disk('public')->delete($diskPath); } catch (\Throwable $e) {}

        // Persist updated attachments (use your helper if present)
        if (method_exists($this, 'putOrderAttachments')) {
            $this->putOrderAttachments($order, $attachments);
        } else {
            $order->orderAttachment = json_encode($attachments);
            $order->save();
        }

        return response()->json(['ok' => true]);
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
                'draft'       => 1,
                'submit'      => 0,
                'pending'     => 0,
                'orderStatus' => 'in_progress',
            ];
            if (empty($order->data_entry_id)) {
                $update['data_entry_id'] = $user->id;
            }
            Order::whereKey($order->getKey())->update($update);
            $order->refresh();
        }

        // Load relations AFTER any status/ownership changes
        $order->loadMissing([
            'products' => fn ($q) => $q->orderBy('ProductID'),
            'products.items' => fn ($q) => $q->orderBy('ItemID'),
            'products.deliveryBreakdowns' => fn ($q) => $q->orderBy('BreakdownID'),
            'artist:id,name',
            'attachments.user:id,name',
        ]);

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
        $materials = Material::where('active', 1)
            ->orderBy('materialName')
            ->get(['materialName']);
        $allMaterials = Material::where('active', 1)
            ->orderBy('materialName')
            ->pluck('materialName')
            ->values()
            ->all();
        $attachments = $this->getOrderAttachments($order);
        // Attachments helpers
        $toPublicUrl = function (string $p): string {
            $p = ltrim($p, '/');
            if (\Illuminate\Support\Str::startsWith($p, 'storage/')) {
                return url($p);
            }
            return Storage::disk('public')->url($p);
        };

        // 2) Order attachments (from order_attachments table, with uploader info)
        $attachmentRows = \App\Models\OrderAttachment::with('uploader:id,name,role')
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get();

        $orderFiles = $attachmentRows->map(function (\App\Models\OrderAttachment $att) use ($toPublicUrl) {
            $p = ltrim((string) $att->file_path, '/');
            $webPath = \Illuminate\Support\Str::startsWith($p, 'storage/')
                ? $p
                : 'storage/' . $p;

            return [
                'id'          => $att->id,
                'name'        => $att->original_name ?: basename($p),
                'ext'         => pathinfo($p, PATHINFO_EXTENSION),
                'url'         => $toPublicUrl($webPath),
                'path'        => $webPath,
                'size'        => $att->size,
                'uploaded_by' => optional($att->uploader)->name,
                'uploaded_at' => optional($att->created_at)->format('d M Y'),
                'uploader_role' => optional($att->uploader)->role, 
            ];
        });

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

        $attachmentRows = \App\Models\OrderAttachment::with('uploader:id,name,role')
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get();

        $artistRoles = ['artist', 'head-artist'];

        $mapAttachment = function ($att) use ($toPublicUrl) {
            $p = ltrim((string) $att->file_path, '/');
            $webPath = \Illuminate\Support\Str::startsWith($p, 'storage/')
                ? $p
                : 'storage/'.$p;

            return [
                'id'            => $att->id,
                'name'          => $att->original_name ?: basename($p),
                'ext'           => pathinfo($p, PATHINFO_EXTENSION),
                'url'           => $toPublicUrl($webPath),
                'path'          => $webPath,               // used when deleting
                'size'          => $att->size,
                'uploaded_by'   => optional($att->uploader)->name,
                'uploaded_at'   => optional($att->created_at)->format('d M Y'),
                'uploader_role' => optional($att->uploader)->role,
            ];
        };

        // files uploaded by artist / head-artist → bottom section
        $orderFiles = $attachmentRows
            ->filter(function ($att) use ($artistRoles) {
                return in_array(optional($att->uploader)->role, $artistRoles, true);
            })
            ->map($mapAttachment)
            ->values();

        // files uploaded by NON-artist (eg. salesperson) → top section
        $orderFilesSales = $attachmentRows
            ->reject(function ($att) use ($artistRoles) {
                return in_array(optional($att->uploader)->role, $artistRoles, true);
            })
            ->map($mapAttachment)
            ->values();

        $sourceOrderId = (int) ($order->redo ?: $order->id);

        $redoReason = DB::table('report_redo')
            ->where('OrderID', $sourceOrderId)
            ->orderByDesc('created_at')
            ->value('reason');

        $artists = \App\Models\User::whereIn('role', ['artist', 'head-artist'])
        ->orderBy('name')
        ->get(['id', 'name', 'role']);

        $printerMachines = \App\Models\Machine::where('machine_type', 'printer')
            ->orderBy('machine_name')
            ->get(['id', 'machine_name', 'active']);

        $cutterMachines = \App\Models\Machine::where('machine_type', 'cutter')
            ->orderBy('machine_name')
            ->get(['id', 'machine_name', 'active']);

        $laminationMachines = \App\Models\Machine::where('machine_type', 'lamination')
            ->orderBy('machine_name')
            ->get(['id', 'machine_name', 'active']);

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
            'orderFiles',
            'orderFilesSales',
            'redoReason',
            'printerMachines',
            'cutterMachines',
            'artists',
            'laminationMachines'
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
        // ----- AuthZ -----
        $user = Auth::user();
        if ($user->role === 'data-entry') {
            if (!empty($order->data_entry_id) && (int)$order->data_entry_id !== (int)$user->id) {
                abort(403);
            }
        }

        // If products_json is present, decode into products[]
        if ($request->filled('products_json')) {
            $decoded = json_decode($request->input('products_json'), true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $payload = $request->all();
                $payload['products'] = $decoded;
                $request->replace($payload); // now $request->input('products') works as before
            }
        }

        // ----- Validation -----
        $rules = [
            'products_items_json'              => ['nullable', 'string'],
            'design_confirmed'  => ['required','boolean'],
            'is_draft'          => ['required','in:0,1'],

            // header (product shown at the top)
            'product.name'      => ['nullable','string','max:255'],
            'product.qty_total' => ['nullable','integer','min:0'],
            'product.material'  => ['nullable','string','max:255'],
            'products.*.permit' => ['nullable','in:0,1'],

            // nested products[*]
            'products'                          => ['array'],
            'products.*.product_id'             => ['nullable','integer'],

            // items
            'products.*.items'                  => ['array'],
            'products.*.items.*.id'             => ['nullable','integer'],
            'products.*.items.*.itemName'       => ['nullable','string','max:255'],
            'products.*.items.*.quantity'       => ['nullable','integer','min:0'],
            'products.*.items.*.sizeWidth'      => ['nullable','numeric'],
            'products.*.items.*.sizeHeight'     => ['nullable','numeric'],
            'products.*.items.*.sizeUnit'       => ['nullable','in:mm,cm,inch,ft,piece'],
            'products.*.items.*.bleedTop'       => ['nullable','numeric'],
            'products.*.items.*.bleedBottom'    => ['nullable','numeric'],
            'products.*.items.*.bleedLeft'      => ['nullable','numeric'],
            'products.*.items.*.bleedRight'     => ['nullable','numeric'],
            'products.*.items.*.bleedUnit'      => ['nullable','in:mm,cm,inch,ft,piece'],
            'products.*.items.*.finishing'      => ['nullable','string','max:255'],
            'products.*.items.*.renderTime'     => ['nullable','integer','min:0'],
            'products.*.items.*.material'       => ['nullable'],
            'products.*.items.*.material.*'     => ['nullable','string','max:255', new KnownMaterial($order)],
            'products.*.items.*.lamination'     => ['nullable','string','max:255'],
            'products.*.items.*.printer'        => ['nullable','string','max:255'],
            'products.*.items.*.cutter'         => ['nullable','string','max:255'],
            'products.*.items.*.prime_centre'   => ['nullable', 'in:0,1'],

            // deliveries
            'products.*.deliveries'             => ['array'],
            'products.*.deliveries.*.id'        => ['nullable','integer'],
            'products.*.deliveries.*.method'    => ['nullable','string','max:255'],
            'products.*.deliveries.*.location'  => ['nullable','string','max:255'],
            'products.*.deliveries.*.quantity'  => ['nullable','numeric','min:0'],
            'products.*.deliveries.*.datetime'  => ['nullable','date'],
            'products.*.deliveries.*.date'      => ['nullable','date'],
            'products.*.deliveries.*.time'      => ['nullable','date_format:H:i'],
            'products.*.deliveries.*.deliver_install_type' => ['nullable','string','max:255'],
            'products.*.deliveries.*.outsource_cost'       => ['nullable','numeric','min:0'],

            // remarks (per product)
            // 'products.*.remarks'                => ['array'],
            // 'products.*.remarks.*.id'           => ['nullable','integer'],
            // 'products.*.remarks.*.operation'    => [
            //     'nullable',
            //     Rule::in(['printing','furnishing','installation','courier','self_pickup', 'artist']),
            // ],            
            // 'products.*.remarks.*.remark'       => ['nullable','string'],
            // 'products.*.delete_remarks'         => ['array'],
            // 'products.*.delete_remarks.*'       => ['integer'],

            // attachments
            'attachments.*'                     => ['file','mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,ppt,pptx','max:20480'],
            'delete_attachments'                => ['array'],
            'delete_attachments.*'              => ['string'],
        ];

        $payload = $request->all();

        // Expand compact items JSON (products_items_json) back into products[*].items
        if (!empty($payload['products_items_json'])) {
            $itemsByIndex = json_decode($payload['products_items_json'], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($itemsByIndex)) {
                if (!empty($payload['products']) && is_array($payload['products'])) {
                    foreach ($itemsByIndex as $pIndex => $block) {
                        $pIndex = (int) $pIndex;
                        if (!isset($payload['products'][$pIndex])) {
                            continue;
                        }
                        if (!empty($block['items']) && is_array($block['items'])) {
                            $payload['products'][$pIndex]['items'] = $block['items'];
                        } else {
                            // no items for this product index
                            $payload['products'][$pIndex]['items'] = [];
                        }
                    }
                }
            }

            unset($payload['products_items_json']); // we don't need the raw JSON afterwards
        }

        if (!empty($payload['products']) && is_array($payload['products'])) {
            foreach ($payload['products'] as &$pg) {

                // 1) NORMALIZE PRIME_CENTRE on items to '0'/'1' (or null)
                if (!empty($pg['items']) && is_array($pg['items'])) {
                    foreach ($pg['items'] as &$it) {
                        if (array_key_exists('prime_centre', $it)) {
                            $raw = $it['prime_centre'];

                            // allow the dash/blank option to stay null
                            if ($raw === '' || $raw === null || $raw === '-') {
                                $it['prime_centre'] = null;
                            } else {
                                $v = strtolower((string)$raw);
                                // treat truthy words as 1, everything else as 0
                                $it['prime_centre'] = in_array($v, ['1','true','on','yes'], true) ? '1' : '0';
                            }
                        }
                    }
                    unset($it);
                }

                // 2) Your existing remarks normalization
                if (!empty($pg['remarks']) && is_array($pg['remarks'])) {
                    foreach ($pg['remarks'] as &$rk) {
                        if (array_key_exists('operation', $rk)) {
                            $op = strtolower(trim((string) $rk['operation']));
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
                return response()->json([
                    'message' => 'Validation error',
                    'errors'  => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $validator->after(function ($v) use ($request, $order) {
            if (empty($order->artist_id)) {
                $products = (array) $request->input('products', []);
                foreach ($products as $pi => $g) {
                    foreach ((array)($g['remarks'] ?? []) as $ri => $row) {
                        $op = strtolower(trim((string)($row['operation'] ?? '')));
                        if ($op === 'artist') {
                            $v->errors()->add(
                                "products.$pi.remarks.$ri.operation",
                                'You can only choose “To Artist” after assigning an artist to this order.'
                            );
                        }
                    }
                }
            }
        });

        try {
            // $authorId = (int) auth()->id();
            DB::transaction(function () use ($request, $order, $product) {

                // ----- 1) Order core -----
                $submitted = $request->boolean('submit'); 

                if ($submitted) {
                    $order->submit = 1;         
                    $order->draft  = 0;     
                    $order->approval    = $request->boolean('design_confirmed');

                    // if ($order->orderStatus !== 'awaiting_keyin') {
                        $order->orderStatus = 'completed';
                    // }                  
                } else {
                    $order->submit = 0;        
                    $order->draft  = (int) $request->input('is_draft', 0);
                    $order->approval    = $request->boolean('design_confirmed');
                }

                
                $order->save();

                // ✅ Record submitted time ONLY when submit=true (exclude drafts)
                if ($submitted) {
                    OrderRecord::firstOrCreate(['order_id' => $order->id]);

                    OrderRecord::where('order_id', $order->id)
                        ->whereNull('submitted_at')
                        ->update(['submitted_at' => now()]);
                }

                // ----- 2) Attachments -----
                $existing = collect($this->getOrderAttachments($order));
                $toDelete = collect($request->input('delete_attachments', []))->filter();

                if ($toDelete->isNotEmpty()) {
                    foreach ($toDelete as $path) {
                        $path = ltrim((string) $path, '/');
                        $storagePath = preg_replace('#^storage/#', '', $path);

                        // remove file from disk if exists
                        if (Storage::disk('public')->exists($storagePath)) {
                            Storage::disk('public')->delete($storagePath);
                        }

                        // drop from our in-memory list (handles both with/without storage/ prefix)
                        $existing = $existing->reject(function ($p) use ($storagePath) {
                            $p = ltrim((string) $p, '/');
                            $p = preg_replace('#^storage/#', '', $p);
                            return $p === $storagePath;
                        })->values();
                    }
                }

                // New uploads
                if ($request->hasFile('attachments')) {
                    $files = $request->file('attachments');
                    if (!is_array($files)) {
                        $files = [$files];
                    }

                    $dir = "orders/{$order->id}/attachments";

                    foreach ($files as $file) {
                        if (!$file || !$file->isValid()) {
                            continue;
                        }

                        $orig = $file->getClientOriginalName();
                        $base = pathinfo($orig, PATHINFO_FILENAME);
                        $ext  = strtolower($file->getClientOriginalExtension());

                        $baseSlug = Str::slug($base) ?: 'file';

                        // Avoid name clashes within this order
                        $candidate = "{$baseSlug}.{$ext}";
                        $i = 1;
                        while (Storage::disk('public')->exists("$dir/$candidate")) {
                            $candidate = "{$baseSlug} ({$i}).{$ext}";
                            $i++;
                        }

                        $path = $file->storeAs($dir, $candidate, 'public');

                        $existing->push($path);
                    }
                }

                // Persist back to orders.orderAttachment (comma-separated)
                $pathsToKeep = $existing->filter()->unique()->values()->all();
                $this->putOrderAttachments($order, $pathsToKeep);

                // ----- 3) Products: header product, items, specs, deliveries, remarks -----
                app(OrderProductService::class)->syncProducts(
                    $order,
                    (array) $request->input('products', []),
                    (array) $request->input('product', []),
                    (array) $request->input('delete_remarks', []),
                    (int) auth()->id(),
                    syncRemarks: false,
                );
            });

            if ($request->boolean('submit')) {

                $actor     = Auth::user();
                $actorName = $actor?->name ?? 'System';
                $actorRole = str_replace('-', ' ', strtolower($actor?->role ?? 'user'));

                // Basic order context
                $orderId  = (int) $order->id;
                $orderNo  = (string) $order->order_number;
                $deadline = $order->deadline
                    ? \Carbon\Carbon::parse($order->deadline)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d')
                    : '-';

                // Pull products with their taskType (for ops notifications)
                $products = \App\Models\Product::where('OrderID', $orderId)
                    ->get(['ProductID','productName','taskType']);

                $productCount      = $products->count();
                $initialTasksArray = $products->pluck('taskType')->filter()->unique()->values()->all();
                $initialTasksStr   = implode(', ', $initialTasksArray ?: ['-']);

                /**
                 * ======================
                 *  1) BUSINESS ROLES
                 *     (order-level message, no dupes)
                 * ======================
                 */
                $orderMsg = "Order {$orderNo} has been submitted by {$actorName} ({$actorRole}). "
                        . "{$productCount} Product(s). Deadline: {$deadline}. "
                        . "Initial task(s): {$initialTasksStr}.";

                $orderUrlFor = function (\App\Models\User $u) use ($orderId) {
                    return match (strtolower($u->role)) {
                        'artist', 'head-artist'            => url("/artist/orders/{$orderId}"),
                        'salesperson', 'head-salesperson'  => url("/orders/{$orderId}"),
                        'admin'                             => url("/admin/orders/{$orderId}"),
                        'boss'                              => url("/boss/orders/{$orderId}"),
                        default                             => url("/"),
                    };
                };

                // Build recipients just like your successful example
                $businessRecipients = collect();

                // Always include these roles
                $baseRoles = ['admin','boss','head-artist'];
                $businessRecipients = $businessRecipients->merge(
                    \App\Models\User::whereIn('role', $baseRoles)->get()
                );

                // Assigned salesperson (if any)
                if (!empty($order->salesperson_id)) {
                    $businessRecipients = $businessRecipients->merge(
                        \App\Models\User::where('id', $order->salesperson_id)->get()
                    );
                }

                // If creator is a normal artist, ensure head-artist is included (already in base, but keep consistent)
                if (strtolower((string)$actor->role) === 'data-entry') {
                    $businessRecipients = $businessRecipients->merge(
                        \App\Models\User::where('role', 'head-artist')->get()
                    );
                }

                // De-duplicate on user id
                $businessRecipients = $businessRecipients->unique('id')->values();

                $bizKey = "order-submitted:order={$orderId}";

                foreach ($businessRecipients as $u) {
                    Helpers::notifyOnce(
                        $u,
                        $orderMsg,
                        $orderUrlFor($u),
                        ['database', 'mail'],
                        $bizKey // <— same for all business recipients; uniqueness is per-user
                    );
                }

                /**
                 * ======================
                 *  2) OPERATIONS ROLES
                 *     (one message per task type; URL must be ProductID;
                 *      de-dupe by user id)
                 * ======================
                 */
                $opsRoleForTask = [
                    'printing'     => 'operations-printing',
                    'furnishing'   => 'operations-furnishing',
                    'delivery'     => 'operations-dispatch-control',
                    'installation' => 'operations-delivery-installation',
                ];

                // Group items by normalized taskType
                $byTask = $products
                    ->filter(fn($p) => filled($p->taskType))
                    ->groupBy(fn($p) => strtolower((string)$p->taskType));

                foreach ($byTask as $task => $group) {
                    if (!isset($opsRoleForTask[$task])) continue;

                    $opsRole   = $opsRoleForTask[$task];
                    $count     = $group->count();
                    $firstId   = (int) $group->first()->ProductID;

                    $idsPreview = $group->pluck('ProductID')->take(5)->implode(', ');
                    $msg = "Order {$orderNo} submitted by {$actorName} ({$actorRole}). "
                        . "{$count} product(s) for {$task}"
                        // . ($idsPreview ? " (#{$idsPreview})" : '')
                        . ". Deadline: {$deadline}.";

                    // STABLE key per task for this order => prevents duplicates for the same user
                    $opsKey = "order-submitted:order={$orderId}:task={$task}";

                    \App\Models\User::whereRaw('LOWER(role) = ?', [$opsRole])
                        ->get()
                        ->each(function ($u) use ($msg, $firstId, $opsKey) {
                            $url = match (strtolower($u->role)) {
                                'operations-printing'              => url("/printing/jobs/{$firstId}"),
                                'operations-furnishing'            => url("/furnishing/jobs/{$firstId}"),
                                'operations-dispatch-control'      => url("/dispatchcontrol/job/{$firstId}"),
                                'operations-delivery-installation' => url("/installation/job/{$firstId}"),
                                default                            => url("/"),
                            };

                            \App\Helpers\Helpers::notifyOnce($u, $msg, $url, ['database', 'mail'], $opsKey);
                        });
                }
            }

            $message = $request->boolean('submit')
                ? 'Order submitted.'
                : ($request->input('is_draft') === '1' ? 'Draft saved.' : 'Order updated.');

            if (
                $request->ajax() ||
                $request->wantsJson() ||
                $request->header('X-Requested-With') === 'XMLHttpRequest'
            ) {
                return response()->json([
                    'ok'      => true,
                    'message' => $message,
                ]);
            }

            return redirect()
                ->route('data-entry.orders')
                ->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Data-entry update failed', [
                'order_id' => $order->id,
                'err'      => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            if (
                $request->ajax() ||
                $request->wantsJson() ||
                $request->header('X-Requested-With') === 'XMLHttpRequest'
            ) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Failed to save. Please try again.',
                ], 500);
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

        // $data = $request->validate(['path' => 'required|string']);
        // $path = trim($data['path']);

        // // Delete physical file if exists
        // if (Storage::disk('public')->exists($path)) {
        //     Storage::disk('public')->delete($path);
        // }

        // // Remove from DB list
        // $list = collect($this->getOrderAttachments($order))
        //     ->reject(fn($p) => trim($p) === $path)
        //     ->values()->all();
        // $this->putOrderAttachments($order, $list);

        // $this->putOrderAttachments($order, $list);

        // return response()->json(['ok' => true]);

        $data = $request->validate(['path' => 'required|string']);
        $path = ltrim($data['path'], '/');
        $storagePath = preg_replace('#^storage/#', '', $path);

        // delete file from storage
        if (Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        }

        // delete DB record(s)
        OrderAttachment::where('order_id', $order->id)
            ->where('file_path', $storagePath)
            ->delete();

        return response()->json(['ok' => true]);
    }
}
