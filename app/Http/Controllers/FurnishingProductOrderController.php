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
        // --- header: product + order + artist name ---
        $headerRow = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('users  as u', 'u.id', '=', 'o.artist_id')   // artist on order table
            ->where('p.ProductID', $productId)
            ->select([
                'p.ProductID',
                'p.OrderID',
                'p.status',
                'p.productName as productName',
                'p.totalQuantity',
                'p.materialRemark',
                'o.order_number',
                'o.orderTitle',              // << we will show this as Job Title
                'o.companyName',
                'o.orderDate',
                'o.deadline',
                'o.artist_id',
                'o.orderAttachment',
                DB::raw('COALESCE(u.name, "") as artist_name'),
            ])
            ->first();

        if (!$headerRow) {
            abort(404);
        }

        // Build product code like: #ORD-2025-003-P0110
        $orderCode    = $headerRow->order_number ?: sprintf('ORD-%03d', (int) $headerRow->OrderID);
        $productCode  = sprintf('%s-P%04d', $orderCode, (int) $productId);

        // ---- Build a compact product header for the blade ----
        $productHeader = [
            'name'     => trim((string)($headerRow->productName ?? $headerRow->product_name_fallback ?? '')),
            'code'     => $productCode,                                         // e.g. #ORD-2025-003-P0110
            'qty'      => (int)($headerRow->totalQuantity ?? 0),                // products.totalQuantity
            'material' => trim((string)($headerRow->materialRemark ?? '')),     // products.materialRemark
        ];

        // pass it to the view
        $data['product_header'] = $productHeader;

        // Dates bucket (just for safe blade usage)
        $dates = [
            'order_date' => $headerRow->orderDate,
            'deadline'   => $headerRow->deadline,
        ];

        // Normalize header object for the view
        $header = (object) [
            'ProductID'   => $headerRow->ProductID,
            'OrderID'     => $headerRow->OrderID,
            'status'      => $headerRow->status,
            'order_number' => $headerRow->order_number,
            'order_title' => $headerRow->orderTitle,     // <-- use this in blade
            'companyName' => $headerRow->companyName,
            'artist_name' => $headerRow->artist_name,
        ];

        // ----- Product remarks (chips) -----
        // show all remarks for this product regardless of operation
        $remarkRows = DB::table('product_remarks')
            ->where('ProductID', $productId)
            ->orderBy('created_at')
            ->get();

        $remarkLabels = $remarkRows->map(function ($row) {
            // Map 'installation' → 'delivery & installation'
            $operation = strtolower($row->operation ?? '');
            $labelOperation = $operation === 'installation' ? 'delivery & installation' : $operation;

            // Combine operation and remark text
            return trim($labelOperation ? "{$labelOperation}: {$row->remark}" : $row->remark);
        })
            ->filter(fn($v) => $v !== '')
            ->unique()
            ->values()
            ->all();

        // ----- Product items + specifications -----
        $itemsRaw = DB::table('product_items as i')
            ->leftJoin('specifications as s', 's.ItemID', '=', 'i.ItemID')
            ->where('i.ProductID', $productId)
            ->orderBy('i.ItemID')
            ->get();

        $items = $itemsRaw->map(function ($r) {
            // Material can be JSON array in DB; render nicely if so
            $material = $r->material;
            if (is_string($material) && strlen($material)) {
                $trim = ltrim($material);
                if (isset($trim[0]) && ($trim[0] === '[' || $trim[0] === '{')) {
                    $decoded = json_decode($material, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        if (is_array($decoded)) {
                            $material = implode(', ', array_filter($decoded, fn($v) => $v !== '' && $v !== null));
                        }
                    }
                }
            }

            // Size (W × H unit)
            $fmt = fn($n) => $n === null ? null : rtrim(rtrim(number_format((float)$n, 2, '.', ''), '0'), '.');
            $size = null;
            if ($r->sizeWidth !== null || $r->sizeHeight !== null) {
                $w = $fmt($r->sizeWidth);
                $h = $fmt($r->sizeHeight);
                $size = trim(($w ?: '0') . ' × ' . ($h ?: '0') . ' ' . (string)($r->sizeUnit ?? ''));
            }

            // Bleed (top / right / bottom / left unit)
            $bleed = null;
            if ($r->bleedTop !== null || $r->bleedRight !== null || $r->bleedBottom !== null || $r->bleedLeft !== null) {
                $parts = [
                    $fmt($r->bleedTop)    ?? '0',
                    $fmt($r->bleedRight)  ?? '0',
                    $fmt($r->bleedBottom) ?? '0',
                    $fmt($r->bleedLeft)   ?? '0',
                ];
                $bleed = implode(' / ', $parts) . ' ' . (string)($r->bleedUnit ?? '');
            }

            // Flags
            $prime = (int)($r->prime_centre ?? 0) === 1;
            $assemble = false;
            // product_items.finishing can be yes/no or null in your schema; treat "yes" or 1 as true
            if (is_string($r->finishing)) {
                $assemble = strtolower($r->finishing) === 'yes';
            } elseif (!is_null($r->finishing)) {
                $assemble = (int)$r->finishing === 1;
            }

            return [
                'name'         => $r->itemName,
                'qty'          => $r->quantity,
                'size'         => $size,
                'bleed'        => $bleed,
                'material'     => $material,
                'prime'        => $prime,
                'lamination'   => $r->lamination,
                'printer'      => $r->printer,
                'cutter'       => $r->cutter,
                'assemble'     => $assemble,
            ];
        })->values();

        // --- Delivery breakdowns ---
        $rows = DB::table('delivery_breakdowns')
            ->where('ProductID', $productId)
            ->select([
                'method',
                'deliver_install_type',
                'outsource_cost',
                'quantity',
                'date',
                'time',
                'location',
            ])
            ->orderBy('BreakdownID')
            ->get();

        // Canonicalize method to: pickup | courier | install
        $canon = function (?string $raw): array {
            $m = strtolower(trim((string)$raw));

            if (str_contains($m, 'courier')) {
                return ['key' => 'courier', 'label' => 'Courier', 'icon' => 'bi-box-arrow-up-right'];
            }

            if (str_contains($m, 'self') || str_contains($m, 'pickup')) {
                return ['key' => 'pickup', 'label' => 'Self Pickup', 'icon' => 'bi-bag-check'];
            }

            // treat anything else as Delivery & Installation
            return ['key' => 'install', 'label' => 'Delivery & Installation', 'icon' => 'bi-truck'];
        };

        // Map rows for Blade
        $deliveries = $rows->map(function ($d) use ($canon) {
            $m = $canon($d->method);

            // date & time -> single display string
            $dateTime = null;
            if ($d->date) {
                $dt = $d->time
                    ? \Carbon\Carbon::parse($d->date . ' ' . $d->time)
                    : \Carbon\Carbon::parse($d->date);
                $dateTime = $d->time ? $dt->format('Y-m-d H:i') : $dt->format('Y-m-d');
            }

            return [
                'method'       => $m['key'],            // courier | pickup | install
                'method_label' => $m['label'],          // Courier | Self Pickup | Delivery & Installation
                'icon'         => $m['icon'],

                'quantity'     => (int) ($d->quantity ?? 0),
                'address'      => (string) ($d->location ?? ''),
                'datetime'     => $dateTime,
                'install'      => $d->deliver_install_type,                       // outsource / both / null
                'cost'         => $d->outsource_cost !== null ? (float) $d->outsource_cost : null,
            ];
        });

        // Total quantity is what was ordered on the product itself
        $productQty = (int) ($headerRow->totalQuantity ?? 0);

        // Delivered is simply the sum of quantities in delivery_breakdowns for this ProductID
        $deliveredQty = (int) $rows->sum(function ($r) {
            return (int) ($r->quantity ?? 0);
        });

        // Remaining = Total - Delivered (never negative)
        $remainingQty = max(0, $productQty - $deliveredQty);

        // Pass to the blade
        $totals = [
            'total'     => $productQty,
            'delivered' => $deliveredQty,
            'remaining' => $remainingQty,
        ];

        $data['deliveries'] = $deliveries->values()->all();
        $data['totals']     = $totals;


        // Who to show in the chips
        $assignee = $header->artist_name ?: '—';
        $uploader = $assignee;

        // Permit & attachments (dummy for now – keep your existing logic or wire real files)
        $permit = ['name' => 'Permit.pdf', 'size' => '1.2 MB', 'url' => '#'];
        $attachments = [];
            $raw = (string)($headerRow->orderAttachment ?? '');

            if ($raw !== '') {
                $paths = [];

                // JSON array?
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $paths = array_values(array_filter($decoded));
                } else {
                    // comma / whitespace separated
                    $paths = preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
                }

                foreach ($paths as $p) {
                    $p = ltrim($p, '/');

                    // Build a URL: prefer public disk, otherwise fall back to asset()
                    if (Str::startsWith($p, ['http://', 'https://'])) {
                        $url = $p;
                    } elseif (Storage::disk('public')->exists($p)) {
                        $url = Storage::url($p);              // e.g. /storage/...
                    } elseif (Storage::exists($p)) {
                        $url = Storage::url($p);
                    } else {
                        $url = asset($p);                     // last resort
                    }

                    $attachments[] = [
                        'name' => basename($p),
                        'size' => '',                         // size unknown (optional)
                        'url'  => $url,
                    ];
                }
            }

        return view('furnishing.job_order_show', [
            'product_code'  => $productCode,
            'header'        => $header,           
            'dates'         => $dates,            
            'remarkLabels'  => $remarkLabels,     
            'items'         => $items,            
            'deliveries'    => $deliveries->toArray(),      
            'totals'        => [
                'total'     => $productQty,
                'delivered' => $deliveredQty,
                'remaining' => $remainingQty,
            ],
            'assignee'      => $assignee,
            'uploader'      => $uploader,
            'permit'        => $permit,
            'attachments'   => $attachments,
            'product_header' => $productHeader,
            'attachments'   => $attachments,  
            'uploader'      => $uploader,
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
