<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FurnishingController extends Controller
{
    public function dashboard(Request $request)
    {
        $jobs = DB::table('products as p')
            ->leftJoin('orders as o', 'p.OrderID', '=', 'o.id')
            ->leftJoin('product_items as pi', 'p.ProductID', '=', 'pi.ProductID')
            ->leftJoin('specifications as s', 'pi.ItemID', '=', 's.ItemID')
            ->select([
                'p.ProductID',
                'p.updated_at as submission_date',
                'p.status',
                'p.taskType',
                'o.id as order_id',
                'o.order_number',
                'o.deadline',
                'pi.ItemID',
                DB::raw('IFNULL(pi.sizeWidth,0) * IFNULL(pi.sizeHeight,0) as sq_inch'),
                DB::raw('COALESCE(s.cutter, "-") as cutter'),
                DB::raw("CONCAT('ORD-', IFNULL(o.order_number, o.id), '-', 'P', p.ProductID) as product_code"),
            ])
            ->where('p.status', 'in_progress')
            ->where('p.taskType', 'furnishing')
            ->orderBy('o.id')
            ->orderBy('p.ProductID')
            ->paginate(10);

        $inProgress = DB::table('products')
            ->where('status', 'in_progress')
            ->where('taskType', 'furnishing')
            ->count();

        $completed = DB::table('products')
            ->where('status', 'completed')
            ->where('taskType', 'furnishing')
            ->count();

        return view('furnishing.dashboard', compact('jobs', 'inProgress', 'completed'));
    }

    // Dashboard 勾确认：把该产品置为 completed（保持 taskType=furnishing）
    public function markComplete($product)
    {
        try {
            DB::table('products')
                ->where('ProductID', $product)
                ->update([
                    'status'     => 'completed',
                    'taskType'   => 'furnishing',
                    'updated_at' => now(),
                ]);

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
