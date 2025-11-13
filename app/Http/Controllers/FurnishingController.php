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
        // Filters (same pattern as printing)
        $q       = trim((string) $request->get('q', ''));          // unified keyword (cutter/order/company/product)
        $artist  = trim((string) $request->get('artist', ''));     // artist dropdown
        $pid     = trim((string) $request->get('pid', ''));        // NEW: product id / code (all pages)
        $mine = $request->boolean('mine');

        $readDate = function (?string $v): ?string {
            if (!$v) return null;
            try { return \Carbon\Carbon::parse($v)->toDateString(); }
            catch (\Throwable $e) {
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $v, $m)) {
                    return "{$m[3]}-".str_pad($m[1],2,'0',STR_PAD_LEFT)."-".str_pad($m[2],2,'0',STR_PAD_LEFT);
                }
                return null;
            }
        };

        $dlStart = $readDate($request->get('deadline_from'));
        $dlEnd   = $readDate($request->get('deadline_to'));
        $sbStart = $readDate($request->get('submitted_from'));
        $sbEnd   = $readDate($request->get('submitted_to'));

        $sqExpr = 'SUM(IFNULL(pi.sizeWidth,0) * IFNULL(pi.sizeHeight,0))'; // display only

        $jobs = DB::table('products as p')
            ->leftJoin('orders as o', 'p.OrderID', '=', 'o.id')
            ->leftJoin('product_items as pi', 'p.ProductID', '=', 'pi.ProductID')
            ->leftJoin('specifications as s', 'pi.ItemID', '=', 's.ItemID')
            ->leftJoin('products as r', function ($j) {
                $j->on('r.redoOf', '=', 'p.ProductID')
                ->where('r.editable', '=', 1);
            })
            ->where(function ($w) {
                $w->whereNull('o.orderStatus')
                ->orWhere('o.orderStatus', '!=', 'awaiting_keyin');
            })
            ->where(function ($w) {
                $w->where('p.editable', '=', 0)  // ✅ bypass check if editable = 0
                ->orWhere(function ($w2) {
                    $w2->whereNull('o.orderStatus')
                        ->orWhere('o.orderStatus', '!=', 'in_progress');
                });
            })
            ->whereIn('p.status', ['in_progress', 'pending', 'completed'])
            // exclude already completed furnishing
            ->whereNotExists(function ($q2) {
                $q2->select(DB::raw(1))
                ->from('fulfillment_progress as fp')
                ->whereColumn('fp.ProductID', 'p.ProductID')
                ->where('fp.stage', 'furnishing')
                ->where('fp.status', 'completed');
            })

            // Unified keyword search: cutter + order title + company name + product name
            ->when($q !== '', function ($qb) use ($q) {
                $like = '%'.$q.'%';
                $qb->where(function ($w) use ($like) {
                    $w->where('s.cutter', 'like', $like)
                    ->orWhere('o.orderTitle', 'like', $like)
                    ->orWhere('o.companyName', 'like', $like)
                    ->orWhere('p.productName', 'like', $like);
                });
            })
            ->where(function ($q) {
                $q->whereNull('o.status')->orWhere('o.status', '!=', 1);
            })

            // Artist filter (orders.artist_id)
            ->when($artist !== '', fn($qb) => $qb->where('o.artist_id', (int) $artist))

            // Deadline (DATE)
            ->when($dlStart && $dlEnd, fn($q) => $q->whereBetween('o.deadline', [$dlStart, $dlEnd]))
            ->when($dlStart && !$dlEnd, fn($q) => $q->whereDate('o.deadline', '>=', $dlStart))
            ->when(!$dlStart && $dlEnd, fn($q) => $q->whereDate('o.deadline', '<=', $dlEnd))

            // Submission (DATE(p.updated_at))
            ->when($sbStart && $sbEnd, fn($q) => $q->whereBetween(DB::raw('DATE(p.updated_at)'), [$sbStart, $sbEnd]))
            ->when($sbStart && !$sbEnd, fn($q) => $q->whereDate('p.updated_at', '>=', $sbStart))
            ->when(!$sbStart && $sbEnd, fn($q) => $q->whereDate('p.updated_at', '<=', $sbEnd))

            ->when($pid !== '', function ($qb) use ($pid) {
                $like = '%'.$pid.'%';

                $qb->where(function ($w) use ($pid, $like) {
                    // Fast exact matches for numeric input
                    if (ctype_digit($pid)) {
                        $w->orWhere('o.id', (int)$pid)         // new order id
                        ->orWhere('o.redo', (int)$pid)       // original order id
                        ->orWhere('p.ProductID', (int)$pid)  // new product id
                        ->orWhere('p.redoOf', (int)$pid);    // original product id
                    }

                    // Fuzzy matches (strings / partials)
                    $w->orWhere('o.id', 'like', $like)
                    ->orWhere('o.redo', 'like', $like)
                    ->orWhere('p.ProductID', 'like', $like)
                    ->orWhere('p.redoOf', 'like', $like)
                    ->orWhere('o.order_number', 'like', $like)

                    // match the formatted code WITHOUT the trailing R (no aggregates in WHERE)
                    ->orWhere(DB::raw("
                        CONCAT(
                            '#ORD-',
                            YEAR(o.orderDate), '-',
                            LPAD(COALESCE(o.redo, o.id), 3, '0'),
                            '-P', LPAD(COALESCE(p.redoOf, p.ProductID), 4, '0')
                        )
                    "), 'like', $like);
                });
            })

            ->groupBy(
                'p.OrderID',
                'o.status','p.ProductID', 'p.updated_at', 'p.status', 'p.taskType',
                'o.id', 'o.order_number', 'o.deadline', 'o.orderDate', 'o.created_at', 'p.accepted', 'p.redoOf','p.editable', 'o.redo'
            )

            ->select([
                'p.ProductID',
                'p.updated_at as submission_date',
                'p.status as product_status',
                'o.orderDate',
                DB::raw('MAX(o.status) as order_status'),
                'p.taskType',
                'o.id as order_id',
                'o.order_number',
                'o.deadline',
                'p.redoOf',
                'p.editable',
                DB::raw("$sqExpr as sq_inch"),
                DB::raw("COALESCE(NULLIF(MAX(NULLIF(s.cutter, '')), ''), '—') as cutter"),
                DB::raw('MAX(r.ProductID) as redo_product_id'),

                // deadline flags for UI
                DB::raw("CASE WHEN o.deadline IS NOT NULL AND o.deadline < CURDATE()
                        THEN 1 ELSE 0 END AS is_overdue"),
                DB::raw("CASE WHEN o.deadline IS NOT NULL AND o.deadline >= CURDATE()
                            AND o.deadline <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                        THEN 1 ELSE 0 END AS is_due_soon"),

                DB::raw("
                CONCAT(
                    '#ORD-',
                    YEAR(o.orderDate), '-',
                    LPAD(COALESCE(o.redo, o.id), 3, '0'),
                    '-P', LPAD(COALESCE(p.redoOf, p.ProductID), 4, '0'),
                    CASE
                    WHEN ( (p.redoOf IS NOT NULL AND p.editable = 1) OR COUNT(r.ProductID) > 0 )
                        THEN 'R'
                    ELSE ''
                    END
                ) AS display_product_id
                "),

                DB::raw("
                CASE 
                    WHEN (p.redoOf IS NOT NULL OR COUNT(r.ProductID) > 0) 
                    THEN 1 ELSE 0 
                END as is_redo_product
                "),
                DB::raw('COALESCE(p.accepted, 0) as accepted'),
            ]);

        $sort = (string) $request->get('sort', 'deadline_nearest');

        switch ($sort) {
            case 'deadline_furthest':
                $jobs->orderByRaw('o.deadline IS NULL')
                    ->orderByRaw('ABS(DATEDIFF(o.deadline, CURDATE())) DESC')
                    ->orderBy('o.deadline', 'asc')
                    ->orderBy('o.id')->orderBy('p.ProductID');
                break;

            case 'submitted_nearest':
                $jobs->orderByRaw('DATE(p.updated_at) IS NULL')
                    ->orderByRaw('ABS(DATEDIFF(DATE(p.updated_at), CURDATE())) ASC')
                    ->orderBy('p.updated_at', 'asc')
                    ->orderBy('o.id')->orderBy('p.ProductID');
                break;

            case 'submitted_furthest':
                $jobs->orderByRaw('DATE(p.updated_at) IS NULL')
                    ->orderByRaw('ABS(DATEDIFF(DATE(p.updated_at), CURDATE())) DESC')
                    ->orderBy('p.updated_at', 'asc')
                    ->orderBy('o.id')->orderBy('p.ProductID');
                break;

            case 'accepted_first':
                // accepted = 1 first, then newest
                $jobs->orderByRaw('CASE WHEN COALESCE(p.accepted,0) = 1 THEN 0 ELSE 1 END')
                    ->orderBy('p.updated_at', 'desc')
                    ->orderBy('o.id')
                    ->orderBy('p.ProductID');
                break;

            case 'accepted_last':
                // accepted = 1 last
                $jobs->orderByRaw('CASE WHEN COALESCE(p.accepted,0) = 1 THEN 1 ELSE 0 END')
                    ->orderBy('p.updated_at', 'desc')
                    ->orderBy('o.id')
                    ->orderBy('p.ProductID');
                break;

            case 'deadline_nearest':
            default:
                $jobs->orderByRaw('o.deadline IS NULL')
                    ->orderByRaw('ABS(DATEDIFF(o.deadline, CURDATE())) ASC')
                    ->orderBy('o.deadline', 'asc')
                    ->orderBy('o.id')->orderBy('p.ProductID');
                break;
        }

        if ($mine) {
            $role = strtolower(Auth::user()->role ?? '');

            // Map roles to the stage/column used in DB
            $stageForRole = match ($role) {
                'operations-printing'              => 'printing',
                'operations-furnishing'            => 'furnishing',
                'operations-dispatch-control'      => 'delivery',      // stored in products.status
                'operations-delivery-installation' => 'installation',  // stored in products.status
                default => null,
            };

            if ($stageForRole) {
                // For printing/furnishing we use products.taskType
                if (in_array($stageForRole, ['furnishing'], true)) {
                    $jobs->where('p.taskType', $stageForRole);
                } else {
                    // For dispatch-control & delivery-installation we use products.status
                    $jobs->whereRaw('LOWER(p.status) = ?', [$stageForRole]);
                }
            }
        }

        $jobs = $jobs->paginate(10)->appends($request->query());

        // KPIs
        $inProgress = DB::table('products as p')
            ->join('orders as o', 'o.id', '=', 'p.OrderID')
            ->where('p.status', 'in_progress')
            ->where('p.taskType', 'furnishing')
            ->where(function ($q) {
                $q->whereNull('o.status')->orWhere('o.status', '!=', 1);
            })
            ->where(function ($q) {
                $q->whereNull('o.orderStatus')
                ->orWhere('o.orderStatus', '!=', 'awaiting_keyin');
            })
            ->where(function ($q) {
                $q->whereNull('o.orderStatus')
                ->orWhere('o.orderStatus', '!=', 'in_progress');
            })
            ->count();

        $completed = DB::table('fulfillment_progress as fp')
            ->join('products as p', 'p.ProductID', '=', 'fp.ProductID')
            ->join('orders as o', 'o.id', '=', 'p.OrderID')
            ->where('fp.stage', 'furnishing')
            ->where('fp.status', 'completed')
            ->where(function ($q) {
                $q->whereNull('o.status')->orWhere('o.status', '!=', 1);
            })
            ->count();

        // Artist dropdown (same approach as printing)
        $artists = DB::table('users as u')
            ->select('u.id','u.name')
            ->whereIn('u.id', function ($sub) {
                $sub->from('orders as o')->select('o.artist_id')->whereNotNull('o.artist_id');
            })
            ->orderBy('u.name')->get();

        return view('furnishing.dashboard', compact('jobs','inProgress','completed','artists'));
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
                $hasInstallation = in_array('installation', $methods, true) || in_array('delivery_installation', $methods, true);
                
                // extra install fields only when BOTH delivery & installation exist
                $installFields = [];

                if ($hasDelivery && $hasInstallation) {
                    $computedNextStage = 'delivery';
                    $installFields = [
                        'installation_task_type' => 1,
                        'installation_status'    => 'in_progress', // note: spelled consistently
                        'installation_accepted'  => null,
                    ];
                } elseif ($hasDelivery) {
                    $computedNextStage = 'delivery';
                } elseif ($hasInstallation) {
                    $computedNextStage = 'installation';
                } else {
                    $computedNextStage = 'delivery';
                }
                
                DB::table('products')
                    ->where('ProductID', $product)
                    ->update(array_merge([
                        'status'     => 'in_progress',
                        'taskType'   => $computedNextStage,
                        'accepted'   => null,
                        'updated_at' => $now,
                    ], $installFields));

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
