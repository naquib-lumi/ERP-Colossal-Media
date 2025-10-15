<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Helpers\Helpers;

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
            $instStatus = $p['stages']['installation']['status'] ?? null;
            $p['installation_completed'] = $instStatus === 'completed' ? 1 : 0;

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
        $perPage = 10; // <-- was 1000
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
            'rows'       => $rowsPaginated,
        ]);
    }

    public function completeWithProof(Request $request, int $product)
    {
        // Load product + order info up-front for notifications
        $p = DB::table('products')
            ->where('ProductID', $product)
            ->select('ProductID','productName','OrderID')
            ->first();

        if (!$p) {
            return response()->json(['ok' => false, 'message' => 'Product not found.'], 404);
        }

        $o = DB::table('orders')
            ->where('id', $p->OrderID)
            ->select('id','order_number','artist_id','salesperson_id')
            ->first();

        if (!$o) {
            return response()->json(['ok' => false, 'message' => 'Order not found for this product.'], 404);
        }

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
                    'stage'       => 'installation',
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

        // ===== Notifications (after commit) =====
        $actor     = Auth::user();
        $actorName = $actor?->name ?? 'System';
        $actorRole = str_replace('-', ' ', $actor?->role ?? 'user');

        $productId   = (int) $p->ProductID;
        $productName = (string) $p->productName;
        $orderNo     = (string) $o->order_number;
        $orderId     = (int) $o->id;

        $message = "Delivery & Installation completed for Product {$productName} by {$actorName} ({$actorRole}), product completed.";

        // Role-aware destination (adjust if your routes differ)
        $urlFor = function (User $user) use ($productId, $orderId) {
            $role = strtolower($user->role);

            if (in_array($role, ['artist','head-artist'])) {
                return url("/artist/orders/{$orderId}");
            }
            if (in_array($role, ['salesperson','head-salesperson'])) {
                return url("/orders/{$orderId}");
            }
            if ($role === 'boss') {
                return url("/boss/orders/{$orderId}");
            }
            if ($role === 'admin') {
                return url("/admin/orders/{$orderId}");
            }
            return url("/orders/{$productId}");
        };

        // Notify assignees (artist + salesperson)
        $targetIds = array_filter([
            $o->salesperson_id ?? null,
            $o->artist_id      ?? null,
        ]);

        if (!empty($targetIds)) {
            User::whereIn('id', $targetIds)->get()->each(function (User $u) use ($message, $urlFor) {
                Helpers::notify($u, $message, $urlFor($u), ['database']);
            });
        }

        // Optional: heads/admin/boss (remove if you only want the above)
        User::whereIn('role', ['head-salesperson','head-artist'])->get()
            ->each(function (User $u) use ($message, $urlFor) {
                Helpers::notify($u, $message, $urlFor($u), ['database']);
            });

        User::whereIn('role', ['admin','boss'])->get()
            ->each(function (User $u) use ($message, $urlFor) {
            Helpers::notify($u, $message, $urlFor($u), ['database']);
            });

        return back()->with('ok', 'Installation marked completed with photo proof.');
    }
}
