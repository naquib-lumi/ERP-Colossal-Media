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
                DB::raw('COALESCE(s.printer, "-") as printer'),
                // Human-friendly product code for display (keep if you render it)
                DB::raw("CONCAT('#ORD-', o.id, '-P', LPAD(p.ProductID, 4, '0')) as product_code"),
            ])
            ->where('p.status', 'in_progress')       // list shows only in-progress
            ->where('p.taskType', 'printing')        // only printing stage
            ->orderBy('o.id', 'asc')
            ->orderBy('p.ProductID', 'asc')
            ->orderBy('pi.ItemID', 'asc')
            ->paginate(10);

        // KPI: In-Progress (Printing)
        $inProgress = DB::table('products')
            ->where('status', 'in_progress')
            ->where('taskType', 'printing')
            ->count();

        // KPI: Completed (persists even after the job moves to Furnishing)
        $completed = DB::table('products')
            ->where('status', 'completed')
            ->count();

        return view('printing.dashboard', compact('jobs', 'inProgress', 'completed'));
    }

    /**
     * Confirm a job is printed:
     * - Mark as completed
     * - Move to the next stage (furnishing)
     */
    public function markPrinted($productId)
    {
        try {
            DB::table('products')
                ->where('ProductID', $productId)
                ->update([
                    'status'     => 'completed',   // counts for KPI even after move
                    'taskType'   => 'furnishing',  // handoff to next team
                    'updated_at' => now(),
                ]);

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
        // Minimal info to show a neat order code
        $row = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('product_items as pi', 'p.ProductID', '=', 'pi.ProductID') // optional, if you need ItemID later
            ->select('p.ProductID', 'p.OrderID', 'o.order_number')
            ->where('p.ProductID', $productId)
            ->first();

        // Prefer order_number if present; else fallback
        if ($row) {
            $orderCode = $row->order_number
                ? $row->order_number
                : ('ORD' . ($row->OrderID ?: $row->ProductID) . '-P' . $row->ProductID);
        } else {
            $orderCode = 'ORD-P' . $productId;
        }

        return view('printing.report_issue', [
            'productId' => $productId,
            'orderCode' => $orderCode,
        ]);
    }

    /**
     * Submit a Printing issue report.
     * Writes to your existing `report_redo` table.
     */
    public function reportSubmit(Request $request, $productId)
    {
        // Accept both field names from different blades (other_reason / reason_other)
        $data = $request->validate([
            'reason'        => ['required', 'string'],
            'other_reason'  => ['nullable', 'string'],
            'reason_other'  => ['nullable', 'string'],
            'notes'         => ['nullable', 'string'], // not stored in report_redo, but OK to receive
        ]);

        $otherText = $data['other_reason'] ?? $data['reason_other'] ?? null;

        $reason = strtolower($data['reason']) === 'others'
            ? ($otherText ?: 'Others')
            : $data['reason'];

        // Resolve OrderID from ProductID
        $orderId = DB::table('products')
            ->where('ProductID', $productId)
            ->value('OrderID');

        // Store in your existing table
        DB::table('report_redo')->insert([
            'OrderID'   => $orderId ?? 0,
            'reason'    => $reason,
            'created_at'=> now(),
            'updated_at'=> now(),
        ]);

        return redirect()
            ->route('printing.dashboard')
            ->with('status', 'Report submitted.');
    }
}
