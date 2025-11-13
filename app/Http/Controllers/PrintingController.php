<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Helpers\Helpers;
use App\Models\Order;
use App\Models\Product;

class PrintingController extends Controller
{
    public function dashboard(Request $request)
    {
        // --- read raw inputs
        $q        = trim((string) $request->get('q', ''));            // unified keyword search
        $artistId = trim((string) $request->get('artist', ''));       // artist filter
        $pid      = trim((string) $request->query('pid', ''));        // Product ID / code (all pages)
        $sort     = (string) $request->get('sort', 'deadline_nearest');
        $mine = $request->boolean('mine');

        $readDate = function (?string $v): ?string {
            if (!$v) return null;
            try {
                return \Carbon\Carbon::parse($v)->toDateString();
            } catch (\Throwable $e) {
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

        // square inch kept for display only (no more filtering)
        $sqExpr = 'SUM(IFNULL(pi.sizeWidth,0) * IFNULL(pi.sizeHeight,0))';

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
                $w->whereNull('o.orderStatus')
                ->orWhere('o.orderStatus', '!=', 'in_progress');
            })
            ->whereIn('p.status', ['in_progress', 'pending', 'completed'])
            ->whereNotExists(function ($q2) {
                $q2->select(DB::raw(1))
                ->from('fulfillment_progress as fp')
                ->whereColumn('fp.ProductID', 'p.ProductID')
                ->where('fp.stage', 'printing')
                ->where('fp.status', 'completed');
            })

            // Unified keyword search: printer, order title, company name, product name
            ->when($q !== '', function ($qb) use ($q) {
                $like = '%' . $q . '%';
                $qb->where(function ($qq) use ($like) {
                    $qq->where('s.printer', 'like', $like)
                    ->orWhere('o.orderTitle', 'like', $like)
                    ->orWhere('o.companyName', 'like', $like)
                    ->orWhere('p.productName', 'like', $like);
                });
            })
            ->where(function ($q) {
                $q->whereNull('o.status')->orWhere('o.status', '!=', 1);
            })

            // Artist filter
            ->when($artistId !== '', fn($qb) => $qb->where('o.artist_id', (int) $artistId))

            // DEADLINE (orders.deadline is a DATE)
            ->when($dlStart && $dlEnd, fn($q) => $q->whereBetween('o.deadline', [$dlStart, $dlEnd]))
            ->when($dlStart && !$dlEnd, fn($q) => $q->whereDate('o.deadline', '>=', $dlStart))
            ->when(!$dlStart && $dlEnd, fn($q) => $q->whereDate('o.deadline', '<=', $dlEnd))

            // SUBMISSION DATE (DATE(p.updated_at))
            ->when($sbStart && $sbEnd, fn($q) => $q->whereBetween(DB::raw('DATE(p.updated_at)'), [$sbStart, $sbEnd]))
            ->when($sbStart && !$sbEnd, fn($q) => $q->whereDate('p.updated_at', '>=', $sbStart))
            ->when(!$sbStart && $sbEnd, fn($q) => $q->whereDate('p.updated_at', '<=', $sbEnd))

            // NEW: Product ID / code filter (server-side, all pages)
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
                'o.status',
                'p.ProductID',
                'p.updated_at',
                'p.status',
                'p.taskType',
                'o.id',
                'o.order_number',
                'o.deadline',
                'o.orderDate',
                'o.created_at',
                'p.accepted','p.redoOf','p.editable', 'o.redo'
            )
            ->select([
                'p.ProductID',
                'p.updated_at as submission_date',
                // 'p.OrderID',
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
                DB::raw("COALESCE(NULLIF(MAX(NULLIF(s.printer, '')), ''), '—') as printer"),
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

        // Sorting
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
                if (in_array($stageForRole, ['printing'], true)) {
                    $jobs->where('p.taskType', $stageForRole);
                } else {
                    // For dispatch-control & delivery-installation we use products.status
                    $jobs->whereRaw('LOWER(p.status) = ?', [$stageForRole]);
                }
            }
        }

        // paginate AFTER all filters → searches across all pages
        $jobs = $jobs->paginate(10)->appends($request->query());

        // KPI blocks (unchanged)
        $inProgress = DB::table('products as p')
            ->join('orders as o', 'o.id', '=', 'p.OrderID')
            ->where('p.status', 'in_progress')
            ->where('p.taskType', 'printing')
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
            ->where('fp.stage', 'printing')
            ->where('fp.status', 'completed')
            ->where(function ($q) {
                $q->whereNull('o.status')->orWhere('o.status', '!=', 1);
            })
            ->count();

        // Artist dropdown (as-is)
        $artists = DB::table('users as u')
            ->select('u.id', 'u.name')
            ->whereIn('u.id', function ($sub) {
                $sub->from('orders as o')
                    ->select('o.artist_id')
                    ->whereNotNull('o.artist_id');
            })
            ->orderBy('u.name')
            ->get();

        return view('printing.dashboard', [
            'jobs'       => $jobs,
            'inProgress' => $inProgress,
            'completed'  => $completed,
            'artists'    => $artists,

            // return current filters so inputs keep values
            'q'          => $q,
            'artist'     => $artistId,
            'pid'        => $pid,
            'sort'       => $sort,
            'deadline_from'   => $dlStart,
            'deadline_to'     => $dlEnd,
            'submitted_from'  => $sbStart,
            'submitted_to'    => $sbEnd,
        ]);
    }

    /**
     * Show a single Printing Job Order detail page
     * Route: GET /printing/jobs/{product}
     * View : resources/views/printing/job_order_show.blade.php
     */
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

        if (!$row) {
            abort(404, 'Product not found');
        }

        $orderCode = $row->order_number ?: ('ORD' . $row->OrderID . '-P' . $row->ProductID);

        return view('printing.job_order_show', [
            'productId'   => $row->ProductID,
            'orderId'     => $row->order_id,
            'orderStatus' => $row->order_status,
            'editable'    => (int) ($row->editable ?? 0),
            'orderCode'   => $orderCode,
            'deadline'    => $row->deadline,
        ]);
    }

    public function updatePrinters(Request $request)
    {
        $data = $request->validate([
            'productId'           => ['required', 'integer'],
            'printers'            => ['required', 'array', 'min:1'],
            'printers.*.item_id'  => ['nullable', 'integer'],
            'printers.*.line'     => ['nullable', 'integer'],
            'printers.*.printer'  => ['nullable', 'string', 'max:255'],
        ]);

        // 确认产品存在
        $productExists = DB::table('products')
            ->where('ProductID', $data['productId'])
            ->exists();

        if (!$productExists) {
            return response()->json([
                'ok' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        // 把本产品下所有 item 排序拿到，便于无 item_id 时按行号映射
        $itemIds = DB::table('product_items')
            ->where('ProductID', $data['productId'])
            ->orderBy('ItemID')
            ->pluck('ItemID')
            ->values(); // [1001, 1002, ...]

        DB::beginTransaction();
        try {
            $saved = [];

            foreach ($data['printers'] as $idx => $row) {
                // 解析目标 ItemID
                $itemId = $row['item_id'] ?? null;

                if (!$itemId && isset($row['line'])) {
                    // line 从 1 开始，映射到排序后的 ItemID
                    $line = (int) $row['line'];
                    $itemId = $itemIds[$line - 1] ?? null;
                }

                // 没能解析到 ItemID 就跳过
                if (!$itemId) {
                    continue;
                }

                $printer = $row['printer'] ?? null;

                // 如果 specifications 已存在此 ItemID，更新；否则插入
                $exists = DB::table('specifications')
                    ->where('ItemID', $itemId)
                    ->exists();

                if ($exists) {
                    DB::table('specifications')
                        ->where('ItemID', $itemId)
                        ->update([
                            'printer'   => $printer,
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('specifications')->insert([
                        'ItemID'     => $itemId,
                        'printer'    => $printer,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $saved[] = ['item_id' => $itemId, 'printer' => $printer];
            }

            DB::commit();

            return response()->json([
                'ok'     => true,
                'count'  => count($saved),
                'saved'  => $saved,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function markPrinted($product)
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

                $installFields = [];

                $hasFurnishingWork = DB::table('product_items as pi')
                    ->leftJoin('specifications as s', 'pi.ItemID', '=', 's.ItemID')
                    ->where('pi.ProductID', $product)
                    ->where(function ($q) {
                        $q->whereNotNull('s.lamination')->where('s.lamination', '!=', 'no')
                            ->orWhereNotNull('s.cutter')->where('s.cutter', '!=', 'no');
                    })
                    ->exists();

                if ($hasFurnishingWork) {
                    $computedNextStage  = 'furnishing';
                } else {
                    $methods = DB::table('delivery_breakdowns')
                        ->where('ProductID', $product)
                        ->pluck('method')
                        ->map(fn($m) => strtolower(trim((string)$m)))
                        ->unique()
                        ->all();

                    $hasDelivery     = in_array('self_pickup', $methods, true) || in_array('courier', $methods, true);
                    $hasInstallation = in_array('installation', $methods, true) || in_array('delivery_installation', $methods, true);

                    if ($hasDelivery && $hasInstallation) {
                        $computedNextStage  = 'delivery';
                        $installFields = [
                            'installation_task_type' => 1,
                            'installation_status'    => 'in_progress', // note: spelled consistently
                            'installation_accepted'  => null,
                        ];
                    } elseif ($hasDelivery) {
                        $computedNextStage  = 'delivery';
                    } elseif ($hasInstallation) {
                        $computedNextStage  = 'installation';
                    } else {
                        $computedNextStage  = 'delivery';
                    }
                }

                DB::table('products')
                    ->where('ProductID', $product)
                    ->update(array_merge([
                        'status'     => 'in_progress',
                        'taskType'   => $computedNextStage,
                        'accepted'   => null,
                        'updated_at' => $now,
                    ], $installFields));

                $existing = DB::table('fulfillment_progress')
                    ->where('ProductID', $product)
                    ->where('stage', 'printing')
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
                        'stage'       => 'printing',
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

            $message = "Printing completed for Product {$productName} by {$actorName} ({$actorRole}). Next stage: {$nextStage}.";

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
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Report issue form (Printing)
     */
    public function reportForm($productId)
    {
        $row = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('product_items as pi', 'p.ProductID', '=', 'pi.ProductID')
            ->select([
                'p.ProductID',
                'p.redoOf',
                'p.created_at',
                'o.orderDate',
                'o.order_number',
                DB::raw("
                    CONCAT(
                        '#ORD-',
                        YEAR(o.orderDate), '-',
                        LPAD(o.id, 3, '0'),
                        '-P',
                        LPAD(COALESCE(p.redoOf, p.ProductID), 4, '0'),
                        CASE 
                            WHEN p.redoOf IS NOT NULL THEN 'R' 
                            ELSE '' 
                        END
                    ) AS product_code_display
                "),
            ])
            ->where('p.ProductID', $productId)
            ->first();

        return view('printing.report_issue', [
            'productId' => $productId,
            'row'       => $row
        ]);
    }


    public function reportSubmit(Request $request, int $productId)
    {
        // 1) Validate + normalize
        $data = $request->validate([
            'reason'        => ['required', 'string', 'max:255'],
            'other_reason'  => ['nullable', 'string', 'max:2000'],
            'reason_other'  => ['nullable', 'string', 'max:2000'], // legacy alias
            'notes'         => ['nullable', 'string', 'max:2000'],
        ]);

        $picked     = trim((string)($data['reason'] ?? ''));
        $otherText  = trim((string)($data['other_reason'] ?? $data['reason_other'] ?? ''));
        $notes      = trim((string)($data['notes'] ?? ''));

        // If "Others" → use typed text; else append notes to chosen reason
        $baseReason = (strcasecmp($picked, 'Others') === 0)
            ? ($otherText !== '' ? $otherText : 'Others')
            : $picked;

        $reasonText = $baseReason;
        if ($notes !== '') {
            $reasonText .= ': ' . $notes; // "reason: note"
        }

        // 2) Resolve the product and order
        $product = Product::findOrFail($productId);
        $order   = Order::findOrFail((int)$product->OrderID);

        // 3) Transaction – archive prior redos of the base, create new redo, clone data
        DB::transaction(function () use ($order, $productId, $reasonText) {

            $baseId    = $order->redo ? (int) $order->redo : (int) $order->id;
            $baseOrder = $order->redo ? Order::findOrFail($baseId) : $order;

            // 🔴 Hide existing redos (status=1) for the same base so only newest redo shows
            Order::where('redo', $baseId)->update(['status' => 1]);

            // (Optional) count existing for analytics; we don’t need it for numbering here
            $existingCount = Order::lockForUpdate()->where('redo', $baseId)->count();

            // 🔵 Create brand-new redo order from the BASE order
            $redo = $baseOrder->replicate([
                'id','order_number','created_at','updated_at','submit','draft','status','redo','orderStatus'
            ]);

            $redo->order_number = $this->nextRedoNumber($baseOrder->order_number);
            $redo->redo         = $baseId;
            $redo->draft        = 1;
            $redo->submit       = 0;
            $redo->orderStatus  = 'in_progress';
            $redo->status       = 0;           // keep visible
            $redo->data_entry_id = null;       // detach from DE
            $redo->created_at   = now();
            $redo->updated_at   = now();

            if ($reasonText !== '') {
                $redo->orderDetail = trim(
                    ($baseOrder->orderDetail ? $baseOrder->orderDetail . "\n\n" : '')
                    . 'REDO Reason: ' . $reasonText
                );
            }

            $redo->save();

            // 🗒️ Log the reason
            if ($reasonText !== '') {
                DB::table('report_redo')->insert([
                    'OrderID'    => $baseId,
                    'reason'     => $reasonText,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 4) Clone ALL products of the BASE order → only the REPORTED product is editable=1
            $baseProducts = Product::with(['items.spec','remarks','deliveryBreakdowns','progress'])
                ->where('OrderID', $baseOrder->id)
                ->orderBy('ProductID')
                ->get();

            // Find the “origin” ProductID for the reported product (in case we’re on a redo)
            $reportedOriginId = Product::where('ProductID', $productId)
                ->value(DB::raw('COALESCE(redoOf, ProductID)'));

            foreach ($baseProducts as $origin) {
                $editable = ((int)$origin->ProductID === (int)$reportedOriginId) ? 1 : 0;

                $np = $origin->replicate(['ProductID','OrderID','created_at','updated_at']);
                $np->OrderID    = $redo->id;
                $np->redoOf     = $origin->ProductID;
                $np->editable   = $editable;
                $np->created_at = now();
                $np->updated_at = now();
                $np->accepted   = null;
                $np->save();

                foreach ($origin->items as $it) {
                    $ni = $it->replicate(['ItemID','ProductID','created_at','updated_at']);
                    $ni->ProductID  = $np->ProductID;
                    $ni->created_at = now();
                    $ni->updated_at = now();
                    $ni->save();

                    if ($it->spec) {
                        $ns = $it->spec->replicate(['SpecificationID','ItemID','created_at','updated_at']);
                        $ns->ItemID     = $ni->ItemID;
                        $ns->created_at = now();
                        $ns->updated_at = now();
                        $ns->save();
                    }
                }
                foreach ($origin->remarks as $rm) {
                    $nr = $rm->replicate(['RemarkID','ProductID','created_at','updated_at']);
                    $nr->ProductID  = $np->ProductID;
                    $nr->created_at = now();
                    $nr->updated_at = now();
                    $nr->save();
                }
                foreach ($origin->deliveryBreakdowns as $db) {
                    $nd = $db->replicate(['BreakdownID','ProductID','created_at','updated_at']);
                    $nd->ProductID  = $np->ProductID;
                    $nd->created_at = now();
                    $nd->updated_at = now();
                    $nd->save();
                }
                foreach ($origin->progress as $pg) {
                    $npgr = $pg->replicate(['ProgressID','ProductID','created_at','updated_at']);
                    $npgr->ProductID  = $np->ProductID;
                    $npgr->created_at = now();
                    $npgr->updated_at = now();
                    $npgr->save();
                }
            }

            // (Optional) if this was the first redo ever, you could archive the base:
            if ($existingCount === 0 && $order->id === $baseOrder->id) {
                $order->forceFill(['status' => 1])->save();
            }
        });

        return redirect()
            ->route('printing.dashboard')
            ->with('status', 'Report submitted. Redo order has been created.');
    }

    protected function nextRedoNumber(string $baseOrderNo): string
    {
        // remove leading '#' and any existing R or R1/R2 etc.
        $base = ltrim($baseOrderNo, '#');
        $base = preg_replace('/R\d*$/i', '', $base);

        // always return one R only
        return '#' . $base . 'R';
    }
}
