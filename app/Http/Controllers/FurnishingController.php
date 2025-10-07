<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FurnishingController extends Controller
{
    /**
     * Furnishing Dashboard
     * 仅显示 Furnishing 阶段 且 in_progress 的任务
     */
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
                DB::raw("COALESCE(s.cutter, '-') as cutter"),
                DB::raw("CONCAT('ORD', COALESCE(o.id, p.OrderID), '-P', p.ProductID) as product_code"),
            ])
            ->where('p.taskType', 'furnishing')
            ->where('p.status', 'in_progress')
            ->orderBy('o.id', 'asc')
            ->orderBy('p.ProductID', 'asc')
            ->orderBy('pi.ItemID', 'asc')
            ->paginate(10);

        $inProgress = DB::table('products')
            ->where('taskType', 'furnishing')
            ->where('status', 'in_progress')
            ->count();

        $completed = DB::table('products')
            ->where('taskType', 'furnishing')
            ->where('status', 'completed')
            ->count();

        return view('furnishing.dashboard', compact('jobs', 'inProgress', 'completed'));
    }

    /**
     * 标记完成（推荐走 PATCH；Blade 里已带 CSRF）
     * 仅更新 status=completed，taskType 保持 furnishing
     */
    public function markCompleted(Request $request, $productId)
    {
        try {
            $affected = DB::table('products')
                ->where('ProductID', $productId)
                ->update([
                    'status'     => 'completed',
                    'updated_at' => now(),
                ]);

            return response()->json(['ok' => $affected > 0, 'affected' => $affected]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 兼容旧调用名：某些地方还在调用 markComplete()
     */
    public function markComplete(Request $request, $productId)
    {
        return $this->markCompleted($request, $productId);
    }
}
