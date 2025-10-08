<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FurnishingController extends Controller
{
    public function dashboard(Request $request)
    {
        // ===== Summary cards =====
        $inProgress = DB::table('products')
            ->whereRaw('LOWER(taskType) = "furnishing"')
            ->whereIn('status', ['to_assign','assigned','in_progress','pending'])
            ->count();

        $completed = DB::table('fulfillment_progress')
            ->where('stage', 'furnishing')
            ->where('status', 'completed')
            ->count();

        // ===== Table rows =====
        // We aggregate per ProductID, pull any cutter found in specifications,
        // and compute sq inch from item sizes (best-effort unit handling).
        $jobs = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('product_items as i', 'i.ProductID', '=', 'p.ProductID')
            ->leftJoin('specifications as s', 's.ItemID', '=', 'i.ItemID')
            ->whereRaw('LOWER(p.taskType) = "furnishing"')
            ->whereIn('p.status', ['to_assign','assigned','in_progress','pending'])
            ->groupBy(
                'p.ProductID','p.OrderID','p.productName','o.deadline','p.created_at','p.updated_at',
                'o.order_number','o.accepted'
            )
            ->select([
                'p.ProductID',
                'p.OrderID',
                'p.productName',
                DB::raw('o.order_number'),
                DB::raw('o.deadline as deadline'),                    // ← CHANGED (was p.deadline)
                DB::raw('COALESCE(p.updated_at, p.created_at) as submission_date'),
                DB::raw("MAX(COALESCE(NULLIF(TRIM(s.cutter), ''), '')) as cutter"),
                DB::raw("
                    ROUND(
                    SUM(
                        COALESCE(i.quantity,0) *
                        COALESCE(i.sizeWidth,0) *
                        COALESCE(i.sizeHeight,0) *
                        (
                        CASE
                            WHEN LOWER(COALESCE(i.sizeUnit,'')) IN ('in','inch','inches') THEN 1
                            WHEN LOWER(COALESCE(i.sizeUnit,'')) = 'cm' THEN (1/2.54/2.54)
                            WHEN LOWER(COALESCE(i.sizeUnit,'')) IN ('mm','millimeter','millimetre') THEN (1/25.4/25.4)
                            ELSE 0
                        END
                        )
                    )
                    , 2) as sq_in
                "),
                DB::raw('COALESCE(o.accepted, 0) as accepted'),
            ])
            ->orderByDesc('submission_date')
            ->paginate(10000);

        return view('furnishing.dashboard', [
            'inProgress' => $inProgress,
            'completed'  => $completed,
            'jobs'       => $jobs,
        ]);
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
