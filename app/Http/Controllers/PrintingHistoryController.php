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

        // Date filters (Completed Date = products.updated_at)
        if ($start !== '') {
            try {
                $startDate = Carbon::createFromFormat('m/d/Y', $start)->startOfDay();
                $query->whereDate('p.updated_at', '>=', $startDate->toDateString());
            } catch (\Throwable $e) {}
        }
        if ($end !== '') {
            try {
                $endDate = Carbon::createFromFormat('m/d/Y', $end)->endOfDay();
                $query->whereDate('p.updated_at', '<=', $endDate->toDateString());
            } catch (\Throwable $e) {}
        }

        $orders = $query
            ->orderByDesc('p.updated_at')
            ->paginate($perPage)
            ->appends($request->query());

        return view('printing.history', compact('orders', 'q', 'start', 'end'));
    }
}
