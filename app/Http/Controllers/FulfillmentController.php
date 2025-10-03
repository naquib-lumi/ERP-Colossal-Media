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

        // ✅ Head Artist = role 'head-artist'
        $isHead = (string)($user->role ?? '') === 'head-artist';

        $limit  = (int) $request->query('limit', 100);
        $q      = trim((string) $request->query('q', ''));
        $task   = strtolower(trim((string) $request->query('task', '')));
        $status = strtolower(trim((string) $request->query('status', '')));

        // Base query (active orders only)
        $query = DB::table('products as p')
            ->join('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('products as op', 'op.ProductID', '=', 'p.redoOf')
            ->leftJoin('orders   as oo', 'oo.id',        '=', 'op.OrderID')
            ->leftJoin('delivery_breakdowns as dd', 'dd.ProductID', '=', 'p.ProductID')
            ->where(function ($w) { $w->whereNull('o.status')->orWhere('o.status', 0); });

        // Permission: Head Artist sees ALL. Others only their own orders.
        if (!$isHead) {
            $query->where(function ($w) use ($userId) {
                $w->where('o.artist_id', $userId)
                ->orWhere('o.salesperson_id', $userId);
            });
        }

        // Search / filters
        if ($q !== '')      $query->where('p.productName', 'like', "%{$q}%");
        if ($task !== '')   $query->whereRaw('LOWER(p.taskType) = ?', [$task]);
        if ($status !== '') $query->whereRaw('LOWER(p.status) = ?', [$status]);

        // Select fields
        $query->select([
            'p.ProductID as pid',
            'p.redoOf',                                   // redo origin (0/null if original)
            'p.editable',                                 // 1 only for selected redo products
            'p.productName as name',
            DB::raw('LOWER(p.taskType) as task'),
            'p.OrderID as order_id_current',              // for edit/report links
            DB::raw('COALESCE(oo.id, o.id) as order_id_for_display'),
            DB::raw("DATE_FORMAT(o.deadline, '%Y-%m-%d') as deadline"),
            DB::raw('LOWER(p.status) as status'),
            DB::raw("DATE_FORMAT(dd.date, '%Y-%m-%d') as deliv_date"),
            DB::raw("DATE_FORMAT(dd.time, '%H:%i')     as deliv_time"),
            'dd.location as deliv_loc',
        ]);

        // Default sort: closest delivery first (nulls last), then order deadline
        $query->orderByRaw("
            COALESCE(dd.date, '9999-12-31') ASC,
            COALESCE(dd.time, '23:59:59') ASC,
            o.deadline ASC
        ");

        // Paginate + keep filters; reset to page 1 if user is on a stale page
        $paginator = $query->paginate($limit)->withQueryString();
        if ($paginator->isEmpty() && $paginator->currentPage() > 1) {
            $paginator = $query->paginate($limit, ['*'], 'page', 1)->withQueryString();
        }

        // Build display code; R only when (redo && editable)
        $rows = $paginator->through(function ($r) {
            $isRedo     = !is_null($r->redoOf);
            $isEditable = ((int) $r->editable === 1);

            $pidForDisplay = $r->redoOf ?: $r->pid;             // show original PID if redo, else own PID
            $oidForDisplay = $r->order_id_for_display;          // original order id if redo, else current
            $suffixR       = ($isRedo && $isEditable) ? 'R' : '';

            $r->code       = sprintf('#ORD-%s-P%04d%s', $oidForDisplay, $pidForDisplay, $suffixR);
            $r->id         = $r->pid;
            $r->edit_url   = route('artist.orders.edit',        $r->order_id_current);
            $r->assign_url = route('artist.orders.redo.create', $r->order_id_current);
            return $r;
        });

        $taskTypes = ['printing','furnishing','installation'];
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
            'rows'      => $rows,
            'taskTypes' => $taskTypes,
            'statuses'  => $statuses,
            'filters'   => [
                'q'      => $q,
                'task'   => $task,
                'status' => $status,
                'limit'  => $limit,
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
            'ItemID','ProductID','itemName','quantity',
            'sizeWidth','sizeUnit','sizeHeight',
            'bleedUnit','bleedTop','bleedBottom','bleedLeft','bleedRight',
            'finishing','material',
            'prime_centre'                 // <-- NEW
        )->with('spec:SpecificationID,ItemID,printer,cutter,lamination'),

        // include the two new delivery fields
        'deliveryBreakdowns:BreakdownID,ProductID,method,location,quantity,date,time,deliver_install_type,outsource_cost',

        // remarks unchanged
        'remarks:RemarkID,ProductID,operation,remark,created_at',
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

    $productCode = sprintf('#ORD-%s-P%04d%s', $displayOrderId, $pidForDisplay, $showR ? 'R' : '');

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

    // ---------- Progress: mark previous steps as 'completed', current as 'in_progress' ----------
    $steps = ['printing','furnishing','installation']; // delivery can be treated outside the 3-step flow
    $current = strtolower((string) $product->taskType);
    $currentIdx = array_search($current, $steps, true);

    $progress = collect($steps)->mapWithKeys(function ($t, $idx) use ($currentIdx) {
        $state = 'pending';
        if ($currentIdx !== false) {
            if ($idx < $currentIdx)       $state = 'completed';
            elseif ($idx === $currentIdx) $state = 'in_progress';
        }
        return [$t => [
            'status'       => $state,
            'accepted_at'  => null,
            'completed_at' => null,
            'duration'     => null,
        ]];
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
        'progress'        => $progress,
        'deliveries'      => $deliveries,
        'leadAttachments' => $leadAttachments,
        'orderFiles'      => $orderFiles,

        // NEW (for header / code):
        'productCode'     => $productCode,
        'displayOrderId'  => $displayOrderId,
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
