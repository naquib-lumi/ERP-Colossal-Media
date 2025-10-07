<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrintingController extends Controller
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
                DB::raw('COALESCE(s.printer, "-") as printer'),
                // ✅ Add properly formatted Product Code here
                DB::raw("CONCAT('#ORD-', o.id, '-P', LPAD(p.ProductID, 4, '0')) as product_code"),
            ])
            ->where('p.status', 'in_progress')   // only in-progress
            ->where('p.taskType', 'printing')    // only printing
            ->orderBy('o.id', 'asc')
            ->orderBy('p.ProductID', 'asc')
            ->orderBy('pi.ItemID', 'asc')
            ->paginate(10);

        $inProgress = DB::table('products')
            ->where('status', 'in_progress')
            ->where('taskType', 'printing')
            ->count();

        $completed = DB::table('products')
            ->where('status', 'completed')
            ->where('taskType', 'printing')
            ->count();

        return view('printing.dashboard', compact('jobs', 'inProgress', 'completed'));
    }

    public function markPrinted($productId)
    {
        try {
            // Move the item out of Printing to Furnishing (keep in_progress for next phase)
            DB::table('products')
                ->where('ProductID', $productId)
                ->update([
                    'taskType'   => 'furnishing',
                    'status'     => DB::raw("IF(status='in_progress','in_progress',status)"),
                    'updated_at' => now(),
                ]);

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function reportForm($productId)
    {
        // Pull minimal info to show a neat order code
        $row = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('product_items as pi', 'p.ProductID', '=', 'pi.ProductID') // optional, in case you want ItemID later
            ->select('p.ProductID', 'p.OrderID', 'o.order_number')
            ->where('p.ProductID', $productId)
            ->first();

        // ★ changed: prefer order_number if available (cleaner code on the form)
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

    public function reportSubmit(Request $request, $productId)
{
    // Accept both "other_reason" and "reason_other" from different blades
    $data = $request->validate([
        'reason'         => ['required', 'string'],
        'other_reason'   => ['nullable', 'string'],
        'reason_other'   => ['nullable', 'string'],
        'notes'          => ['nullable', 'string'], // kept; table doesn't have it, we just ignore it
    ]);

    $otherText = $data['other_reason'] ?? $data['reason_other'] ?? null;

    $reason = strtolower($data['reason']) === 'others'
        ? ($otherText ?: 'Others')
        : $data['reason'];

    // Get the OrderID that belongs to this ProductID
    $orderId = DB::table('products')
        ->where('ProductID', $productId)
        ->value('OrderID');

    // Insert into your existing table "report_redo"
    DB::table('report_redo')->insert([
        'OrderID'   => $orderId ?? 0,   // or make this required if you prefer
        'reason'    => $reason,
        'created_at'=> now(),
        'updated_at'=> now(),
    ]);

    return redirect()->route('printing.dashboard')
        ->with('status', 'Report submitted.');
}

}
