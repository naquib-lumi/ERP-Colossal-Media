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
            'order:id,orderTitle,order_number,companyName,leadName,leadPhone,leadEmail,deadline,created_at',
            'items',            // or list the columns you want
            'items.spec',       // <-- use the correct relation name
            'deliveryBreakdowns'
        ]);

        // Attachments are stored on order (you already have helpers)
        $attachments = [];
        if (method_exists($this, 'getOrderAttachments')) {
            $attachments = (array) $this->getOrderAttachments($order);
        } elseif ($order && $order->orderAttachment) {
            $raw = $order->orderAttachment;
            $decoded = is_array($raw) ? $raw : json_decode($raw, true);
            $attachments = is_array($decoded) ? $decoded : [];
        }


        $taskTypes = ['printing','furnishing','installation','delivery'];
        $progress = collect($taskTypes)->mapWithKeys(function ($t) use ($product) {
            $isThisTask = strtolower($product->taskType ?? '') === $t;
            $status = $isThisTask ? ($product->status ?? 'pending') : 'pending';
            return [$t => [
                'status' => $status,         
                'accepted_at'  => null,     
                'completed_at' => null,      
                'duration'     => null,      
            ]];
        });

        return view('artist.fulfillment.product-show', [
            'product'     => $product,
            'order'       => $product->order,
            'items'       => $product->items,
            'deliveries'  => $product->deliveryBreakdowns,
            'attachments' => $attachments,
            'progress'    => $progress,
        ]);
    }

    // Optional: hook your PDF later
    public function export(Product $product)
    {
        // generate a PDF and return download/stream
        abort(501, 'Export not implemented yet.');
    }
}
