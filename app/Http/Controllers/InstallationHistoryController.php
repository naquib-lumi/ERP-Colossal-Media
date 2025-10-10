<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class InstallationHistoryController extends Controller
{
    public function index(Request $request)
    {
        $q     = trim($request->get('q', ''));
        $start = trim($request->get('start', ''));  // mm/dd/yyyy
        $end   = trim($request->get('end', ''));    // mm/dd/yyyy
        $perPage = 8;

        $query = DB::table('fulfillment_progress as fp')
            ->join('products as p', 'p.ProductID', '=', 'fp.ProductID')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->whereRaw('LOWER(fp.stage) = ?', ['installation'])
            ->whereRaw('LOWER(fp.status) = ?', ['completed'])
            ->select([
                'p.ProductID',
                'p.productName as product_name',
                'p.materialRemark as remarks',
                'o.id as order_id',
                'o.order_number',
                'fp.completedAt as completed_date',   // ✅ Completed Date comes from progress table
            ]);

        if ($q !== '') {
            $qLower = mb_strtolower($q);
            $query->where(function ($w) use ($q, $qLower) {
                // product name / remarks
                $w->where('p.productName', 'like', "%{$q}%")
                ->orWhere('p.materialRemark', 'like', "%{$q}%")
                // completed date text search (yyyy-mm-dd or part of it)
                ->orWhereRaw('DATE(fp.completedAt) LIKE ?', ["%{$qLower}%"])
                // order number too (often useful)
                ->orWhere('o.order_number', 'like', "%{$q}%");
            });
        }

        // Date range (Completed Date = fp.completedAt)
        if ($start !== '') {
            try {
                $startDate = Carbon::createFromFormat('m/d/Y', $start)->startOfDay();
                $query->where('fp.completedAt', '>=', $startDate);
            } catch (\Throwable $e) {}
        }
        if ($end !== '') {
            try {
                $endDate = Carbon::createFromFormat('m/d/Y', $end)->endOfDay();
                $query->where('fp.completedAt', '<=', $endDate);
            } catch (\Throwable $e) {}
        }

        $orders = $query
            ->orderByDesc('fp.completedAt')
            ->paginate($perPage)
            ->appends($request->query());

        return view('installation.history', compact('orders', 'q', 'start', 'end'));
    }

    public function show($productId)
    {
        // Reuse the same data as FurnishingProductOrderController@show
        $data = app(\App\Http\Controllers\InstallationProductOrderController::class)->show($productId);

        // If show() in the original controller returns a view,
        // we can just re-render that but hide the actionbar using a flag.
        return $data->with('isHistoryView', true);
    }
}
