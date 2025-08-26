<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\DeliveryBreakdown;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FulfillmentController extends Controller
{
    public function index(Request $request)
    {
        // --- Filters from UI
        $q      = trim($request->get('q', ''));        
        $task   = trim($request->get('task', ''));    
        $status = trim($request->get('status', '')); 

        // --- Base query: products with their order + delivery rows
        $products = Product::with([
            'order:id,orderTitle,deadline',
            'deliveryBreakdowns' => function ($q) {
                $q->orderBy('date')->orderBy('time');
            },
        ])
        ->select('products.*')
        // Compute nearest delivery datetime for ordering
        ->selectSub(
            DeliveryBreakdown::selectRaw('MIN(CONCAT(date, " ", COALESCE(time,"00:00:00")))')
                ->whereColumn('delivery_breakdowns.ProductID', 'products.ProductID')
                ->whereNotNull('date'),
            'next_at'
        )
        // (optional) text search / task / status filters you already apply...
        ->when($q !== '', function ($qry) use ($q) {
            $qry->where(function ($qq) use ($q) {
                $qq->where('productName', 'like', "%{$q}%")
                   ->orWhereHas('order', fn($oq) =>
                       $oq->where('orderTitle', 'like', "%{$q}%")
                         ->orWhere('id', $q) // allow searching by order id if you want
                   );
            });
        })
        ->when($task !== '', fn($qry) => $qry->where('taskType', $task))
        ->when($status !== '', fn($qry) => $qry->where('status', $status))
        // closest first; nulls last
        ->orderByRaw('COALESCE(next_at, "9999-12-31 23:59:59") ASC')
        ->paginate(1000)
        ->withQueryString();

        // Map to rows your table expects
        $rows = $products->flatMap(function (Product $p) {
            // Common product fields
            $base = [
                'id'       => $p->ProductID,
                'code'     => sprintf('#ORD-%s-P%s', optional($p->order)->id ?? '—', $p->ProductID),
                'name'     => $p->productName ?? '-',
                'task'     => $p->taskType ?? '-',
                'deadline' => optional($p->order?->deadline)->format('Y-m-d') ?? '-',
                'status'   => $p->status ?? 'in_progress',
                'show_url' => route('artist.orders.show', $p->OrderID),
                'edit_url' => route('artist.orders.edit', $p->OrderID),
                'assign_url' => route('artist.orders.show', $p->OrderID ?? 0),
            ];

            // If there ARE deliveries, return one row per delivery
            if ($p->deliveryBreakdowns->count()) {
                return $p->deliveryBreakdowns->map(function ($d) use ($base) {
                    $date = $d->date ? Carbon::parse($d->date)->format('Y-m-d') : null;
                    $time = $d->time ? substr($d->time, 0, 5) : null;
                    return (object) array_merge($base, [
                        'deliv_date' => $date,
                        'deliv_time' => $time,
                        'deliv_loc'  => $d->location ?: null,
                        'sort_at'    => $d->date ? ($d->date . ' ' . ($d->time ?: '00:00:00')) : null,
                    ]);
                });
            }

            // Otherwise, one placeholder row for this product (no deliveries)
            return collect([(object) array_merge($base, [
                'deliv_date' => null,
                'deliv_time' => null,
                'deliv_loc'  => null,
                'sort_at'    => null,
            ])]);
        })
        // closest dated rows first; nulls last
        ->sortBy(fn($r) => $r->sort_at ?? '9999-12-31 23:59:59')
        ->values();

        if ($request->ajax() && $request->boolean('partial')) {
            return view('artist.fulfillment._table', [
                'rows'     => $rows,
                'products' => $products, // keep if you use it for pagination
            ]);
        }

        return view('artist.fulfillment.index', [
            'rows'       => $rows,
            'products'   => $products, // paginator for links
            'taskTypes'  => ['printing','furnishing','installation','delivery'], // or from config
            'statuses'   => ['in_progress','completed'],
            'filters'    => [
                'q'      => $request->get('q',''),
                'task'   => $request->get('task',''),
                'status' => $request->get('status',''),
            ],
        ]);
    }

    public function show(Request $request, Product $product)
    {
        $user = $request->user();
        $order = $product->order()->first();

        $product->load([
            // Order + people
            'order:id,order_number,orderTitle,companyName,leadName,leadPhone,leadEmail,deadline,created_at,artist_id,salesperson_id,orderAttachment',
            'order.artist:id,name',
            'order.salesperson:id,name',

            'items' => function ($q) {
                $q->select('ItemID','ProductID','itemName','quantity',
                        'sizeWidth','sizeHeight','sizeLength',
                        'bleedTop','bleedBottom','bleedLeft','bleedRight',
                        'finishing','material')
                ->with('spec:SpecificationID,ItemID,printer,cutter,lamination'); 
                // <-- no "id" here, use SpecID as PK
            },

            // Delivery rows (their PK is BreakdownID)
            'deliveryBreakdowns',
        ]);

        $order = $product->order;

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

        $taskTypes = ['printing','furnishing','installation','delivery'];
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

        $vm = [
            'created_by' => $order?->salesperson?->name ?? '-',  // ① Created by
            'artist'     => $order?->artist?->name ?? '-',       // ③ Artist
            'attachments'=> $attachments,                        // ⑤ Order attachments list (array of storage paths/urls)
            // keep the rest raw so your partials can iterate easily
            'product'    => $product,
            'order'      => $order,
            'items'      => $product->items,
            'breakdowns' => $product->deliveryBreakdowns,
        ];

        return view('artist.fulfillment.product-show', [
            'product'     => $product,
            'order'       => $order,
            'items'      => $product->items,
            'attachments' => $attachments,   // your existing attachments array
            'progress'    => $progress,      // ← new
            'deliveries'  => $deliveries,    // ← new
        ]);
    }

    // Optional: hook your PDF later
    public function export(Product $product)
    {
        // generate a PDF and return download/stream
        abort(501, 'Export not implemented yet.');
    }
}
