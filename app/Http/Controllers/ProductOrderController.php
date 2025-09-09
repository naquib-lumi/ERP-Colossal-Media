<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProductOrderController extends Controller
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
            ->when($status !== 'all', fn ($c) => $c->where('status', $status))
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

        return view('productorder.dashboard', [ // 视图路径按需调整
            'statuses' => $statuses,
            'search'   => $search,
            'status'   => $status,
            'orders'   => $orders,
        ]);
    }

    /**
     *（可选）详情页：同样基于 dummy data
     */
    public function show($id)
    {
        $order = $this->makeDummyOrders(40)->firstWhere('id', (int) $id);
        abort_unless($order, 404);

        return view('productorder.show', [
            'order' => $order,
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
                'assigned_to'   => ['Artist A','Artist B','Artist C'][array_rand(['a','b','c'])],
                'printer'       => ['Handtop Hybrid','Epson UV','Roland VersaUV'][array_rand(['a','b','c'])],
            ];
        });
    }
}
