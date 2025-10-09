<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrintingController extends Controller
{
    /**
     * Printing dashboard
     * - Shows only Printing jobs that are still IN PROGRESS
     * - KPIs:
     *   * In Progress  -> status=in_progress & taskType=printing
     *   * Completed    -> status=completed  (regardless of downstream taskType)
     */
    public function dashboard(Request $request)
{
    $jobs = DB::table('products as p')
        ->leftJoin('orders as o', 'p.OrderID', '=', 'o.id')
        ->leftJoin('product_items as pi', 'p.ProductID', '=', 'pi.ProductID')
        ->leftJoin('specifications as s', 'pi.ItemID', '=', 's.ItemID')
        ->where('p.status', 'in_progress')
        ->where('p.taskType', 'printing')
        ->groupBy('p.ProductID', 'p.updated_at', 'p.status', 'p.taskType', 'o.id', 'o.order_number', 'o.deadline', 'o.accepted')
        ->select([
            'p.ProductID',
            'p.updated_at as submission_date',
            'p.status',
            'p.taskType',
            'o.id as order_id',
            'o.order_number',
            'o.deadline',

            // total sq inch across all items for this product
            DB::raw('SUM(IFNULL(pi.sizeWidth,0) * IFNULL(pi.sizeHeight,0)) as sq_inch'),

            // pick one non-empty printer across items; fallback to "—"
            DB::raw("COALESCE(NULLIF(MAX(NULLIF(s.printer, '')), ''), '—') as printer"),

            // nice code e.g. #ORD-35-P0040
            DB::raw("CONCAT('#ORD-', o.id, '-P', LPAD(p.ProductID, 4, '0')) as product_code"),

            DB::raw('COALESCE(o.accepted, 0) as accepted'),
        ])
        ->orderBy('o.id')
        ->orderBy('p.ProductID')
        ->paginate(10);

    // KPI: In-Progress (Printing)
    $inProgress = DB::table('products')
        ->where('status', 'in_progress')
        ->where('taskType', 'printing')
        ->count();

    // KPI: Completed at printing stage
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

        $orderCode = $row->order_number ?: ('ORD'.$row->OrderID.'-P'.$row->ProductID);

        return view('printing.job_order_show', [
            'productId'   => $row->ProductID,
            'orderId'     => $row->order_id,
            'orderStatus' => $row->order_status,
            'editable'    => (int) ($row->editable ?? 0),
            'orderCode'   => $orderCode,
            'deadline'    => $row->deadline,
        ]);
    }

    /**
     * ====== 保存 Printer 选择（前端编辑后提交）======
     * Route (POST): printing.update.printers
     *
     * 期望 payload (JSON)：
     * {
     *   "productId": 123,
     *   "printers": [
     *     {"item_id": 1001, "printer": "HP Indigo 7800"},
     *     {"item_id": 1002, "printer": "Handtop Roll2Roll"}
     *   ]
     * }
     *
     * 兼容没有 item_id 的情况（用 line 顺序对齐）：
     * {
     *   "productId": 123,
     *   "printers": [
     *     {"line": 1, "printer": "HP Indigo 7800"},
     *     {"line": 2, "printer": "Handtop Roll2Roll"}
     *   ]
     * }
     *
     * 写入逻辑：
     * - 优先写入 specifications.printer（依据 ItemID）
     * - 如该 ItemID 不存在 specifications，则自动插入
     */
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
                            'updated_at'=> now(),
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

    /**
     * Confirm a job is printed:
     * - Mark as completed
     * - Move to the next stage (furnishing)
     */
    public function markPrinted($product)
    {
        try {
            DB::transaction(function () use ($product) {
                // 1) move the product forward to delivery phase
                DB::table('products')
                    ->where('ProductID', $product)
                    ->update([
                        'status'     => 'in_progress',
                        'taskType'   => 'furnishing',
                        'updated_at' => now(),
                    ]);

                // 2) mark furnishing stage as completed in fulfillment_progress
                $now = now();

                $exists = DB::table('fulfillment_progress')
                    ->where('ProductID', $product)
                    ->where('stage', 'printing')
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
                'ok' => false,
                'message' => $e->getMessage()
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
