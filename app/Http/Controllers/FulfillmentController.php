<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\DeliveryBreakdown;
use App\Models\LeadAttachment;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FulfillmentController extends Controller
{
public function index(Request $request)
{
    $user   = Auth::user();
    $userId = (int) $user->id;
    $isHead = (string)($user->role ?? '') === 'head-artist';

    // big limit so DT can paginate client-side if you use it
    $limit   = (int) $request->query('limit', 10000000000);

    $q       = trim((string) $request->query('q', ''));
    $task    = strtolower(trim((string) $request->query('task', '')));
    $status  = strtolower(trim((string) $request->query('status', '')));

    // NEW: extra filters (names consistent with your UI)
    $codeStr   = trim((string) $request->query('code', ''));     // product code search
    // Accept either "assignees" (dropdown name) or "assignee" (older param) from the request
    $assigneeParam = $request->query('assignees', $request->query('assignee', null));
    $assigneeId    = $assigneeParam !== null ? (int) $assigneeParam : 0;

    $dateFrom  = trim((string) $request->query('from', ''));     // YYYY-MM-DD
    $dateTo    = trim((string) $request->query('to', ''));       // YYYY-MM-DD
    $nearSort  = trim((string) $request->query('deliv_sort', ''));// nearest | furthest

    // ---------------- Base query you already have (UNCHANGED shape) ----------------
    $query = DB::table('products as p')
        ->join('orders as o', 'o.id', '=', 'p.OrderID')
        ->leftJoin('products as op', 'op.ProductID', '=', 'p.redoOf')
        ->leftJoin('orders   as oo', 'oo.id',        '=', 'op.OrderID')
        ->leftJoin('delivery_breakdowns as dd', 'dd.ProductID', '=', 'p.ProductID')
        ->where(function ($w) {
            $w->whereNull('o.status')->orWhere('o.status', 0);
        })
        ->where(function ($w) {
            $w->whereNull('o.orderStatus')
            ->orWhere('o.orderStatus', '!=', 'awaiting_keyin');
        })
        ->where(function ($w) {
            $w->where('p.editable', '=', 0)  // ✅ bypass check if editable = 0
            ->orWhere(function ($w2) {
                $w2->whereNull('o.orderStatus')
                    ->orWhere('o.orderStatus', '!=', 'in_progress');
            });
        })
        ->whereNotNull('p.taskType');

    // Permission: non head-artist only sees own orders
    if (!$isHead) {
        $query->where(function ($w) use ($userId) {
            $w->where('o.artist_id', $userId)
              ->orWhere('o.salesperson_id', $userId);
        });
    }

    // ---------------- ADD-ON filters (non-breaking) ----------------

    // (1) Free-text: product name + order title + company
    if ($q !== '') {
        $query->where(function ($w) use ($q) {
            $w->where('p.productName', 'like', "%{$q}%")
              ->orWhere('o.orderTitle',  'like', "%{$q}%")
              ->orWhere('o.companyName','like', "%{$q}%");
        });
    }

    // (2) Product Code search (digits-only fuzzy OR full #ORD-YYYY-XXXX-P0001[R])
    if ($codeStr !== '') {
        if (preg_match('/^\d+$/', $codeStr)) {
            // digits only → fuzzy match ProductID / redoOf / numeric order number part
            $query->where(function ($w) use ($codeStr) {
                $w->where('p.ProductID', 'like', "%{$codeStr}%")
                  ->orWhere('p.redoOf',   'like', "%{$codeStr}%")
                  ->orWhere(
                      DB::raw('REPLACE(REPLACE(COALESCE(oo.order_number, o.order_number), "ORD-", ""), "-", "")'),
                      'like',
                      "%{$codeStr}%"
                  );
            });
        } else {
            if (preg_match('/^#?(ORD-\d{4}-\d{3,4})-P0*([1-9]\d*)(R)?$/i', $codeStr, $m)) {
                $orderStr = strtoupper($m[1]);
                $pidNum   = (int) $m[2];

                if (preg_match('/^ORD-\d{4}-(\d{3,4})$/', $orderStr, $mm)) {
                    $last  = $mm[1];
                    $orderStrPad = sprintf(
                        'ORD-%s-%s',
                        substr($orderStr, 4, 4),
                        str_pad($last, 4, '0', STR_PAD_LEFT)
                    );
                } else {
                    $orderStrPad = $orderStr;
                }

                $query->where(function ($w) use ($orderStr, $orderStrPad, $pidNum) {
                    $w->where(function ($x) use ($orderStr, $orderStrPad) {
                          $x->where(DB::raw('UPPER(COALESCE(oo.order_number, o.order_number))'), $orderStr)
                            ->orWhere(DB::raw('UPPER(COALESCE(oo.order_number, o.order_number))'), $orderStrPad);
                      })
                      ->where(function ($x) use ($pidNum) {
                          $x->where('p.ProductID', '=', $pidNum)
                            ->orWhere('p.redoOf',   '=', $pidNum);
                      });
                });
            }
        }
    }

    // (3) Task filter:
    // Printing / Furnishing → match taskType directly (your original)
    // Dispatch Control      → DB status 'delivery'
    // Delivery & Installation → DB status 'installation'
    $taskNorm = strtolower(preg_replace('/\s+/', ' ', trim($task)));

    if ($taskNorm !== '') {
        // Printing / Furnishing → match taskType directly
        if (in_array($taskNorm, ['printing', 'furnishing'], true)) {
            $query->whereRaw('LOWER(p.taskType) = ?', [$taskNorm]);

        // Dispatch Control (dropdown key = "delivery")
        } elseif (in_array($taskNorm, ['delivery', 'dispatch control', 'dispatch_control'], true)) {
            // delivery products that do NOT have delivery_installation on this breakdown row
            $query->whereRaw('LOWER(p.taskType) = ?', ['delivery'])
                ->where(function ($w) {
                    $w->whereNull('dd.method')
                        ->orWhereRaw("LOWER(dd.method) <> 'delivery_installation'");
                });

        // Delivery & Installation (dropdown key = "installation")
        } elseif (in_array($taskNorm, ['installation', 'delivery & installation', 'delivery_installation'], true)) {
            // either pure installation products OR delivery rows whose method is delivery_installation
            $query->where(function ($w) {
                $w->whereRaw('LOWER(p.taskType) = ?', ['installation'])
                ->orWhere(function ($w2) {
                    $w2->whereRaw('LOWER(p.taskType) = ?', ['delivery'])
                        ->whereRaw("LOWER(dd.method) = 'delivery_installation'");
                });
            });

        } else {
            $query->whereRaw('LOWER(p.taskType) = ?', [$taskNorm]);
        }
    }

    // (4) Status filter (kept)
    if ($status !== '') {
        $query->whereRaw('LOWER(p.status) = ?', [$status]);
    }

    // (5) Artist dropdown filter → order belongs to this artist
    $assignees = DB::table('users')
        ->whereIn('role', ['artist', 'head-artist'])
        ->orderBy('name')
        ->select('id','name','role')
        ->get();

    if ($assigneeId > 0) {
        $query->where('o.artist_id', $assigneeId);
    }

    // (6) Delivery date range
    if ($dateFrom !== '' && $dateTo !== '') {
        $query->whereBetween('dd.date', [$dateFrom, $dateTo]);
    } elseif ($dateFrom !== '') {
        $query->whereDate('dd.date', '>=', $dateFrom);
    } elseif ($dateTo !== '') {
        $query->whereDate('dd.date', '<=', $dateTo);
    }

    // (7) Nearest / furthest by delivery date (NULLs last)
    if (in_array($nearSort, ['nearest','furthest'], true)) {
        $query->orderByRaw('CASE WHEN dd.date IS NULL THEN 1 ELSE 0 END ASC')
              ->orderByRaw('ABS(DATEDIFF(dd.date, CURDATE())) ' . ($nearSort === 'furthest' ? 'DESC' : 'ASC'))
              ->orderByRaw('COALESCE(dd.time, "23:59:59") ASC')
              ->orderBy('o.deadline', 'ASC');
    }

    // ---------------- Select / default order / pagination ----------------
    $query->select([
        'p.ProductID as pid',
        'p.redoOf',
        'p.editable',
        'p.productName as name',
        DB::raw('LOWER(p.taskType) as task'),
        'p.OrderID as order_id_current',
        DB::raw('COALESCE(oo.id, o.id) as order_id_for_display'),
        DB::raw("DATE_FORMAT(o.deadline, '%Y-%m-%d') as deadline"),
        DB::raw('LOWER(p.status) as status'),
        DB::raw("DATE_FORMAT(dd.date, '%Y-%m-%d') as deliv_date"),
        DB::raw("DATE_FORMAT(dd.time, '%H:%i')     as deliv_time"),
        'dd.location as deliv_loc',
        'dd.method as method',   // 👈 NEW: used to decide Delivery & Installation
        DB::raw('COALESCE(oo.order_number, o.order_number) as order_no_display'),
    ]);

    // default ordering if no explicit delivery sort requested
    if ($nearSort === '') {
        $query->orderByRaw("
            COALESCE(dd.date, '9999-12-31') ASC,
            COALESCE(dd.time, '23:59:59') ASC,
            o.deadline ASC
        ");
    }

    $paginator = $query->paginate($limit)->withQueryString();
    if ($paginator->isEmpty() && $paginator->currentPage() > 1) {
        $paginator = $query->paginate($limit, ['*'], 'page', 1)->withQueryString();
    }

    // Build display code and routes
    $rows = $paginator->through(function ($r) {
        $isRedo     = !is_null($r->redoOf);
        $isEditable = ((int) $r->editable === 1);

        // 🔹 Decide display task based on delivery method
        $rawTask = strtolower((string)($r->task ?? ''));
        $method  = strtolower((string)($r->method ?? ''));

        // If this is a delivery row whose method is delivery_installation,
        // treat it as "Delivery & Installation" for the UI
        if ($rawTask === 'delivery' && $method === 'delivery_installation') {
            $r->task = 'delivery_installation';
        }

        $orderNo = ltrim((string) $r->order_no_display, '#'); // e.g. ORD-2025-0044
        $pidForDisplay = $r->redoOf ?: $r->pid;
        $suffixR       = ($isRedo && $isEditable) ? 'R' : '';

        $r->code       = sprintf('#%s-P%04d%s', $orderNo, $pidForDisplay, $suffixR);

        $r->id         = $r->pid;
        $r->view_url   = route('artist.fulfillment.product.show',        $r->pid);
        $r->edit_url   = route('artist.orders.edit',        $r->order_id_current);
        $r->assign_url = route('artist.orders.redo.create', $r->order_id_current);

        return $r;
    });

    // Show DB values for taskType + aliases in the UI filter
    $taskTypes = ['printing','furnishing','installation','dispatch_control','delivery_installation'];

    $statuses = DB::table('products')
        ->whereNotNull('status')
        ->selectRaw('LOWER(status) as status')
        ->distinct()
        ->orderBy('status')
        ->pluck('status')
        ->toArray();

    if ($request->ajax()) {
        return view('artist.fulfillment._table', compact('rows'))->render();
    }

    return view('artist.fulfillment.index', [
        'rows'       => $rows,
        'taskTypes'  => $taskTypes,
        'statuses'   => $statuses,
        'assignees'  => $assignees,
        'filters'    => [
            'q'          => $q,
            'code'       => $codeStr,
            'task'       => $task,
            'status'     => $status,
            'assignees'  => $assigneeId,   // ← keep selected value for the dropdown
            'from'       => $dateFrom,
            'to'         => $dateTo,
            'deliv_sort' => $nearSort,
            'limit'      => $limit,
        ],
    ]);
}


    public function show(Request $request, Product $product)
    {
        $user  = $request->user();
        $order = $product->order()->first();

        // Eager load with extra fields needed on the page
        $product->load([
            'order:id,order_number,orderTitle,companyName,leadName,leadPhone,leadEmail,lead_id,deadline,created_at,artist_id,salesperson_id,orderAttachment',
            'order.artist:id,name',
            'order.salesperson:id,name',

            // add prime_centre; keep your existing columns
            'items' => fn($q) => $q->select(
                'ItemID',
                'ProductID',
                'itemName',
                'quantity',
                'sizeWidth',
                'sizeUnit',
                'sizeHeight',
                'bleedUnit',
                'bleedTop',
                'bleedBottom',
                'bleedLeft',
                'bleedRight',
                'finishing',
                'material',
                'prime_centre'                 // <-- NEW
            )->with('spec:SpecificationID,ItemID,printer,cutter,lamination'),

            // include the two new delivery fields
            'deliveryBreakdowns:BreakdownID,ProductID,method,location,quantity,date,time,deliver_install_type,outsource_cost',

            // remarks unchanged
            'remarks' => function ($q) {
                $q->select('RemarkID','ProductID','operation','remark','created_at','user_id')
                ->with('user:id,name');   // or ->with('author:id,name') if your relation is named 'author'
            },
        ]);

        $order = $product->order()->first();

        // ---------- Product/Order display code (handles redo R & original ids) ----------
        $isRedo     = !is_null($product->redoOf);
        $isEditable = (int)($product->editable ?? 0) === 1; // only selected redo shows "R"
        $showR      = $isRedo && $isEditable;

        // the product id to show (original for redo rows)
        $pidForDisplay = $isRedo ? (int)$product->redoOf : (int)$product->ProductID;

        // the order id to show (original order for redo rows, else current)
        $displayOrderId = (int)$product->OrderID;
        if ($isRedo) {
            $origOrderId = \App\Models\Product::where('ProductID', $product->redoOf)->value('OrderID');
            if ($origOrderId) {
                $displayOrderId = (int)$origOrderId;
            }
        }

        $displayOrderYear = null;
        if ($displayOrderId) {
            $ordDates = DB::table('orders')
                ->where('id', $displayOrderId)
                ->select(['orderDate', 'created_at'])
                ->first();

            $dateForYear = $ordDates->orderDate ?? $ordDates->created_at ?? now();
            $displayOrderYear = \Carbon\Carbon::parse($dateForYear)->format('Y');
        } else {
            $displayOrderYear = now()->format('Y');
        }

        $productCode = sprintf(
            '#ORD-%s-%04d-P%04d%s',
            $displayOrderYear,
            $displayOrderId,
            $pidForDisplay,
            $showR ? 'R' : ''
        );

        // ---------- Lead attachments ----------
        $toPublicUrl = function (string $p): string {
            $p = ltrim($p, '/');
            if (Str::startsWith($p, 'storage/')) {
                return url($p);
            }
            return Storage::disk('public')->url($p);
        };

        $leadAttachments = LeadAttachment::where('lead_id', $order->lead_id)
            ->orderBy('id')
            ->get()
            ->map(function ($row) {
                $p = $row->file_location;
                $p = ltrim($p, '/');
                $p = preg_replace('#^public/#', '', $p);
                $p = preg_replace('#^storage/#', '', $p);
                $web = 'storage/' . $p;

                return (object)[
                    'name' => basename($p),
                    'size' => (int) $row->file_size,
                    'ext'  => $row->file_extension,
                    'url'  => asset($web),
                ];
            });

        $raw   = $order->orderAttachment; // string|array|null
        $paths = [];
        if (is_array($raw)) {
            $paths = $raw;
        } elseif (is_string($raw)) {
            $rawTrim = trim($raw);
            if (Str::startsWith($rawTrim, '[')) {
                $paths = json_decode($rawTrim, true) ?: [];
            } else {
                $paths = array_filter(array_map('trim', explode(',', $rawTrim)));
            }
        }

        $orderFiles = collect($paths)->map(function ($p) use ($toPublicUrl) {
            $p   = ltrim($p, '/');
            $url = $toPublicUrl($p);
            return [
                'name' => basename($p),
                'ext'  => pathinfo($p, PATHINFO_EXTENSION),
                'url'  => $url,
            ];
        });

        // Attachments stored on order (keeps your helper if present)
        $attachments = [];
        if (method_exists($this, 'getOrderAttachments')) {
            $attachments = (array) $this->getOrderAttachments($order);
        } else {
            $raw = $order?->orderAttachment;
            if (is_string($raw) && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $attachments = $decoded;
                } else {
                    $attachments = [$raw];
                }
            } elseif (is_array($raw)) {
                $attachments = $raw;
            }
        }

        // ---------- Installation proof files ----------
        $installationProofs = DB::table('installation_proofs')
            ->where('ProductID', $product->ProductID)
            ->orderBy('created_at')
            ->get()
            ->map(function ($row) use ($toPublicUrl) {
                $path = (string) ($row->file_path ?? '');
                if ($path === '') {
                    return null;
                }

                $url  = $toPublicUrl($path);

                return (object) [
                    'id'   => $row->id,
                    'name' => $row->original_name ?: basename($path),
                    'url'  => $url,
                    'size' => (int) ($row->size ?? 0),
                    'mime' => $row->mime,
                ];
            })
            ->filter()
            ->values();

        // ---------- Fulfillment progress (use fulfillment_progress, fall back to products when in_progress) ----------
        $ALL_STAGES = ['printing', 'furnishing', 'delivery', 'installation'];

        // Pull all rows for this product and keep the latest per stage
        $rows = DB::table('fulfillment_progress')
            ->where('ProductID', $product->ProductID)
            ->whereIn('stage', $ALL_STAGES)
            ->get();

        $latest = []; // stage => ['status','acceptedAt','completedAt','_rank']
        foreach ($rows as $r) {
            $rank = $r->completedAt ?? $r->acceptedAt ?? $r->created_at;
            $cur  = $latest[$r->stage]['_rank'] ?? null;
            if (!$cur || $rank > $cur) {
                $latest[$r->stage] = [
                    'status'      => strtolower((string) $r->status),
                    'acceptedAt'  => $r->acceptedAt,
                    'completedAt' => $r->completedAt,
                    '_rank'       => $rank,
                ];
            }
        }

        // product's current stage/status (to force "in_progress" display if needed)
        $currentStage  = strtolower((string) $product->taskType);
        $currentStatus = strtolower((string) $product->status);

        // Determine which of the forked stages exists
        // $hasDelivery      = isset($latest['delivery']);
        // $hasInstallation  = isset($latest['installation']);

        // Helper to check completion
        $st = fn($k) => $latest[$k]['status'] ?? null;
        $isCompleted = fn($k) => ($latest[$k]['status'] ?? null) === 'completed';

        // For blanking earlier no-touch stages when a later stage is completed
        $laterCompleted = [
            'printing'     => $isCompleted('furnishing')
                            || $isCompleted('delivery')
                            || $isCompleted('installation'),
            'furnishing'   => $isCompleted('delivery') || $isCompleted('installation'),
            'delivery'     => false,
            'installation' => false,
        ];

        // Build what the Blade expects
        $progress = collect($ALL_STAGES)->mapWithKeys(function ($stage) use (
            $latest,
            $currentStage,
            $currentStatus,
            $laterCompleted
        ) {
            $row = $latest[$stage] ?? null;

            if ($row) {
                $status      = $row['status'];
                $acceptedAt  = $row['acceptedAt'];
                $completedAt = $row['completedAt'];
            } else {
                // No DB row for this stage
                // If a later stage is completed → BLANK; otherwise default to "pending"
                $status      = $laterCompleted[$stage] ? '' : 'pending';
                $acceptedAt  = null;
                $completedAt = null;
            }

            // If this is the product's current stage and it's in progress,
            // show in_progress (unless already completed/rejected)
            if (
                $currentStage === $stage &&
                $currentStatus === 'in_progress' &&
                !in_array($status, ['completed', 'rejected'], true)
            ) {
                $status = 'in_progress';
            }

            // Optional duration when both timestamps exist
            $duration = null;
            if ($acceptedAt && $completedAt) {
                $start    = \Carbon\Carbon::parse($acceptedAt);
                $end      = \Carbon\Carbon::parse($completedAt);
                $duration = $start->diffForHumans($end, [
                    'parts'  => 3,
                    'short'  => true,
                    'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
                ]);
            }

            return [
                $stage => [
                    'status'       => $status,      // '' means: render no pill
                    'accepted_at'  => $acceptedAt,
                    'completed_at' => $completedAt,
                    'duration'     => $duration,
                ],
            ];
        });

        // ---------- Deliveries for this product (sorted, nulls last) ----------
        $deliveries = $product->deliveryBreakdowns()
            ->orderByRaw('CASE WHEN `date` IS NULL THEN 1 ELSE 0 END, `date` ASC, `time` ASC')
            ->get();

        return view('artist.fulfillment.product-show', [
            // keep everything you already return
            'product'         => $product,
            'order'           => $order,
            'items'           => $product->items,
            'attachments'     => $attachments,
            'progress'        => $progress,     // now includes printing, furnishing, delivery, installation
            'deliveries'      => $deliveries,
            'leadAttachments' => $leadAttachments,
            'orderFiles'      => $orderFiles,

            // NEW (for header / code):
            'productCode'     => $productCode,
            'displayOrderId'  => $displayOrderId,
            'installationProofs' => $installationProofs,
        ]);
    }



    // Optional: hook your PDF later
    public function export(Product $product)
    {
        // generate a PDF and return download/stream
        abort(501, 'Export not implemented yet.');
    }

    public function fulfillmentCounts()
    {
        $user = Auth::user(); // head-artist => all, artist => own

        return response()->json([
            'totals'    => Product::fulfillmentCounts($user),
            'breakdown' => Product::fulfillmentBreakdown($user),
        ]);
    }
}
