<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DispatchControlJobOrderTableController extends Controller
{
    public function index(Request $request)
    {
        // Filters
        $q      = trim($request->get('q', ''));
        $type   = trim($request->get('type', ''));    // printing / furnishing / installation / courier / self_pickup / etc.
        $status = trim($request->get('status', ''));  // in_progress / completed

        // --------- Overview tiles (safe even if tables are empty) ----------
        $stats = [
            'printing'        => DB::table('products')->whereRaw('LOWER(taskType) = ?', ['printing'])->count(),
            'furnishing'      => DB::table('products')->whereRaw('LOWER(taskType) = ?', ['furnishing'])->count(),
            'permit_delivery' => DB::table('delivery_breakdowns')->whereRaw('LOWER(deliver_install_type) = ?', ['delivery'])->count(),
            'permit_install'  => DB::table('delivery_breakdowns')->whereRaw('LOWER(deliver_install_type) = ?', ['installation'])->count(),
            'self_pickup'     => DB::table('delivery_breakdowns')->whereRaw('LOWER(method) = ?', ['self pickup'])
                                    ->orWhereRaw('LOWER(method) = ?', ['self_pickup'])->count(),
            'courier'         => DB::table('delivery_breakdowns')->whereRaw('LOWER(method) = ?', ['courier'])->count(),
        ];

        // --------- Table data (join what exists; all LEFT JOINs so it's safe) ----------
        $orders = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('delivery_breakdowns as db', 'db.ProductID', '=', 'p.ProductID')
            ->selectRaw("
                p.ProductID,
                p.productName                         as product_name,
                p.taskType                            as task_type,
                p.status                              as product_status,
                DATE_FORMAT(p.updated_at, '%Y-%m-%d') as deadline,       -- sample; change if you have a real deadline column
                DATE_FORMAT(db.date, '%Y-%m-%d')      as delivery_date,
                db.location                           as delivery_location,
                o.id                                  as order_id,
                CONCAT('#ORD-', COALESCE(o.id, 0), '-P', LPAD(p.ProductID, 4, '0')) as product_code
            ")
            ->when($q !== '', function ($qb) use ($q) {
                $like = "%{$q}%";
                $qb->where(function ($w) use ($like) {
                    $w->where('p.productName', 'like', $like)
                      ->orWhere('p.ProductID', 'like', $like)
                      ->orWhere('o.id', 'like', $like);
                });
            })
            ->when($type !== '', fn($qb) => $qb->whereRaw('LOWER(p.taskType) = ?', [strtolower($type)]))
            ->when($status !== '', fn($qb) => $qb->where('p.status', $status))
            ->orderByDesc('p.updated_at')
            ->paginate(24)
            ->appends($request->query());

        // ...
    return view('dispatchcontrol.job-order', compact('stats','orders','q','type','status'));

    }
}
