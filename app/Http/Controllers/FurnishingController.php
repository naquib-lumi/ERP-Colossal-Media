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
            // show ALL products; only keep a sensible status filter
            ->whereIn('p.status', ['in_progress', 'pending','completed'])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('fulfillment_progress as fp')
                    ->whereColumn('fp.ProductID', 'p.ProductID')
                    ->where('fp.stage', 'furnishing')
                    ->where('fp.status', 'completed');
            })
            ->groupBy(
                'p.ProductID',
                'p.updated_at',
                'p.status',
                'p.taskType',
                'o.id',
                'o.order_number',
                'o.deadline',
                'p.accepted'
            )
            ->select([
                'p.ProductID',
                'p.updated_at as submission_date',
                'p.status',
                'p.taskType', // <-- keep taskType so Blade can tell its stage
                'o.id as order_id',
                'o.order_number',
                'o.deadline',

                // total sq inch across all items for this product
                DB::raw('SUM(IFNULL(pi.sizeWidth,0) * IFNULL(pi.sizeHeight,0)) as sq_inch'),

                // pick one non-empty printer across items; fallback to "—"
                DB::raw("COALESCE(NULLIF(MAX(NULLIF(s.cutter, '')), ''), '—') as cutter"),

                // nice code e.g. #ORD-35-P0040
                DB::raw("CONCAT('#ORD-', o.id, '-P', LPAD(p.ProductID, 4, '0')) as product_code"),

                // accepted flag is now on products
                DB::raw('COALESCE(p.accepted, 0) as accepted'),
            ])
            ->orderBy('o.id')
            ->orderBy('p.ProductID')
            ->paginate(10);

        // KPIs
        $inProgress = DB::table('products')
            ->where('status', 'in_progress')
            ->where('taskType', 'furnishing')
            ->count();

        $completed = DB::table('fulfillment_progress')
            ->where('stage', 'furnishing')
            ->where('status', 'completed')
            ->count();

        return view('furnishing.dashboard', compact('jobs', 'inProgress', 'completed'));
    }


    // Dashboard 勾确认：把该产品置为 completed（保持 taskType=furnishing）
    public function markComplete($product)
    {
        try {
            DB::transaction(function () use ($product) {
                $now = now();

                $nextStage = null;
               
                $methods = DB::table('delivery_breakdowns')
                    ->where('ProductID', $product)
                    ->pluck('method')
                    ->map(fn ($m) => strtolower(trim((string)$m)))
                    ->unique()
                    ->all();

                $hasDelivery     = in_array('self_pickup', $methods, true) || in_array('courier', $methods, true);
                $hasInstallation = in_array('installation', $methods, true) || in_array('delivery_installation', $methods, true);;

                if ($hasDelivery && $hasInstallation) {
                    $nextStage = 'delivery';
                } elseif ($hasDelivery) {
                    $nextStage = 'delivery';
                } elseif ($hasInstallation) {
                    $nextStage = 'installation';
                } else {
                    $nextStage = 'delivery';
                }
                
                DB::table('products')
                    ->where('ProductID', $product)
                    ->update([
                        'status'     => 'in_progress',
                        'taskType'   => $nextStage,
                        'updated_at' => $now,
                    ]);

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
