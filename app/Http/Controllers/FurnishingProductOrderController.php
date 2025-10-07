<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FurnishingProductOrderController extends Controller
{
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

        abort_unless($row, 404);

        return view('furnishing.job_order_show', [
            'productId'    => $row->ProductID,
            'orderId'      => $row->order_id,
            'orderStatus'  => $row->order_status,
            'editable'     => (int) ($row->editable ?? 0),
            'orderCode'    => $row->order_number ?: ('ORD'.$row->OrderID.'-P'.$row->ProductID),
            'deadline'     => $row->deadline,
        ]);
    }

    public function accept(Request $request, $product)
    {
        $p = DB::table('products')->where('ProductID', $product)->first();
        if (!$p) {
            return response()->json(['ok' => false, 'message' => 'Product not found'], 404);
        }

        DB::transaction(function () use ($p, $product) {
            DB::table('products')->where('ProductID', $product)->update([
                'editable'   => 1,
                'updated_at' => now(),
            ]);

            if ($p->OrderID) {
                DB::table('orders')->where('id', $p->OrderID)->update([
                    'orderStatus' => 'assigned',
                    'updated_at'  => now(),
                ]);
            }
        });

        return response()->json(['ok' => true]);
    }

    public function reject(Request $request, $product)
    {
        $reason = (string) $request->input('reason', '');
        $p = DB::table('products')->where('ProductID', $product)->first();
        if (!$p) {
            return response()->json(['ok' => false, 'message' => 'Product not found'], 404);
        }

        DB::transaction(function () use ($p, $product, $reason) {
            DB::table('products')->where('ProductID', $product)->update([
                'editable'   => 0,
                'updated_at' => now(),
            ]);

            if ($p->OrderID) {
                DB::table('orders')->where('id', $p->OrderID)->update([
                    'orderStatus' => 'rejected',
                    'updated_at'  => now(),
                ]);
            }

            // 如果要记录拒绝原因，可在此写入自定义表
            // DB::table('order_rejections')->insert([...]);
        });

        return response()->json(['ok' => true]);
    }
}
