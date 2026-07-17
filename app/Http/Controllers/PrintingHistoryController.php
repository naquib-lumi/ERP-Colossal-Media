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
        $q          = trim((string) $request->get('q', ''));
        $pid        = trim((string) $request->query('pid', ''));
        $start      = trim((string) $request->get('start', ''));
        $end        = trim((string) $request->get('end', ''));
        $artist     = trim((string) $request->get('artist', ''));
        $sort       = (string) $request->get('sort', 'completed');
        $dir        = strtolower((string) $request->get('dir', 'desc'));
        $dir        = $dir === 'asc' ? 'asc' : 'desc';
        $onlyStatus = strtolower((string) $request->query('status', ''));

        /*
         * Preserve the existing minimum zero-padding while allowing IDs of any
         * length. Unlike LPAD(value, 3/4, '0'), these expressions never truncate
         * a 5-digit, 6-digit, or longer ID.
         *
         * Order examples:   7 => 007, 351 => 351, 12345 => 12345
         * Product examples: 7 => 0007, 642 => 0642, 123456 => 123456
         */
        $orderIdValueExpr = "CAST(COALESCE(o.redo, o.id) AS CHAR)";
        $productIdValueExpr = "CAST(COALESCE(p.redoOf, p.ProductID) AS CHAR)";

        $orderCodeExpr = "
            CONCAT(
                REPEAT('0', GREATEST(0, 3 - CHAR_LENGTH($orderIdValueExpr))),
                $orderIdValueExpr
            )
        ";

        $productCodeNumberExpr = "
            CONCAT(
                REPEAT('0', GREATEST(0, 4 - CHAR_LENGTH($productIdValueExpr))),
                $productIdValueExpr
            )
        ";

        $formattedCodeWithoutRedoExpr = "
            CONCAT(
                '#ORD-',
                LPAD(COALESCE(YEAR(o.orderDate), YEAR(o.created_at)), 4, '0'),
                '-',
                $orderCodeExpr,
                '-P',
                $productCodeNumberExpr
            )
        ";

        $toYmd = function (?string $v): ?string {
            if (!$v) return null;

            try {
                return \Carbon\Carbon::parse($v)->toDateString();
            } catch (\Throwable $e) {
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $v, $m)) {
                    return "{$m[3]}-"
                        . str_pad($m[1], 2, '0', STR_PAD_LEFT)
                        . "-"
                        . str_pad($m[2], 2, '0', STR_PAD_LEFT);
                }

                return null;
            }
        };

        // Same approach as dashboard: artists come from users referenced by orders.artist_id.
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

        // Editable redo children used to determine the trailing R.
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
                $w->whereNull('o.status')
                    ->orWhere('o.status', '!=', 1);
            })
            ->when($pid !== '', function ($qb) use ($pid, $formattedCodeWithoutRedoExpr) {
                $like = "%{$pid}%";

                $qb->where(function ($w) use ($pid, $like, $formattedCodeWithoutRedoExpr) {
                    // Exact numeric matches. Keep the value as a string so PHP does
                    // not impose an integer-size limit before the query reaches SQL.
                    if (ctype_digit($pid)) {
                        $w->orWhere('o.id', $pid)
                            ->orWhere('o.redo', $pid)
                            ->orWhere('p.ProductID', $pid)
                            ->orWhere('p.redoOf', $pid);
                    }

                    // Fuzzy and formatted-code matches.
                    $w->orWhere('o.id', 'like', $like)
                        ->orWhere('o.redo', 'like', $like)
                        ->orWhere('p.ProductID', 'like', $like)
                        ->orWhere('p.redoOf', 'like', $like)
                        ->orWhere('o.order_number', 'like', $like)
                        ->orWhere(
                            DB::raw($formattedCodeWithoutRedoExpr),
                            'like',
                            $like
                        );
                });
            })
            ->select([
                'p.ProductID',
                'o.orderTitle as product_name',
                'p.materialRemark',
                'p.status',
                DB::raw('fpx.completed_date'),
                'o.id as order_id',
                'o.order_number',
                DB::raw("
                    CONCAT(
                        $formattedCodeWithoutRedoExpr,
                        CASE
                            WHEN (
                                (p.redoOf IS NOT NULL AND p.editable = 1)
                                OR rr.redoOf IS NOT NULL
                            ) THEN 'R'
                            ELSE ''
                        END
                    ) AS product_code
                "),
            ]);

        // Unified keyword search. EXISTS is used for printer to avoid duplicate rows.
        if ($q !== '') {
            $like = "%{$q}%";

            $base->where(function ($w) use ($q, $like, $formattedCodeWithoutRedoExpr) {
                $w->where('o.orderTitle', 'like', $like)
                    ->orWhere('o.companyName', 'like', $like)
                    ->orWhere('p.productName', 'like', $like)
                    ->orWhere('o.order_number', 'like', $like)
                    ->orWhere('p.materialRemark', 'like', $like);

                if (preg_match('/^\d+$/', $q)) {
                    $w->orWhere('p.ProductID', $q);
                } else {
                    $w->orWhere('p.ProductID', 'like', $like);
                }

                $w->orWhereExists(function ($sub) use ($like) {
                    $sub->from('product_items as pi')
                        ->join('specifications as s', 's.ItemID', '=', 'pi.ItemID')
                        ->whereColumn('pi.ProductID', 'p.ProductID')
                        ->where('s.printer', 'like', $like);
                });

                $w->orWhere(
                    DB::raw($formattedCodeWithoutRedoExpr),
                    'like',
                    $like
                );
            });
        }

        // Artist filter.
        if ($artist !== '') {
            $base->where('o.artist_id', $artist);
        }

        // Completion-date range uses the fpx alias.
        if ($startYmd) {
            $base->whereDate('fpx.completed_date', '>=', $startYmd);
        }

        if ($endYmd) {
            $base->whereDate('fpx.completed_date', '<=', $endYmd);
        }

        // Ordering uses the fpx alias.
        $base->orderByRaw('fpx.completed_date IS NULL')
            ->orderBy('fpx.completed_date', $dir)
            ->orderBy('o.id')
            ->orderBy('p.ProductID');

        $orders = $base->paginate(10)->withQueryString();

        return view('printing.history', [
            'pid'     => $pid,
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
