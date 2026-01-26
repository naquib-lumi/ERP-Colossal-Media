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

        $pid     = trim((string)$request->query('pid', ''));    // product id/code (all pages)
        $q       = trim((string)$request->query('q', ''));      // keyword
        $artist  = trim((string)$request->query('artist', '')); // orders.artist_id
        $dFrom   = trim((string)$request->query('deadline_from', ''));
        $dTo     = trim((string)$request->query('deadline_to', ''));
        $mine = $request->boolean('mine');
        $sort   = (string) $request->get('sort');

        $search  = $q;
        $status  = $request->query('status', 'all');

        $toYmd = static function (?string $v): ?string {
            if (!$v) return null;
            try { return \Carbon\Carbon::parse($v)->toDateString(); } catch (\Throwable $e) { return null; }
        };
        $dFromY = $toYmd($dFrom);
        $dToY   = $toYmd($dTo);

         $artists = DB::table('users')
            ->select('id', 'name')
            ->whereIn('role', ['artist', 'head-artist'])
            ->orderBy('name')
            ->get();

        // 1) Pull all rows we need
        $rows = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('fulfillment_progress as fp', 'fp.ProductID', '=', 'p.ProductID')
            ->leftJoin(DB::raw('(
                SELECT DISTINCT redoOf
                FROM products
                WHERE editable = 1 AND redoOf IS NOT NULL
            ) rr'), 'rr.redoOf', '=', 'p.ProductID')
             ->where(function ($q) {
                $q->whereNull('o.status')->orWhere('o.status', '!=', 1);
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
            // ⬇️ NEW FILTER HERE
            ->where(function ($w) {
                // keep everything EXCEPT rows where
                // p.taskType = 'installation' AND p.status = 'completed'
                $w->where('p.taskType', '!=', 'installation')
                ->orWhereNull('p.status')
                ->orWhere('p.status', '!=', 'completed');
            })
            ->whereNotExists(function ($q2) {
                $q2->select(DB::raw(1))
                ->from('fulfillment_progress as fp')
                ->whereColumn('fp.ProductID', 'p.ProductID')
                ->where('fp.stage', 'delivery')
                ->where('fp.status', 'completed');
            })
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
            ->select([
                'p.ProductID',
                'p.productName',
                'p.OrderID',
                'p.taskType as current_stage',
                'p.status   as current_status',
                // NEW
                'p.installation_task_type',
                'p.installation_status',
                'p.installation_accepted',
                'p.packaging',
                'o.order_number',
                'o.orderDate',
                'o.deadline',
                'o.orderTitle',     
                'o.companyName',    
                'o.artist_id',
                'fp.stage',
                'fp.status as stage_status',
                'fp.acceptedAt',
                'fp.completedAt',
                'fp.created_at as fp_created_at',
                'o.redo',                    
                'rr.redoOf as redo_marker',   
                'p.redoOf',
                'p.editable',
                'o.id as order_id',
                'o.orderDate',
                DB::raw("
                CONCAT(
                    '#ORD-',
                    YEAR(o.orderDate), '-',
                    LPAD(COALESCE(o.redo, o.id), 3, '0'),
                    '-P', LPAD(COALESCE(p.redoOf, p.ProductID), 4, '0'),
                    CASE
                    WHEN ((p.redoOf IS NOT NULL AND p.editable = 1) OR rr.redoOf IS NOT NULL)
                        THEN 'R'
                    ELSE ''
                    END
                ) AS display_product_id
                "),

                // boolean flag for ‘redo related’
                DB::raw("
                CASE
                    WHEN (p.redoOf IS NOT NULL OR rr.redoOf IS NOT NULL) THEN 1
                    ELSE 0
                END AS is_redo_product
                "),
                DB::raw('COALESCE(p.accepted, 0) as accepted'),
            ])
            ->orderBy('p.ProductID')
            ->get();

        // 2) Reduce to "latest row per stage" for each product
        $byProduct = [];
        foreach ($rows as $r) {
            $pidKey = $r->ProductID;
            if (!isset($byProduct[$pidKey])) {
                // null-safe lowercasing for current stage/status
                $curStage  = $r->current_stage ?? null;
                $curStatus = $r->current_status ?? null;

                $byProduct[$pidKey] = [
                    'ProductID'       => $pidKey,
                    'productName'     => $r->productName,
                    'OrderID'         => $r->OrderID,
                    'order_number'    => $r->order_number,
                    'orderDate'       => $r->orderDate,
                    'deadline'        => $r->deadline,
                    'orderTitle'      => $r->orderTitle,     // keep for search
                    'companyName'     => $r->companyName,    // keep for search
                    'artist_id'       => $r->artist_id,      // keep for filter
                    'stages'          => [],
                    'current_stage'   => $curStage  ? strtolower($curStage)  : null,
                    'current_status'  => $curStatus ? strtolower($curStatus) : null,
                    'accepted'        => (int)($r->accepted ?? 0),
                    'packaging' => (int)($r->packaging ?? 0),
                    // NEW
                    'installation_task_type' => $r->installation_task_type ?? null,
                    'installation_status'     => $r->installation_status ? strtolower($r->installation_status) : null,
                    'installation_accepted'   => $r->installation_accepted ?? null,

                    'order_base_id'    => $r->redo ?? $r->order_id,                 // COALESCE(o.redo, o.id)
                    'product_base_id'  => $r->redoOf ?? $r->ProductID,              // COALESCE(p.redoOf, p.ProductID)
                    'append_R'         => ((isset($r->redoOf) && (int)$r->editable === 1)  // own redoOf + editable=1
                                        || !is_null($r->redo_marker)), 
                ];
            }

            if (!$r->stage) {
                continue;
            }

            $k    = strtolower(trim($r->stage)); // printing|furnishing|delivery|installation
            $rank = $r->completedAt ?? $r->acceptedAt ?? $r->fp_created_at;
            $cur  = $byProduct[$pidKey]['stages'][$k]['_rank'] ?? null;

            if (!$cur || $rank > $cur) {
                $byProduct[$pidKey]['stages'][$k] = [
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

        $inProgress = DB::table('products as p')
            ->join('orders as o', 'o.id', '=', 'p.OrderID')
            ->where(function ($q) {
                $q->whereNull('o.status')->orWhere('o.status', '!=', 1);   // exclude deleted/cancelled orders
            })
            ->where('p.taskType', 'delivery')
            ->where('p.status', 'in_progress')
            ->count();

        // Completed = fulfillment_progress says installation completed (distinct products)
        $completed = DB::table('fulfillment_progress as fp')
            ->join('products as p', 'p.ProductID', '=', 'fp.ProductID')
            ->join('orders as o', 'o.id', '=', 'p.OrderID')
            ->where(function ($q) {
                $q->whereNull('o.status')->orWhere('o.status', '!=', 1);   // exclude deleted/cancelled orders
            })
            ->where('fp.stage', 'delivery')
            ->where('fp.status', 'completed')
            ->distinct('fp.ProductID')
            ->count('fp.ProductID');

        $list = [];
        $today = now()->startOfDay();
        foreach ($byProduct as $p) {
            $year = $p['orderDate'] ? substr($p['orderDate'], 0, 4) : date('Y');
            $ord  = str_pad((string)$p['order_base_id'],   3, '0', STR_PAD_LEFT);
            $prod = str_pad((string)$p['product_base_id'], 4, '0', STR_PAD_LEFT);

            $p['product_code'] = "#ORD-{$year}-{$ord}-P{$prod}" . ($p['append_R'] ? 'R' : '');

            $p['progress'] = $progressWidth($p);
            $instStatus = $p['stages']['delivery']['status'] ?? null;
            $p['delivery_completed'] = $instStatus === 'completed' ? 1 : 0;

            $dlRaw = $p['deadline'] ?? null;
            $dl    = $dlRaw ? \Carbon\Carbon::parse($dlRaw)->startOfDay() : null;

            $p['is_overdue']  = $dl ? $dl->lt($today) : false;                         // deadline < today
            $p['is_due_soon'] = $dl ? (!$p['is_overdue'] && $dl->lte($today->copy()->addDays(3))) : false;

            $list[] = $p;
        }

        // Product ID / code (searches across pages)
        if ($pid !== '') {
            $needle = mb_strtolower($pid);
            $list = array_values(array_filter($list, function ($p) use ($needle) {
                $code = mb_strtolower((string)($p['product_code'] ?? ''));
                return str_contains((string)($p['ProductID'] ?? ''), $needle) || str_contains($code, $needle);
            }));
        }

        // Keyword: order title / company / product name
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $list = array_values(array_filter($list, function ($p) use ($needle) {
                return str_contains(mb_strtolower($p['orderTitle']  ?? ''), $needle)
                    || str_contains(mb_strtolower($p['companyName'] ?? ''), $needle)
                    || str_contains(mb_strtolower($p['productName'] ?? ''), $needle);
            }));
        }

        // Artist filter
        if ($artist !== '') {
            $list = array_values(array_filter($list, fn($p) => (string)($p['artist_id'] ?? '') === (string)$artist));
        }

        // Deadline range
        if ($dFromY || $dToY) {
            $fromTS = $dFromY ? strtotime($dFromY) : null;
            $toTS   = $dToY   ? strtotime($dToY)   : null;
            $list = array_values(array_filter($list, function ($p) use ($fromTS, $toTS) {
                $raw = $p['deadline'] ?? null;
                if (!$raw) return false;
                $ts = strtotime($raw);
                if ($fromTS && $ts < $fromTS) return false;
                if ($toTS   && $ts > $toTS)   return false;
                return true;
            }));
        }

        // 4) Search filter (optional)
        // if ($search !== '') {
        //     $needle = mb_strtolower($search);
        //     $list = array_values(array_filter($list, function ($p) use ($needle) {
        //         return str_contains(mb_strtolower($p['productName'] ?? ''), $needle)
        //             || str_contains(mb_strtolower($p['order_number'] ?? ''), $needle)
        //             || str_contains((string)$p['ProductID'], $needle);
        //     }));
        // }

        // ----- UNIFIED SORT (primary accepted/progress, secondary proximity by deadline/date_in) -----
        $sortBy   = $request->query('sort_by', '');       // 'deadline' | 'date_in' | ''
        $sortMode = $request->query('sort_mode', 'near'); // 'near' | 'far'

        $today = new \DateTimeImmutable('today');
        $parseDate = static function ($raw): ?\DateTimeImmutable {
            if (!$raw) return null;
            try { return new \DateTimeImmutable($raw); } catch (\Throwable $e) { return null; }
        };
        $proximity = static function (array $row, string $key) use ($today, $parseDate): int {
            $d = $parseDate($row[$key] ?? null);
            return $d ? abs($d->getTimestamp() - $today->getTimestamp()) : PHP_INT_MAX;
        };

        usort($list, function ($a, $b) use ($sort, $sortBy, $sortMode, $proximity) {
            // 1) PRIMARY: accepted_* or progress
            switch ($sort) {
                case 'accepted_first':
                    // accepted=1 first
                    $cmp = ($b['accepted'] ?? 0) <=> ($a['accepted'] ?? 0);
                    break;
                case 'accepted_last':
                    // accepted=1 last
                    $cmp = ($a['accepted'] ?? 0) <=> ($b['accepted'] ?? 0);
                    break;
                default:
                    // least progress first
                    $cmp = ($a['progress'] ?? 0) <=> ($b['progress'] ?? 0);
                    break;
            }
            if ($cmp !== 0) return $cmp;

            // 2) SECONDARY: proximity to today by deadline/date_in (optional)
            if ($sortBy === 'deadline' || $sortBy === 'date_in') {
                $key = $sortBy === 'deadline' ? 'deadline' : 'orderDate';
                $da  = $proximity($a, $key);
                $db  = $proximity($b, $key);
                $cmp = $da <=> $db;                         // smaller = nearer
                if ($cmp !== 0) return $sortMode === 'far' ? -$cmp : $cmp;
            }

            // 3) TERTIARY: recent first by "installation done" or orderDate
            $au = $a['stages']['installation']['done'] ?? $a['orderDate'] ?? null;
            $bu = $b['stages']['installation']['done'] ?? $b['orderDate'] ?? null;
            if ($au !== $bu) return strcmp((string)$bu, (string)$au); // newer first

            // 4) TIE-BREAKERS
            $cmp = ((int)($a['OrderID']   ?? 0)) <=> ((int)($b['OrderID']   ?? 0));
            if ($cmp !== 0) return $cmp;
            return ((int)($a['ProductID'] ?? 0)) <=> ((int)($b['ProductID'] ?? 0));
        });

        if ($mine) {
            $role = strtolower(Auth::user()->role ?? '');

            // Match your roles to the production stage name used in the table
            $stageForRole = match ($role) {
                'operations-printing'             => 'printing',
                'operations-furnishing'           => 'furnishing',
                'operations-dispatch-control'     => 'delivery',      // dispatch control column in UI
                'operations-delivery-installation'=> 'installation',  // delivery & installation column in UI
                default => null,
            };

            if ($stageForRole) {
                // Keep only the products currently in *my* stage
                $list = array_values(array_filter($list, function ($row) use ($stageForRole) {
                    return ($row['current_stage'] ?? null) === $stageForRole
                        // Some teams want to see the whole family of their stage;
                        // if you prefer strictly current stage only, keep just the line above.
                        || in_array($stageForRole, $row['stages'] ?? [], true);
                }));
            }
        }

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

        return view('dispatchcontrol.dashboard', [
            'statuses'   => $statuses,
            'search'     => $search,
            'status'     => $status,
            'inProgress' => $inProgress,
            'completed'  => $completed,
            'rows'       => $rowsPaginated,
            'pid'           => $pid,
            'sort'          => $sort,
            'q'             => $q,
            'artist'        => $artist,
            'deadline_from' => $dFromY,
            'deadline_to'   => $dToY,
            'artists'       => $artists,
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
                ->where('stage', 'delivery')
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

        // ===== Notifications (after commit) =====
        $actor     = Auth::user();
        $actorName = $actor?->name ?? 'System';
        $actorRole = str_replace('-', ' ', $actor?->role ?? 'user');

        $productId   = (int) $p->ProductID;
        $productName = (string) $p->productName;
        $orderNo     = (string) $o->order_number;
        $orderId     = (int) $o->id;

        $message = "Dispatch Control completed for Product {$productName} by {$actorName} ({$actorRole}), product completed.";

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

        return back()->with('ok', 'Dispatch Control marked completed with photo proof.');
    }

    public function index(Request $request)
    {
        // ---------- FILTER INPUTS ----------
        $pid       = trim((string)$request->query('pid', ''));              // Product ID/code (all pages)
        $q         = trim((string)$request->query('q', ''));                // keyword: order title/company/product/remarks
        $artist    = trim((string)$request->query('artist', ''));           // orders.artist_id
        $taskType  = trim((string)$request->query('task_type', $request->query('type',''))); // keep old 'type'
        $status    = trim((string)$request->query('status', ''));

        // Nearest/furthest sort
        $sortBy    = trim((string)$request->query('sort_by', ''));          // 'deadline' | 'delivery_date' | ''
        $sortMode  = trim((string)$request->query('sort_mode', 'near'));    // 'near' | 'far'

        // ---------- OVERVIEW TILES (unchanged) ----------
        $stats = [
            'printing' => DB::table('products as p')
                ->join('orders as o', 'o.id', '=', 'p.OrderID')
                ->whereRaw('LOWER(p.taskType) = "printing"')
                ->where(function ($w) {
                    $w->whereNull('o.status')->orWhere('o.status', 0);
                })
                ->count(),

            'furnishing' => DB::table('products as p')
                ->join('orders as o', 'o.id', '=', 'p.OrderID')
                ->whereRaw('LOWER(p.taskType) = "furnishing"')
                ->where(function ($w) {
                    $w->whereNull('o.status')->orWhere('o.status', 0);
                })
                ->count(),

            'installation' => DB::table('products as p')
                ->join('orders as o', 'o.id', '=', 'p.OrderID')
                ->whereRaw('LOWER(p.taskType) = "installation"')
                ->orWhere('p.installation_task_type', 1)
                ->where(function ($w) {
                    $w->whereNull('o.status')->orWhere('o.status', 0);
                })
                ->count(),

            'self_pickup' => DB::table('delivery_breakdowns as d')
                ->join('products as p', 'p.ProductID', '=', 'd.ProductID')
                ->join('orders as o', 'o.id', '=', 'p.OrderID')
                ->where(function ($q) {
                    $q->whereRaw('LOWER(d.method) = "self pickup"')
                    ->orWhereRaw('LOWER(d.method) = "self_pickup"');
                })
                ->where(function ($w) {
                    $w->whereNull('o.status')->orWhere('o.status', 0);
                })
                ->count(),

            'courier' => DB::table('delivery_breakdowns as d')
                ->join('products as p', 'p.ProductID', '=', 'd.ProductID')
                ->join('orders as o', 'o.id', '=', 'p.OrderID')
                ->whereRaw('LOWER(d.method) = "courier"')
                ->where(function ($w) {
                    $w->whereNull('o.status')->orWhere('o.status', 0);
                })
                ->count(),
        ];

        // ---------- ARTIST DROPDOWN ----------
        $artists = DB::table('users')
            ->select('id','name')
            ->whereIn(DB::raw('LOWER(role)'), ['artist','head-artist'])
            ->orderBy('name')
            ->get();

        // ---------- TABLE QUERY ----------
        $orders = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('delivery_breakdowns as db', 'db.ProductID', '=', 'p.ProductID')

            // EXCLUDE archived/rejected orders (status = 1)
            ->where(function ($w) {
                $w->whereNull('o.status')->orWhere('o.status', 0);
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
            // EXCLUDE products with NULL/blank taskType
            ->whereNotNull('p.taskType')
            ->whereRaw("TRIM(p.taskType) <> ''")

            ->selectRaw("
                p.ProductID,
                p.productName                                     as product_name,
                p.taskType                                        as task_type,
                p.status                                          as status,

                DATE_FORMAT(COALESCE(o.deadline, p.updated_at), '%Y-%m-%d') as deadline,
                DATE_FORMAT(db.date, '%Y-%m-%d')                  as delivery_date,
                db.location                                       as delivery_location,
                p.installation_task_type,
                db.method                                         as delivery_method, 
                db.deliver_install_type                           as deliver_install_type,
                o.id                                              as order_id,
                o.orderTitle,
                o.companyName,
                o.artist_id,

                -- Code: #ORD-YYYY-<baseOrderId>-P<baseProductId>[R]
                CONCAT(
                    '#ORD-',
                    LPAD(COALESCE(YEAR(o.orderDate), YEAR(o.created_at), YEAR(p.updated_at)), 4, '0'),
                    '-',
                    LPAD(COALESCE(o.redo, o.id, 0), 3, '0'),
                    '-P',
                    LPAD(COALESCE(p.redoOf, p.ProductID), 4, '0'),
                    CASE WHEN p.redoOf IS NOT NULL AND COALESCE(p.editable,0) = 1 THEN 'R' ELSE '' END
                ) as product_code
            ")

            // PRODUCT ID / CODE search (works across all pages)
            ->when($pid !== '', function ($qb) use ($pid) {
                $like = "%{$pid}%";
                $qb->where(function ($w) use ($pid, $like) {
                    // numeric convenience
                    if (ctype_digit($pid)) {
                        $w->where('p.ProductID', (int)$pid)
                        ->orWhere('o.id', (int)$pid);
                    } else {
                        $w->where('p.ProductID', 'like', $like)
                        ->orWhere('o.id', 'like', $like);
                    }

                    // also match the formatted product code with base ids + optional R
                    $w->orWhereRaw("
                        CONCAT(
                            '#ORD-',
                            LPAD(COALESCE(YEAR(o.orderDate), YEAR(o.created_at), YEAR(p.updated_at)), 4, '0'),
                            '-',
                            LPAD(COALESCE(o.redo, o.id, 0), 3, '0'),
                            '-P',
                            LPAD(COALESCE(p.redoOf, p.ProductID), 4, '0'),
                            CASE WHEN p.redoOf IS NOT NULL AND COALESCE(p.editable,0) = 1 THEN 'R' ELSE '' END
                        ) LIKE ?
                    ", [$like]);
                });
            })

            // KEYWORD: order title / company name / product name / delivery location
            ->when($q !== '', function ($qb) use ($q) {
                $like = "%{$q}%";
                $qb->where(function ($w) use ($like) {
                    $w->where('o.orderTitle',   'like', $like)
                    ->orWhere('o.companyName','like', $like)
                    ->orWhere('p.productName','like', $like)
                    ->orWhere('db.location',  'like', $like);
                });
            })

            // ARTIST filter
            ->when($artist !== '', fn($qb) => $qb->where('o.artist_id', $artist))

            // TASK TYPE & STATUS filter
            ->when($taskType !== '', function ($qb) use ($taskType) {
                $t = strtolower($taskType);

                // Treat "installation" / "delivery & installation"
                if (in_array($t, ['installation', 'delivery_installation'], true)) {
                    $qb->where(function ($w) {
                        $w->whereRaw('LOWER(p.taskType) = "installation"')
                        ->orWhere(function ($q) {
                            $q->whereRaw('LOWER(p.taskType) = "delivery"')
                                ->where('p.installation_task_type', 1)
                                ->whereRaw("LOWER(db.method) = 'delivery_installation'");
                        });
                    });
                    return;
                }

                // Treat "dispatch control" / pure delivery
                if (in_array($t, ['delivery', 'dispatch_control'], true)) {
                    $qb->where(function ($w) {
                        $w->whereRaw('LOWER(p.taskType) = "delivery"')
                        ->where(function ($q) {
                            $q->whereNull('p.installation_task_type')
                                ->orWhere('p.installation_task_type', '!=', 1)
                                ->orWhereRaw("LOWER(db.method) <> 'delivery_installation'");
                        });
                    });
                    return;
                }

                // Default: printing, furnishing, etc.
                $qb->whereRaw('LOWER(p.taskType) = ?', [$t]);
            })
            ->when($status   !== '', fn($qb) => $qb->where('p.status', $status));

        // SORTING: nearest/furthest by date, or default latest updated
        if (in_array($sortBy, ['deadline', 'delivery_date'], true)) {
            $colSql = $sortBy === 'deadline'
                ? 'COALESCE(o.deadline, p.updated_at)'
                : 'db.date';

            $dir = ($sortMode === 'far') ? 'DESC' : 'ASC';

            $orders->orderByRaw("CASE WHEN {$colSql} IS NULL THEN 1 ELSE 0 END ASC")
                ->orderByRaw("ABS(DATEDIFF({$colSql}, CURDATE())) {$dir}");
        } else {
            $orders->orderByDesc('p.updated_at');
        }

        // PAGINATION
        $orders = $orders->paginate(10)->appends($request->query());

        $orders->getCollection()->transform(function ($r) {
            $r->details_url = route('dispatchcontrol.job.show', $r->ProductID);
            return $r;
        });

        return view('dispatchcontrol.job-order', [
            'stats'      => $stats,
            'orders'     => $orders,
            'q'          => $q,
            'pid'        => $pid,
            'artist'     => $artist,
            'task_type'  => $taskType,
            'status'     => $status,
            'artists'    => $artists,
            'sort_by'    => $sortBy,
            'sort_mode'  => $sortMode,
        ]);
    }


    private function countByType(string $type): int
    {
        return (int) DB::table('products')
            ->where(DB::raw('LOWER(taskType)'), strtolower($type))
            ->count();
    }

    private function mapTaskLabel(?string $raw): string
    {
        return match (strtolower((string)$raw)) {
            'delivery'     => 'Dispatch Control',
            'installation' => 'Delivery & Installation',
            default        => \Illuminate\Support\Str::title((string)$raw),
        };
    }

    public function completePackaging(Request $request, int $product)
    {
        $updated = DB::table('products')
            ->where('ProductID', $product)
            ->where(function ($q) {
                $q->whereNull('packaging')->orWhere('packaging', 0);
            })
            ->update([
                'packaging'  => 1,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            // already completed OR product not found
            return response()->json([
                'ok' => false,
                'message' => 'Packaging already completed or product not found.',
            ], 422);
        }

        return response()->json(['ok' => true]);
    }

}
