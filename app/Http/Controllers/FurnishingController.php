<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Helpers\Helpers;

class FurnishingController extends Controller
{

    public function dashboard(Request $request)
    {
        // --- Read filters
        $cutter         = trim(mb_strtolower((string) $request->get('cutter', '')));
        $sqMin          = $request->get('sq_min', '');
        $sqMax          = $request->get('sq_max', '');
        $deadlineFrom  = $request->filled('deadline_from')
            ? optional(Carbon::parse($request->input('deadline_from')))->toDateString()
            : null;
        $deadlineTo    = $request->filled('deadline_to')
            ? optional(Carbon::parse($request->input('deadline_to')))->toDateString()
            : null;

        $submittedFrom = $request->filled('submitted_from')
            ? optional(Carbon::parse($request->input('submitted_from')))->toDateString()
            : null;
        $submittedTo   = $request->filled('submitted_to')
            ? optional(Carbon::parse($request->input('submitted_to')))->toDateString()
            : null;

        // We'll reuse this SQL in SELECT and HAVING
        $sqExprSql = 'SUM(IFNULL(pi.sizeWidth,0) * IFNULL(pi.sizeHeight,0))';

        $jobs = DB::table('products as p')
            ->leftJoin('orders as o', 'p.OrderID', '=', 'o.id')
            ->leftJoin('product_items as pi', 'p.ProductID', '=', 'pi.ProductID')
            ->leftJoin('specifications as s', 'pi.ItemID', '=', 's.ItemID')

            // sensible statuses
            ->whereIn('p.status', ['in_progress', 'pending', 'completed'])

            // exclude already completed furnishing in fulfillment_progress
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                ->from('fulfillment_progress as fp')
                ->whereColumn('fp.ProductID', 'p.ProductID')
                ->where('fp.stage', 'furnishing')
                ->where('fp.status', 'completed');
            })

            // --- Filters (WHERE) ---
            // cutter (case-insensitive)
            ->when($cutter !== '', function ($qb) use ($cutter) {
                $qb->whereRaw('LOWER(s.cutter) LIKE ?', ['%'.$cutter.'%']);
            })

            // DEADLINE range (orders.deadline)
            ->when($deadlineFrom && $deadlineTo, function ($qb) use ($deadlineFrom, $deadlineTo) {
                $qb->whereBetween('o.deadline', [$deadlineFrom, $deadlineTo]);
            })
            ->when($deadlineFrom && !$deadlineTo, function ($qb) use ($deadlineFrom) {
                $qb->whereDate('o.deadline', '>=', $deadlineFrom);
            })
            ->when(!$deadlineFrom && $deadlineTo, function ($qb) use ($deadlineTo) {
                $qb->whereDate('o.deadline', '<=', $deadlineTo);
            })

            // SUBMITTED range (products.updated_at)
            ->when($submittedFrom && $submittedTo, function ($qb) use ($submittedFrom, $submittedTo) {
                $qb->whereBetween('p.updated_at', [$submittedFrom, $submittedTo]);
            })
            ->when($submittedFrom && !$submittedTo, function ($qb) use ($submittedFrom) {
                $qb->whereDate('p.updated_at', '>=', $submittedFrom);
            })
            ->when(!$submittedFrom && $submittedTo, function ($qb) use ($submittedTo) {
                $qb->whereDate('p.updated_at', '<=', $submittedTo);
            })

            // group for aggregates
            ->groupBy(
                'p.ProductID',
                'p.updated_at',
                'p.status',
                'p.taskType',
                'o.id',
                'o.order_number',
                'o.deadline',
                'p.accepted'
            )

            // select
            ->select([
                'p.ProductID',
                'p.updated_at as submission_date',
                'p.status',
                'p.taskType',
                'o.id as order_id',
                'o.order_number',
                'o.deadline',

                DB::raw("$sqExprSql as sq_inch"),

                // pick one non-empty cutter across items; fallback "—"
                DB::raw("COALESCE(NULLIF(MAX(NULLIF(s.cutter, '')), ''), '—') as cutter"),

                DB::raw("CONCAT('#ORD-', o.id, '-P', LPAD(p.ProductID, 4, '0')) as product_code"),
                DB::raw('COALESCE(p.accepted, 0) as accepted'),
            ])

            // sq inch range must be in HAVING (aggregate)
            ->when($sqMin !== '', fn($qb) => $qb->havingRaw("$sqExprSql >= ?", [(float)$sqMin]))
            ->when($sqMax !== '', fn($qb) => $qb->havingRaw("$sqExprSql <= ?", [(float)$sqMax]))

            ->orderBy('o.id')
            ->orderBy('p.ProductID')
            ->paginate(10)
            ->appends($request->query()); // keep filters on pagination links

        // KPIs
        $inProgress = DB::table('products')
            ->where('status', 'in_progress')
            ->where('taskType', 'furnishing')
            ->count();

        $completed = DB::table('fulfillment_progress')
            ->where('stage', 'furnishing')
            ->where('status', 'completed')
            ->count();

        return view('furnishing.dashboard', compact('jobs', 'inProgress', 'completed'));
    }

    // Dashboard 勾确认：把该产品置为 completed（保持 taskType=furnishing）
    public function markComplete($product)
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

        // We want to reuse the computed next stage after commit
        $computedNextStage = null;

        try {
            DB::transaction(function () use ($product, &$computedNextStage) {
                $now = now();
               
                $methods = DB::table('delivery_breakdowns')
                    ->where('ProductID', $product)
                    ->pluck('method')
                    ->map(fn ($m) => strtolower(trim((string)$m)))
                    ->unique()
                    ->all();

                $hasDelivery     = in_array('self_pickup', $methods, true) || in_array('courier', $methods, true);
                $hasInstallation = in_array('installation', $methods, true) || in_array('delivery_installation', $methods, true);;

                if ($hasDelivery && $hasInstallation) {
                    $computedNextStage = 'delivery';
                } elseif ($hasDelivery) {
                    $computedNextStage = 'delivery';
                } elseif ($hasInstallation) {
                    $computedNextStage = 'installation';
                } else {
                    $computedNextStage = 'delivery';
                }
                
                DB::table('products')
                    ->where('ProductID', $product)
                    ->update([
                        'status'     => 'in_progress',
                        'taskType'   => $computedNextStage,
                        'accepted'   => null,
                        'updated_at' => $now,
                    ]);

                $exists = DB::table('fulfillment_progress')
                    ->where('ProductID', $product)
                    ->where('stage', 'furnishing')
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
                        'stage'       => 'furnishing',
                        'acceptedAt'  => null,
                        'completedAt' => $now,
                        'status'      => 'completed',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                }
            });

            // ===== Notifications (after commit) =====
            $actor     = Auth::user();
            $actorName = $actor?->name ?? 'System';
            $actorRole = str_replace('-', ' ', $actor?->role ?? 'user');

            $productId   = (int) $p->ProductID;
            $productName = (string) $p->productName;
            $orderNo     = (string) $o->order_number;
            $orderId     = (int) $o->id;
            $nextStage   = $computedNextStage ?: 'next stage';

            $message = "Furnishing completed for Product {$productName} by {$actorName} ({$actorRole}). Next stage: {$nextStage}.";

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
                if ($role === 'operations-furnishing') {
                    return url("/furnishing/job/{$productId}");
                }
                if ($role === 'operations-dispatch-control') {
                    return url("/dispatchcontrol/job/{$productId}");
                }
                if ($role === 'operations-delivery-installation') {
                    return url("/installation/job/{$productId}");
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

            // Notify the Operations team responsible for the NEXT stage
            $stageRoleMap = [
                'furnishing'   => 'operations-furnishing',
                'delivery'     => 'operations-dispatch-control',
                'installation' => 'operations-delivery-installation',
            ];
            if (isset($stageRoleMap[$computedNextStage])) {
                $opsRole = $stageRoleMap[$computedNextStage];
                User::where('role', $opsRole)->get()->each(function (User $u) use ($message, $urlFor) {
                    // add a short directive for ops users
                    $opsMsg = $message . ' Please take over.';
                    Helpers::notify($u, $opsMsg, $urlFor($u), ['database']);
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

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
