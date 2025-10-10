<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DispatchControlController extends Controller
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
                'p.taskType as current_stage',
                'p.status   as current_status',
                'o.order_number',
                'o.orderDate',
                'o.deadline',
                'fp.stage',
                'fp.status as stage_status',
                'fp.acceptedAt',
                'fp.completedAt',
                'fp.created_at as fp_created_at',
                DB::raw('COALESCE(p.accepted, 0) as accepted'),
            ])
            ->orderBy('p.ProductID')
            ->get();

        // 2) Reduce to "latest row per stage" for each product
        $byProduct = [];
        foreach ($rows as $r) {
            $pid = $r->ProductID;
            if (!isset($byProduct[$pid])) {
                // null-safe lowercasing for current stage/status
                $curStage  = $r->current_stage ?? null;
                $curStatus = $r->current_status ?? null;

                $byProduct[$pid] = [
                    'ProductID'       => $pid,
                    'productName'     => $r->productName,
                    'OrderID'         => $r->OrderID,
                    'order_number'    => $r->order_number,
                    'orderDate'       => $r->orderDate,
                    'deadline'        => $r->deadline,
                    'stages'          => [],
                    'current_stage'   => $curStage  ? strtolower($curStage)  : null,
                    'current_status'  => $curStatus ? strtolower($curStatus) : null,
                    'accepted'        => (int)($r->accepted ?? 0),
                ];
            }

            if (!$r->stage) {
                continue;
            }

            $k    = strtolower(trim($r->stage)); // printing|furnishing|delivery|installation
            $rank = $r->completedAt ?? $r->acceptedAt ?? $r->fp_created_at;
            $cur  = $byProduct[$pid]['stages'][$k]['_rank'] ?? null;

            if (!$cur || $rank > $cur) {
                $byProduct[$pid]['stages'][$k] = [
                    'status' => strtolower((string)$r->stage_status), // completed|rejected|in_progress|null
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
                // pending/missing → stop at last completed
                return $lastCompleted >= 0 ? $positions[$lastCompleted] : 0;
            }
            return 100; // all done
        };

        $inProgress = 0;
        $completed  = 0;
        $list = [];
        foreach ($byProduct as $p) {
            $p['product_code'] = ($p['order_number'] ?: ('ORD-' . $p['OrderID']))
                . '-P' . str_pad((string)$p['ProductID'], 4, '0', STR_PAD_LEFT);

            $p['progress'] = $progressWidth($p);

            // installation completed flag for UI
            $instStatus = $p['stages']['delivery']['status'] ?? null;
            $p['delivery_completed'] = $instStatus === 'completed' ? 1 : 0;

            // KPI: (installation completed => completed)
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
        $perPage = 1000; // <-- was 1000
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

        return view('dispatchcontrol.dashboard', [
            'statuses'   => $statuses,
            'search'     => $search,
            'status'     => $status,
            'inProgress' => $inProgress,
            'completed'  => $completed,
            'rows'       => $rowsPaginated,
        ]);
    }

    public function completeWithProof(Request $request, int $product)
    {
        // Validate at least one image
        $validated = $request->validate([
            'photos'   => 'required|array|min:1',
            'photos.*' => 'file|image|max:12288', // 12MB each
        ]);

        // Resolve order id *without* changing products.status
        $orderId = DB::table('products')->where('ProductID', $product)->value('OrderID');
        abort_if(!$orderId, 404, 'Product not found.');

        $userId = (int)($request->user()->id ?? 0);
        $now    = now();

        DB::transaction(function () use ($validated, $product, $orderId, $userId, $now) {

            // 1) Store images
            $records = [];
            foreach ($validated['photos'] as $file) {
                $dir  = "installation_proofs/{$product}";
                $name = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs($dir, $name, 'public');  // storage/app/public/...

                $records[] = [
                    'ProductID'     => $product,
                    'OrderID'       => $orderId,
                    'file_path'     => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime'          => $file->getClientMimeType(),
                    'size'          => $file->getSize(),
                    'uploaded_by'   => $userId ?: null,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }

            if (!empty($records)) {
                DB::table('installation_proofs')->insert($records);
            }

            // 2) Mark INSTALLATION stage completed in fulfillment_progress
            $existing = DB::table('fulfillment_progress')
                ->where('ProductID', $product)
                ->where('stage', 'installation')
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
                    'stage'       => 'delivery',
                    'acceptedAt'  => null,
                    'completedAt' => $now,
                    'status'      => 'completed',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }

            DB::table('products')
                ->where('ProductID', $product)
                ->update([
                    'status'     => 'completed',
                    'updated_at' => $now,
            ]);

        });

        return back()->with('ok', 'Dispatch Control marked completed with photo proof.');
    }

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
                                               ->orWhere(DB::raw('LOWER(method)'), 'self_pickup')
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
