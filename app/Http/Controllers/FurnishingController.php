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
                            WHEN LOWER(COALESCE(i.sizeUnit,'')) IN ('cm')                  THEN (1/2.54/2.54)
                            WHEN LOWER(COALESCE(i.sizeUnit,'')) IN ('mm','millimeter','millimetre') THEN (1/25.4/25.4)
                            WHEN LOWER(COALESCE(i.sizeUnit,'')) IN ('ft','foot','feet')   THEN 144
                            ELSE 0
                            END
                        )
                        )
                    , 2) as sq_in
                "),
                DB::raw('COALESCE(o.accepted, 0) as accepted'),
            ])
            ->where('o.status', '=', 'in_progress')
            ->orderByDesc('submission_date')
            ->paginate(10);

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
            DB::transaction(function () use ($product) {
                // 1) move the product forward to delivery phase
                DB::table('products')
                    ->where('ProductID', $product)
                    ->update([
                        'status'     => 'in_progress',
                        'taskType'   => 'delivery',
                        'updated_at' => now(),
                    ]);

                // 2) mark furnishing stage as completed in fulfillment_progress
                $now = now();

                $exists = DB::table('fulfillment_progress')
                    ->where('ProductID', $product)
                    ->where('stage', 'furnishing')
                    ->lockForUpdate()
                    ->first();

                if ($exists) {
                    DB::table('fulfillment_progress')
                        ->where('ProgressID', $exists->ProgressID)
                        ->update([
                            'completedAt' => $now,
                            'status'      => 'completed',
                            'updated_at'  => $now,
                        ]);
                } else {
                    DB::table('fulfillment_progress')->insert([
                        'ProductID'   => $product,
                        'stage'       => 'furnishing',
                        'acceptedAt'  => null,
                        'completedAt' => $now,
                        'status'      => 'completed',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                }
            });

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
