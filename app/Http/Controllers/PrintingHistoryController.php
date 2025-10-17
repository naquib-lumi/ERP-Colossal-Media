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
        $artist = trim((string) $request->get('artist', ''));
        $sort = (string) $request->get('sort', 'completed');        // fixed key
        $dir  = strtolower((string) $request->get('dir', 'desc'));
        $dir  = $dir === 'asc' ? 'asc' : 'desc';

        $toYmd = function (?string $v): ?string {
            if (!$v) return null;
            try { return \Carbon\Carbon::parse($v)->toDateString(); }
            catch (\Throwable $e) {
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $v, $m)) {
                    return "{$m[3]}-".str_pad($m[1],2,'0',STR_PAD_LEFT)."-".str_pad($m[2],2,'0',STR_PAD_LEFT);
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

        $base = DB::table('fulfillment_progress as fp')
            ->join('products as p', 'p.ProductID', '=', 'fp.ProductID')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('product_items as pi', 'pi.ProductID', '=', 'p.ProductID')
            ->leftJoin('specifications as s', 's.ItemID', '=', 'pi.ItemID')
            ->where('fp.stage', 'printing')
            ->where(function ($w) {
                $w->whereIn('fp.status', ['completed', 'rejected'])
                ->orWhereNotNull('fp.completedAt');
            })
            ->select([
                'p.ProductID',
                'p.productName as product_name',
                'p.materialRemark',
                DB::raw('fp.completedAt as completed_date'),
                'o.id as order_id',
                'o.order_number',
                DB::raw("
                    CONCAT(
                        '#ORD-',
                        LPAD(COALESCE(YEAR(o.orderDate), YEAR(o.created_at)), 4, '0'),
                        '-',
                        LPAD(o.id, 3, '0'),
                        '-P',
                        LPAD(p.ProductID, 4, '0')
                    ) as product_code
                "),
            ]);

        // 🔎 unified keyword search (order title/company/product/printer/etc.)
        if ($q !== '') {
            $like = "%{$q}%";
            $base->where(function ($w) use ($q, $like) {
                $w->where('o.orderTitle', 'like', $like)
                ->orWhere('o.companyName', 'like', $like)
                ->orWhere('p.productName', 'like', $like)
                ->orWhere('s.printer', 'like', $like)
                ->orWhere('o.order_number', 'like', $like)
                ->orWhere('p.materialRemark', 'like', $like);
                if (preg_match('/^\d+$/', $q)) $w->orWhere('p.ProductID', (int)$q);
                else $w->orWhere('p.ProductID', 'like', $like);
            });
        }

        // 🎯 artist filter (orders.artist_id)
        if ($artist !== '') {
            $base->where('o.artist_id', (int)$artist);
        }

        // 📅 completedAt range
        if ($startYmd) $base->whereDate('fp.completedAt', '>=', $startYmd);
        if ($endYmd)   $base->whereDate('fp.completedAt', '<=', $endYmd);

        $base->orderByRaw('fp.completedAt IS NULL')   // NULLs last
            ->orderBy('fp.completedAt', $dir)        // ASC or DESC
            ->orderBy('o.id')                        // stable tiebreakers
            ->orderBy('p.ProductID');

        $orders = $base->paginate(10)->withQueryString();

        return view('printing.history', [
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
