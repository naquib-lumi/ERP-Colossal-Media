<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PrintingHistoryController extends Controller
{
    public function index(Request $request)
    {
        $q     = trim($request->get('q', ''));
        $start = trim($request->get('start', ''));  // mm/dd/yyyy
        $end   = trim($request->get('end', ''));    // mm/dd/yyyy

        $perPage = 8;

        $query = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('product_items as pi', 'pi.ProductID', '=', 'p.ProductID')
            ->select([
                'p.ProductID',
                'p.productName as product_name',
                'p.materialRemark',
                'p.updated_at as completed_date',   // when printing finished
                'o.id as order_id',
                'o.order_number',
                'pi.ItemID',

                DB::raw("
                    CASE
                    WHEN o.order_number IS NOT NULL AND o.order_number <> ''
                        THEN CONCAT(o.order_number, '-P', LPAD(p.ProductID, 4, '0'))
                    ELSE CONCAT(o.id, '-P', LPAD(p.ProductID, 4, '0'))
                    END AS product_code
                "),
            ])
            ->whereRaw('LOWER(p.taskType) = ?', ['printing'])
            ->where('p.status', 'completed');

        // Text search (product id, order no, name)
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('p.productName', 'like', "%{$q}%")
                  ->orWhere('o.order_number', 'like', "%{$q}%")
                  ->orWhere('p.ProductID', 'like', "%{$q}%")
                  ->orWhere('pi.ItemID', 'like', "%{$q}%");
            });
        }

        // ---- Date filters (Completed Date = products.updated_at) ----
        $parse = function (string $v) {
            $v = trim($v);
            if ($v === '') return null;

            // Accept mm/dd/yyyy OR yyyy-mm-dd
            try { return \Carbon\Carbon::createFromFormat('m/d/Y', $v); } catch (\Throwable $e) {}
            try { return \Carbon\Carbon::createFromFormat('Y-m-d', $v); } catch (\Throwable $e) {}
            try { return \Carbon\Carbon::parse($v); } catch (\Throwable $e) {}

            return null;
        };

        $startDate = $parse($start);
        $endDate   = $parse($end);

        if ($startDate && $endDate && $startDate->gt($endDate)) {
            // swap if user entered reversed range
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        if ($startDate) {
            $query->whereDate('p.updated_at', '>=', $startDate->copy()->startOfDay()->toDateString());
        }
        if ($endDate) {
            $query->whereDate('p.updated_at', '<=', $endDate->copy()->endOfDay()->toDateString());
        }

        $orders = $query
            ->orderByDesc('p.updated_at')
            ->paginate($perPage)
            ->appends($request->query());

        return view('printing.history', compact('orders', 'q', 'start', 'end'));
    }
}
