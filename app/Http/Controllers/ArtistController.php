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
use App\Helpers\Helpers;
use App\Notifications\GenericNotification;
use App\Models\OrderAttachment;

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

        $statsBase = (clone $base)->where(function ($q) {
            $q->whereNull('status')->orWhere('status', 0);
        });

        // 4) Metrics from the same base visibility
        $metrics = [
            'total'       => (clone $statsBase)->count(),
            'pending'     => (clone $statsBase)->where('orderStatus', 'assigned')->where('pending', 1)->count(),
            'in_progress' => (clone $statsBase)->where('orderStatus', 'in_progress')->count(),
            'completed'   => (clone $statsBase)->where('orderStatus', 'completed')->count(),
            'rejected'    => (clone $statsBase)->where('orderStatus', 'rejected')->count(),
        ];

        if ($isHead) {
            $metrics['to_assign'] = (clone $statsBase)->where('orderStatus', 'to_assign')->count();
            $metrics['assigned']  = (clone $statsBase)->where('orderStatus', 'assigned')->count();
        }

        // 5) Rows
        $ordersForTop5 = $this->visibleOrders()
            ->where(function ($q) {  
                $q->whereNull('status')->orWhere('status', 0);
            })
            ->with(['artist:id,name', 'salesperson:id,name', 'originalOrder:id,order_number'])
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END, deadline DESC')
            ->take(50)
            ->get();

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

        $status = $request->query('status');
        $allowed = ['to_assign','assigned','in_progress','completed','rejected'];
        if ($status && in_array($status, $allowed, true)) {
            $query->where('orderStatus', $status);
        }

        return view('artist.dashboard', [
            'orders'        => $ordersForTop5,
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
            return view('artist.partials.orders-table', compact('orders', 'isHead'))->render();
        }

        return view('artist.orders', compact('orders', 'metrics', 'isHead', 'statusRaw'));
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

        return view('artist.orders.assign', compact('order', 'artists'));
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
        ->route('artist.dashboard')
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
        $artistRoles = ['artist', 'head-artist', 'data-entry', 'boss', 'admin'];

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

        return view('artist.orders.show', compact(
            'order',
            'attachments',
            'headerAttachments'
        ));
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
        $artistRoles = ['artist', 'head-artist', 'data-entry', 'boss', 'admin'];

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

        return view('artist.orders.edit', compact(
            'order','orderCode','today','attachments','product','items', 'materials', 'allMaterials', 'deliveries', 'leadAttachments',
            'orderFiles','orderFilesSales', 'redoReason', 'artists', 'printerMachines', 'cutterMachines', 'laminationMachines'
        ));

        return view('artist.orders.edit', [
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

                // Explicitly treat AJAX / JSON requests as API and always return JSON
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
                ->route('artist.orders')
                ->with('success', $message);

        } catch (\Throwable $e) {
            Log::error('Artist update failed', [
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
        return $user && $user->role === 'head-artist';
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

    public function ProfileShow(Request $request)
    {
        $user = $request->user();
        return view('artist.profile.show', compact('user'));
    }

    public function ProfileEdit(Request $request)
    {
        $user = $request->user();
        return view('artist.profile.edit', compact('user'));
    }

    public function ProfileUpdate(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'           => ['required','string','max:255'],
            'email'          => ['required','email','max:255'],
            'contact_number' => ['nullable','string','max:30'],

            // Password section (optional)
            // If 'password' is present, 'current_password' must match the logged-in user
            'current_password' => ['nullable','required_with:password','current_password'],
            'password'         => ['nullable', Password::min(8)->mixedCase()->numbers()->symbols(), 'confirmed'],
        ]);

        // Update profile fields
        $user->fill([
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'contact_number' => $validated['contact_number'] ?? null,
        ]);

        // Update password if provided
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Profile updated.');
    }

    public function destroyProduct(Request $request, Order $order, Product $product)
    {
        $user = Auth::user();
        if (!$user || !($user->hasRole('artist') || $user->hasRole('head-artist'))) {
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
}
