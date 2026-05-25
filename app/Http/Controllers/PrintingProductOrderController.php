<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use App\Models\Order;
use App\Helpers\Helpers;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\OrderAttachment;

class PrintingProductOrderController extends Controller
{
    /**
     * Product Order 列表 / Dashboard（使用 Dummy Data）
     */
    public function productorder(Request $request)
    {
        // 下拉与筛选 Pill 的映射
        $statuses = [
            'all'        => 'All Status',
            'pending'    => 'Pending',
            'processing' => 'Processing',
            'completed'  => 'Completed',
            'cancelled'  => 'Cancelled',
            'refunded'   => 'Refunded',
            'issue'      => 'Issue',
        ];

        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status', 'all');

        if (! array_key_exists($status, $statuses)) {
            $status = 'all';
        }

        // 生成 40 笔假资料
        $allOrders = $this->makeDummyOrders(40);

        // 过滤：搜索 + 状态
        $filtered = $allOrders
            ->when($search !== '', function (Collection $c) use ($search) {
                $needle = Str::lower($search);
                return $c->filter(function ($o) use ($needle) {
                    return Str::contains(Str::lower($o['order_no']), $needle)
                        || Str::contains(Str::lower($o['product_name']), $needle)
                        || Str::contains(Str::lower($o['customer_name']), $needle);
                });
            })
            ->when($status !== 'all', fn($c) => $c->where('status', $status))
            ->sortByDesc('created_at')  // 以创建时间倒序
            ->values();

        // 分页（手动分页，因为没有 DB）
        $perPage = 10;
        $page    = max((int) $request->query('page', 1), 1);

        $orders = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('printing.product-order', [ // 视图路径按需调整
            'statuses' => $statuses,
            'search'   => $search,
            'status'   => $status,
            'orders'   => $orders,
        ]);
    }

    private function stageForRole(\App\Models\User $user): ?string
    {
        return [
            'operations-printing' => 'printing',
        ][$user->role] ?? null;
    }

    private function assertRoleMatchesProductStage(int $productId): void
    {
        $row = DB::table('products')->select('taskType')->where('ProductID', $productId)->first();
        abort_unless($row, 404, 'Product not found.');
        $productStage = strtolower((string)($row->taskType ?? ''));
        $roleStage    = $this->stageForRole(request()->user());
        abort_unless($productStage === $roleStage, 403, 'Not allowed to act on this stage.');
    }

    /**
     *（可选）详情页：同样基于 dummy data
     */
    public function show(int $productId)
    {
        // --- 1) Load the selected product + order header ---
        $headerRow = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('users  as u', 'u.id', '=', 'o.artist_id')
            ->leftJoin('users  as de', 'de.id', '=', 'o.data_entry_id')
            ->where('p.ProductID', $productId)
            ->select([
                'p.ProductID',
                'p.OrderID',
                'p.status',
                'p.taskType', 
                'p.productName as productName',
                'p.totalQuantity',
                'p.materialRemark',
                'p.permit',
                'p.redoOf as redo_product_of',     
                'o.redo   as redo_order',
                'p.editable',
                'o.order_number',
                'o.orderTitle',
                'o.orderDetail',
                'o.companyName',
                'o.orderDate',
                'o.deadline',
                'o.artist_id',
                'o.orderAttachment',
                'o.status as orderStatus',
                'p.accepted',
                'p.packaging',
                DB::raw('COALESCE(u.name, "") as artist_name'),
                DB::raw('COALESCE(de.name, "") as data_entry_name'),
            ])
            ->first();

        if (!$headerRow) abort(404);

        // Helper to build a product code like #ORD-2025-003-P0110
        // $orderCodeFor = function ($orderId, $orderNumber) {
        //     return $orderNumber ?: sprintf('ORD-%03d', (int)$orderId);
        // };
        // $orderCode   = $orderCodeFor($headerRow->OrderID, $headerRow->order_number);
        // $productCode = sprintf('%s-P%04d', $orderCode, (int)$productId);

        $baseOrderId   = $headerRow->redo_order      ?: $headerRow->OrderID;
        $baseProductId = $headerRow->redo_product_of ?: $productId;
        $year          = $headerRow->orderDate ? \Carbon\Carbon::parse($headerRow->orderDate)->format('Y') : date('Y');

        // R only if this is a redo product AND editable = 1
        $rFlag = ($headerRow->redo_product_of && (int)$headerRow->editable === 1) ? 'R' : '';

        $displayProductCode = sprintf(
            '#ORD-%s-%03d-P%04d%s',
            $year,
            (int)$baseOrderId,
            (int)$baseProductId,
            $rFlag
        );

        $displayOrderCode = sprintf(
            '#ORD-%s-%03d%s',
            $year,
            (int)$baseOrderId,
            $rFlag
        );

        // Header object used by your current blade
        $header = (object)[
            'ProductID'    => $headerRow->ProductID,
            'OrderID'      => $headerRow->OrderID,
            'status'       => $headerRow->status,
            'taskType'     => $headerRow->taskType,
            'order_number' => $headerRow->order_number,
            'order_title'  => $headerRow->orderTitle,
            'orderDetail'  => $headerRow->orderDetail,
            'companyName'  => $headerRow->companyName,
            'permit'       => $headerRow->permit,
            'artist_name'  => $headerRow->artist_name,
            'orderStatus'   => $headerRow->orderStatus,
            'accepted'      => $headerRow->accepted,
            'packaging'    => $headerRow->packaging,
            'data_entry_name' => $headerRow->data_entry_name,
        ];

        $canEdit = ((int)($headerRow->accepted ?? 0) === 1) && (strtolower((string)($headerRow->orderStatus ?? '')) !== 'rejected');

        $dates = [
            'order_date' => $headerRow->orderDate,
            'deadline'   => $headerRow->deadline,
        ];

        $productCode = $displayProductCode;

        // is this selected product a redo?
        $selectedIsRedo = ($headerRow->redo_product_of && (int)$headerRow->editable === 1);

        // ---- compact product header (for the selected product) ----
        $productHeader = [
            'name'     => trim((string)($headerRow->productName ?? '')),
            'code'     => $displayProductCode,   // ← use redo-aware code
            'qty'      => (int)($headerRow->totalQuantity ?? 0),
            'material' => trim((string)($headerRow->materialRemark ?? '')),
            'packaging' => $headerRow->packaging,
            'is_redo'  => $selectedIsRedo,
        ];

        // ===== Helpers we reuse for any ProductID =====
        $fmt = fn($n) => $n === null ? null : rtrim(rtrim(number_format((float)$n, 2, '.', ''), '0'), '.');

        $buildItems = function (int $pid) use ($fmt) {
            $raw = DB::table('product_items as i')
                ->leftJoin('specifications as s', 's.ItemID', '=', 'i.ItemID')
                ->where('i.ProductID', $pid)
                ->orderBy('i.ItemID')
                ->get();

            return $raw->map(function ($r) use ($fmt) {
                // material: support json array or plain text
                $material = $r->material;
                if (is_string($material) && $material !== '') {
                    $t = ltrim($material);
                    if ($t !== '' && ($t[0] === '[' || $t[0] === '{')) {
                        $d = json_decode($material, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($d)) {
                            $material = implode(', ', array_filter($d, fn($v) => $v !== '' && $v !== null));
                        }
                    }
                }

                $size = null;
                if ($r->sizeWidth !== null || $r->sizeHeight !== null) {
                    $size = ($fmt($r->sizeWidth) ?: '0') . ' × ' . ($fmt($r->sizeHeight) ?: '0') . ' ' . (string)($r->sizeUnit ?? '');
                }

                $bleed = null;
                if ($r->bleedTop !== null || $r->bleedRight !== null || $r->bleedBottom !== null || $r->bleedLeft !== null) {
                    $bleed = implode(' / ', [
                        $fmt($r->bleedTop)    ?? '0',
                        $fmt($r->bleedBottom) ?? '0',
                        $fmt($r->bleedLeft)   ?? '0',
                        $fmt($r->bleedRight)  ?? '0',
                    ]) . ' ' . (string)($r->bleedUnit ?? '');
                }

                $prime    = (int)($r->prime_centre ?? 0) === 1;
                $assemble = is_string($r->finishing) ? strtolower($r->finishing) === 'yes'
                    : (!is_null($r->finishing) && (int)$r->finishing === 1);

                return [
                    'item_id'          => (int)$r->ItemID,
                    'name'       => $r->itemName,
                    'qty'        => $r->quantity,
                    'size'       => $size,
                    'bleed'      => $bleed,
                    'material'   => $material,
                    'prime'      => $prime,
                    'lamination' => $r->lamination,
                    'printer'    => $r->printer,
                    'cutter'     => $r->cutter,
                    'assemble'   => $assemble,
                ];
            })->values()->all();
        };

        $canonMethod = function (?string $raw): array {
            $m = strtolower(trim((string)$raw));
            if (str_contains($m, 'courier'))  
                return ['key' => 'courier', 'label' => 'Courier', 'icon' => 'bi-box-arrow-up-right'];
            if (str_contains($m, 'self') || str_contains($m, 'pickup'))
                return ['key' => 'pickup', 'label' => 'Self Pickup', 'icon' => 'bi-bag-check'];
            if (str_contains($m, 'installation'))
                return ['key' => 'installation', 'label' => 'Installation', 'icon' => 'bi-wrench'];
            if (str_contains($m, 'delivery'))
                return ['key' => 'delivery ', 'label' => 'Delivery', 'icon' => 'bi-truck'];
            return ['key' => 'delivery ', 'label' => 'Delivery', 'icon' => 'bi-truck'];
        };

        $buildDeliveriesAndTotals = function (int $pid, int $productQty) use ($canonMethod) {
            $rows = DB::table('delivery_breakdowns')
                ->where('ProductID', $pid)
                ->select(['method', 'deliver_install_type', 'outsource_cost', 'quantity', 'date', 'time', 'location'])
                ->orderBy('BreakdownID')
                ->get();

            $deliveries = $rows->map(function ($d) use ($canonMethod) {
                $m = $canonMethod($d->method);

                $dateTime = null;
                if ($d->date) {
                    $dt = $d->time
                        ? \Carbon\Carbon::parse($d->date . ' ' . $d->time)
                        : \Carbon\Carbon::parse($d->date);
                    $dateTime = $d->time ? $dt->format('Y-m-d H:i') : $dt->format('Y-m-d');
                }

                return [
                    'method'       => $m['key'],
                    'method_label' => $m['label'],
                    'icon'         => $m['icon'],
                    'quantity'     => (int)($d->quantity ?? 0),
                    'location'      => (string)($d->location ?? ''),
                    'datetime'     => $dateTime,
                    'install'      => $d->deliver_install_type,
                    'cost'         => $d->outsource_cost !== null ? (float)$d->outsource_cost : null,
                ];
            })->values()->all();

            $deliveredQty = (int)$rows->sum(fn($r) => (int)($r->quantity ?? 0));
            $totals = [
                'total'     => $productQty,
                'delivered' => $deliveredQty,
                'remaining' => max(0, $productQty - $deliveredQty),
            ];

            return [$deliveries, $totals];
        };

        // ===== 2) Data for the SELECTED product (keeps your current blade working) =====
        $items = $buildItems($productId);
        [$deliveries, $totals] = $buildDeliveriesAndTotals($productId, (int)($headerRow->totalQuantity ?? 0));

        // Product remarks for the selected product
        $remarkRows = DB::table('product_remarks')
            ->where('ProductID', $productId)
            ->orderBy('created_at')->get();

        $remarkLabels = $remarkRows->map(function ($row) {
            $op = strtolower($row->operation ?? '');
            $label = $op === 'installation' ? 'delivery & installation' : $op;
            return trim($label ? "{$label}: {$row->remark}" : $row->remark);
        })->filter()->unique()->values()->all();

        $remarks = DB::table('product_remarks as pr')
            ->leftJoin('users as u', 'u.id', '=', 'pr.user_id')
            ->where('pr.ProductID', $productId)              // $product is the ProductID you already have
            ->orderBy('pr.created_at', 'desc')
            ->select([
                'pr.RemarkID',
                'pr.ProductID',
                'pr.operation',
                'pr.remark',
                'pr.created_at',
                'u.name as author_name',
            ])
            ->get();
        $remarksByOp = $remarks->groupBy('operation');

        /* ========= 🔥 NEW ATTACHMENT LOGIC 🔥 ========= */

        // Load ALL attachments for this order, with uploader + role
        $attachmentRows = OrderAttachment::with('uploader:id,name,role')
            ->where('order_id', $headerRow->OrderID)
            ->orderBy('id')
            ->get();

        // Split by role
        $salespersonRows = $attachmentRows->filter(function ($att) {
            return in_array(optional($att->uploader)->role, ['sales', 'salesperson', 'sales-person', 'head-salesperson']);
        });

        $artistRows = $attachmentRows->filter(function ($att) {
            return in_array(optional($att->uploader)->role, ['artist', 'head-artist', 'data-entry', 'boss', 'admin']);
        });

        // Common mapper
        $mapAttachment = function (OrderAttachment $att) {
            $path = ltrim((string) $att->file_path, '/');
            $url  = \Illuminate\Support\Facades\Storage::url($path);

            return [
                'name'        => $att->original_name ?: basename($path),
                'size'        => $att->size ? number_format($att->size / 1024, 0) . ' KB' : null,
                'url'         => $url,
                'uploaded_by' => optional($att->uploader)->name,
                'uploaded_at' => $att->created_at?->format('d M Y'),
            ];
        };

        $salespersonAttachments = $salespersonRows->map($mapAttachment)->values()->all();
        $artistAttachments      = $artistRows->map($mapAttachment)->values()->all();

        // keep legacy variable for existing blade (artist section)
        $attachments = $artistAttachments;

        /* ============================================= */

        // ===== 3) Build ALL product blocks for the same order (to show multiple products) =====
        $productIds = DB::table('products')
            ->where('OrderID', $headerRow->OrderID)
            ->orderBy('ProductID')
            ->pluck('ProductID');

        $blocks = [];
        foreach ($productIds as $pid) {
            $p = DB::table('products')->where('ProductID', $pid)->first();
            if (!$p) continue;

            $yearForBlocks        = $headerRow->orderDate ? \Carbon\Carbon::parse($headerRow->orderDate)->format('Y') : date('Y');
            $baseOrderIdForBlocks = $headerRow->redo_order ?: $headerRow->OrderID;

            $isRedoBlock = ($p->redoOf && (int)$p->editable === 1);
            $rFlagBlock = ($p->redoOf && (int)$p->editable === 1) ? 'R' : '';

            $code = sprintf(
                '#ORD-%s-%03d-P%04d%s',
                $yearForBlocks,
                (int)$baseOrderIdForBlocks,
                (int)($p->redoOf ?: $p->ProductID),
                $rFlagBlock
            );

            $ph = [
                'name'     => (string)($p->productName ?? ''),
                'code'     => $code,
                'qty'      => (int)($p->totalQuantity ?? 0),
                'material' => (string)($p->materialRemark ?? ''),
                'packaging' => $p->packaging,
                'is_redo'  => $isRedoBlock,
            ];
            $its = $buildItems((int)$pid);
            [$dels, $tots] = $buildDeliveriesAndTotals((int)$pid, (int)($p->totalQuantity ?? 0));
            $hasDeliveries = !empty($dels);

            $baseOrderIdForBlocks = $headerRow->redo_order ?: $headerRow->OrderID;

            $fetchRedoInfo = function (int $orderId) {
                $row = DB::table('report_redo as rr')
                    ->leftJoin('users as u', 'u.id', '=', 'rr.user_id')
                    ->where('rr.OrderID', $orderId)
                    ->orderByDesc('rr.created_at')
                    ->select([
                        'rr.reason',
                        'rr.created_at',
                        DB::raw('COALESCE(u.name, "") as actor_name'),
                    ])
                    ->first();

                return [
                    'reason' => $row->reason ?? null,
                    'by'     => $row->actor_name ?? null,
                    'at'     => isset($row->created_at)
                                ? \Carbon\Carbon::parse($row->created_at)->format('Y-m-d H:i')
                                : null,
                ];
            };

            $redoInfoForOrder = $fetchRedoInfo((int) $baseOrderIdForBlocks);
            
            $blocks[] = [
                'id'             => (int)$pid,
                'product_header' => $ph + [
                    'is_redo' => ($rFlagBlock === 'R'),
                ],
                'redo'           => $redoInfoForOrder,
                'items'          => $its,
                'deliveries'     => $dels,
                'totals'         => $tots,
                'has_deliveries' => $hasDeliveries,
                'is_redo'        => $isRedoBlock,
            ];
        }

        // Who to show in chips
        $assignee = $header->artist_name ?: '—';
        $dataEntry = $header->data_entry_name ?: '—';
        $uploader = $assignee;
        $permit   = ['name' => 'Permit.pdf', 'size' => '1.2 MB', 'url' => '#'];

        $printers = DB::table('machines')
            ->where('machine_type', 'printer')
            ->orderBy('machine_name')
            ->get(['id', 'machine_name']);

        // Collect ItemIDs from ALL items that appear on the page
        $itemIds = [];

        // 1) items from the selected product section
        foreach ($items as $it) {
            if (!empty($it['item_id'])) $itemIds[] = (int)$it['item_id'];
        }

        // 2) items from every product block shown on the page
        foreach ($blocks as $b) {
            foreach ($b['items'] as $it) {
                if (!empty($it['item_id'])) $itemIds[] = (int)$it['item_id']; // <-- use item_id
            }
        }
        $itemIds = array_values(array_unique($itemIds));

        // Map: [ ItemID => 'Printer Name' ]
        $specPrinters = collect();
        if (!empty($itemIds)) {
            $specPrinters = DB::table('specifications')
                ->whereIn('ItemID', $itemIds)
                ->pluck('printer', 'ItemID');
        }

        $hasRedoBefore = !empty($headerRow->redo_product_of); 

        return view('printing.job_order_show', [
            // current single-product variables (unchanged)
            'product_code'   => $productCode,
            'header'         => $header,
            'dates'          => $dates,
            'remarkLabels'   => $remarkLabels,
            'items'          => $items,
            'deliveries'     => $deliveries,
            'totals'         => $totals,
            'assignee'       => $assignee,
             'dataEntry'  => $dataEntry,
            'uploader'       => $uploader,
            'permit'         => $permit,
            'attachments'    => $attachments,
            'artistAttachments'     => $artistAttachments,      // explicit artist list
            'salespersonAttachments'=> $salespersonAttachments,
            'product_header' => $productHeader,
            'blocks'         => $blocks,
            'canEdit' => $canEdit,
            'remarksByOp' => $remarksByOp,
            'remarks' => $remarks,
            'printers'  => $printers,
            'specPrinters'  => $specPrinters,
            'job_order_code'   => $displayOrderCode, 
            'hasRedoBefore'    => $hasRedoBefore,
        ]);
    }


    public function accept(\Illuminate\Http\Request $request, int $product)
    {
        $this->assertRoleMatchesProductStage($product);

        $stage = 'printing';
        $now   = now();

        // --- Load product & order info up-front (for notifications) ---
        $p = DB::table('products')
            ->where('ProductID', $product)
            ->select('ProductID', 'productName', 'OrderID')
            ->first();

        if (!$p) {
            return back()->with('error', 'Product not found.');
        }

        $o = DB::table('orders')
            ->where('id', $p->OrderID)  // products.OrderID -> orders.id
            ->select('id', 'order_number', 'artist_id', 'salesperson_id')
            ->first();

        if (!$o) {
            return back()->with('error', 'Order not found for this product.');
        }

        DB::transaction(function () use ($product, $stage, $now) {
            // 1) mark only THIS product as accepted
            DB::table('products')
                ->where('ProductID', $product)
                ->update(['accepted' => 1, 'updated_at' => $now]);
            
            // ── 1) Check if there is already an "in_progress" row for this product+stage
            $existing = DB::table('fulfillment_progress')
                ->where('ProductID', $product)
                ->where('stage', $stage)
                ->where('status', 'rejected')
                ->lockForUpdate()
                ->first();

            // 2) upsert progress (do not overwrite acceptedAt)
            if ($existing) {
                // 👉 Already in progress → mark as completed
                DB::table('fulfillment_progress')
                    ->where('ProductID', $product)
                    ->where('stage', $stage)
                    ->where('status', 'rejected')
                    ->update([
                        // stage + acceptedAt stay unchanged
                        'status'      => 'in_progress',
                        'acceptedAt' => $now,
                        'updated_at'  => $now,
                    ]);

            } else {
                // 👉 No in-progress row → behave like original accept: insert/upsert as in_progress
                DB::table('fulfillment_progress')->upsert(
                    [[
                        'ProductID'  => (int) $product,
                        'stage'      => $stage,
                        'acceptedAt' => $now,       // only used on insert
                        'status'     => 'in_progress',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]],
                    ['ProductID', 'stage'],        // unique-by
                    ['status', 'updated_at']       // update columns
                );
            }
        });

        // --- Build message once ---
        $actor      = Auth::user();
        $actorName  = $actor?->name ?? 'System';
        $actorRole  = str_replace('-', ' ', $actor?->role ?? 'user');
        $productId  = (int) $p->ProductID;
        $productName= (string) $p->productName;
        $orderNo    = (string) $o->order_number;
        $orderId    = (int) $o->id;

        $message = "Product {$productName} has been accepted by {$actorName} ({$actorRole}).";

        // Helper to pick a destination URL based on the recipient’s role
        $urlFor = function (User $user) use ($orderId) {
            if (in_array($user->role, ['artist','head-artist'])) {
                return url("/artist/orders/{$orderId}");
            }
            if (in_array($user->role, ['salesperson','head-salesperson'])) {
                return url("/orders/{$orderId}");
            }
            if (in_array($user->role, ['boss','Boss'])) {
                return url("/boss/orders/{$orderId}");
            } 
            if (in_array($user->role, ['admin','Admin'])) {
                return url("/admin/orders/{$orderId}");
            } 
            return url("/");
        };

        // ---- Targeted recipients (people in charge of this product) ----
        $targetIds = array_filter([
            $o->salesperson_id ?? null,
            $o->artist_id      ?? null,
        ]);

        if (!empty($targetIds)) {
            User::whereIn('id', $targetIds)->get()->each(function (User $u) use ($message, $urlFor) {
                Helpers::notify($u, $message, $urlFor($u), ['database', 'mail']);
            });
        }

        // ---- Optional: notify leads/management (keep or remove as you wish) ----
        // Head roles (if they exist in your enum)
        User::whereIn('role', ['head-salesperson','head-artist'])->get()
            ->each(function (User $u) use ($message, $urlFor) {
                Helpers::notify($u, $message, $urlFor($u), ['database', 'mail']);
            });

        // Admin + Boss (broadcast)
        User::whereIn('role', ['admin','boss'])->get()
            ->each(function (User $u) use ($message, $urlFor) {
                Helpers::notify($u, $message, $urlFor($u), ['database', 'mail']);
            });

        return back()->with('ok', 'Product accepted for printing.');
    }

    
    public function reject(\Illuminate\Http\Request $request, int $product)
{
    $this->assertRoleMatchesProductStage($product);

    $request->validate([
        'reason' => 'required|string|max:2000',
    ]);

    $stage = 'printing';
    $now   = now();

    // ---- Load product & order (for notifications) ----
    $p = DB::table('products')
        ->where('ProductID', (int)$product)
        ->select('ProductID', 'productName', 'OrderID')
        ->first();
    abort_if(!$p, 404, 'Product not found.');

    $o = DB::table('orders')
        ->where('id', (int)$p->OrderID)
        ->select('id', 'order_number', 'artist_id', 'salesperson_id')
        ->first();
    abort_if(!$o, 404, 'Order not found for this product.');

    $orderId = (int) $o->id;
    $actorId = (int) auth()->id();

    DB::transaction(function () use ($product, $orderId, $stage, $now, $request, $actorId) {

        // 1) Force ALL *other* products in this order to editable = 0
        //    (do not touch rows already rejected)
        DB::table('products')
            ->where('OrderID', $orderId)
            ->where('ProductID', '<>', (int)$product)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'rejected');
            })
            ->update([
                'editable'   => 0,
                'updated_at' => $now,
            ]);

        // 2) ONLY the selected product -> rejected + not accepted + editable=1
        DB::table('products')
            ->where('ProductID', (int)$product)
            ->update([
                'accepted'   => 0,
                'status'     => 'rejected',
                'editable'   => 1,
                'updated_at' => $now,
            ]);

        // 3) Order flags (your spec)
        DB::table('orders')
            ->where('id', $orderId)
            ->update([
                'orderStatus'   => 'rejected',
                'draft'         => 1,
                'submit'        => 0,
                'pending'       => 0,
                'data_entry_id' => null,
                'updated_at'    => $now,
            ]);

        // 4) Reason (+ who rejected)
        DB::table('report_redo')->insert([
            'OrderID'    => $orderId,
            'reason'     => (string) $request->reason,
            'user_id'    => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 5) Fulfillment progress for THIS product
        DB::table('fulfillment_progress')->upsert(
            [[
                'ProductID'  => (int)$product,
                'stage'      => $stage,
                'status'     => 'rejected',
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['ProductID', 'stage'],
            ['status', 'updated_at']
        );
    });

    // ----- notifications -----
    $actor      = Auth::user();
    $actorName  = $actor?->name ?? 'System';
    $actorRole  = str_replace('-', ' ', $actor?->role ?? 'user');

    $productName   = (string) $p->productName;
    $orderNo       = (string) $o->order_number;
    $reason        = trim((string) $request->reason);
    $reasonPreview = mb_strimwidth($reason, 0, 60, '…', 'UTF-8');

    $message = "Product {$productName} was rejected by {$actorName} ({$actorRole}). Reason: {$reasonPreview}";

    $urlFor = function (\App\Models\User $user) use ($orderId) {
        $role = strtolower($user->role);
        return match (true) {
            in_array($user->role, ['artist','head-artist'])           => url("/artist/orders/{$orderId}"),
            in_array($user->role, ['salesperson','head-salesperson']) => url("/orders/{$orderId}"),
            $role === 'boss'                                          => url("/boss/orders/{$orderId}"),
            $role === 'admin'                                         => url("/admin/orders/{$orderId}"),
            default                                                   => url("/"),
        };
    };

    $targetIds = array_filter([$o->salesperson_id ?? null, $o->artist_id ?? null]);
    if ($targetIds) {
        \App\Models\User::whereIn('id', $targetIds)->get()
            ->each(fn($u) => \App\Helpers\Helpers::notify($u, $message, $urlFor($u), ['database', 'mail']));
    }
    \App\Models\User::whereIn('role', ['head-salesperson','head-artist'])->get()
        ->each(fn($u) => \App\Helpers\Helpers::notify($u, $message, $urlFor($u), ['database', 'mail']));
    \App\Models\User::whereIn('role', ['admin','boss'])->get()
        ->each(fn($u) => \App\Helpers\Helpers::notify($u, $message, $urlFor($u), ['database', 'mail']));

    return back()->with('ok', 'Product rejected; editable flags updated correctly.');
}


    private function ensureCanEdit(int $orderId): void
    {
        $row = DB::table('orders')
            ->select('accepted', 'orderStatus')
            ->where('id', $orderId)
            ->first();

        $accepted = (int)($row->accepted ?? 0);
        $rejected = strtolower((string)($row->orderStatus ?? '')) === 'rejected';

        abort_unless($accepted === 1 && !$rejected, 403, 'Editing is locked for this order.');
    }

    public function save(Request $request, int $product)
    {
        // only allow save when the order has been accepted and not rejected
        $order = DB::table('products')
            ->join('orders', 'orders.id', '=', 'products.OrderID')
            ->where('products.ProductID', $product)
            ->select(
                'products.ProductID',
                'products.productName',
                'products.accepted',
                'orders.id as order_id',
                'orders.order_number',
                'orders.orderStatus',
                'orders.artist_id',
                'orders.salesperson_id'
            )
            ->first();

        if (
            !$order ||
            (int)($order->accepted ?? 0) !== 1 ||
            strtolower((string)$order->orderStatus) === 'rejected'
        ) {
            return back()->with('error', 'This job cannot be edited.');
        }

        $userId = Auth::id();

        // ---- ONLY allow item IDs that belong to THIS product ----
        $allowedItemIds = DB::table('product_items')
            ->where('ProductID', $product)
            ->pluck('ItemID')
            ->map(fn($v) => (int)$v)
            ->all();

        // ---- Normalize incoming payload to: [itemId => printerName|null] ----
        $incoming = [];

        // A) New shape from Blade: items[<ItemID>][printer_id] + optional [printer]
        $itemsPayload = $request->input('items', []);
        if (is_array($itemsPayload) && !empty($itemsPayload)) {
            // preload machines: id => name
            $machines = DB::table('machines')->pluck('machine_name', 'id'); // [id => name]

            foreach ($itemsPayload as $rawId => $row) {
                $itemId = (int)$rawId;
                if (!in_array($itemId, $allowedItemIds, true)) continue; // ignore non-selected products

                $printerName = null;

                // prefer printer_id → resolve to name
                if (isset($row['printer_id']) && $row['printer_id'] !== '') {
                    $pid = (int)$row['printer_id'];
                    if (isset($machines[$pid])) {
                        $printerName = (string)$machines[$pid];
                    }
                }

                // fallback to plain text if provided
                if ($printerName === null && isset($row['printer'])) {
                    $tmp = trim((string)$row['printer']);
                    if ($tmp !== '') $printerName = $tmp;
                }

                // empty selection => clear
                $incoming[$itemId] = $printerName; // may be null
            }
        }

        // B) Backward-compat: printers[<ItemID>] => 'Minolta DGFP'
        $legacyPrinters = $request->input('printers', []);
        if (is_array($legacyPrinters)) {
            foreach ($legacyPrinters as $rawId => $name) {
                $itemId = (int)$rawId;
                if (!in_array($itemId, $allowedItemIds, true)) continue;
                $nm = trim((string)$name);
                $incoming[$itemId] = ($nm === '') ? null : $nm;
            }
        }

        // Also read remarks (unchanged)
        $remarks = (array) $request->input('remarks', []);

        // Preload existing specs for change detection
        $existingSpecs = empty($incoming)
            ? collect()
            : DB::table('specifications')
                ->whereIn('ItemID', array_keys($incoming))
                ->pluck('printer', 'ItemID'); // [ItemID => printer]

        // Diff trackers (optional)
        $printerDiffs = [];
        $remarksAdded = [];

        DB::transaction(function () use (
            $product,
            $incoming,
            $remarks,
            $existingSpecs,
            &$printerDiffs,
            &$remarksAdded,
            $userId
        ) {
            // 1) Upsert printers into specifications
            foreach ($incoming as $itemId => $newName) {
                $newDb = ($newName === null || $newName === '') ? null : $newName;
                $old   = $existingSpecs->get($itemId);

                if ($old !== $newDb) {
                    $type = $old === null && $newDb !== null ? 'set'
                        : ($old !== null && $newDb === null ? 'cleared'
                        : 'changed');
                    $printerDiffs[] = ['item' => $itemId, 'from' => $old, 'to' => $newDb, 'type' => $type];
                }

                DB::table('specifications')->updateOrInsert(
                    ['ItemID' => (int)$itemId],
                    [
                        'printer'    => $newDb,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            // 2) Append new remarks (same as your logic)
            $allowedOps = ['printing', 'furnishing', 'installation', 'courier', 'self_pickup', 'artist'];

            foreach ($remarks as $row) {
                $opRaw = strtolower(trim((string)($row['operation'] ?? '')));
                $text  = trim((string)($row['remark'] ?? ''));
                if ($text === '') continue;

                if (in_array($opRaw, ['installation','install','delivery_installation','delivery & installation'], true)) {
                    $op = 'installation';
                } elseif (in_array($opRaw, ['self_pickup','self pickup','pickup'], true)) {
                    $op = 'self_pickup';
                } elseif ($opRaw === 'courier') {
                    $op = 'courier';
                } elseif ($opRaw === 'printing') {
                    $op = 'printing';
                } elseif ($opRaw === 'furnishing') {
                    $op = 'furnishing';
                } elseif ($opRaw === 'artist') {
                    $op = 'artist';
                } else {
                    $op = 'furnishing';
                }

                if (!in_array($op, $allowedOps, true)) $op = 'furnishing';

                DB::table('product_remarks')->insert([
                    'ProductID'  => $product,
                    'operation'  => $op,
                    'remark'     => $text,
                    'user_id'    => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $remarksAdded[] = ['operation' => $op, 'remark' => $text];
            }
        });

        // ==== Notifications (only if something changed) ====
        if (!empty($printerDiffs) || !empty($remarksAdded)) {
            $actor     = Auth::user();
            $actorName = $actor?->name ?? 'System';
            $actorRole = str_replace('-', ' ', $actor?->role ?? 'user');

            $orderId   = (int)$order->order_id;
            $orderNo   = (string)$order->order_number;
            $prodId    = (int)$order->ProductID;
            $prodName  = (string)$order->productName;

            // Summaries
            $setCnt     = count(array_filter($printerDiffs, fn($d) => $d['type'] === 'set'));
            $chgCnt     = count(array_filter($printerDiffs, fn($d) => $d['type'] === 'changed'));
            $clrCnt     = count(array_filter($printerDiffs, fn($d) => $d['type'] === 'cleared'));
            $remarksCnt = count($remarksAdded);

            $previewParts = [];
            if ($setCnt    ) $previewParts[] = "printer set: {$setCnt}";
            if ($chgCnt    ) $previewParts[] = "printer changed: {$chgCnt}";
            if ($clrCnt    ) $previewParts[] = "printer cleared: {$clrCnt}";
            if ($remarksCnt) $previewParts[] = "remarks added: {$remarksCnt}";
            $preview = $previewParts ? ' (' . implode(', ', $previewParts) . ')' : '';

            $message = "Updates on Product {$prodName} by {$actorName} ({$actorRole}){$preview}.";

            // Role-aware URL
            $urlFor = function (User $user) use ($orderId) {
                $role = strtolower($user->role);

                if (in_array($user->role, ['artist','head-artist'])) {
                    return url("/artist/orders/{$orderId}");
                }
                if (in_array($user->role, ['salesperson','head-salesperson'])) {
                    return url("/orders/{$orderId}");
                }
                if ($role === 'boss') {
                    return url("/boss/orders/{$orderId}");
                } 
                if ($role === 'admin') {
                    return url("/admin/orders/{$orderId}");
                } 
                return url("/");
            };

            // Target assignees
            $targetIds = array_filter([
                $order->salesperson_id ?? null,
                $order->artist_id      ?? null,
            ]);

            if (!empty($targetIds)) {
                User::whereIn('id', $targetIds)->get()->each(function (User $u) use ($message, $urlFor) {
                    Helpers::notify($u, $message, $urlFor($u), ['database', 'mail']);
                });
            }

            // Optional broadcasts
            User::whereIn('role', ['head-salesperson','head-artist'])->get()
                ->each(function (User $u) use ($message, $urlFor) {
                    Helpers::notify($u, $message, $urlFor($u), ['database', 'mail']);
                });

            User::whereIn('role', ['admin','boss'])->get()
                ->each(function (User $u) use ($message, $urlFor) {
                    Helpers::notify($u, $message, $urlFor($u), ['database', 'mail']);
                });
        }

        return back()->with('ok', true);
    }
}
