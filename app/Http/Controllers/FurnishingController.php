<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FurnishingController extends Controller
{

    public function dashboard(Request $request)
    {
        // --- Read filters
        $cutter         = trim(mb_strtolower((string) $request->get('cutter', '')));
        $sqMin          = $request->get('sq_min', '');
        $sqMax          = $request->get('sq_max', '');
        $deadlineFrom  = $request->filled('deadline_from')
            ? optional(Carbon::parse($request->input('deadline_from')))->toDateString()
            : null;
        $deadlineTo    = $request->filled('deadline_to')
            ? optional(Carbon::parse($request->input('deadline_to')))->toDateString()
            : null;

        $submittedFrom = $request->filled('submitted_from')
            ? optional(Carbon::parse($request->input('submitted_from')))->toDateString()
            : null;
        $submittedTo   = $request->filled('submitted_to')
            ? optional(Carbon::parse($request->input('submitted_to')))->toDateString()
            : null;

        // We'll reuse this SQL in SELECT and HAVING
        $sqExprSql = 'SUM(IFNULL(pi.sizeWidth,0) * IFNULL(pi.sizeHeight,0))';

        $jobs = DB::table('products as p')
            ->leftJoin('orders as o', 'p.OrderID', '=', 'o.id')
            ->leftJoin('product_items as pi', 'p.ProductID', '=', 'pi.ProductID')
            ->leftJoin('specifications as s', 'pi.ItemID', '=', 's.ItemID')

            // sensible statuses
            ->whereIn('p.status', ['in_progress', 'pending', 'completed'])

            // exclude already completed furnishing in fulfillment_progress
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                ->from('fulfillment_progress as fp')
                ->whereColumn('fp.ProductID', 'p.ProductID')
                ->where('fp.stage', 'furnishing')
                ->where('fp.status', 'completed');
            })

            // --- Filters (WHERE) ---
            // cutter (case-insensitive)
            ->when($cutter !== '', function ($qb) use ($cutter) {
                $qb->whereRaw('LOWER(s.cutter) LIKE ?', ['%'.$cutter.'%']);
            })

            // DEADLINE range (orders.deadline)
            ->when($deadlineFrom && $deadlineTo, function ($qb) use ($deadlineFrom, $deadlineTo) {
                $qb->whereBetween('o.deadline', [$deadlineFrom, $deadlineTo]);
            })
            ->when($deadlineFrom && !$deadlineTo, function ($qb) use ($deadlineFrom) {
                $qb->whereDate('o.deadline', '>=', $deadlineFrom);
            })
            ->when(!$deadlineFrom && $deadlineTo, function ($qb) use ($deadlineTo) {
                $qb->whereDate('o.deadline', '<=', $deadlineTo);
            })

            // SUBMITTED range (products.updated_at)
            ->when($submittedFrom && $submittedTo, function ($qb) use ($submittedFrom, $submittedTo) {
                $qb->whereBetween('p.updated_at', [$submittedFrom, $submittedTo]);
            })
            ->when($submittedFrom && !$submittedTo, function ($qb) use ($submittedFrom) {
                $qb->whereDate('p.updated_at', '>=', $submittedFrom);
            })
            ->when(!$submittedFrom && $submittedTo, function ($qb) use ($submittedTo) {
                $qb->whereDate('p.updated_at', '<=', $submittedTo);
            })

            // group for aggregates
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

            // select
            ->select([
                'p.ProductID',
                'p.updated_at as submission_date',
                'p.status',
                'p.taskType',
                'o.id as order_id',
                'o.order_number',
                'o.deadline',

                DB::raw("$sqExprSql as sq_inch"),

                // pick one non-empty cutter across items; fallback "—"
                DB::raw("COALESCE(NULLIF(MAX(NULLIF(s.cutter, '')), ''), '—') as cutter"),

                DB::raw("CONCAT('#ORD-', o.id, '-P', LPAD(p.ProductID, 4, '0')) as product_code"),
                DB::raw('COALESCE(p.accepted, 0) as accepted'),
            ])

            // sq inch range must be in HAVING (aggregate)
            ->when($sqMin !== '', fn($qb) => $qb->havingRaw("$sqExprSql >= ?", [(float)$sqMin]))
            ->when($sqMax !== '', fn($qb) => $qb->havingRaw("$sqExprSql <= ?", [(float)$sqMax]))

            ->orderBy('o.id')
            ->orderBy('p.ProductID')
            ->paginate(10)
            ->appends($request->query()); // keep filters on pagination links

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
