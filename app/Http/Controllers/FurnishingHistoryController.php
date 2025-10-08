<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FurnishingHistoryController extends Controller
{
    public function index(Request $request)
    {
        $q     = trim($request->get('q', ''));
        $start = trim($request->get('start', ''));  // mm/dd/yyyy
        $end   = trim($request->get('end', ''));    // mm/dd/yyyy

        $perPage = 8;

        // Base: fulfillment_progress (completed) + product + order
        $query = DB::table('fulfillment_progress as fp')
            ->join('products as p', 'p.ProductID', '=', 'fp.ProductID')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->select([
                'p.ProductID',
                'p.productName as product_name',
                'p.materialRemark',
                DB::raw('fp.completedAt as completed_date'),
                'o.id as order_id',
                'o.order_number',
            ])
            // ✅ Only the furnishing stage
            ->where('fp.stage', '=', 'furnishing')
            // ✅ And only completed rows
            ->where(function ($w) {
                $w->where('fp.status', 'completed')
                ->orWhereNotNull('fp.completedAt');
            });

        // keep this subquery (ItemID) and all your search/date/pagination logic
        $query->selectSub(function ($sub) {
            $sub->from('product_items')
                ->selectRaw('MIN(ItemID)')
                ->whereColumn('ProductID', 'p.ProductID');
        }, 'ItemID');

        // Text search (by product name, order no, product id, item id)
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('p.productName', 'like', "%{$q}%")
                ->orWhere('p.materialRemark', 'like', "%{$q}%")
                ->orWhere('o.order_number', 'like', "%{$q}%")
                ->orWhere('p.ProductID', 'like', "%{$q}%");
            });
        }

        // helper inside index() (or make it a private method in the controller)
        $parseDate = function (?string $s) {
            $s = trim((string)$s);
            if ($s === '') return null;

            try {
                // HTML date input gives YYYY-MM-DD
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
                    return Carbon::createFromFormat('Y-m-d', $s);
                }
                // fallback to mm/dd/yyyy
                return Carbon::createFromFormat('m/d/Y', $s);
            } catch (\Throwable $e) {
                return null;
            }
        };

        $startDate = $parseDate($start);
        $endDate   = $parseDate($end);

        if ($startDate) {
            $query->whereDate('fp.completedAt', '>=', $startDate->toDateString());
        }
        if ($endDate) {
            $query->whereDate('fp.completedAt', '<=', $endDate->toDateString());
        }


        $orders = $query
            ->orderByDesc('fp.completedAt')
            ->paginate($perPage)
            ->appends($request->query());

        return view('furnishing.history', compact('orders', 'q', 'start', 'end'));
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