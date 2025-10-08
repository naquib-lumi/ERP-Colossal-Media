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
                  ->orWhere('o.order_number', 'like', "%{$q}%")
                  ->orWhere('p.ProductID', 'like', "%{$q}%")
                  ->orWhere(function ($w2) use ($q) {
                      // search ItemID through subquery result by re-checking product_items
                      $w2->whereIn('p.ProductID', function ($s) use ($q) {
                          $s->from('product_items')
                            ->select('ProductID')
                            ->where('ItemID', 'like', "%{$q}%");
                      });
                  });
            });
        }

        // Date filters against fulfillment_progress.completedAt
        if ($start !== '') {
            try {
                $startDate = Carbon::createFromFormat('m/d/Y', $start)->startOfDay();
                $query->whereDate('fp.completedAt', '>=', $startDate->toDateString());
            } catch (\Throwable $e) { /* ignore */ }
        }
        if ($end !== '') {
            try {
                $endDate = Carbon::createFromFormat('m/d/Y', $end)->endOfDay();
                $query->whereDate('fp.completedAt', '<=', $endDate->toDateString());
            } catch (\Throwable $e) { /* ignore */ }
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