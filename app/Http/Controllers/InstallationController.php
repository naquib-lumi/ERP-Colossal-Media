<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class InstallationController extends Controller
{
    public function dashboard(Request $request)
    {
        $statuses = [
            'all'         => 'All Status',
            'in_progress' => 'In Progress',
            'completed'   => 'Completed',
            'pending'     => 'Pending',
            'issue'       => 'Issue',
        ];

        $search  = trim((string) $request->query('q', ''));
        $status  = $request->query('status', 'all');

        // 1) Pull all rows we need
        $rows = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('fulfillment_progress as fp', 'fp.ProductID', '=', 'p.ProductID')
            ->select([
                'p.ProductID',
                'p.productName',
                'p.OrderID',
                'o.order_number',
                'o.orderDate',
                'o.deadline',
                'fp.stage',
                'fp.status as stage_status',
                'fp.acceptedAt',
                'fp.completedAt',
                'fp.created_at as fp_created_at',
            ])
            ->orderBy('p.ProductID')
            ->get();

        // 2) Reduce to "latest row per stage" for each product
        $byProduct = [];
        foreach ($rows as $r) {
            $pid = $r->ProductID;
            if (!isset($byProduct[$pid])) {
                $byProduct[$pid] = [
                    'ProductID'    => $pid,
                    'productName'  => $r->productName,
                    'OrderID'      => $r->OrderID,
                    'order_number' => $r->order_number,
                    'orderDate'    => $r->orderDate,
                    'deadline'     => $r->deadline,
                    'stages'       => [],
                ];
            }
            if (!$r->stage) continue;

            $k    = strtolower(trim($r->stage)); // printing|furnishing|delivery|installation
            $rank = $r->completedAt ?? $r->acceptedAt ?? $r->fp_created_at;
            $cur  = $byProduct[$pid]['stages'][$k]['_rank'] ?? null;

            if (!$cur || $rank > $cur) {
                $byProduct[$pid]['stages'][$k] = [
                    'status' => strtolower((string)$r->stage_status), // completed|rejected
                    'done'   => $r->completedAt,
                    '_rank'  => $rank,
                ];
            }
        }

        // 3) Convert to list, compute product code and progress width (for sorting)
        $STAGES    = ['printing', 'furnishing', 'delivery', 'installation'];
        $positions = [12.5, 37.5, 62.5, 87.5];

        $progressWidth = function (array $p) use ($STAGES, $positions) {
            $lastCompleted = -1;
            foreach ($STAGES as $i => $stg) {
                $st = $p['stages'][$stg]['status'] ?? null;
                if ($st === 'rejected') {
                    return $lastCompleted >= 0 ? $positions[$lastCompleted] : 0;
                }
                if ($st === 'completed') {
                    $lastCompleted = $i;
                    continue;
                }
                // pending/missing
                return $lastCompleted >= 0 ? $positions[$lastCompleted] : 0;
            }
            return 100; // all done
        };

        $inProgress = 0;
        $completed = 0;
        $list = [];
        foreach ($byProduct as $p) {
            $p['product_code'] = '#' . ($p['order_number'] ?: ('ORD-' . $p['OrderID'])) . '-P' . str_pad((string)$p['ProductID'], 4, '0', STR_PAD_LEFT);
            $p['progress']     = $progressWidth($p);

            $instStatus = $p['stages']['installation']['status'] ?? null;
            if ($instStatus === 'completed') $completed++;
            else $inProgress++;

            $list[] = $p;
        }

        // 4) Search filter (optional)
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $list = array_values(array_filter($list, function ($p) use ($needle) {
                return str_contains(mb_strtolower($p['productName'] ?? ''), $needle)
                    || str_contains(mb_strtolower($p['order_number'] ?? ''), $needle)
                    || str_contains((string)$p['ProductID'], $needle);
            }));
        }

        // 5) Sort by lesser progress FIRST (ascending)
        usort($list, fn($a, $b) => $a['progress'] <=> $b['progress']);

        // 6) Paginate manually (10 per page)
        $perPage = 1000;
        $page    = max(1, (int)$request->query('page', 1));
        $total   = count($list);
        $items   = array_slice($list, ($page - 1) * $perPage, $perPage);

        $rowsPaginated = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('installation.dashboard', [
            'statuses'   => $statuses,
            'search'     => $search,
            'status'     => $status,
            'inProgress' => $inProgress,
            'completed'  => $completed,
            'rows'       => $rowsPaginated, // <-- paginator
        ]);
    }
}
