<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InstallationHistoryController extends Controller
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
                // Pretty code like #ORD-12-P0016
                DB::raw("CONCAT('#ORD-', COALESCE(p.OrderID, o.id), '-P', LPAD(p.ProductID, 4, '0')) as product_code"),

                'p.ProductID as product_id',
                'p.OrderID as order_id',
                'p.productName as product_name',

                // Completed date is based on products.updated_at (same pattern you used)
                DB::raw("DATE_FORMAT(p.updated_at, '%b %d, %Y') as completed_date"),

                // Use products.materialRemark as 'remarks' (safe – this column exists)
                DB::raw("COALESCE(NULLIF(p.materialRemark,''), '–') as remarks"),

                // No proof file yet — keep a nullable placeholder
                DB::raw('NULL as proof_url'),

                'o.order_number',
            ])
            ->where('p.status', 'completed')
            ->whereRaw('LOWER(COALESCE(p.taskType, "")) = ?', ['installation']); // <-- Installation only

        // Text search
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('p.productName', 'like', "%{$q}%")
                  ->orWhere('o.order_number', 'like', "%{$q}%")
                  ->orWhere('p.ProductID', 'like', "%{$q}%")
                  ->orWhere('pi.ItemID', 'like', "%{$q}%");
            });
        }

        // Date range (Completed Date = p.updated_at)
        try {
            if ($start !== '') {
                $startDate = Carbon::createFromFormat('m/d/Y', $start)->startOfDay();
                $query->whereDate('p.updated_at', '>=', $startDate->toDateString());
            }
        } catch (\Throwable $e) {}
        try {
            if ($end !== '') {
                $endDate = Carbon::createFromFormat('m/d/Y', $end)->endOfDay();
                $query->whereDate('p.updated_at', '<=', $endDate->toDateString());
            }
        } catch (\Throwable $e) {}

        $orders = $query
            ->orderByDesc('p.updated_at')
            ->paginate($perPage)
            ->appends($request->query());

        return view('installation.history', compact('orders', 'q', 'start', 'end'));
    }
}
