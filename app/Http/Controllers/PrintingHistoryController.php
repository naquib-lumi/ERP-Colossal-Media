<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PrintingHistoryController extends Controller
{
    /**
     * GET /printing/history  路由名：printing.history
     */
    public function index(Request $request)
    {
        $q      = trim((string) $request->get('q', ''));
        $start  = trim((string) $request->get('start', ''));
        $end    = trim((string) $request->get('end', ''));

        $startYmd = $this->toYmd($start);
        $endYmd   = $this->toYmd($end);

        $base = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->where('p.status', 'completed')
            ->select([
                'p.ProductID',
                'p.OrderID',
                'o.order_number',
                // ✅ 别名按你的 blade：completed_date
                'p.updated_at as completed_date',
                DB::raw('COALESCE(p.productName, CONCAT("Product #", p.ProductID)) as product_name'),
                // 备注先给空串占位，避免不存在列报错
                DB::raw('"" as materialRemark'),
                DB::raw('CONCAT("ORD", o.id, "-P", LPAD(p.ProductID, 4, "0")) as product_code'),
            ]);

        // 搜索（Order No / ProductID / 名称）
        if ($q !== '') {
            $base->where(function ($w) use ($q) {
                $w->where('o.order_number', 'like', "%{$q}%")
                  ->orWhere('p.productName', 'like', "%{$q}%");

                if (preg_match('/^\d+$/', $q)) {
                    $w->orWhere('p.ProductID', (int) $q);
                } else {
                    $w->orWhere('p.ProductID', 'like', "%{$q}%");
                }
            });
        }

        // 完成时间范围（对 updated_at 过滤）
        if ($startYmd) {
            $base->whereDate('p.updated_at', '>=', $startYmd);
        }
        if ($endYmd) {
            $base->whereDate('p.updated_at', '<=', $endYmd);
        }

        $base->orderBy('p.updated_at', 'desc')
             ->orderBy('p.ProductID', 'asc');

        $orders = $base->paginate(10)->withQueryString();

        // 详情链接
        $orders->getCollection()->transform(function ($row) {
            $row->details_url = route('printing.orders.show', ['product' => $row->ProductID]);
            return $row;
        });

        //（可选）总数
        $totalCount = DB::table('products')->where('status', 'completed')->count();

        return view('printing.history', [
            'orders' => $orders,
            'q'      => $q,
            'start'  => $start,
            'end'    => $end,
            'total'  => $totalCount,
        ]);
    }

    /** mm/dd/yyyy -> yyyy-mm-dd，非法返回 null */
    private function toYmd(?string $us): ?string
    {
        if (!$us) return null;
        if (!preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $us, $m)) return null;
        [$all, $mm, $dd, $yy] = $m;
        try {
            return Carbon::createFromFormat('m/d/Y', "$mm/$dd/$yy")->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
