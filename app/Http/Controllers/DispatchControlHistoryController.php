<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DispatchControlHistoryController extends Controller
{
    public function index(Request $request)
{
    $q     = trim($request->get('q', ''));
    $start = trim($request->get('start', ''));
    $end   = trim($request->get('end', ''));
    $perPage = 8;

    $query = DB::table('products as p')
        ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
        ->leftJoin('product_items as pi', 'pi.ProductID', '=', 'p.ProductID')
        ->select([
            // NEW: pretty product code like #ORD-3-P0016
            DB::raw("CONCAT('#ORD-', COALESCE(p.OrderID, o.id), '-P', LPAD(p.ProductID, 4, '0')) as product_code"),

            // keep raw ids if you need them elsewhere
            'p.ProductID as product_id',
            'p.OrderID as order_id',

            'p.productName as product_name',
            DB::raw("DATE_FORMAT(p.updated_at, '%b %d, %Y') as completed_date"),
            DB::raw("COALESCE(NULLIF(p.materialRemark,''), '–') as remarks"),
            DB::raw('NULL as proof_url'),
            'o.order_number',
        ])
        ->where('p.status', 'completed')
        ->where(function ($w) {
            $w->whereIn(DB::raw("LOWER(COALESCE(p.taskType, ''))"), [
                'dispatch','delivery','printing','furnishing'
            ])->orWhereNull('p.taskType');
        });

    if ($q !== '') {
        $query->where(function ($w) use ($q) {
            $w->where('p.productName', 'like', "%{$q}%")
              ->orWhere('o.order_number', 'like', "%{$q}%")
              ->orWhere('p.ProductID', 'like', "%{$q}%")
              ->orWhere('pi.ItemID', 'like', "%{$q}%");
        });
    }

    try {
        if ($start !== '') {
            $s = \Carbon\Carbon::createFromFormat('m/d/Y', $start)->startOfDay()->toDateString();
            $query->whereDate('p.updated_at', '>=', $s);
        }
    } catch (\Throwable $e) {}
    try {
        if ($end !== '') {
            $e = \Carbon\Carbon::createFromFormat('m/d/Y', $end)->endOfDay()->toDateString();
            $query->whereDate('p.updated_at', '<=', $e);
        }
    } catch (\Throwable $e) {}

    $orders = $query->orderByDesc('p.updated_at')
                    ->paginate($perPage)
                    ->appends($request->query());

    return view('dispatchcontrol.history', compact('orders','q','start','end'));
    }
}
