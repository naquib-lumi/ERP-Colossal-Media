<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrintingController extends Controller
{
    public function dashboard(Request $request)
{
    // ---- filters
    $printer   = trim((string) $request->get('printer', ''));
    $sqMin     = $request->get('sqmin');
    $sqMax     = $request->get('sqmax');
    $dlStart   = $request->get('deadline_start');
    $dlEnd     = $request->get('deadline_end');
    $sbStart   = $request->get('submitted_start'); // DATE on p.updated_at
    $sbEnd     = $request->get('submitted_end');

    // expression used in select & having
    $sqExpr = 'SUM(IFNULL(pi.sizeWidth,0) * IFNULL(pi.sizeHeight,0))';

    $jobs = DB::table('products as p')
        ->leftJoin('orders as o', 'p.OrderID', '=', 'o.id')
        ->leftJoin('product_items as pi', 'p.ProductID', '=', 'pi.ProductID')
        ->leftJoin('specifications as s', 'pi.ItemID', '=', 's.ItemID')
        ->whereIn('p.status', ['in_progress', 'pending', 'completed'])

        // exclude rows already completed in printing
        ->whereNotExists(function ($q2) {
            $q2->select(DB::raw(1))
               ->from('fulfillment_progress as fp')
               ->whereColumn('fp.ProductID', 'p.ProductID')
               ->where('fp.stage', 'printing')
               ->where('fp.status', 'completed');
        })

        // ---- filters
        ->when($printer !== '', function ($qb) use ($printer) {
            $qb->where('s.printer', 'like', '%'.$printer.'%');
        })
        // deadline range (DATE column on orders)
        ->when($dlStart && $dlEnd, function ($qb) use ($dlStart, $dlEnd) {
            $qb->whereBetween('o.deadline', [$dlStart, $dlEnd]);
        })
        ->when($dlStart && !$dlEnd, function ($qb) use ($dlStart) {
            $qb->where('o.deadline', '>=', $dlStart);
        })
        ->when(!$dlStart && $dlEnd, function ($qb) use ($dlEnd) {
            $qb->where('o.deadline', '<=', $dlEnd);
        })
        // submission date range (DATE on p.updated_at)
        ->when($sbStart && $sbEnd, function ($qb) use ($sbStart, $sbEnd) {
            $qb->whereBetween(DB::raw('DATE(p.updated_at)'), [$sbStart, $sbEnd]);
        })
        ->when($sbStart && !$sbEnd, function ($qb) use ($sbStart) {
            $qb->where(DB::raw('DATE(p.updated_at)'), '>=', $sbStart);
        })
        ->when(!$sbStart && $sbEnd, function ($qb) use ($sbEnd) {
            $qb->where(DB::raw('DATE(p.updated_at)'), '<=', $sbEnd);
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
            'p.taskType',
            'o.id as order_id',
            'o.order_number',
            'o.deadline',

            // use the string with selectRaw/DB::raw
            DB::raw("$sqExpr as sq_inch"),

            DB::raw("COALESCE(NULLIF(MAX(NULLIF(s.printer, '')), ''), '—') as printer"),
            DB::raw("CONCAT('#ORD-', o.id, '-P', LPAD(p.ProductID, 4, '0')) as product_code"),
            DB::raw('COALESCE(p.accepted, 0) as accepted'),
        ])

        // sq inch range — must use HAVING (it's an aggregate)
        ->when($sqMin !== null && $sqMin !== '', fn($qb) => $qb->having('sq_inch', '>=', (float) $sqMin))
        ->when($sqMax !== null && $sqMax !== '', fn($qb) => $qb->having('sq_inch', '<=', (float) $sqMax))

        ->orderBy('o.id')
        ->orderBy('p.ProductID')
        ->paginate(10)
        ->appends($request->query()); // keep filters on pagination

    // KPIs (unchanged)
    $inProgress = DB::table('products')
        ->where('status', 'in_progress')
        ->where('taskType', 'printing')
        ->count();

    $completed = DB::table('fulfillment_progress')
        ->where('stage', 'printing')
        ->where('status', 'completed')
        ->count();

    return view('printing.dashboard', compact('jobs', 'inProgress', 'completed'));
}


    /**
     * Show a single Printing Job Order detail page
     * Route: GET /printing/jobs/{product}
     * View : resources/views/printing/job_order_show.blade.php
     */
    public function show($product)
    {
        $row = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->select(
                'p.ProductID',
                'p.OrderID',
                'p.status',
                'p.taskType',
                'p.editable',
                'o.id as order_id',
                'o.order_number',
                'o.orderStatus as order_status',
                'o.deadline'
            )
            ->where('p.ProductID', $product)
            ->first();

        if (!$row) {
            abort(404, 'Product not found');
        }

        $orderCode = $row->order_number ?: ('ORD' . $row->OrderID . '-P' . $row->ProductID);

        return view('printing.job_order_show', [
            'productId'   => $row->ProductID,
            'orderId'     => $row->order_id,
            'orderStatus' => $row->order_status,
            'editable'    => (int) ($row->editable ?? 0),
            'orderCode'   => $orderCode,
            'deadline'    => $row->deadline,
        ]);
    }

    public function updatePrinters(Request $request)
    {
        $data = $request->validate([
            'productId'           => ['required', 'integer'],
            'printers'            => ['required', 'array', 'min:1'],
            'printers.*.item_id'  => ['nullable', 'integer'],
            'printers.*.line'     => ['nullable', 'integer'],
            'printers.*.printer'  => ['nullable', 'string', 'max:255'],
        ]);

        // 确认产品存在
        $productExists = DB::table('products')
            ->where('ProductID', $data['productId'])
            ->exists();

        if (!$productExists) {
            return response()->json([
                'ok' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        // 把本产品下所有 item 排序拿到，便于无 item_id 时按行号映射
        $itemIds = DB::table('product_items')
            ->where('ProductID', $data['productId'])
            ->orderBy('ItemID')
            ->pluck('ItemID')
            ->values(); // [1001, 1002, ...]

        DB::beginTransaction();
        try {
            $saved = [];

            foreach ($data['printers'] as $idx => $row) {
                // 解析目标 ItemID
                $itemId = $row['item_id'] ?? null;

                if (!$itemId && isset($row['line'])) {
                    // line 从 1 开始，映射到排序后的 ItemID
                    $line = (int) $row['line'];
                    $itemId = $itemIds[$line - 1] ?? null;
                }

                // 没能解析到 ItemID 就跳过
                if (!$itemId) {
                    continue;
                }

                $printer = $row['printer'] ?? null;

                // 如果 specifications 已存在此 ItemID，更新；否则插入
                $exists = DB::table('specifications')
                    ->where('ItemID', $itemId)
                    ->exists();

                if ($exists) {
                    DB::table('specifications')
                        ->where('ItemID', $itemId)
                        ->update([
                            'printer'   => $printer,
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('specifications')->insert([
                        'ItemID'     => $itemId,
                        'printer'    => $printer,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $saved[] = ['item_id' => $itemId, 'printer' => $printer];
            }

            DB::commit();

            return response()->json([
                'ok'     => true,
                'count'  => count($saved),
                'saved'  => $saved,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function markPrinted($product)
    {
        try {
            DB::transaction(function () use ($product) {
                $now = now();

                $hasFurnishingWork = DB::table('product_items as pi')
                    ->leftJoin('specifications as s', 'pi.ItemID', '=', 's.ItemID')
                    ->where('pi.ProductID', $product)
                    ->where(function ($q) {
                        $q->whereNotNull('s.lamination')->where('s.lamination', '<>', '')
                        ->orWhereNotNull('s.cutter')->where('s.cutter', '<>', '');
                    })
                    ->exists();

                $nextStage = null;

                if ($hasFurnishingWork) {
                    $nextStage = 'furnishing';
                } else {
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
                }

                DB::table('products')
                    ->where('ProductID', $product)
                    ->update([
                        'status'     => 'in_progress',
                        'taskType'   => $nextStage,
                        'updated_at' => $now,
                    ]);

                $existing = DB::table('fulfillment_progress')
                    ->where('ProductID', $product)
                    ->where('stage', 'printing')
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    DB::table('fulfillment_progress')
                        ->where('ProgressID', $existing->ProgressID)
                        ->update([
                            'completedAt' => $now,
                            'status'      => 'completed',
                            'updated_at'  => $now,
                        ]);
                } else {
                    DB::table('fulfillment_progress')->insert([
                        'ProductID'   => $product,
                        'stage'       => 'printing',
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
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Report issue form (Printing)
     */
    public function reportForm($productId)
    {
        $row = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('product_items as pi', 'p.ProductID', '=', 'pi.ProductID')
            ->select('p.ProductID', 'p.OrderID', 'o.order_number')
            ->where('p.ProductID', $productId)
            ->first();

        $orderCode = $row
            ? ($row->order_number ?: ('ORD' . ($row->OrderID ?: $row->ProductID) . '-P' . $row->ProductID))
            : ('ORD-P' . $productId);

        return view('printing.report_issue', [
            'productId' => $productId,
            'orderCode' => $orderCode,
        ]);
    }

    /**
     * Submit a Printing issue report.
     * Writes to `report_redo` table.
     */
    public function reportSubmit(Request $request, $productId)
    {
        $data = $request->validate([
            'reason'        => ['required', 'string'],
            'other_reason'  => ['nullable', 'string'],
            'reason_other'  => ['nullable', 'string'],
            'notes'         => ['nullable', 'string'],
        ]);

        $otherText = $data['other_reason'] ?? $data['reason_other'] ?? null;

        $reason = strtolower($data['reason']) === 'others'
            ? ($otherText ?: 'Others')
            : $data['reason'];

        $orderId = DB::table('products')
            ->where('ProductID', $productId)
            ->value('OrderID');

        DB::table('report_redo')->insert([
            'OrderID'    => $orderId ?? 0,
            'reason'     => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('printing.dashboard')
            ->with('status', 'Report submitted.');
    }
}
