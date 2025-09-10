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
        if ($status !== '') $query->whereRaw('LOWER(o.orderStatus) = ?', [$status]);

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
            DB::raw('LOWER(o.orderStatus) as status'),
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
        $statuses  = ['in_progress','completed','pending','rejected'];

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
        $user = $request->user();
        $order = $product->order()->first();

        $product->load([
            'order:id,order_number,orderTitle,companyName,leadName,leadPhone,leadEmail,lead_id,deadline,created_at,artist_id,salesperson_id,orderAttachment',
            'order.artist:id,name',
            'order.salesperson:id,name',
            'items' => fn($q) => $q->select('ItemID', 'ProductID', 'itemName', 'quantity', 'sizeWidth', 'sizeUnit', 'sizeHeight', 'bleedUnit', 'bleedTop', 'bleedBottom', 'bleedLeft', 'bleedRight', 'finishing', 'material')
                ->with('spec:SpecificationID,ItemID,printer,cutter,lamination'),
            'deliveryBreakdowns:BreakdownID,ProductID,method,location,quantity,date,time',
            // IMPORTANT: include FK + PK in the select
            'remarks:RemarkID,ProductID,operation,remark,created_at',
        ]);

        $order = $product->order()->first();

        $toPublicUrl = function (string $p): string {
            $p = ltrim($p, '/');

            // If DB already stores '/storage/...', use it as-is.
            if (Str::startsWith($p, 'storage/')) {
                return url($p); // -> https://site.test/storage/...
            }

            // Otherwise it’s a disk-relative path (e.g. 'orders/14/attachments/foo.pdf')
            return Storage::disk('public')->url($p);
        };

        // ---------- 1) Lead attachments ----------
        $leadAttachments = LeadAttachment::where('lead_id', $order->lead_id)
            ->orderBy('id')
            ->get()
            ->map(function ($row) {
                $p = $row->file_location;

                $p = ltrim($p, '/');
                $p = preg_replace('#^public/#', '', $p);
                $p = preg_replace('#^storage/#', '', $p); // now we only keep relative part
                $web = 'storage/' . $p;                     // final public URL

                return (object)[
                    'name' => basename($p),
                    'size' => (int) $row->file_size,
                    'ext'  => $row->file_extension,
                    'url'  => asset($web),
                ];
            });

        $raw = $order->orderAttachment; // string|array|null
        $paths = [];

        if (is_array($raw)) {
            $paths = $raw; // JSON casted
        } elseif (is_string($raw)) {
            $rawTrim = trim($raw);
            if (Str::startsWith($rawTrim, '[')) {
                $paths = json_decode($rawTrim, true) ?: [];
            } else {
                // comma-separated
                $paths = array_filter(array_map('trim', explode(',', $rawTrim)));
            }
        }

        $orderFiles = collect($paths)->map(function ($p) use ($toPublicUrl) {
            $p = ltrim($p, '/');               // normalize
            $url = $toPublicUrl($p);
            return [
                'name' => basename($p),
                'ext'  => pathinfo($p, PATHINFO_EXTENSION),
                'url'  => $url,
            ];
        });

        // Attachments are stored on order (you already have helpers)
        $attachments = [];
        if (method_exists($this, 'getOrderAttachments')) {
            // returns array of storage paths (your existing helper)
            $attachments = (array) $this->getOrderAttachments($order);
        } else {
            $raw = $order?->orderAttachment;
            if (is_string($raw) && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $attachments = $decoded;
                } else {
                    // if a single path/string was stored
                    $attachments = [$raw];
                }
            } elseif (is_array($raw)) {
                $attachments = $raw;
            }
        }

        $taskTypes = ['printing', 'furnishing', 'installation', 'delivery'];
        $progress = collect($taskTypes)->mapWithKeys(function ($t) use ($product) {
            $isThisTask = strtolower($product->taskType ?? '') === $t;
            $status     = $isThisTask ? (strtolower($product->status ?? 'in_progress')) : 'pending';

            return [
                $t => [
                    'status'       => $status,   // 'in_progress' | 'completed' | 'pending' | 'rejected'
                    'accepted_at'  => null,      // fill if you later track them
                    'completed_at' => null,
                    'duration'     => null,
                ],
            ];
        });

        // Deliveries for this product (sorted, nulls last)
        $deliveries = $product->deliveryBreakdowns()
            ->orderByRaw('CASE WHEN `date` IS NULL THEN 1 ELSE 0 END, `date` ASC, `time` ASC')
            ->get();

        return view('artist.fulfillment.product-show', [
            'product'     => $product,
            'order'       => $order,
            'items'      => $product->items,
            'attachments' => $attachments,   // your existing attachments array
            'progress'    => $progress,      // ← new
            'deliveries'  => $deliveries,    // ← new
            'leadAttachments'  => $leadAttachments,
            'orderFiles' => $orderFiles,
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
        return response()->json([
            'totals'    => Product::fulfillmentCounts(),
            'breakdown' => Product::fulfillmentBreakdown(),
        ]);
    }
}
