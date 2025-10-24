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

class FurnishingProductOrderController extends Controller
{
    private function stageForRole(\App\Models\User $user): ?string
    {
        return [
            'operations-furnishing' => 'furnishing',
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

        return view('furnishing.product-order', [ // 视图路径按需调整
            'statuses' => $statuses,
            'search'   => $search,
            'status'   => $status,
            'orders'   => $orders,
        ]);
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
                'p.accepted',
                'p.totalQuantity',
                'p.materialRemark',
                'p.redoOf as redo_product_of',  
                'p.editable',   
                'o.redo   as redo_order',
                'o.order_number',
                'o.orderTitle',
                'o.companyName',
                'o.orderDate',
                'o.deadline',
                'o.artist_id',
                'o.orderAttachment',
                'o.status as orderStatus',
                'p.accepted',
                DB::raw('COALESCE(u.name, "") as artist_name'),
                DB::raw('COALESCE(de.name, "") as data_entry_name'),
            ])
            ->first();

        if (!$headerRow) abort(404);

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

        // Header object used by your current blade
        $header = (object)[
            'ProductID'    => $headerRow->ProductID,
            'OrderID'      => $headerRow->OrderID,
            'status'       => $headerRow->status,
            'taskType'     => $headerRow->taskType,
            'productName'  => $headerRow->productName, 
            'accepted'     => $headerRow->accepted,
            'order_number' => $headerRow->order_number,
            'order_title'  => $headerRow->orderTitle,
            'companyName'  => $headerRow->companyName,
            'artist_name'  => $headerRow->artist_name,
            'orderStatus'   => $headerRow->orderStatus,
            'accepted'      => $headerRow->accepted,
            'data_entry_name' => $headerRow->data_entry_name,
        ];

        $canEdit = ((int)($headerRow->accepted ?? 0) === 1) && (strtolower((string)($headerRow->orderStatus ?? '')) !== 'rejected');

        $dates = [
            'order_date' => $headerRow->orderDate,
            'deadline'   => $headerRow->deadline,
        ];

        $productCode = $displayProductCode;

        // ---- compact product header (for the selected product) ----
        $productHeader = [
            'name'     => trim((string)($headerRow->productName ?? '')),
            'code'     => $displayProductCode,   // ← use redo-aware code
            'qty'      => (int)($headerRow->totalQuantity ?? 0),
            'material' => trim((string)($headerRow->materialRemark ?? '')),
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
                        $fmt($r->bleedRight)  ?? '0',
                        $fmt($r->bleedBottom) ?? '0',
                        $fmt($r->bleedLeft)   ?? '0',
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
            if (str_contains($m, 'courier'))  return ['key' => 'courier', 'label' => 'Courier', 'icon' => 'bi-box-arrow-up-right'];
            if (str_contains($m, 'self') || str_contains($m, 'pickup'))
                return ['key' => 'pickup', 'label' => 'Self Pickup', 'icon' => 'bi-bag-check'];
            return ['key' => 'install', 'label' => 'Delivery & Installation', 'icon' => 'bi-truck'];
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

        // Attachments (from orders.orderAttachment)
        $attachments = [];
        $rawAtt = (string)($headerRow->orderAttachment ?? '');
        if ($rawAtt !== '') {
            $paths = [];
            $decoded = json_decode($rawAtt, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $paths = array_values(array_filter($decoded));
            } else {
                $paths = preg_split('/[\s,]+/', $rawAtt, -1, PREG_SPLIT_NO_EMPTY);
            }
            foreach ($paths as $p) {
                $p = ltrim($p, '/');
                if (Str::startsWith($p, ['http://', 'https://'])) {
                    $url = $p;
                } elseif (Storage::disk('public')->exists($p)) {
                    $url = Storage::url($p);
                } elseif (Storage::exists($p)) {
                    $url = Storage::url($p);
                } else {
                    $url = asset($p);
                }
                $attachments[] = ['name' => basename($p), 'size' => '', 'url' => $url];
            }
        }

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
            ];
            $its = $buildItems((int)$pid);
            [$dels, $tots] = $buildDeliveriesAndTotals((int)$pid, (int)($p->totalQuantity ?? 0));
            $hasDeliveries = !empty($dels);

            $blocks[] = [
                'id'             => (int)$pid,
                'product_header' => $ph,
                'items'          => $its,
                'deliveries'     => $dels,
                'totals'         => $tots,
                'has_deliveries' => $hasDeliveries,
            ];
        }

        // Who to show in chips
        $assignee = $header->artist_name ?: '—';
        $dataEntry = $header->data_entry_name ?: '—';
        $uploader = $assignee;
        $permit   = ['name' => 'Permit.pdf', 'size' => '1.2 MB', 'url' => '#'];

        return view('furnishing.job_order_show', [
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
            'product_header' => $productHeader,
            'blocks'         => $blocks,
            'canEdit' => $canEdit,
            'remarksByOp' => $remarksByOp,
            'remarks' => $remarks,
        ]);
    }

    public function accept(Request $request, int $product)
    {
        $this->assertRoleMatchesProductStage($product);

        $stage = 'furnishing';
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
            // 1) mark THIS product accepted
            DB::table('products')
                ->where('ProductID', $product)
                ->update(['accepted' => 1, 'updated_at' => $now]);

            // 2) upsert furnishing progress (don’t overwrite acceptedAt)
            DB::table('fulfillment_progress')->upsert(
                [[
                    'ProductID'  => (int)$product,
                    'stage'      => $stage,
                    'acceptedAt' => $now,          // insert only
                    'status'     => 'in_progress',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['ProductID', 'stage'],
                ['status', 'updated_at']
            );
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
                Helpers::notify($u, $message, $urlFor($u), ['database']);
            });
        }

        // ---- Optional: notify leads/management (keep or remove as you wish) ----
        // Head roles (if they exist in your enum)
        User::whereIn('role', ['head-salesperson','head-artist'])->get()
            ->each(function (User $u) use ($message, $urlFor) {
                Helpers::notify($u, $message, $urlFor($u), ['database']);
            });

        // Admin + Boss (broadcast)
        User::whereIn('role', ['admin','boss'])->get()
            ->each(function (User $u) use ($message, $urlFor) {
                Helpers::notify($u, $message, $urlFor($u), ['database']);
            });

        return back()->with('ok', 'Product accepted for furnishing.');
    }

    public function reject(Request $request, int $product)
    {
        $this->assertRoleMatchesProductStage($product);

        $data = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $stage   = 'furnishing';
        $now     = now();
        $orderId = DB::table('products')->where('ProductID', $product)->value('OrderID');
        abort_if(!$orderId, 404, 'Product not found.');

        // --- Load product & order info up-front (for notifications) ---
        $p = DB::table('products')
            ->where('ProductID', $product)
            ->select('ProductID', 'productName', 'OrderID')
            ->first();

        if (!$p) {
            abort(404, 'Product not found.');
        }

        $o = DB::table('orders')
            ->where('id', $p->OrderID)  // products.OrderID -> orders.id
            ->select('id', 'order_number', 'artist_id', 'salesperson_id')
            ->first();

        if (!$o) {
            abort(404, 'Order not found for this product.');
        }

        DB::transaction(function () use ($product, $orderId, $stage, $now, $data) {
            // 1) product flags
            DB::table('products')
                ->where('ProductID', $product)
                ->update(['accepted' => 0, 'status' => 'rejected', 'updated_at' => $now]);

            // 2) order becomes rejected
            DB::table('orders')
                ->where('id', $orderId)
                ->update(['orderStatus' => 'rejected', 'updated_at' => $now]);

            // 3) (optional) store reason
            DB::table('report_redo')->insert([
                'OrderID'    => $orderId,
                'reason'     => $data['reason'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // 4) progress row for THIS product at furnishing -> rejected
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

        // --- Build and send notifications (after commit) ---
        $actor      = Auth::user();
        $actorName  = $actor?->name ?? 'System';
        $actorRole  = str_replace('-', ' ', $actor?->role ?? 'user');

        $productId   = (int) $p->ProductID;
        $productName = (string) $p->productName;
        $orderNo     = (string) $o->order_number;
        $orderId     = (int) $o->id;

        // Trim very long reasons in the notification text to stay readable
        $reason = trim((string) $request->reason);
        $reasonPreview = mb_strimwidth($reason, 0, 60, '…', 'UTF-8');

        $message = "Product {$productName} was rejected by {$actorName} ({$actorRole}). Reason: {$reasonPreview}";

        // Role-aware destination
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

        // Targeted recipients: assigned salesperson & artist for this order
        $targetIds = array_filter([
            $o->salesperson_id ?? null,
            $o->artist_id      ?? null,
        ]);

        if (!empty($targetIds)) {
            User::whereIn('id', $targetIds)->get()->each(function (User $u) use ($message, $urlFor) {
                Helpers::notify($u, $message, $urlFor($u), ['database']);
            });
        }

        // Optional broadcasts (keep/remove as you prefer)
        User::whereIn('role', ['head-salesperson','head-artist'])->get()
            ->each(function (User $u) use ($message, $urlFor) {
                Helpers::notify($u, $message, $urlFor($u), ['database']);
            });

        User::whereIn('role', ['admin','boss'])->get()
            ->each(function (User $u) use ($message, $urlFor) {
                Helpers::notify($u, $message, $urlFor($u), ['database']);
            });

        return back()->with('ok', 'Product rejected and order marked rejected.');
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
                'orders.salesperson_id')
            ->first();

        if (!$order || (int)($order->accepted ?? 0) !== 1 || strtolower((string)$order->orderStatus) === 'rejected') {
            return back()->with('error', 'This job cannot be edited.');
        }

        $cutters = (array) $request->input('cutters', []);
        $remarks = (array) $request->input('remarks', []);
        $userId   = Auth::id();

        // Preload existing specs for change detection
        $itemIds = array_keys($cutters);
        $existingSpecs = empty($itemIds)
            ? collect()
            : DB::table('specifications')
                ->whereIn('ItemID', array_map('intval', $itemIds))
                ->pluck('cutter', 'ItemID'); // [ItemID => cutter]

        // Diff trackers
        $cutterDiffs = []; 
        $remarksAdded = []; 

        DB::transaction(function () use (
            $product,
            $cutters,
            $remarks,
            $existingSpecs,  
            &$cutterDiffs,   
            &$remarksAdded,
            $userId,
        ) {
            // 1) Save cutters to specifications (by ItemID)
            foreach ($cutters as $itemId => $cutter) {
                $cutter = trim((string)$cutter);
                if ($itemId === '' || $itemId === null) continue;

                $itemId  = (int) $itemId;
                $new     = trim((string)$cutter);
                $newDb   = ($new === '' ? null : $new);
                $old     = $existingSpecs->get($itemId);

                if ($old !== $newDb) {
                    $type = $old === null && $newDb !== null ? 'set'
                        : ($old !== null && $newDb === null ? 'cleared'
                        : 'changed');
                    $cutterDiffs[] = ['item' => $itemId, 'from' => $old, 'to' => $newDb, 'type' => $type];
                }

                DB::table('specifications')->updateOrInsert(
                    ['ItemID' => (int) $itemId],
                    [
                        'cutter'     => $cutter === '' ? null : $cutter,
                        'updated_at' => now(),
                        'created_at' => now(), // harmless if row already exists
                    ]
                );
            }

            // 2) Append new remarks (if any) to product_remarks
            $allowedOps = ['printing','furnishing','installation','courier','self_pickup', 'artist'];

            foreach ($remarks as $row) {
                $opRaw = strtolower(trim((string)($row['operation'] ?? '')));
                $text  = trim((string)($row['remark'] ?? ''));

                if ($text === '') {
                    continue; // nothing to save
                }

                // Normalize a few common variants to our 5 keys
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
                    // fallback if the UI somehow sends an unexpected value
                    $op = 'furnishing';
                }

                // final guard to keep DB enum happy
                if (!in_array($op, $allowedOps, true)) {
                    $op = 'furnishing';
                }

                DB::table('product_remarks')->insert([
                    'ProductID'  => $product,
                    'operation'  => $op,   // exactly one of the 5 keys
                    'remark'     => $text,
                    'user_id'    => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $remarksAdded[] = ['operation' => $op, 'remark' => $text];
            }
        });

        // ==== Notifications (only if something changed) ====
        if (!empty($cutterDiffs) || !empty($remarksAdded)) {
            $actor     = Auth::user();
            $actorName = $actor?->name ?? 'System';
            $actorRole = str_replace('-', ' ', $actor?->role ?? 'user');

            $orderId   = (int)$order->order_id;
            $orderNo   = (string)$order->order_number;
            $prodId    = (int)$order->ProductID;
            $prodName  = (string)$order->productName;

            // Summaries
            $setCnt     = count(array_filter($cutterDiffs, fn($d) => $d['type'] === 'set'));
            $chgCnt     = count(array_filter($cutterDiffs, fn($d) => $d['type'] === 'changed'));
            $clrCnt     = count(array_filter($cutterDiffs, fn($d) => $d['type'] === 'cleared'));
            $remarksCnt = count($remarksAdded);

            $previewParts = [];
            if ($setCnt    ) $previewParts[] = "cutter set: {$setCnt}";
            if ($chgCnt    ) $previewParts[] = "cutter changed: {$chgCnt}";
            if ($clrCnt    ) $previewParts[] = "cutter cleared: {$clrCnt}";
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
                    Helpers::notify($u, $message, $urlFor($u), ['database']);
                });
            }

            // Optional broadcasts
            User::whereIn('role', ['head-salesperson','head-artist'])->get()
                ->each(function (User $u) use ($message, $urlFor) {
                    Helpers::notify($u, $message, $urlFor($u), ['database']);
                });

            User::whereIn('role', ['admin','boss'])->get()
                ->each(function (User $u) use ($message, $urlFor) {
                    Helpers::notify($u, $message, $urlFor($u), ['database']);
                });
        }

        return back()->with('ok', true);
    }

}
