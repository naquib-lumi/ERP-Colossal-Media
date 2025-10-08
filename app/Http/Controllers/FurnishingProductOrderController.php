<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class FurnishingProductOrderController extends Controller
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
            ->where('p.ProductID', $productId)
            ->select([
                'p.ProductID',
                'p.OrderID',
                'p.status',
                'p.productName as productName',
                'p.totalQuantity',
                'p.materialRemark',
                'o.order_number',
                'o.orderTitle',
                'o.companyName',
                'o.orderDate',
                'o.deadline',
                'o.artist_id',
                'o.orderAttachment',
                DB::raw('COALESCE(u.name, "") as artist_name'),
            ])
            ->first();

        if (!$headerRow) abort(404);

        // Helper to build a product code like #ORD-2025-003-P0110
        $orderCodeFor = function ($orderId, $orderNumber) {
            return $orderNumber ?: sprintf('ORD-%03d', (int)$orderId);
        };
        $orderCode   = $orderCodeFor($headerRow->OrderID, $headerRow->order_number);
        $productCode = sprintf('%s-P%04d', $orderCode, (int)$productId);

        // Header object used by your current blade
        $header = (object)[
            'ProductID'    => $headerRow->ProductID,
            'OrderID'      => $headerRow->OrderID,
            'status'       => $headerRow->status,
            'order_number' => $headerRow->order_number,
            'order_title'  => $headerRow->orderTitle,
            'companyName'  => $headerRow->companyName,
            'artist_name'  => $headerRow->artist_name,
        ];

        $dates = [
            'order_date' => $headerRow->orderDate,
            'deadline'   => $headerRow->deadline,
        ];

        // ---- compact product header (for the selected product) ----
        $productHeader = [
            'name'     => trim((string)($headerRow->productName ?? '')),
            'code'     => $productCode,
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

            $code = sprintf('%s-P%04d', $orderCodeFor($headerRow->OrderID, $headerRow->order_number), (int)$pid);

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
            'uploader'       => $uploader,
            'permit'         => $permit,
            'attachments'    => $attachments,
            'product_header' => $productHeader,

            // NEW: all products for the same order
            'blocks'         => $blocks,
        ]);
    }


    /**
     * 生成假订单集合
     */
    private function makeDummyOrders(int $count = 30): Collection
    {
        $products  = ['Business Cards Premium', 'Flyers A5', 'Poster A3', 'Stickers', 'Brochure Tri-Fold'];
        $customers = ['ACME Inc.', 'Globex', 'Soylent', 'Initech', 'Umbrella', 'Wayne Corp', 'Stark Industries'];
        $statusKeys = ['pending', 'processing', 'completed', 'cancelled', 'refunded', 'issue'];

        return collect(range(1, $count))->map(function ($i) use ($products, $customers, $statusKeys) {
            $created  = Carbon::now()->subDays(rand(0, 60))->subMinutes(rand(0, 1440));
            $deadline = (clone $created)->addDays(rand(2, 14))->setTime(rand(9, 18), [0, 15, 30, 45][rand(0, 3)]);

            return [
                'id'            => $i,
                'order_no'      => sprintf('#ORD-%04d', $i),
                'product_name'  => $products[array_rand($products)],
                'customer_name' => $customers[array_rand($customers)],
                'status'        => $statusKeys[array_rand($statusKeys)],
                'quantity'      => rand(50, 2000),
                'unit_price'    => rand(5, 50),               // 假单价
                'total'         => rand(120, 5000),           // 假总价
                'created_at'    => $created,
                'deadline_at'   => $deadline,
                // 供 UI 细节展示的额外字段
                'assigned_to'   => ['Artist A', 'Artist B', 'Artist C'][array_rand(['a', 'b', 'c'])],
                'printer'       => ['Handtop Hybrid', 'Epson UV', 'Roland VersaUV'][array_rand(['a', 'b', 'c'])],
            ];
        });
    }
}
