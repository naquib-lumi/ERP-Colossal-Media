<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DispatchControlController extends Controller
{
    public function index(Request $request)
    {
        $q        = trim((string) $request->get('q', ''));
        $taskType = strtolower((string) $request->get('task_type', ''));
        $status   = strtolower((string) $request->get('status', ''));

        // ---- Stats (for the top cards)
        $stats = [
            'printing'                    => $this->countByType('printing'),
            'furnishing'                  => $this->countByType('furnishing'),
            'delivery_needing_permit'     => DB::table('delivery_breakdowns')
                                               ->where(DB::raw('LOWER(deliver_install_type)'), 'delivery')
                                               ->count(),
            'installation_needing_permit' => DB::table('delivery_breakdowns')
                                               ->where(DB::raw('LOWER(deliver_install_type)'), 'installation')
                                               ->count(),
            'self_pickup'                 => DB::table('delivery_breakdowns')
                                               ->where(DB::raw('LOWER(method)'), 'self pickup')
                                               ->orWhere(DB::raw('LOWER(method)'), 'self-pickup')
                                               ->count(),
            'courier'                     => DB::table('delivery_breakdowns')
                                               ->where(DB::raw('LOWER(method)'), 'courier')
                                               ->count(),
        ];

        // ---- Main list
        $orders = DB::table('products as p')
            ->leftJoin('delivery_breakdowns as db', 'db.ProductID', '=', 'p.ProductID')
            ->select([
                'p.ProductID as product_id',
                'p.OrderID   as order_id',
                'p.productName as product_name',
                'p.taskType as task_type',
                'p.status',
                DB::raw("DATE_FORMAT(p.updated_at, '%Y-%m-%d') as deadline"),
                DB::raw("DATE_FORMAT(db.date, '%Y-%m-%d') as delivery_date"),
                'db.location as delivery_location',
                // product code like #ORD-27-P0007
                DB::raw("CONCAT('#ORD-', p.OrderID, '-P', LPAD(p.ProductID, 4, '0')) as product_code"),
            ])
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($w) use ($q) {
                    $like = '%'.$q.'%';
                    $w->where('p.productName', 'like', $like)
                      ->orWhere('p.ProductID', 'like', $like)
                      ->orWhere('p.OrderID',   'like', $like);
                });
            })
            ->when($taskType !== '', function ($qb) use ($taskType) {
                $qb->where(DB::raw('LOWER(p.taskType)'), $taskType);
            })
            ->when($status !== '', function ($qb) use ($status) {
                $qb->where(DB::raw('LOWER(p.status)'), $status);
            })
            ->orderByDesc('p.updated_at')
            ->paginate(10)
            ->appends($request->query());

        // Optional CSV export when ?export=1 and there are results
        if ($request->boolean('export') && $orders->total() > 0) {
            $rows = collect($orders->items())->map(function ($r) {
                return [
                    'Product ID'        => $r->product_code,
                    'Product Name'      => $r->product_name,
                    'Task Type'         => $r->task_type,
                    'Deadline'          => $r->deadline,
                    'Status'            => $r->status,
                    'Delivery Date'     => $r->delivery_date,
                    'Delivery Location' => $r->delivery_location,
                ];
            });

            $filename = 'job-orders-'.now()->format('Ymd_His').'.csv';
            $handle   = fopen('php://temp', 'r+');
            if ($rows->isNotEmpty()) {
                fputcsv($handle, array_keys($rows->first()));
                foreach ($rows as $line) { fputcsv($handle, $line); }
            }
            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);

            return response($csv, 200, [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ]);
        }

        return view('dispatchcontrol.job-order', compact('orders', 'stats'));
    }

    private function countByType(string $type): int
    {
        return (int) DB::table('products')
            ->where(DB::raw('LOWER(taskType)'), strtolower($type))
            ->count();
    }

}
