<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Material;
use App\Models\ProductRemark;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Helpers\Helpers;
use App\Notifications\GenericNotification;
use App\Models\OrderAttachment;
use App\Models\Lead;
use Yajra\DataTables\Facades\DataTables;

class BossOrderController extends Controller
{
    public function orders(Request $request)
    {
        $user   = Auth::user();
        $isHead = $this->isHeadArtist($user);

        // ---- Active flag (status is NULL or 0) ----
        $active = function ($q) {
            $q->whereNull('status')->orWhereIn('status', [0, 1]);
        };

        // 1) Your normal visibility (already permission-aware) + active
        $normal = $this->visibleOrders()->where($active);

        // 2) Permission-aware base for redo copies + active
        $permBase = \App\Models\Order::query()->where($active);
        if (!$isHead) {
            $permBase->where(function ($p) use ($user) {
                $p->where('artist_id', $user->id)
                ->orWhere('salesperson_id', $user->id);
            });
        }
        $redoIdsSub = (clone $permBase)
            ->whereNotNull('redo')
            ->select('id');

        // 3) Final base = (normal) OR (redo copies) *grouped properly*
        $base = \App\Models\Order::query()
            ->where(function ($q) use ($normal, $redoIdsSub) {
                $q->whereIn('id', (clone $normal)->select('id'))
                ->orWhereIn('id', $redoIdsSub);
            });

        // ------------------ filters ------------------
        $raw = strtolower(preg_replace('/[^a-z]/', '', (string) $request->query('status', '')));

        $map = [
            'toassign'   => 'to_assign',
            'assigned'   => 'assigned',
            'inprogress' => 'in_progress',
            'completed'  => 'completed',
            'rejected'   => 'rejected',
            'awaitingkeyin'  => 'awaiting_keyin',
            'redo'          => 'redo',
        ];

        $query = clone $base;

        // existing status logic preserved
        if ($raw !== '') {
            if ($raw === 'pending') {
                $query->where('orderStatus', 'assigned')->where('pending', 1);
                if ($isHead) {
                    $query->whereRaw('1=0');
                }
            } else {
                $db = $map[$raw] ?? $raw;

                if ($db === 'redo') {                 // <-- NEW
                    // "Redo" means orders.status = 1
                    $query->where('status', 1);
                } elseif ($db === 'rejected') {
                    // Rejected ONLY; do NOT include redo
                    $query->where('orderStatus', 'rejected')
                        ->where(function ($w) {
                            $w->whereNull('status')->orWhere('status', 0);
                        });
                } else {
                    $query->where('orderStatus', $db);

                    // EXCLUDE archived/redone for the listed statuses
                    if (in_array($db, ['in_progress', 'completed', 'awaiting_keyin', 'to_assign', 'assigned'], true)) {
                        $query->where(function ($w) {
                            $w->whereNull('status')->orWhere('status', 0);
                        });
                    }

                    if (!$isHead && $db === 'in_progress') {
                        $query->where('artist_id', auth()->id())
                            ->where('pending', 0);
                    }
                }
            }
        }

        // -------- NEW: artist name filter --------
        if ($person = trim((string) $request->query('artist', ''))) {
            if ($isHead) {
                // Head-artist searches the assigned artist
                $query->whereHas('artist', function ($aq) use ($person) {
                    $aq->where('name', 'like', "%{$person}%");
                });
            } else {
                // Normal artist searches the salesperson instead
                $query->whereHas('salesperson', function ($sq) use ($person) {
                    $sq->where('name', 'like', "%{$person}%");
                });
            }
        }

        // -------- NEW: date range by deadline --------
        $from = $request->query('from');
        $to   = $request->query('to');
        if ($from && $to) {
            $query->whereBetween('deadline', [$from, $to]);
        } elseif ($from) {
            $query->whereDate('deadline', '>=', $from);
        } elseif ($to) {
            $query->whereDate('deadline', '<=', $to);
        }

        // -------- UPDATED: global search (also search products.productName) --------
        if ($s = trim($request->query('q', ''))) {
            $query->where(function ($q) use ($s) {
                $q->where('orderTitle',   'like', "%{$s}%")
                ->orWhere('order_number','like', "%{$s}%")
                ->orWhere('companyName','like', "%{$s}%")
                ->orWhere('leadName',   'like', "%{$s}%")
                ->orWhereHas('products', function ($p) use ($s) {
                    $p->where('productName', 'like', "%{$s}%");
                });
            });
        }

        // -------- Date range (Deadline filter using YYYY-MM-DD) --------
        $from = $request->query('from');
        $to   = $request->query('to');

        if ($from && $to) {
            $query->whereBetween(DB::raw('CAST(deadline AS DATE)'), [$from, $to]);
        } elseif ($from) {
            $query->whereDate('deadline', '>=', $from);
        } elseif ($to) {
            $query->whereDate('deadline', '<=', $to);
        }

        // -------- Deadline nearest / furthest --------
        if ($sort = $request->query('deadline_sort')) {
            $sort = in_array($sort, ['nearest', 'furthest']) ? $sort : 'nearest';
            // push NULL deadlines last
            $query->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END ASC');

            // distance from today
            $query->orderByRaw(
                'ABS(DATEDIFF(deadline, CURDATE())) ' . ($sort === 'furthest' ? 'DESC' : 'ASC')
            );
            // fallback for same distance
            $query->orderBy('deadline', $sort === 'furthest' ? 'DESC' : 'ASC');
        }

        // Metrics from the same base, excluding archived/redone (status=1)
        $statsBase = (clone $base)->where(function ($q) {
            $q->whereNull('status')->orWhere('status', 0);
        });

        $metrics = [
            'total'       => (clone $statsBase)->count(),
            'pending'     => (clone $statsBase)->where('orderStatus', 'assigned')->where('pending', 1)->count(),
            'in_progress' => (clone $statsBase)->where('orderStatus', 'in_progress')->count(),
            'completed'   => (clone $statsBase)->where('orderStatus', 'completed')->count(),
            'rejected'    => (clone $statsBase)->where('orderStatus', 'rejected')->count(),
            'awaiting_keyin'=> (clone $statsBase)->where('orderStatus', 'awaiting_keyin')->count(),
        ];
        if ($isHead) {
            $metrics['to_assign'] = (clone $statsBase)->where('orderStatus', 'to_assign')->count();
            $metrics['assigned']  = (clone $statsBase)->where('orderStatus', 'assigned')->count();
        }

        // Rows
        $orders = $query->with(['artist:id,name', 'salesperson:id,name'])
                        // keep your default when no deadline_sort was requested
                        ->when(!$request->filled('deadline_sort'), fn($q) => $q->latest('orderDate'))
                        ->paginate(1000)
                        ->withQueryString();

        $statusRaw = $raw;

        if ($request->ajax()) {
            // pass isHead in case your row buttons differ for heads
            return view('boss.partials.orders-table', compact('orders', 'isHead'))->render();
        }

        return view('boss.orders', compact('orders', 'metrics', 'isHead', 'statusRaw'));
    }

    public function showAssign(Order $order)
    {

        // Only head artists should be here (guard with middleware)
        $order->load([
            'salesperson:id,name',
            'artist:id,name',
            'products.deliveryBreakdowns',
            'dataEntry'
        ]);

        // Normal artists to assign to
        $artists = User::whereIn('role', ['artist', 'head-artist'])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('boss.orders.assign', compact('order', 'artists'));
    }

    public function storeAssign(Request $request, Order $order)
    {
        $data = $request->validate([
            'artist_id' => ['required', 'exists:users,id'],
        ]);

        $assignee = User::select('id', 'role')->find($data['artist_id']);

        if (!$assignee) {
            return back()->with('error', 'Selected artist not found.');
        }

        if ($assignee->role === 'head-artist') {
            $order->orderStatus = 'in_progress';
            $order->pending     = 0;
        } else {
            $order->orderStatus = 'assigned';
            $order->pending     = 1;
        }

        $order->artist_id   = $data['artist_id'];
        $order->save();

        return redirect()
        ->route('boss.dashboard')
        ->with('ok', "Order assigned to {$assignee->name} ({$assignee->role}) successfully.");
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
        $artistRoles = ['artist', 'head-artist', 'data-entry', 'boss'];

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

        return view('boss.orders.show', compact(
            'order',
            'attachments',
            'headerAttachments'
        ));
    }

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

        /**
         * A) If currently REJECTED and we enter Edit:
         *    Bring it back to work-in-progress, make it a draft, not submitted, not pending.
         */
        if (strtolower((string)$order->orderStatus) === 'rejected') {
            $order->orderStatus = 'in_progress';
            $order->submit      = 0;
            $order->draft       = 1;
            $order->pending     = 0;

            // ensure ownership to the editing artist (if not head-artist and artist not set)
            if (!$isHead && empty($order->artist_id)) {
                $order->artist_id = $user->id;
            }

            $order->save();
        }
        /**
         * B) Original behavior: if assigned + pending=1 and non-head artist opens Edit,
         *    flip to in_progress and clear pending.
         */
        elseif (!$isHead
            && strtolower((string)$order->orderStatus) === 'assigned'
            && (int)$order->pending === 1) {

            $order->orderStatus = 'in_progress';
            $order->pending     = 0;

            if (empty($order->artist_id)) {
                $order->artist_id = $user->id;
            }

            $order->save();
        }

        // Load relations AFTER any status/ownership changes
        $order->loadMissing([
            'products' => fn ($q) => $q->orderBy('ProductID'),
            'products.items' => fn ($q) => $q->orderBy('ItemID'),
            'products.deliveryBreakdowns' => fn ($q) => $q->orderBy('BreakdownID'),
            'artist:id,name',
            'attachments.user:id,name',
        ]);

        $orderCode = sprintf('ORD-%04d', $order->id);
        $today     = now()->format('M d, Y');

        $product = Product::with([
            'items.spec','deliveryBreakdowns' => function ($q) {
                // select only real columns – no "id" here
                $q->select('BreakdownID', 'ProductID', 'method', 'deliver_install_type', 'outsource_cost', 'location', 'quantity', 'date', 'time')
                ->orderBy('BreakdownID');
            },
            'remarks' => function ($q) {
                $q->with('user:id,name')
                ->orderByRaw("FIELD(operation,'printing','furnishing','installation','courier','self_pickup','artist')")
                ->orderBy('created_at')
                ->orderBy('RemarkID');
            },
        ])->where('OrderID', $order->id)->with(['remarks'])->first();

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
                pi.sizeWidth, pi.sizeHeight, pi.sizeUnit,
                pi.bleedTop, pi.bleedBottom, pi.bleedLeft, pi.bleedRight,
                pi.finishing, pi.material, pi.prime_centre,
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

        $materials = Material::where('active', 1)
            ->orderBy('materialName')
            ->get(['materialName']);

        $allMaterials = Material::where('active', 1)
            ->orderBy('materialName')
            ->pluck('materialName')
            ->values()
            ->all();
            
        $attachments = $this->getOrderAttachments($order);

        $toPublicUrl = function (string $p): string {
            $p = ltrim($p, '/');
            if (Str::startsWith($p, 'storage/')) {
                return url($p);
            }
            return Storage::disk('public')->url($p);
        };

        $attachmentRows = OrderAttachment::with('uploader:id,name,role')
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get();

        $orderFiles = $attachmentRows->map(function (OrderAttachment $att) use ($toPublicUrl) {
            $p = ltrim((string) $att->file_path, '/');
            $webPath = Str::startsWith($p, 'storage/') ? $p : 'storage/' . $p;

            return [
                'id'            => $att->id,
                'name'          => $att->original_name ?: basename($p),
                'ext'           => pathinfo($p, PATHINFO_EXTENSION),
                'url'           => $toPublicUrl($webPath),
                'path'          => $webPath,
                'size'          => $att->size,
                'uploaded_by'   => optional($att->uploader)->name,
                'uploaded_at'   => optional($att->created_at)->format('d M Y'),
                'uploader_role' => optional($att->uploader)->role,   // 👈 new
            ];
        });

        // 1) Lead attachments (read-only)
        $leadAttachments = LeadAttachment::where('lead_id', $order->lead_id)
            ->orderBy('id')
            ->get()
            ->map(function ($row) use ($toPublicUrl) {
                $p = ltrim((string)$row->file_location, '/');
                $p = preg_replace('#^public/#', '', $p);
                $p = preg_replace('#^storage/#', '', $p);
                $web = 'storage/'.$p;

                return (object) [
                    'name' => basename($p),
                    'size' => (int) $row->file_size,
                    'ext'  => $row->file_extension,
                    'url'  => $toPublicUrl($web),
                ];
            });

        // 2) Order attachments from order_attachments table
        $attachmentRows = \App\Models\OrderAttachment::with('uploader:id,name,role')
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get();

        // treat these as "artist side" uploads
        $artistRoles = ['artist', 'head-artist', 'data-entry', 'boss'];

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

        // Latest reason from report_redo for that order
        $redoReason = DB::table('report_redo')
            ->where('OrderID', $sourceOrderId)
            ->orderByDesc('created_at')
            ->value('reason');

        $artists = \App\Models\User::whereIn('role', ['artist', 'head-artist'])
        ->orderBy('name')
        ->get(['id', 'name', 'role']);

        $printerMachines = \App\Models\Machine::where('machine_type', 'printer')
            ->orderBy('machine_name')
            ->get(['id', 'machine_name']);

        $cutterMachines = \App\Models\Machine::where('machine_type', 'cutter')
            ->orderBy('machine_name')
            ->get(['id', 'machine_name']);

        $laminationMachines = \App\Models\Machine::where('machine_type', 'lamination')
            ->orderBy('machine_name')
            ->get(['id', 'machine_name']);

        return view('boss.orders.edit', compact(
            'order','orderCode','today','attachments','product','items', 'materials', 'allMaterials', 'deliveries', 'leadAttachments',
            'orderFiles','orderFilesSales', 'redoReason', 'artists', 'printerMachines', 'cutterMachines', 'laminationMachines'
        ));

        return view('boss.orders.edit', [
            'order'       => $order,
            'materials' => $materials,
            'product'     => $product,
            'deliveries'  => $deliveries,
            'isSubmitted'  => (int) ($order->submit ?? 0) === 1,
        ]);
    }

    public function dataEntryUsers(\Illuminate\Http\Request $request)
    {
        try {
            // Adjust role values to match your DB
            $roles = ['data-entry','data_entry','data entry','dataentry'];
            $users = \App\Models\User::query()
                ->whereIn('role', $roles)
                ->orderBy('name')
                ->get(['id','name'])
                ->map(fn($u) => ['id' => $u->id, 'name' => $u->name])
                ->values();

            return response()->json(['ok' => true, 'users' => $users]);
        } catch (\Throwable $e) {
            Log::error('dataEntryUsers failed: '.$e->getMessage());
            return response()->json(['ok' => false, 'message' => 'Unable to fetch users'], 500);
        }
    }

    public function passToDataEntry(\Illuminate\Http\Request $request, \App\Models\Order $order)
    {
        $user = auth()->user();

        // Only head-artist or the assigned artist can pass the order
        if (!$this->isHeadArtist($user) && (int) $order->artist_id !== (int) $user->id) {
            abort(403);
        }

        // Must be a data-entry user
        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('role', 'data-entry')),
            ],
        ]);

        $order->data_entry_id = (int) $validated['user_id'];
        $order->orderStatus   = 'awaiting_keyin'; // ✅ the new target status
        $order->draft         = 1;
        $order->submit        = 0;
        $order->pending       = 0;                // ✅ make sure it doesn't stay "pending"
        $order->save();

        /**
         * =======================
         *  NOTIFICATIONS
         * =======================
         */
        $actor      = $user;
        $actorName  = $actor->name;
        $actorRole  = str_replace('-', ' ', strtolower($actor->role));

        $orderId    = (int) $order->id;
        $orderNo    = (string) $order->order_number;
        $deadline   = $order->deadline
            ? \Carbon\Carbon::parse($order->deadline)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d')
            : '-';

        $productCount   = Product::where('OrderID', $orderId)->count();
        $dataEntryUser  = User::find((int)$order->data_entry_id);
        $dataEntryName  = $dataEntryUser?->name ?? 'Data Entry';

        // Messages
        $messageCommon = "Order {$orderNo} has been passed to Data Entry ({$dataEntryName}) by {$actorName} ({$actorRole}). "
                    . "{$productCount} Product(s). Deadline: {$deadline}.";

        $messageForDE  = "You have been passed an Order {$orderNo} for Data Entry by {$actorName} ({$actorRole}). "
                    . "{$productCount} Product(s). Deadline: {$deadline}, please take over.";

        // Role-aware URL
        $urlFor = function (User $u) use ($orderId) {
            return match (strtolower($u->role)) {
                'artist', 'head-artist'               => url("/artist/orders/{$orderId}"),
                'salesperson', 'head-salesperson'     => url("/orders/{$orderId}"),
                'admin'                               => url("/admin/orders/{$orderId}"),
                'boss'                                => url("/boss/orders/{$orderId}"),
                'data-entry'                          => url("/data-entry/orders/{$orderId}/edit"), // adjust if your route differs
                default                               => url("/"),
            };
        };

        // Build recipients (NO operation roles)
        $recipients = collect();

        // Core business roles
        $recipients = $recipients->merge(
            User::whereIn('role', ['head-artist','head-salesperson','admin','boss'])->get()
        );

        // Assigned salesperson (if any)
        if (!empty($order->salesperson_id)) {
            if ($sp = User::find($order->salesperson_id)) {
                $recipients->push($sp);
            }
        }

        // Assigned data-entry
        if ($dataEntryUser) {
            $recipients->push($dataEntryUser);
        }

        // De-duplicate by id
        $recipients = $recipients->unique('id')->values();

        // Send
        foreach ($recipients as $u) {
            $msg = ($dataEntryUser && $u->id === $dataEntryUser->id) ? $messageForDE : $messageCommon;
            Helpers::notify($u, $msg, $urlFor($u), ['database']);
        }

        return response()->json(['ok' => true]);
    }

    public function update(Request $request, Order $order, Product $product = null)
    {
        // ----- AuthZ -----
        $user = Auth::user();
        if (!$this->isHeadArtist($user) && $order->artist_id !== $user->id) {
            abort(403);
        }

        // ----- Validation -----
        $rules = [
            'design_confirmed'  => ['required','boolean'],
            'is_draft'          => ['required','in:0,1'],

            // header (product shown at the top)
            'product.name'      => ['nullable','string','max:255'],
            'product.qty_total' => ['nullable','integer','min:0'],
            'product.material'  => ['nullable','string','max:255'],

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
            'products.*.items.*.sizeUnit'       => ['nullable','in:mm,cm,inch,ft'],
            'products.*.items.*.bleedTop'       => ['nullable','numeric'],
            'products.*.items.*.bleedBottom'    => ['nullable','numeric'],
            'products.*.items.*.bleedLeft'      => ['nullable','numeric'],
            'products.*.items.*.bleedRight'     => ['nullable','numeric'],
            'products.*.items.*.bleedUnit'      => ['nullable','in:mm,cm,inch,ft'],
            'products.*.items.*.finishing'      => ['nullable','string','max:255'],
            'products.*.items.*.renderTime'     => ['nullable','integer','min:0'],
            'products.*.items.*.material'       => ['nullable'],
            'products.*.items.*.material.*'     => ['nullable','string','max:255'],
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
            'products.*.remarks'                => ['array'],
            'products.*.remarks.*.id'           => ['nullable','integer'],
            'products.*.remarks.*.operation'    => [
                'nullable',
                Rule::in(['printing','furnishing','installation','courier','self_pickup', 'artist']),
            ],            
            'products.*.remarks.*.remark'       => ['nullable','string'],
            'products.*.delete_remarks'         => ['array'],
            'products.*.delete_remarks.*'       => ['integer'],

            // attachments
            'attachments.*'                     => ['file','mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,ppt,pptx,ai,ps','max:20480'],
            'delete_attachments'                => ['array'],
            'delete_attachments.*'              => ['string'],
        ];

        $payload = $request->all();

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
            $authorId = (int) auth()->id();
            DB::transaction(function () use ($request, $order, $product, $authorId) {

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

                // ----- 3) “Header” product (the one shown at the top) -----
                $postedProducts = collect($request->input('products', []))->values();
                $hasPerProductHeader = $postedProducts->contains(function ($g) {
                    return is_array($g) && (
                        array_key_exists('name', $g) ||
                        array_key_exists('qty_total', $g) ||
                        array_key_exists('material', $g)
                    );
                });

                // ----- 3) “Header” product (only if no per-product header present) -----
                if (!$hasPerProductHeader) {
                    $hdr = (array) $request->input('product', []);
                    $productHdr = Product::where('OrderID', $order->id)->first()
                                ?: new Product(['OrderID' => $order->id]);

                    if (array_key_exists('name', $hdr))      $productHdr->productName    = $hdr['name'];
                    if (array_key_exists('qty_total', $hdr)) $productHdr->totalQuantity  = $hdr['qty_total'];
                    if (array_key_exists('material', $hdr))  $productHdr->materialRemark = $hdr['material'];
                    $productHdr->save();
                }

                // ----- 4) Per-product payload -----
                $postedProducts = collect($request->input('products', []))->values();

                foreach ($postedProducts as $group) {
                    $pid = (int) ($group['product_id'] ?? 0);
                    if (!$pid) continue;

                    $productRow = Product::where('OrderID', $order->id)
                                    ->where('ProductID', $pid)
                                    ->first();
                    if (!$productRow) continue;

                    // 1️⃣ Snapshot original acceptance flags directly from DB
                    $originalFlags = DB::table('products')
                        ->where('ProductID', $productRow->ProductID)
                        ->select('accepted', 'installation_accepted')
                        ->first();

                    // --- per-product header fields (safe) ---
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

                    collect($group['items'] ?? [])
                        ->filter(fn($row) => is_array($row))
                        ->each(function ($row) use ($productRow, &$keepItemIds) {

                            $isEmpty = collect($row)->except(['id','material'])
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

                            foreach ([
                                'itemName','quantity',
                                'sizeWidth','sizeHeight','sizeUnit',
                                'bleedTop','bleedBottom','bleedLeft','bleedRight', 'bleedUnit',
                                'finishing'
                            ] as $k) {
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

                            // prime_centre (boolean yes/no)
                            if (array_key_exists('prime_centre', $row)) {
                                $v = strtolower((string)$row['prime_centre']);
                                $item->prime_centre = in_array($v, ['1','true','on','yes'], true) ? 1 : 0;
                            }

                            $item->save();
                            $keepItemIds[] = $item->ItemID;

                            // ---- Spec (optional) ----
                            if (array_key_exists('lamination',$row) ||
                                array_key_exists('printer',$row)    ||
                                array_key_exists('cutter',$row) ) {     // <-- fixed function name
                                $spec = Specification::firstOrNew(['ItemID' => $item->ItemID]);
                                $spec->lamination = $row['lamination'] ?? null;
                                $spec->printer    = $row['printer']    ?? null;
                                $spec->cutter     = $row['cutter']     ?? null;
                                $spec->save();
                            }

                        });

                    // delete items not posted (including “all removed” case)
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
                        $row  = array_change_key_case($row, CASE_LOWER);

                        // split/normalize datetime
                        $date = $row['date'] ?? null;
                        $time = $row['time'] ?? null;
                        if ((!$date || !$time) && !empty($row['datetime'])) {
                            try {
                                $dt   = \Carbon\Carbon::parse($row['datetime']);
                                $date = $date ?: $dt->toDateString();
                                $time = $time ?: $dt->format('H:i:s');
                            } catch (\Throwable $e) {}
                        }

                        // normalize fields
                        $method      = trim((string)($row['method'] ?? ''));
                        $location    = trim((string)($row['location'] ?? ''));
                        $qty         = $row['quantity'] ?? null;

                        // NEW: installation type & cost (normalized)
                        $installType = strtolower(trim((string)($row['deliver_install_type'] ?? '')));
                        $installType = $installType !== '' ? $installType : null;

                        $costRaw = $row['outsource_cost'] ?? null;
                        $cost    = ($costRaw === '' || $costRaw === null) ? null : (float)$costRaw;

                        // empty row guard
                        if ($method === '' && $location === '' &&
                            ($qty === null || $qty === '') && !$date && !$time) {
                            return null;
                        }

                        return [
                            'id'                   => isset($row['id']) ? (int)$row['id'] : null,
                            'method'               => $method !== '' ? $method : null,
                            'location'             => $location !== '' ? $location : null,
                            'quantity'             => (int)($qty ?? 0),
                            'date'                 => $date ?: null,
                            'time'                 => $time ?: null,
                            // NEW keys that will actually be saved below
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
                                                    ? (float) $d['outsource_cost']
                                                    : null;
                        $bd->save();

                        $keepDeliveryIds[] = $bd->BreakdownID;
                    }

                    if (array_key_exists('deliveries', $group)) {                             
                        DeliveryBreakdown::where('ProductID', $productRow->ProductID)
                            ->when(count($keepDeliveryIds) > 0, fn($q) => $q->whereNotIn('BreakdownID', $keepDeliveryIds))
                            ->when(count($keepDeliveryIds) === 0, fn($q) => $q) 
                            ->delete();
                    }

                    // -------- Remarks (THIS product) --------
                    $keepRemarkIds = [];

                    foreach (collect($group['remarks'] ?? [])->filter(fn ($v) => is_array($v)) as $row) {
                        $op  = strtolower(trim((string)($row['operation'] ?? '')));
                        $txt = trim((string)($row['remark'] ?? ''));

                        // skip empty rows
                        if ($op === '' && $txt === '') {
                            continue;
                        }

                        // block "artist" when no artist assigned
                        if ($op === 'artist' && empty($order->artist_id)) {
                            continue;
                        }

                        // normalize to null / canonical values
                        $newOp  = $op ?: null;
                        $newTxt = $txt ?: null;

                        $remark = null;
                        if (!empty($row['id'])) {
                            $remark = ProductRemark::where('RemarkID', (int)$row['id'])
                                ->where('ProductID', $productRow->ProductID)
                                ->first();
                        }

                        if (!$remark) {
                            // NEW REMARK → set creator
                            $remark = new ProductRemark();
                            $remark->ProductID = $productRow->ProductID;
                            $remark->operation = $newOp;
                            $remark->remark    = $newTxt;
                            $remark->user_id   = $authorId;     // creator only on create
                            $remark->save();
                        } else {
                            // EXISTING REMARK → only change user_id if content changed
                            $dirty = false;

                            if ($remark->operation !== $newOp) {
                                $remark->operation = $newOp;
                                $dirty = true;
                            }
                            if ($remark->remark !== $newTxt) {
                                $remark->remark = $newTxt;
                                $dirty = true;
                            }

                            if ($dirty) {
                                // content changed → attribute the edit to current user
                                $remark->user_id = $authorId;
                                $remark->save();
                            }
                            // if not dirty, leave user_id (creator) untouched
                        }

                        $keepRemarkIds[] = $remark->RemarkID;
                    }

                    $toDelete = collect($group['delete_remarks'] ?? [])
                        ->merge($request->input('delete_remarks', []))
                        ->map(fn ($id) => (int)$id)
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
                    

                    // 2️⃣ Force-restore original accepted flags at the very end
                    if ($originalFlags) {
                        DB::table('products')
                            ->where('ProductID', $productRow->ProductID)
                            ->update([
                                'accepted'             => $originalFlags->accepted,
                                'installation_accepted'=> $originalFlags->installation_accepted,
                            ]);
                    }

                    $productRow->syncTaskTypeFromSpecs();
                } 
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
                $baseRoles = ['admin','boss','head-artist','head-salesperson'];
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
                if (strtolower((string)$actor->role) === 'artist') {
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
                        ['database'],
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

                            \App\Helpers\Helpers::notifyOnce($u, $msg, $url, ['database'], $opsKey);
                        });
                }
            }

            $message = $request->boolean('submit')
            ? 'Order submitted.'
            : ($request->input('is_draft') === '1' ? 'Draft saved.' : 'Order updated.');

            if ($request->expectsJson()) {
                return response()->json(['ok' => true, 'message' => $message]);
            }
            return redirect()
                ->route('boss.orders')
                ->with('success', $message);

        } catch (\Throwable $e) {
            Log::error('Artist update failed', [
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
        if (!$this->isHeadArtist($user) && $order->artist_id !== $user->id) {
            return response()->json(['ok' => false, 'message' => 'Forbidden'], 403);
        }

        // Load remark and ensure it belongs to a product of THIS order
        $row = ProductRemark::where('RemarkID', (int)$remark)->first();
        if (!$row) {
            return response()->json(['ok' => true]); // already gone, idempotent
        }

        // guard: remark’s product must belong to this order
        $belongs = Product::where('ProductID', $row->ProductID)
            ->where('OrderID', $order->id)
            ->exists();

        if (!$belongs) {
            return response()->json(['ok' => false, 'message' => 'Remark does not belong to this order'], 422);
        }

        $row->delete();
        return response()->json(['ok' => true]);
    }

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
        return $user && $user->role === 'boss';
    }

    private function getOrderAttachments(Order $order): array
    {
        return OrderAttachment::where('order_id', $order->id)
            ->orderBy('id')
            ->pluck('file_path')
            ->filter()
            ->values()
            ->all();
    }

    private function putOrderAttachments(Order $order, array $paths): void
    {
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

    // Upload endpoint (Dropzone)
    public function uploadAttachments(Request $request, Order $order)
    {
        $user = Auth::user();
        if (!$this->isHeadArtist($user) && $order->artist_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

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

    // Remove from order + delete file (optional)
    public function deleteAttachment(Request $request, Order $order)
    {
        $user = Auth::user();
        if (!$this->isHeadArtist($user) && $order->artist_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

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

    public function deleteDelivery(Request $request, Order $order, int $delivery)
    {
        $user = Auth::user();
        if (!$this->isHeadArtist($user) && $order->artist_id !== $user->id) {
            return response()->json(['ok' => false, 'message' => 'Forbidden'], 403);
        }

        // Get all product IDs for this order (covers multi-product orders)
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

    public function destroyAttachment(Request $request, Order $order)
    {
        // AuthZ as you already do elsewhere
        $user = $request->user();
        if (!$this->isHeadArtist($user) && $order->artist_id !== $user->id) {
            abort(403);
        }

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

    public function destroyProduct(Request $request, Order $order, Product $product)
    {
        $user = Auth::user();
        if (!$user || !($user->hasRole('artist') || $user->hasRole('head-artist') || $user->hasRole('boss'))) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'Unauthorized'], 401)
                : back()->with('error', 'Unauthorized');
        }

        // Ensure this product belongs to the given order
        // (your products table uses "OrderID" per earlier code)
        if ((int) $product->OrderID !== (int) $order->id) {
            // not found to avoid leaking existence
            abort(404);
        }

        // If artist (not head-artist), only allow deleting within own order
        if ($user->hasRole('artist') && (int) $order->artist_id !== (int) $user->id) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'Forbidden'], 403)
                : back()->with('error', 'Forbidden');
        }

        DB::transaction(function () use ($product) {
            // Clean up dependent rows you already use
            DB::table('product_remarks')->where('ProductID', $product->getKey())->delete();
            // If you have other child tables (deliveries, etc.), delete them here similarly.

            $product->delete();
        });

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'deleted_product_id' => $product->getKey()]);
        }

        return back()->with('success', 'Product deleted.');
    }

    public function searchLeads(\Illuminate\Http\Request $request)
    {
        $q = trim((string) $request->query('q', ''));   
        if ($q === '' || strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $leads = \App\Models\Lead::query()
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                ->orWhere('company_name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id','name','company_name','phone','email']);

        return response()->json([
            'results' => $leads->map(fn($l) => [
                'id'   => $l->id,
                'text' => $l->name,
                'meta' => [
                    'company_name' => $l->company_name,
                    'phone'        => $l->phone,
                    'email'        => $l->email,
                ],
            ]),
        ]);
    }

    public function getLead($id)
    {
        $lead = \App\Models\Lead::findOrFail($id);
        return response()->json([
            'id'            => $lead->id,
            'name'          => $lead->name,
            'company_name'  => $lead->company_name,
            'phone'         => $lead->phone,
            'email'         => $lead->email,
            'company_phone' => $lead->company_phone ?? '',
        ]);
    }

    public function create(?int $lead_id = null)
    {
        $lead = $lead_id ? \App\Models\Lead::find($lead_id) : null;

        // Try the new name first, fall back to the old file name if present
        return view()->first([
            'boss.orders.create',    
            'boss.orders.add-order',  
        ], compact('lead'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !($user->hasRole('artist') || $user->hasRole('head-artist') || $user->hasRole('boss'))) {
            return back()->with('error', 'Unauthorized');
        }

        try {
            $request->validate([
                'lead_id'     => 'nullable|exists:leads,id',
                'orderTitle'  => 'required|string|max:255',
                'deadline'    => 'required|date|after_or_equal:today',
                'approval'    => 'required|boolean',
                'orderDetail' => 'nullable|string',

                'products'                       => ['required','array','min:1'],
                'products.*.product_name'        => ['required','string','max:255'],
                'products.*.quantity'            => ['required','integer','min:1'],
                'products.*.material_info'       => ['required','string'],
                'products.*.remarks'             => ['nullable','array'],
                'products.*.remarks.*.operation' => [
                'required_with:products.*.remarks.*.remark',
                Rule::in(['printing','furnishing','installation','courier','self_pickup','artist']),
                ],
                'products.*.remarks.*.remark'    => ['required_with:products.*.remarks.*.operation','string'],

                'csv_file'    => 'nullable|file|mimes:csv,txt',
                'attachments' => 'required|array',
                'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,ppt,pptx,ai,ps|max:20480',
            ]);

            $lead = $request->filled('lead_id') ? Lead::find($request->lead_id) : null;

            $attachmentFiles = [];
            if ($request->hasFile('attachments')) {
                $attachmentFiles = $request->file('attachments');
                if (!is_array($attachmentFiles)) {
                    $attachmentFiles = [$attachmentFiles];
                }
            }

            // Create Order (as Artist)
            $order                    = new Order();
            $order->order_number      = $this->makeOrderNumber();
            $order->artist_id         = $user->id;                        
            $order->salesperson_id    = $lead->salesperson_id ?? null;
            $order->lead_id           = $lead->id           ?? null;
            $order->leadName          = $lead->name         ?? null;
            $order->leadPhone         = $lead->phone        ?? null;
            $order->leadEmail         = $lead->email        ?? null;
            $order->companyName       = $lead->company_name ?? null;

            $order->orderTitle        = $request->orderTitle;
            $order->orderDetail       = $request->orderDetail;
            $order->deadline          = $request->deadline;
            $order->approval          = (int) $request->approval;
            $order->orderDate         = now();

            // Default statuses for a new artist order
            $order->orderStatus = 'in_progress';
            $order->draft       = 1;
            $order->submit      = 0;
            $order->pending     = 0;
            $order->status      = 0;
            
            // Head-artist assigning to a normal artist
            $assignee = null;
            if (auth()->user()->hasRole('boss') && $request->filled('assignee_artist_id')) {
                $assigneeId        = (int) $request->input('assignee_artist_id');
                $order->artist_id  = $assigneeId;
                $assignee          = \App\Models\User::find($assigneeId);

                // Selected a normal artist → keep your previous behavior
                $order->orderStatus = 'assigned';
                $order->pending     = 1;
                
            }

            $order->save();

            // ----- Save attachments into order_attachments table -----
            $attachmentPaths = [];
            $attachmentFiles = $request->file('attachments', []);

            if ($attachmentFiles && !is_array($attachmentFiles)) {
                $attachmentFiles = [$attachmentFiles];
            }

            if (!empty($attachmentFiles)) {
                $dir = "orders/{$order->id}/attachments";

                foreach ($attachmentFiles as $file) {
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

                    // Store file
                    $path = $file->storeAs($dir, $candidate, 'public');

                    // For backward compatibility (comma-separated path list)
                    $attachmentPaths[] = $path;

                    // Insert into order_attachments
                    OrderAttachment::create([
                        'order_id'      => $order->id,
                        'user_id'       => $user->id,
                        'file_path'     => $path,
                        'original_name' => $orig,
                        'mime_type'     => $file->getClientMimeType(),
                        'size'          => $file->getSize(),
                    ]);
                }
            }
            
            // Collect products from form
            $productsData = $request->products;

            // If CSV uploaded, parse & append rows
            if ($request->hasFile('csv_file')) {
                $path        = $request->file('csv_file')->getPathname();
                $reader      = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                $spreadsheet = $reader->load($path);
                $rows        = $spreadsheet->getActiveSheet()->toArray();

                foreach ($rows as $idx => $row) {
                    if ($idx === 0) continue; 
                    $productsData[] = [
                        'product_name'  => $row[0] ?? '',
                        'quantity'      => (int)($row[1] ?? 0),
                        'remark'        => $row[2] ?? null,
                        'material_info' => $row[3] ?? null,
                        'location'      => $row[4] ?? null,
                        'date_time'     => $row[5] ?? null,
                    ];
                }
            }

            // Persist products
            $authorId = (int) auth()->id();
            foreach ($request->input('products', []) as $p) {
                if (empty($p['product_name']) || empty($p['quantity'])) {
                    continue;
                }

                // Save into products table (columns based on your screenshot)
                $product = \App\Models\Product::create([
                    'OrderID'       => $order->id,
                    'productName'   => $p['product_name'],
                    'totalQuantity' => (int) $p['quantity'],
                    'materialRemark'=> $p['material_info'] ?? null,
                ]);

                // Save remarks into product_remarks (use model if you have one; else DB::table)
                if (!empty($p['remarks']) && is_array($p['remarks'])) {
                    $rows = [];
                    $now  = now();
                    foreach ($p['remarks'] as $r) {
                        if (empty($r['operation']) || empty($r['remark'])) continue;
                        $rows[] = [
                            'ProductID'   => $product->getKey(), 
                            'operation'   => $r['operation'],   
                            'remark'      => $r['remark'],
                            'user_id'    => $authorId,
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ];
                    }
                    if ($rows) {
                        DB::table('product_remarks')->insert($rows);
                    }
                }
            }

            /**
             * =======================
             *  NOTIFICATIONS (NEW)
             * =======================
             */
            $actor     = $user;
            $actorName = $actor->name;
            $actorRole = str_replace('-', ' ', strtolower($actor->role));

            $productCount = DB::table('products')->where('OrderID', $order->id)->count();
            $deadlineTxt  = $order->deadline
                ? \Carbon\Carbon::parse($order->deadline)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d')
                : '-';

            // Base message for everyone
            $messageCommon = "New order {$order->order_number} created by {$actorName} ({$actorRole}). "
                        . "{$productCount} Product(s) added. Deadline: {$deadlineTxt}.";

            // Optional special message when head-artist assigns to a normal artist
            $messageForAssignee = null;
            if ($actor->hasRole('boss') && isset($assignee) && $assignee && $assignee->hasRole('artist')) {
                $messageForAssignee = "You have been **assigned** a new order {$order->order_number} by {$actorName} "
                                    . "(boss). {$productCount} Product(s) added. Deadline: {$deadlineTxt}.";
            }

            // Role-aware URL builder
            $urlFor = function (User $u) use ($order) {
                return match ($u->role) {
                    'artist'                          => url("/artist/orders/{$order->id}/edit"),
                    'head-artist'                     => url("/artist/orders/{$order->id}"),
                    'salesperson', 'head-salesperson' => url("/orders/{$order->id}"),
                    'admin', 'Admin'                  => url("/admin/orders/{$order->id}"),
                    'boss',  'Boss'                   => url("/boss/orders/{$order->id}"),
                    default                           => url("/"),
                };
            };

            // ======================
            // Build recipients list
            // ======================
            $recipients = collect();

            // 1) Always include these roles
            $baseRoles = ['admin', 'boss', 'head-artist', 'head-salesperson']; // head-salesperson is optional in your DB
            $recipients = $recipients->merge(
                User::whereIn('role', $baseRoles)->get()
            );

            // 2) Assigned salesperson (if any)
            if (!empty($order->salesperson_id)) {
                $recipients = $recipients->merge(
                    User::where('id', $order->salesperson_id)->get()
                );
            }

            // 3) If creator is a NORMAL artist, make sure head-artists are included (already in baseRoles, but keep this for clarity/safety)
            // if (strtolower($actor->role) === 'artist') {
            //     $recipients = $recipients->merge(
            //         User::where('role', 'head-artist')->get()
            //     );
            // }

            // 4) If creator is head-artist and assigned to a normal artist, include that assignee
            if (strtolower($actor->role) === 'boss' && isset($assignee) && $assignee && (strtolower($assignee->role) === 'artist' || strtolower($assignee->role) === 'head-artist')) {
                $recipients = $recipients->merge([$assignee]);
            }

            // De-duplicate on user id
            $recipients = $recipients->unique('id')->values();

            // Send notifications
            foreach ($recipients as $u) {
                $msg = ($messageForAssignee && isset($assignee) && $u->id === $assignee->id)
                    ? $messageForAssignee
                    : $messageCommon;

                Helpers::notify($u, $msg, $urlFor($u), ['database', 'mail']);
            }

            if (auth()->user()->hasRole('boss')) {
                if ($assignee && $assignee->hasRole('boss')) {
                    // Assigned to head-artist → go straight to edit
                    return redirect()
                        ->route('boss.orders.edit', $order->id)
                        ->with('success', 'Order created and assigned.');
                }
                // Others unchanged → go back to list
                return redirect()
                    ->route('boss.orders')
                    ->with('success', 'Order created and assigned.');
            }

            return redirect()->route('boss.orders.edit', $order->id)
                ->with('success', 'Order created successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Failed to create order.')->withInput();
        }
    }

    public function getOrders(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('boss')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $orders = Order::with('lead', 'salesperson', 'products')->orderBy('created_at', 'desc');

        if (!$user->hasRole('head-salesperson')) {
            $orders->where('salesperson_id', $user->id);
        } else {
            if ($request->has('salesperson') && $request->salesperson) {
                $orders->where('salesperson_id', $request->salesperson);
            }
        }

        if ($request->has('status') && $request->status) {
            $orders->where('orderStatus', $request->status);
        }

        if ($request->has('search') && $request->input('search')['value']) {
            $search = $request->input('search')['value'];
            $orders->where(function ($query) use ($search) {
                $query->where('orderTitle', 'like', "%{$search}%")
                    ->orWhere('order_number', 'like', "%{$search}%")
                    ->orWhereHas('lead', function ($q) use ($search) {
                        $q->where('company_name', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        return DataTables::of($orders)
            ->addColumn('order_id', function ($order) {
                return $order->order_number ?? $order->id;
            })
            ->addColumn('order_name', function ($order) {
                return $order->orderTitle;
            })
            ->addColumn('company_info', function ($order) {
                $lead = $order->lead;
                return '<div class="company-info-cell text-secondary">' .
                    '<div class="d-flex align-items-center mb-1"><i class="bx bxs-building me-2"></i>' . ($lead->company_name ?? 'N/A') . '</div>' .
                    '<div class="d-flex align-items-center mb-1"><i class="bx bxs-phone me-2"></i>' . ($lead->company_phone ?? 'N/A') . '</div>' .
                    '</div>';
            })
            ->addColumn('lead_details', function ($order) {
                $lead = $order->lead;
                $assignedTo = $order->salesperson?->name ?? 'Unassigned';
                return '<div class="lead-details-cell text-secondary">' .
                    '<div class="d-flex align-items-center mb-1"><i class="bx bxs-user me-2"></i>' . ($lead->name ?? 'N/A') . '</div>' .
                    '<div class="d-flex align-items-center mb-1"><i class="bx bxs-phone me-2"></i>' . ($lead->phone ?? 'N/A') . '</div>' .
                    '<div class="d-flex align-items-center mb-1"><i class="bx bx-envelope me-2"></i>' . ($lead->email ?? 'N/A') . '</div>' .
                    '<div class="d-flex align-items-center mb-1"><i class="bx bxs-id-card me-2"></i>Assigned To: ' . $assignedTo . '</div>' .
                    '</div>';
            })
            ->addColumn('status', function ($order) {
                $color = match ($order->orderStatus) {
                    'to_assign'   => 'danger',
                    'assigned'    => 'primary',
                    'pending'     => 'warning',
                    'in_progress' => 'info',
                    'completed'   => 'success',
                    'rejected'    => 'danger',
                    default       => 'secondary',
                };
                return '<span class="btn btn-sm btn-label-' . $color . '" 
                         style="white-space: nowrap; min-width:120px; text-align:center;">'
                    . ucwords(str_replace('_', ' ', $order->orderStatus)) .
                    '</span>';
            })
            ->addColumn('products', function ($order) {
                return '<button class="btn view-products" data-id="' . $order->id . '"  style="white-space: nowrap; min-width:120px; text-align:center;">
                            <span class="icon-base bx bxs-show me-2"></span>
                            View Products
                        </button>';
            })
            ->addColumn('actions', function ($order) {
                $editRoute = route('boss.orders.edit', $order->id);
                $leadViewRoute = route('boss.orders.show', $order->id);
                $html = '<div class="actions-cell d-flex gap-2">' .
                    '<a href="' . $editRoute . '" class="btn" title="Edit"><i class="bx bxs-edit me-2" style="font-size: 1.5em;"></i></a>' .
                    '<a href="' . $leadViewRoute . '" class="btn" title="View Lead"><i class="bx bxs-show me-2" style="font-size: 1.5em;"></i></a>';
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['company_info', 'lead_details', 'status', 'products', 'actions'])
            ->toJson();
    }

    public function csvTemplate()
    {
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=products_template.csv");

        $output = fopen("php://output", "w");

        // Add column headers
        $headers = ['Product_Name', 'Quantity', 'Material_Info', 'Printing_Remark', 'Furnishing_Remark', 'Installation_Remark', 'Courier_Remark', 'Self_Pickup_Remark'];
        fputcsv($output, $headers);

        // Generate example data with material info and up to 5 remarks, some empty
        $data = [
            ['Banner Print', 100, 'Vinyl 12oz', 'High resolution', '', '', 'Next day', ''],
            ['Flyer A5', 5000, 'Art Paper 128gsm', '', 'Glossy finish', '', '', ''],
            ['T-Shirt', 50, 'Cotton', 'Front print', '', 'Embroidery', '', ''],
            ['Poster A3', 200, 'Art Card 260gsm', '', '', '', 'Fragile', ''],
            ['Sticker Roll', 1000, 'PP Synthetic', '', '', '', '', 'Call ahead'],
            ['Name Card', 300, 'Art Card 310gsm', '', 'Double sided', '', '', ''],
            ['Booklet A4', 100, '80gsm Simili', 'Color print', '', '', 'Express', ''],
            ['Backdrop', 5, 'Tarpaulin', '', 'Sturdy frame', '', '', ''],
            ['Mug Print', 40, 'Ceramic', 'Heat resistant', '', '', '', ''],
            ['Cap Embroidery', 25, 'Polyester', '', 'Red thread', '', '', ''],
        ];

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    public function orderShow($id)
    {
        $order = Order::with('lead.attachments', 'salesperson', 'products', 'artist')->findOrFail($id);
        $attachments = $order->getAttachmentPathsAttribute()->map(function ($path) {
            return ['url' => Storage::url($path), 'name' => basename($path), 'size' => Storage::size($path)];
        });
        $leadAttachments = $order->lead ? $order->lead->attachments->map(function ($attachment) {
            return [
                'url' => asset('storage/' . $attachment->file_location),
                'name' => basename($attachment->file_location),
                'size' => $attachment->file_size
            ];
        }) : collect();
        return view('artist.orders.order-view', compact('order', 'attachments', 'leadAttachments'));
    }

    public function searchOrderArtists(\Illuminate\Http\Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $base = \App\Models\User::query()
            ->whereIn('role', ['artist', 'head-artist'])
            ->orderBy('name');

        if ($q !== '') {
            $base->where('name', 'like', "%{$q}%");
        }

        $users = $base->limit(100)->get(['id','name','role']);

        return response()->json([
            'results' => $users->map(fn($u) => [
                'id'   => $u->id,
                'text' => "{$u->name} ({$u->role})",
            ]),
        ]);
    }

    public function assign(Request $request, \App\Models\Order $order)
    {
        try {
            // Only head-artist can assign
            if (auth()->user()->role !== 'boss') {
                return response()->json(['ok' => false, 'message' => 'Forbidden'], 403);
            }

            // If you want to allow unassign (user_id = null), use nullable
            $validated = $request->validate([
                'user_id' => ['nullable','integer','exists:users,id'],
            ]);

            $assignee = isset($validated['user_id']) ? User::find($validated['user_id']) : null;

            // defaults
            $newStatus = 'to_assign';
            $pending   = 0;

            if ($assignee) {
                if ($assignee->role === 'head-artist') {
                    // assigning to a head-artist → they can start work immediately
                    $newStatus = 'in_progress';
                    $pending   = 0;
                } else {
                    // assigning to a normal artist → mark as pending until they pick up
                    $newStatus = 'assigned';
                    $pending   = 1;
                }
            } // else keep to_assign + pending=0 for unassign

            $order->artist_id   = $assignee?->id;   // allow unassign (null)
            $order->orderStatus = $newStatus;
            $order->pending     = $pending;
            $order->save();

            return response()->json([
                'ok'           => true,
                'artist_id'    => $order->artist_id,
                'orderStatus'  => $order->orderStatus,
                'pending'      => $order->pending,
                'assigneeRole' => $assignee?->role,
            ]);
        } catch (\Throwable $e) {
            // For bad input, 422 is more appropriate than 500
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Please select a valid artist or head artist to assign.',
                ], 422);
            }
            return back()->with('error', 'Failed to assign. Please try again.');
        }
    }

    public function storeProduct(Request $request, \App\Models\Order $order)
    {
        $user = auth()->user();
        if (!$user || !($user->hasRole('boss') )) {
            return back()->with('error', 'Unauthorized');
        }

        $request->validate([
            'product_name'        => ['nullable','string','max:255'],
            'quantity'            => ['nullable','integer','min:1'],
            'material_info'       => ['nullable','string'],
            'remarks'             => ['nullable','array'],
            'remarks.*.operation' => ['nullable','in:printing,furnishing,installation,courier,self_pickup,artist'],
            'remarks.*.remark'    => ['nullable','string'],
        ]);

        // Create product (columns match your products table)
        $product = \App\Models\Product::create([
            'OrderID'        => $order->id,
            'productName'    => $request->product_name,
            'totalQuantity'  => (int) $request->quantity,
            'materialRemark' => $request->material_info,
            // optional defaults that exist in your schema:
            'status'      => 'in_progress',
            'editable'    => 1,
        ]);

        // Optional: save product remarks if provided (product_remarks table)
        if ($request->filled('remarks') && is_array($request->remarks)) {
            $rows = [];
            $now  = now();
            $userId = auth()->id();
            foreach ($request->remarks as $r) {
                if (empty($r['operation']) || empty($r['remark'])) continue;
                $rows[] = [
                    'ProductID'  => $product->getKey(),
                    'user_id'    => $userId, 
                    'operation'  => $r['operation'],
                    'remark'     => $r['remark'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($rows) {
                DB::table('product_remarks')->insert($rows);
            }
        }

        // Redirect back to edit page so the new accordion block appears
        return redirect()
            ->back()
            ->with('success', 'Product added to order.');
    }

    public function redoCreate(Order $order)
    {
        // 1) Base/original order (even if you came from a redo)
        $baseOrder = $order->redo ? Order::findOrFail((int) $order->redo) : $order;

        // 2) Products to pick from the CURRENT order (as before)
        $products = Product::where('OrderID', $order->id)
            ->orderBy('ProductID')
            ->get(['ProductID','productName','totalQuantity','taskType']);

        // 3) Latest redo reason (same)
        $latestRedoReason = DB::table('report_redo')
            ->where('OrderID', $baseOrder->id)
            ->orderByDesc('created_at')
            ->value('reason');

        // 4) How many redo children exist for the BASE
        $existingCount = Order::where('redo', $baseOrder->id)->count();

        // 5) Build labels
        $baseNumber = $this->normalizeOrderNumber($baseOrder->order_number); // strip # and any trailing R/R1…
        $headerOrderNumber = '#' . $baseNumber . ($existingCount > 0 ? 'R' : ''); // show R only if has any redo already
        $nextRedoNumber    = '#' . $baseNumber . 'R'; // the id that will be created if user submits

        return view('boss.orders.redo', [
            'order'              => $order,
            'products'           => $products,
            'latestRedoReason'   => $latestRedoReason,
            'headerOrderNumber'  => $headerOrderNumber, // <-- use in page header
            'nextRedoNumber'     => $nextRedoNumber,    // <-- use in “What happens next?”
        ]);
    }


    public function redoStore(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason'     => ['required', 'string', 'max:255'],
            'reason_alt' => ['nullable', 'string', 'max:2000', 'required_if:reason,Others'],
            'products'   => ['nullable', 'array'],
            'products.*' => ['integer'],
        ]);

        $selectedCurrentIds = collect($validated['products'] ?? [])->filter()->unique()->values();

        // Build final reason text
        $reasonRaw = trim((string)($validated['reason'] ?? ''));
        $reasonAlt = trim((string)($validated['reason_alt'] ?? ''));
        $isOthers  = strcasecmp($reasonRaw, 'Others') === 0;
        $reasonText = $isOthers ? $reasonAlt : ($reasonAlt !== '' ? "{$reasonRaw}: {$reasonAlt}" : $reasonRaw);

        $actorId = auth()->id();

        DB::transaction(function () use ($order, $selectedCurrentIds, $reasonText, $actorId) {

            $baseId    = $order->redo ? (int) $order->redo : (int) $order->id;
            $baseOrder = $order->redo ? Order::findOrFail($baseId) : $order;
            $sourceOrder = $order;

            // 🔴 Archive ALL existing redos for this base so they’re hidden in lists
            Order::where('redo', $baseId)->update(['status' => 1]);

            // Count again (after archiving is fine too; count is only for numbering)
            $existingCount = Order::lockForUpdate()->where('redo', $baseId)->count();

            // Create a brand-new redo order
            $redoOrder = $baseOrder->replicate([
                'id','order_number','created_at','updated_at','submit','draft','status','redo','orderStatus'
            ]);
            $redoOrder->order_number = $this->normalizeOrderNumber($baseOrder->order_number);
            $redoOrder->redo         = $baseId;
            $redoOrder->draft        = 1;
            $redoOrder->submit       = 0;
            $redoOrder->orderStatus  = 'in_progress';
            $redoOrder->status       = 0;          // 🔵 keep the latest redo visible
            $redoOrder->data_entry_id       = null;          // 🔵 keep the latest redo visible
            $redoOrder->pending       = 0;          
            $redoOrder->created_at   = now();
            $redoOrder->updated_at   = now();

            // if ($reasonText !== '') {
            //     $redoOrder->orderDetail = trim(($sourceOrder->orderDetail ? $sourceOrder->orderDetail . "\n\n" : '') . "REDO Reason: " . $reasonText);
            // }
            $redoOrder->save();

            if ($reasonText !== '') {
                DB::table('report_redo')->insert([
                    'OrderID'    => $baseId,
                    'user_id'    => $actorId,
                    'reason'     => $reasonText,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            /**
             * =========================================
             *  🔁  DUPLICATE ORDER ATTACHMENTS
             *  from $sourceOrder -> $redoOrder
             * =========================================
             */
            $oldAttachments = OrderAttachment::where('order_id', $sourceOrder->id)->get();

            foreach ($oldAttachments as $att) {
                $oldPath = ltrim((string) $att->file_path, '/');     // e.g. orders/274/attachments/file.pdf
                $disk    = Storage::disk('public');

                if (!$disk->exists($oldPath)) {
                    // file missing, skip this row
                    continue;
                }

                $filename = basename($oldPath);
                $name     = pathinfo($filename, PATHINFO_FILENAME);
                $ext      = pathinfo($filename, PATHINFO_EXTENSION);

                $newDir  = "orders/{$redoOrder->id}/attachments";
                $candidate = $filename;
                $i = 1;

                // avoid collisions in the new folder
                while ($disk->exists("$newDir/$candidate")) {
                    $candidate = "{$name} ({$i}).{$ext}";
                    $i++;
                }

                $newPath = "$newDir/$candidate";

                // copy the physical file
                $disk->makeDirectory($newDir);
                $disk->copy($oldPath, $newPath);

                // insert new DB row pointing to the new file
                OrderAttachment::create([
                    'order_id'      => $redoOrder->id,
                    'user_id'       => $att->user_id,        // keep original uploader
                    'file_path'     => $newPath,             // e.g. orders/275/attachments/file.pdf
                    'original_name' => $att->original_name,
                    'mime_type'     => $att->mime_type,
                    'size'          => $att->size,
                ]);
            }
            // ===== END attachments clone =====

            // Duplicate products/items/specs/remarks/deliveries/progress from BASE
            $baseProducts = Product::with(['items.spec','remarks','deliveryBreakdowns'])
                ->where('OrderID', $sourceOrder->id)
                ->orderBy('ProductID')
                ->get();

            $selectedOriginIds = $selectedCurrentIds;
            // if ($selectedCurrentIds->isNotEmpty()) {
            //     $selectedOriginIds = Product::whereIn('ProductID', $selectedCurrentIds)
            //         ->pluck(DB::raw('COALESCE(redoOf, ProductID)'))
            //         ->unique()
            //         ->values();
            // }

            foreach ($baseProducts as $origin) {
                $originId = $origin->ProductID;
                $editable = $selectedOriginIds->contains($originId) ? 1 : 0;

                $np = $origin->replicate(['ProductID','OrderID','created_at','updated_at']);
                $np->OrderID    = $redoOrder->id;
                $np->redoOf     = $originId;
                $np->editable   = $editable;
                $np->created_at = now();
                $np->updated_at = now();
                if ($editable) {
                    $np->accepted = null;
                    $np->installation_accepted = null;
                    $np->installation_status   = null;
                }
                $np->save();

                foreach ($origin->items as $it) {
                    $ni = $it->replicate(['ItemID','ProductID','created_at','updated_at']);
                    $ni->ProductID  = $np->ProductID;
                    $ni->created_at = now();
                    $ni->updated_at = now();
                    $ni->save();

                    if ($it->spec) {
                        $ns = $it->spec->replicate(['SpecificationID','ItemID','created_at','updated_at']);
                        $ns->ItemID     = $ni->ItemID;
                        $ns->created_at = now();
                        $ns->updated_at = now();
                        $ns->save();
                    }
                }
                foreach ($origin->remarks as $rm) {
                    $nr = $rm->replicate(['RemarkID','ProductID','created_at','updated_at']);
                    $nr->ProductID  = $np->ProductID;
                    $nr->created_at = now();
                    $nr->updated_at = now();
                    $nr->save();
                }
                foreach ($origin->deliveryBreakdowns as $db) {
                    $nd = $db->replicate(['BreakdownID','ProductID','created_at','updated_at']);
                    $nd->ProductID  = $np->ProductID;
                    $nd->created_at = now();
                    $nd->updated_at = now();
                    $nd->save();
                }
                if ((int)$np->editable === 0) {
                    foreach ($origin->progress as $pg) {
                        $npgr = $pg->replicate(['ProgressID','ProductID','created_at','updated_at']);
                        $npgr->ProductID  = $np->ProductID;
                        $npgr->created_at = now();
                        $npgr->updated_at = now();
                        $npgr->save();
                    }
                }
            }

            // First redo on the base order → bump status (optional rule)
            if ($existingCount === 0 && $order->id === $baseOrder->id) {
                $order->forceFill(['status' => 1])->save();
            }
        });

        $actor      = auth()->user();
        $actorName  = $actor?->name ?? 'System';
        $actorRole  = str_replace('-', ' ', strtolower($actor?->role ?? 'user'));

        $baseId     = $order->redo ? (int)$order->redo : (int)$order->id;
        $baseOrder  = \App\Models\Order::find($baseId);
        $redoOrder  = \App\Models\Order::where('redo', $baseId)->latest('id')->first();

        $deadline   = $baseOrder?->deadline
            ? Carbon::parse($baseOrder->deadline)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d')
            : '-';

        $selectedCurrentIds  = collect($request->input('products', []))->filter()->unique()->values();
        $baseProductsQuery   = Product::with(['items.spec', 'remarks', 'deliveryBreakdowns', 'progress'])
            ->where('OrderID', $baseId);

        $allBaseProducts     = $baseProductsQuery->clone()->get(['ProductID', 'productName']);
        $affectedOriginIds   = $selectedCurrentIds->isNotEmpty()
            ? Product::whereIn('ProductID', $selectedCurrentIds)->pluck(DB::raw('COALESCE(redoOf, ProductID)'))->unique()
            : $allBaseProducts->pluck('ProductID');

        $affectedProducts    = $allBaseProducts->whereIn('ProductID', $affectedOriginIds);
        $productCount        = $affectedProducts->count();

        // ------- Business recipients (head-artist, head-salesperson, admin, boss) -------
        $businessRecipients = User::whereIn('role', ['head-artist', 'head-salesperson', 'admin', 'boss'])->get();

        // Role-aware order URL (point them to the redo order container)
        $orderUrlFor = function (User $u) use ($redoOrder, $baseOrder) {
            $orderId = $redoOrder?->id ?? $baseOrder?->id ?? 0;
            return match (strtolower($u->role)) {
                'artist', 'head-artist'            => url("/artist/orders/{$orderId}"),
                'salesperson', 'head-salesperson'  => url("/orders/{$orderId}"),
                'admin'                           => url("/admin/orders/{$orderId}"),
                'boss'                            => url("/boss/orders/{$orderId}"),
                default                           => url('/'),
            };
        };

        // One concise message for business roles
        // $reasonText   = trim((string)$request->input('reason','') . ' ' . (string)$request->input('reason_alt',''));
        $idsPreview   = $affectedProducts->pluck('ProductID')->take(5)->implode(', ');
        $orderNoBase  = (string)($baseOrder?->order_number ?? '');
        $orderNoRedo  = (string)($redoOrder?->order_number ?? '');

        $businessMsg = "Redo for Order {$orderNoBase} by {$actorName} ({$actorRole}). "
            . "{$productCount} product(s)"
            
            . ". Deadline: {$deadline}"
            . ($reasonText ? ". Reason: {$reasonText}" : ".");

        // Send to business recipients (deduped by id)
        $businessRecipients->unique('id')->each(function (User $u) use ($businessMsg, $orderUrlFor) {
            Helpers::notify($u, $businessMsg, $orderUrlFor($u), ['database']);
        });

        // ------- Assigned operations users (per affected product) -------
        $opsUsersById = collect();
        foreach ($affectedProducts as $p) {
            $candidateUserId = null;

            // Try common direct columns (adjust if your schema names differ)
            foreach (['assigned_user_id', 'operator_user_id', 'printing_user_id', 'furnishing_user_id'] as $col) {
                if (isset($p->{$col}) && $p->{$col}) {
                    $candidateUserId = (int)$p->{$col};
                    break;
                }
            }

            // Fallback: latest progress row with any of these columns
            if (!$candidateUserId && $p->relationLoaded('progress')) {
                $latest = $p->progress->sortByDesc('created_at')->first();
                if ($latest) {
                    foreach (['user_id', 'operator_id', 'assigned_to'] as $col) {
                        if (isset($latest->{$col}) && $latest->{$col}) {
                            $candidateUserId = (int)$latest->{$col};
                            break;
                        }
                    }
                }
            }

            if ($candidateUserId) {
                $opsUsersById[$candidateUserId] = array_values(array_unique(
                    array_merge($opsUsersById[$candidateUserId] ?? [], [(int)$p->ProductID])
                ));
            }
        }

        // Notify each assigned ops user exactly once, with the list of their product IDs.
        // URL is product-focused (first product for that user), mapped by the user's role.
        $mapOpsUrl = function (User $u, int $productId) {
            return match (strtolower($u->role)) {
                'operations-printing'               => url("/printing/jobs/{$productId}"),
                'operations-furnishing'             => url("/furnishing/jobs/{$productId}"),
                'operations-dispatch-control'       => url("/dispatchcontrol/job/{$productId}"),
                'operations-delivery-installation'  => url("/installation/job/{$productId}"),
                default                             => url("/"),
            };
        };

        foreach ($opsUsersById as $uid => $pids) {
            $opsUser = User::find($uid);
            if (!$opsUser) continue;

            sort($pids);
            $firstPid   = (int)($pids[0] ?? 0);
            $prodList   = implode(', ', array_slice($pids, 0, 5));
            $msgOps = "Redo requested in Order {$orderNoBase} by {$actorName} ({$actorRole}). "
                . "Your assigned product"
                . (count($pids) > 1 ? "s (IDs: {$prodList}) have" : " (ID: {$prodList}) has")
                . " been sent for redo. Deadline: {$deadline}.";

            Helpers::notify($opsUser, $msgOps, $mapOpsUrl($opsUser, $firstPid), ['database']);
        }

        return redirect()->route('boss.orders')->with('success', 'Redo updated.');
    }

    /**
     * Generate a redo order number:
     *  Try "<old>R"; if taken, "<old>R2", "<old>R3", ...
     */
    protected function normalizeOrderNumber(string $orderNo): string
    {
        $n = ltrim($orderNo, '#');
        return preg_replace('/R\d*$/i', '', $n);
    }
}
