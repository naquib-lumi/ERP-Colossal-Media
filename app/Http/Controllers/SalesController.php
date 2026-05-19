<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Order;
use App\Models\JobOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;


class SalesController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

public function dashboard(Request $request)
{
    $user = Auth::user();
    if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
        abort(403, 'Unauthorized');
    }

    $currentYear  = Carbon::now()->year;
    $currentMonth = Carbon::now()->month;
    $selectedSalespersonId = $request->get('salesperson_id');

   
  $salespeople = collect();
if ($user->hasRole('head-salesperson')) {
    $salespeople = User::whereIn('role', ['salesperson', 'head-salesperson'])->get(['id', 'name'])->sortBy('name');
}
  

    // Leads scope
    $leadsQuery = Lead::whereYear('created_at', $currentYear);
    if ($user->hasRole('head-salesperson')) {
        if ($selectedSalespersonId) {
            $leadsQuery->whereHas('user', fn($q) => $q->where('id', $selectedSalespersonId));
        }
    } else {
        $leadsQuery->where('salesperson_id', $user->id);
    }
    $leads = $leadsQuery->get();

    // Meetings scope (unchanged)
    $meetings = $user->meetings()
        ->where('status', 'scheduled')
        ->where('start_time', '>=', Carbon::now())
        ->orderBy('start_time', 'asc')
        ->limit(3)
        ->get() ?? collect();

    $orders = []; // Placeholder

    /**
     * Monthly stats (with Meeting)
     */
    $monthlyData = $leads->groupBy(function ($lead) {
        return Carbon::parse($lead->created_at)->format('M');
    })->map(function ($group) {
        return [
            'accept'   => $group->where('status', 'accept')->count(),
            'reject'   => $group->where('status', 'reject')->count(),
            'followup' => $group->where('status', 'followup')->count(),
            'meeting'  => $group->where('status', 'meeting')->count(),
        ];
    });

    // Fill arrays to current month
    $acceptCounts   = array_fill(0, $currentMonth, 0);
    $rejectCounts   = array_fill(0, $currentMonth, 0);
    $followupCounts = array_fill(0, $currentMonth, 0);
    $meetingCounts  = array_fill(0, $currentMonth, 0);

    foreach ($monthlyData as $month => $counts) {
        $monthIndex = Carbon::parse("$currentYear-$month-01")->month - 1;
        if ($monthIndex < $currentMonth) {
            $acceptCounts[$monthIndex]   = $counts['accept']   ?? 0;
            $rejectCounts[$monthIndex]   = $counts['reject']   ?? 0;
            $followupCounts[$monthIndex] = $counts['followup'] ?? 0;
            $meetingCounts[$monthIndex]  = $counts['meeting']  ?? 0;
        }
    }

    // Totals
    $acceptCount   = $leads->where('status', 'accept')->count();
    $rejectCount   = $leads->where('status', 'reject')->count();
    $followupCount = $leads->where('status', 'followup')->count();
    $meetingCount  = $leads->where('status', 'meeting')->count();

    $monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    return view('sales.dashboard', compact(
        'leads', 'meetings', 'orders',
        'acceptCount', 'rejectCount', 'followupCount', 'meetingCount',
        'acceptCounts', 'rejectCounts', 'followupCounts', 'meetingCounts',
        'currentYear', 'currentMonth', 'monthNames',
        'salespeople', 'selectedSalespersonId'
    ));
}

    public function leadManagement()
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        $leads = $user->leads;
        return view('sales.lead-management', compact('leads'));
    }

    public function addLead()
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        return view('sales.add-lead');
    }

    public function storeLead(Request $request)
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        // ✅ 允许 meeting 作为线索状态
        $request->validate([
            'name'   => 'required',
            'email'  => 'required|email',
            'phone'  => 'required',
            'notes'  => 'nullable',
            'status' => 'required|in:accept,reject,followup,meeting',
        ]);

        Lead::create([
            'user_id' => $user->id,
            'name'    => $request->name,
            'email'   => $request->email,
            'phone'   => $request->phone,
            'notes'   => $request->notes,
            'status'  => $request->status,
        ]);

        return redirect()->route('sales.lead-management')->with('success', 'Lead added successfully');
    }

    public function calendar()
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        $meetings = []; // Implement Meeting model and relationship if needed
        return view('sales.calendar', compact('meetings'));
    }

    public function scheduleMeeting()
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        return view('sales.schedule-meeting');
    }

    public function storeMeeting(Request $request)
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'title'       => 'required',
            'start_time'  => 'required|date',
            'end_time'    => 'required|date|after:start_time',
            'description' => 'nullable',
            'status'      => 'required|in:scheduled,completed,cancelled',
        ]);

        // Meeting::create([...]);

        return redirect()->route('sales.calendar')->with('success', 'Meeting scheduled successfully');
    }

    public function order()
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        $orders = []; // Implement Order model and relationship if needed
        return view('sales.order', compact('orders'));
    }

    public function jobOrderStatus()
    {
        $user = Auth::user();
        if (!($user->hasRole('salesperson') || $user->hasRole('head-salesperson'))) {
            abort(403, 'Unauthorized');
        }

        $orders = []; // Implement Order model and relationship if needed
        $jobOrders = collect(); // Placeholder
        return view('sales.job-order-status', compact('jobOrders'));
    }

    // ===== Profile =====
    public function ProfileShow(Request $request)
    {
        $user = $request->user();
        return view('sales.profile.show', compact('user'));
    }

    public function ProfileUpdate(Request $request)
    {
        $user = $request->user();

    $validated = $request->validate([
        'name'           => ['required','string','max:255'],
        'email'          => ['required','email','max:255'],
        'contact_number' => ['nullable','string','max:30'],

        // Password section (optional)
        // If 'password' is present, 'current_password' must match the logged-in user
        'current_password' => ['nullable','required_with:password','current_password'],
        'password'         => ['nullable', Password::min(8)->mixedCase()->numbers()->symbols(), 'confirmed'],
    ]);

    // Update profile fields
    $user->fill([
        'name'           => $validated['name'],
        'email'          => $validated['email'],
        'contact_number' => $validated['contact_number'] ?? null,
    ]);

    // Update password if provided
    if (!empty($validated['password'])) {
        $user->password = Hash::make($validated['password']);
    }

        $user->save();

        return back()->with('success', 'Profile updated.');
    }

    public function progress(Request $request)
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

        // Completed = fulfillment_progress says installation completed (distinct products)
        $completed = DB::table('fulfillment_progress as fp')
            ->join('products as p', 'p.ProductID', '=', 'fp.ProductID')
            ->join('orders as o', 'o.id', '=', 'p.OrderID')
            ->where(function ($q) {
                $q->whereNull('o.status')->orWhere('o.status', '!=', 1);   // exclude deleted/cancelled orders
            })
            ->where('fp.stage', 'printing')
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

        return view('sales.progress', [
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
}
