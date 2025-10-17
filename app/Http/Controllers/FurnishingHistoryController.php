<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FurnishingHistoryController extends Controller
{
    public function index(Request $request)
    {
        $q       = trim((string) $request->get('q', ''));
        $start   = trim((string) $request->get('start', ''));
        $end     = trim((string) $request->get('end', ''));
        $artist  = trim((string) $request->get('artist', ''));
        $sort = (string) $request->get('sort', 'completed');      // fixed key
        $dir  = strtolower((string) $request->get('dir', 'desc')); // default DESC
        $dir  = $dir === 'asc' ? 'asc' : 'desc';

        // mm/dd/yyyy or any parseable → yyyy-mm-dd
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

        $startYmd = $toYmd($start);
        $endYmd   = $toYmd($end);

        // Build base query for furnishing completion history
        $base = DB::table('fulfillment_progress as fp')
            ->join('products as p', 'p.ProductID', '=', 'fp.ProductID')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('product_items as pi', 'pi.ProductID', '=', 'p.ProductID')
            ->leftJoin('specifications as s', 's.ItemID', '=', 'pi.ItemID')
            ->where('fp.stage', 'furnishing')
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
                // "#ORD-YYYY-OOO-PXXXX" (year from orderDate or created_at)
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

        // Unified keyword search (order title/company/product/cutter/remarks/order_number/ProductID)
        if ($q !== '') {
            $like = "%{$q}%";
            $base->where(function ($w) use ($q, $like) {
                $w->where('o.orderTitle', 'like', $like)
                ->orWhere('o.companyName', 'like', $like)
                ->orWhere('p.productName', 'like', $like)
                ->orWhere('s.cutter', 'like', $like)
                ->orWhere('o.order_number', 'like', $like)
                ->orWhere('p.materialRemark', 'like', $like);

                if (preg_match('/^\d+$/', $q)) {
                    $w->orWhere('p.ProductID', (int) $q);
                } else {
                    $w->orWhere('p.ProductID', 'like', $like);
                }
            });
        }

        // Artist filter (orders.artist_id -> users.id)
        if ($artist !== '') {
            $base->where('o.artist_id', (int) $artist);
        }

        // Completed date range (fp.completedAt)
        if ($startYmd) $base->whereDate('fp.completedAt', '>=', $startYmd);
        if ($endYmd)   $base->whereDate('fp.completedAt', '<=', $endYmd);

        // Server-side sort across all pages
        if ($sort === 'completed') {
            // plain asc/desc completed date
            $base->orderByRaw('fp.completedAt IS NULL')
                ->orderBy('fp.completedAt', $dir)
                ->orderBy('o.id')->orderBy('p.ProductID');
        }

        $orders = $base->paginate(10)->withQueryString();

        // Artists for dropdown (same approach as printing)
        $artists = DB::table('users as u')
            ->select('u.id','u.name')
            ->whereIn('u.id', function ($sub) {
                $sub->from('orders as o')->select('o.artist_id')->whereNotNull('o.artist_id');
            })
            ->orderBy('u.name')
            ->get();

        return view('furnishing.history', [
            'orders'  => $orders,
            'q'       => $q,
            'start'   => $start,
            'end'     => $end,
            'artists' => $artists,
            'sort'    => $sort,
            'dir'     => $dir,
            'total'   => DB::table('products')->where('status', 'completed')->count(),
        ]);
    }

    public function show($productId)
    {
        // Reuse the same data as FurnishingProductOrderController@show
        $data = app(\App\Http\Controllers\FurnishingProductOrderController::class)->show($productId);

        // If show() in the original controller returns a view,
        // we can just re-render that but hide the actionbar using a flag.
        return $data->with('isHistoryView', true);
    }

}