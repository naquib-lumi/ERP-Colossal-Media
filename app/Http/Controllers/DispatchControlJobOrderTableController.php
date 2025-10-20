<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DispatchControlJobOrderTableController extends Controller
{
public function index(Request $request)
{
    // Existing filters (keep yours)
    $q      = trim((string) $request->query('q', ''));
    $type   = trim((string) $request->query('type', ''));
    $status = trim((string) $request->query('status', ''));

    // NEW: normalize & read "method" just like your other filters
    $method = (string) ($request->query('method', $request->get('method', '')));
    $method = trim($method);

    // We'll compare in a case-insensitive way and treat "_" == " "
    $methodNorm = strtolower(str_replace('_', ' ', $method));

    // --------- Overview tiles ----------
    $stats = [
        'printing'        => DB::table('products')->whereRaw('LOWER(taskType) = ?', ['printing'])->count(),
        'furnishing'      => DB::table('products')->whereRaw('LOWER(taskType) = ?', ['furnishing'])->count(),
        'permit_delivery' => DB::table('delivery_breakdowns')->whereRaw('LOWER(deliver_install_type) = ?', ['delivery'])->count(),
        'permit_install'  => DB::table('delivery_breakdowns')->whereRaw('LOWER(deliver_install_type) = ?', ['installation'])->count(),

        // count DISTINCT products by method so tile numbers match the table
        'self_pickup'     => DB::table('delivery_breakdowns')
                                ->select('ProductID')->distinct()
                                ->where(function ($w) {
                                    $w->whereRaw('LOWER(method) = ?', ['self pickup'])
                                      ->orWhereRaw('LOWER(method) = ?', ['self_pickup']);
                                })->count('ProductID'),
        'courier'         => DB::table('delivery_breakdowns')
                                ->select('ProductID')->distinct()
                                ->whereRaw('LOWER(method) = ?', ['courier'])
                                ->count('ProductID'),
    ];

    // --------- Base table query ----------
    $orders = DB::table('products as p')
        ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
        ->leftJoin('delivery_breakdowns as db', 'db.ProductID', '=', 'p.ProductID')
        ->selectRaw("
            p.ProductID,
            p.productName,
            LOWER(p.taskType)   as task_type,
            LOWER(p.status)     as status,
            p.updated_at,
            db.date             as delivery_date,
            db.location         as delivery_location,
            o.id                as order_id
        ");

    // ---- apply filters you already have (keep your own logic here) ----
    if ($q !== '') {
        $orders->where(function ($w) use ($q) {
            $qLike = "%{$q}%";
            $w->where('p.productName', 'like', $qLike)
              ->orWhere('p.ProductID', 'like', $qLike)
              ->orWhere('db.location', 'like', $qLike);
        });
    }

    if ($type !== '') {
        $orders->whereRaw('LOWER(p.taskType) = ?', [strtolower($type)]);
    }

    if ($status !== '') {
        $orders->whereRaw('LOWER(p.status) = ?', [strtolower($status)]);
    }

    // ---- NEW: method filter from the tiles ----
    if ($methodNorm !== '') {
        // Use EXISTS so we don't explode rows when a product has multiple breakdowns
        $orders->whereExists(function ($q) use ($methodNorm) {
            $q->from('delivery_breakdowns as d2')
            ->whereRaw('d2.ProductID = p.ProductID')
            // LOWER(REPLACE()) lets "self_pickup", "Self Pickup", etc. all match
            ->whereRaw('LOWER(REPLACE(COALESCE(d2.method, \'\'), \'_\', \' \')) = ?', [$methodNorm]);
        });
    }

    // avoid duplicates if a product has multiple breakdown rows
    $orders->groupBy([
        'p.ProductID', 'p.productName', 'p.taskType', 'p.status',
        'p.updated_at', 'db.date', 'db.location', 'o.id'
    ])
    ->orderByDesc('p.updated_at');

    $orders = $orders->paginate(24)->appends($request->query());

    return view('dispatchcontrol.job-order', compact('stats', 'orders', 'q', 'type', 'status', 'method'));
}
}
