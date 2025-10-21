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
        $pid      = trim((string) $request->query('pid', ''));
        $start  = trim((string) $request->get('start', ''));
        $end    = trim((string) $request->get('end', ''));
        $artist = trim((string) $request->get('artist', ''));
        $sort = (string) $request->get('sort', 'completed');        // fixed key
        $dir  = strtolower((string) $request->get('dir', 'desc'));
        $dir  = $dir === 'asc' ? 'asc' : 'desc';
        $onlyStatus = strtolower((string) $request->query('status', ''));

        $toYmd = function (?string $v): ?string {
            if (!$v) return null;
            try {
                return \Carbon\Carbon::parse($v)->toDateString();
            } catch (\Throwable $e) {
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $v, $m)) {
                    return "{$m[3]}-" . str_pad($m[1], 2, '0', STR_PAD_LEFT) . "-" . str_pad($m[2], 2, '0', STR_PAD_LEFT);
                }
                return null;
            }
        };

        // ✅ same approach as dashboard: artists come from users referenced by orders.artist_id
        $artists = DB::table('users as u')
            ->select('u.id', 'u.name')
            ->whereIn('u.id', function ($sub) {
                $sub->from('orders as o')
                    ->select('o.artist_id')
                    ->whereNotNull('o.artist_id');
            })
            ->orderBy('u.name')
            ->get();

        $startYmd = $toYmd($start);
        $endYmd   = $toYmd($end);

        $fpLatest = DB::table('fulfillment_progress')
            ->selectRaw('ProductID, MAX(completedAt) AS completed_date')
            ->where('stage', 'printing')
            ->where(function ($w) {
                $w->whereIn('status', ['completed', 'rejected'])
                    ->orWhereNotNull('completedAt');
            })
            ->groupBy('ProductID');

        // editable redo children (for trailing R)
        $redoChildren = DB::raw('(
    SELECT DISTINCT redoOf
    FROM products
    WHERE editable = 1 AND redoOf IS NOT NULL
) rr');

        $base = DB::table('products as p')
            ->joinSub($fpLatest, 'fpx', function ($j) {
                $j->on('fpx.ProductID', '=', 'p.ProductID');
            })
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin($redoChildren, 'rr.redoOf', '=', 'p.ProductID')
            ->where(function ($w) {
                $w->whereNull('o.status')->orWhere('o.status', '!=', 1);
            })
            ->when($pid !== '', function ($qb) use ($pid) {
                $like = "%{$pid}%";
                $qb->where(function ($w) use ($pid, $like) {
                    // numeric fast path
                    if (ctype_digit($pid)) {
                        $w->orWhere('o.id', (int)$pid)        // new order id
                        ->orWhere('o.redo', (int)$pid)      // original order id
                        ->orWhere('p.ProductID', (int)$pid) // new product id
                        ->orWhere('p.redoOf', (int)$pid);   // original product id
                    }
                    // fuzzy
                    $w->orWhere('o.id', 'like', $like)
                    ->orWhere('o.redo', 'like', $like)
                    ->orWhere('p.ProductID', 'like', $like)
                    ->orWhere('p.redoOf', 'like', $like)
                    ->orWhere('o.order_number', 'like', $like)
                    // formatted code WITHOUT trailing R
                    ->orWhere(DB::raw("
                        CONCAT(
                            '#ORD-',
                            LPAD(COALESCE(YEAR(o.orderDate), YEAR(o.created_at)), 4, '0'),
                            '-',
                            LPAD(COALESCE(o.redo, o.id), 3, '0'),
                            '-P',
                            LPAD(COALESCE(p.redoOf, p.ProductID), 4, '0')
                        )
                    "), 'like', $like);
                });
            })
            ->select([
                'p.ProductID',
                'p.productName as product_name',
                'p.materialRemark',
                DB::raw('fpx.completed_date'),
                'o.id as order_id',
                'o.order_number',
                DB::raw("
            CONCAT(
                '#ORD-',
                LPAD(COALESCE(YEAR(o.orderDate), YEAR(o.created_at)), 4, '0'),
                '-',
                LPAD(COALESCE(o.redo, o.id), 3, '0'),
                '-P',
                LPAD(COALESCE(p.redoOf, p.ProductID), 4, '0'),
                CASE
                    WHEN ((p.redoOf IS NOT NULL AND p.editable = 1) OR rr.redoOf IS NOT NULL) THEN 'R'
                    ELSE ''
                END
            ) AS product_code
        "),
            ]);

        // unified keyword search (use EXISTS for printer to avoid dupes)
        if ($q !== '') {
            $like = "%{$q}%";
            $base->where(function ($w) use ($q, $like) {
                $w->where('o.orderTitle', 'like', $like)
                    ->orWhere('o.companyName', 'like', $like)
                    ->orWhere('p.productName', 'like', $like)
                    ->orWhere('o.order_number', 'like', $like)
                    ->orWhere('p.materialRemark', 'like', $like);

                if (preg_match('/^\d+$/', $q)) $w->orWhere('p.ProductID', (int)$q);
                else                           $w->orWhere('p.ProductID', 'like', $like);

                $w->orWhereExists(function ($sub) use ($like) {
                    $sub->from('product_items as pi')
                        ->join('specifications as s', 's.ItemID', '=', 'pi.ItemID')
                        ->whereColumn('pi.ProductID', 'p.ProductID')
                        ->where('s.printer', 'like', $like);
                });

                $w->orWhere(DB::raw("
            CONCAT(
                '#ORD-',
                LPAD(COALESCE(YEAR(o.orderDate), YEAR(o.created_at)), 4, '0'),
                '-',
                LPAD(COALESCE(o.redo, o.id), 3, '0'),
                '-P',
                LPAD(COALESCE(p.redoOf, p.ProductID), 4, '0')
            )
        "), 'like', $like);
            });
        }

        // artist filter
        if ($artist !== '') {
            $base->where('o.artist_id', (int)$artist);
        }

        // completed range uses fpx alias
        if ($startYmd) $base->whereDate('fpx.completed_date', '>=', $startYmd);
        if ($endYmd)   $base->whereDate('fpx.completed_date', '<=', $endYmd);

        // ordering uses fpx alias too
        $base->orderByRaw('fpx.completed_date IS NULL')
            ->orderBy('fpx.completed_date', $dir)
            ->orderBy('o.id')
            ->orderBy('p.ProductID');

        $orders = $base->paginate(10)->withQueryString();

        return view('printing.history', [
            'pid'        => $pid,
            'orders'  => $orders,
            'q'       => $q,
            'start'   => $start,
            'end'     => $end,
            'artists' => $artists,
            'total'   => DB::table('products')->where('status', 'completed')->count(),
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

    public function show($productId)
    {
        // Reuse the same data as FurnishingProductOrderController@show
        $data = app(\App\Http\Controllers\PrintingProductOrderController::class)->show($productId);

        // If show() in the original controller returns a view,
        // we can just re-render that but hide the actionbar using a flag.
        return $data->with('isHistoryView', true);
    }
}
