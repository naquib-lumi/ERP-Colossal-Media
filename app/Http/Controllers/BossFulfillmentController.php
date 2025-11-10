<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadAttachment;
use Carbon\Carbon;
use App\Models\ProductPermit;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BossFulfillmentController extends Controller
{
    /* -------------------- Fulfillment -------------------- */
    public function fulfillment(Request $request)
    {
        // --- pagination
        $perPage = (int) $request->query('per_page', 10);
        $perPage = $perPage > 0 ? $perPage : 10;

        // --- filters from toolbar
        $orderId  = trim((string) $request->query('order_id', ''));
        $artistId = $request->query('artist', '');           // allow '' | '0' | '123'
        $q        = trim((string) $request->query('q', ''));
        $task     = strtolower(trim((string) $request->query('task', '')));
        $status   = strtolower(trim((string) $request->query('status', '')));

        // ---- Date range from "dd/mm/yyyy - dd/mm/yyyy"
        $from = $to = null;
        if ($dr = trim((string) $request->query('date_range', ''))) {
            // tolerate " - " or "~"
            $parts = preg_split('/\s*[-~]\s*/', $dr);
            if (!empty($parts[0])) {
                try { $from = \Carbon\Carbon::createFromFormat('d/m/Y', trim($parts[0]))->format('Y-m-d'); } catch (\Throwable $e) {}
            }
            if (!empty($parts[1])) {
                try { $to = \Carbon\Carbon::createFromFormat('d/m/Y', trim($parts[1]))->format('Y-m-d'); } catch (\Throwable $e) {}
            }
        }

        // ---- latest permit row per product
        $latestPermit = DB::table('product_permit')
            ->select('product_id', DB::raw('MAX(id) as last_id'))
            ->groupBy('product_id');

        $query = DB::table('products as p')
            ->join('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('products as op', 'op.ProductID', '=', 'p.redoOf')
            ->leftJoin('delivery_breakdowns as d', 'd.ProductID', '=', 'p.ProductID')
            ->leftJoinSub($latestPermit, 'pp', 'pp.product_id', '=', 'p.ProductID')
            ->leftJoin('product_permit as pf', 'pf.id', '=', 'pp.last_id')
            ->where(function ($q) { $q->whereNull('o.status')->orWhere('o.status', 0); })
            ->where('o.orderStatus', 'completed')
            ->select([
                'p.ProductID','p.OrderID','p.productName','p.taskType','p.status as product_status',
                'p.redoOf','p.editable',
                'o.id as order_id','o.redo as order_redo','o.orderTitle','o.companyName',
                'o.orderDate','o.created_at as order_created_at',
                'd.BreakdownID as breakdown_id','d.date as delivery_date','d.time as delivery_time',
                'd.location as delivery_location','d.deliver_install_type','d.outsource_cost',
                'pf.permit_file',
            ])
            // rows missing date/location first → then by delivery date/time
            ->orderByRaw('CASE WHEN d.date IS NULL OR d.location IS NULL THEN 0 ELSE 1 END ASC')
            ->orderByRaw('COALESCE(d.date, o.orderDate, o.created_at) ASC')
            ->orderByRaw('COALESCE(d.time, "00:00:00") ASC');

        // (1) Order box: numeric id or text (job title)
        if ($orderId !== '') {
            if (preg_match('/^\d+$/', $orderId)) {
                $query->where(function ($w) use ($orderId) {
                    $w->where('o.id', $orderId)
                    ->orWhere('p.ProductID', 'like', "%{$orderId}%")
                    ->orWhere('p.redoOf',   'like', "%{$orderId}%");
                });
            } else {
                $query->where('o.orderTitle', 'like', "%{$orderId}%");
            }
        }

        // (2) Artist filter (assignee)
        if ($artistId !== '' && $artistId !== null) {
            $query->where('o.artist_id', (int) $artistId);
        }

        // (3) Global search
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('o.orderTitle',  'like', "%{$q}%")
                ->orWhere('o.companyName','like', "%{$q}%")
                ->orWhere('p.productName','like', "%{$q}%");
            });
        }

        // (4) Task filter
        if ($task !== '') {
            $taskNorm = str_replace('&', 'and', $task);
            $taskNorm = preg_replace('/\s+/', '_', $taskNorm);
            if (in_array($taskNorm, ['printing','furnishing'], true)) {
                $query->whereRaw('LOWER(p.taskType) = ?', [$taskNorm]);
            } elseif (in_array($taskNorm, ['delivery','dispatch_control'], true)) {
                $query->whereRaw('LOWER(p.taskType) = ?', ['delivery']);
            } elseif (in_array($taskNorm, ['installation','delivery_installation','delivery_and_installation'], true)) {
                $query->whereRaw('LOWER(p.taskType) = ?', ['installation']);
            }
        }

        // (5) Status filter
        if ($status !== '') {
            $query->whereRaw('LOWER(p.status) = ?', [$status]);
        }

        // --- DELIVERY DATE range (accepts either from/to or date_range)
    $from = trim((string) $request->query('from', ''));
    $to   = trim((string) $request->query('to',   ''));

    // normalize to Y-m-d; input[type=date] already posts Y-m-d
    $normDate = function (?string $v) {
        if (!$v) return null;
        $v = trim($v);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return $v;  // already Y-m-d
        try { return \Carbon\Carbon::parse($v)->format('Y-m-d'); } catch (\Throwable $e) { return null; }
    };

    $from = $normDate($from);
    $to   = $normDate($to);

    // fallback: support old "date_range" field in dd/mm/yyyy - dd/mm/yyyy
    if (!$from && !$to) {
        if ($dr = trim((string) $request->query('date_range', ''))) {
            $parts = preg_split('/\s*[-~]\s*/', $dr);
            if (!empty($parts[0])) {
                try { $from = \Carbon\Carbon::createFromFormat('d/m/Y', trim($parts[0]))->format('Y-m-d'); } catch (\Throwable $e) {}
            }
            if (!empty($parts[1])) {
                try { $to   = \Carbon\Carbon::createFromFormat('d/m/Y', trim($parts[1]))->format('Y-m-d'); } catch (\Throwable $e) {}
            }
        }
    }

        if ($from && $to) {
            $query->whereBetween('d.date', [$from, $to]);
        } elseif ($from) {
            $query->whereDate('d.date', '>=', $from);
        } elseif ($to) {
            $query->whereDate('d.date', '<=', $to);
        }

        // paginate
        $rows = $query->paginate($perPage)->appends($request->query());

        // map display
        $rows->setCollection(
            $rows->getCollection()->map(function ($r) {
                $baseOrderId   = $r->order_redo ?: $r->order_id;
                $baseProductId = $r->redoOf ?: $r->ProductID;
                $rFlag         = ($r->redoOf && (int)$r->editable === 1) ? 'R' : '';

                $year = $r->orderDate
                    ? \Carbon\Carbon::parse($r->orderDate)->format('Y')
                    : \Carbon\Carbon::parse($r->order_created_at)->format('Y');

                $productCode = sprintf('#ORD-%s-%03d-P%04d%s', $year, (int)$baseOrderId, (int)$baseProductId, $rFlag);

                $task = strtolower((string)$r->taskType);
                $taskLabel = match ($task) {
                    'installation' => 'Delivery & Installation',
                    'delivery'     => 'Dispatch Control',
                    default        => $task !== '' ? ucfirst($task) : '-',
                };

                $dt = null;
                if ($r->delivery_date) {
                    $dt = $r->delivery_time
                        ? \Carbon\Carbon::parse($r->delivery_date.' '.$r->delivery_time)->format('Y-m-d H:i')
                        : \Carbon\Carbon::parse($r->delivery_date)->format('Y-m-d');
                }

                $installLabel = match (strtolower((string)$r->deliver_install_type)) {
                    'in_house','in-house' => 'In House',
                    'outsource'           => 'Outsource',
                    'both'                => 'Both',
                    default               => '—',
                };

                // permit_url from either "path" or "path|OriginalName"
                $permitUrl = null;
                if (!empty($r->permit_file)) {
                    $parts = explode('|', $r->permit_file, 2);
                    $storedPath = ltrim($parts[0], '/');               // e.g. "permits/2025/11/file.pdf"
                    // files are saved on "public" disk → served at /storage/...
                    $permitUrl  = asset('storage/'.$storedPath);
                }

                return (object)[
                    'product_code'   => $productCode,
                    'product_id'     => (int)$r->ProductID,
                    'breakdown_id'   => (int)($r->breakdown_id ?? 0),
                    'order_title'    => $r->orderTitle,
                    'company'        => $r->companyName,
                    'task_label'     => $taskLabel,
                    'status'         => (string)($r->product_status ?? ''),
                    'delivery_dt'    => $dt,
                    'delivery_loc'   => (string)($r->delivery_location ?? ''),
                    'install_type'   => $installLabel,
                    'outsource_cost' => is_null($r->outsource_cost) ? null : (float)$r->outsource_cost,
                    'permit_url'     => $permitUrl,
                ];
            })
        );

        // --- populate artist dropdown (all artists & head-artists)
        $assignees = DB::table('users')
            ->whereIn('role', ['artist', 'head-artist'])
            ->orderBy('name')
            ->get(['id','name','role']);

        // optional: distinct statuses for the status dropdown
        $statuses = DB::table('products')
            ->whereNotNull('status')
            ->selectRaw('LOWER(status) as status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->toArray();

        return view('boss.fulfillment', [
            'rows'       => $rows,
            'per_page'   => $perPage,
            'assignees'  => $assignees,
            'statuses'   => $statuses,
            'filters'    => [
                'from' => $from,
                'to'   => $to,
                // ...whatever else you’re already passing
            ],
        ]);
    }

    public function fulfillmentShow(Request $request, int $id)
    {
        $product = Product::findOrFail($id);
        $order   = $product->order()->first();

        // Eager load everything the page needs (same as artist example)
        $product->load([
            'order:id,order_number,orderTitle,companyName,leadName,leadPhone,leadEmail,lead_id,deadline,created_at,artist_id,salesperson_id,orderAttachment',
            'order.artist:id,name',
            'order.salesperson:id,name',

            'items' => fn($q) => $q->select(
                'ItemID','ProductID','itemName','quantity',
                'sizeWidth','sizeUnit','sizeHeight',
                'bleedUnit','bleedTop','bleedBottom','bleedLeft','bleedRight',
                'finishing','material','prime_centre'
            )->with('spec:SpecificationID,ItemID,printer,cutter,lamination'),

            'deliveryBreakdowns:BreakdownID,ProductID,method,location,quantity,date,time,deliver_install_type,outsource_cost',

            // remarks + author
            'remarks' => function ($q) {
                $q->select('RemarkID','ProductID','operation','remark','created_at','user_id')
                ->with('user:id,name');
            },
        ]);

        // ---------- Redo-aware display IDs ----------
        $isRedo     = !is_null($product->redoOf);
        $isEditable = (int)($product->editable ?? 0) === 1; // only the selected redo shows "R"
        $showR      = $isRedo && $isEditable;

        // display ProductID: original for redo
        $pidForDisplay = $isRedo ? (int)$product->redoOf : (int)$product->ProductID;

        // display OrderID: original order for redo (else current)
        $displayOrderId = (int)$product->OrderID;
        if ($isRedo) {
            $origOrderId = Product::where('ProductID', $product->redoOf)->value('OrderID');
            if ($origOrderId) $displayOrderId = (int)$origOrderId;
        }

        // resolve the year from the display order's dates
        $displayOrderYear = now()->format('Y');
        if ($displayOrderId) {
            $ordDates = DB::table('orders')->where('id', $displayOrderId)
                ->select(['orderDate','created_at'])->first();
            $dateForYear = $ordDates->orderDate ?? $ordDates->created_at ?? now();
            $displayOrderYear = \Carbon\Carbon::parse($dateForYear)->format('Y');
        }

        $productCode = sprintf(
            '#ORD-%s-%04d-P%04d%s',
            $displayOrderYear,
            $displayOrderId,
            $pidForDisplay,
            $showR ? 'R' : ''
        );

        // ---------- Lead attachments ----------
        $toPublicUrl = function (string $p): string {
            $p = ltrim($p, '/');
            if (Str::startsWith($p, 'storage/')) {
                return url($p);
            }
            return Storage::disk('public')->url($p);
        };

        $leadAttachments = collect();
        if ($order?->lead_id) {
            $leadAttachments = LeadAttachment::where('lead_id', $order->lead_id)
                ->orderBy('id')
                ->get()
                ->map(function ($row) {
                    $p = $row->file_location;
                    $p = ltrim($p, '/');
                    $p = preg_replace('#^public/#', '', $p);
                    $p = preg_replace('#^storage/#', '', $p);
                    $web = 'storage/' . $p;

                    return (object)[
                        'name' => basename($p),
                        'size' => (int) $row->file_size,
                        'ext'  => $row->file_extension,
                        'url'  => asset($web),
                    ];
                });
        }

        // attachments saved on order
        $raw   = $order->orderAttachment; // string|array|null
        $paths = [];
        if (is_array($raw)) {
            $paths = $raw;
        } elseif (is_string($raw)) {
            $rawTrim = trim($raw);
            if (Str::startsWith($rawTrim, '[')) {
                $paths = json_decode($rawTrim, true) ?: [];
            } else {
                $paths = array_filter(array_map('trim', explode(',', $rawTrim)));
            }
        }

        $orderFiles = collect($paths)->map(function ($p) use ($toPublicUrl) {
            $p   = ltrim($p, '/');
            $url = $toPublicUrl($p);
            return [
                'name' => basename($p),
                'ext'  => pathinfo($p, PATHINFO_EXTENSION),
                'url'  => $url,
            ];
        });

        // Attachments helper fallback
        $attachments = [];
        if (method_exists($this, 'getOrderAttachments')) {
            $attachments = (array) $this->getOrderAttachments($order);
        } else {
            $raw = $order?->orderAttachment;
            if (is_string($raw) && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $attachments = $decoded;
                } else {
                    $attachments = [$raw];
                }
            } elseif (is_array($raw)) {
                $attachments = $raw;
            }
        }

        // ---------- Fulfillment progress (printing, furnishing, delivery, installation) ----------
        $ALL_STAGES = ['printing', 'furnishing', 'delivery', 'installation'];

        $rows = DB::table('fulfillment_progress')
            ->where('ProductID', $product->ProductID)
            ->whereIn('stage', $ALL_STAGES)
            ->get();

        // keep latest row per stage
        $latest = []; // stage => ['status','acceptedAt','completedAt','_rank']
        foreach ($rows as $r) {
            $rank = $r->completedAt ?? $r->acceptedAt ?? $r->created_at;
            $cur  = $latest[$r->stage]['_rank'] ?? null;
            if (!$cur || $rank > $cur) {
                $latest[$r->stage] = [
                    'status'      => strtolower((string)$r->status),
                    'acceptedAt'  => $r->acceptedAt,
                    'completedAt' => $r->completedAt,
                    '_rank'       => $rank,
                ];
            }
        }

        $currentStage  = strtolower((string)$product->taskType);
        $currentStatus = strtolower((string)$product->status);

        $hasDelivery     = isset($latest['delivery']);
        $hasInstallation = isset($latest['installation']);

        $isCompleted = fn($k) => ($latest[$k]['status'] ?? null) === 'completed';

        $laterCompleted = [
            'printing'     => ($isCompleted('furnishing') ?? false)
                            || (($hasDelivery && $isCompleted('delivery')) || ($hasInstallation && $isCompleted('installation'))),
            'furnishing'   => (($hasDelivery && $isCompleted('delivery')) || ($hasInstallation && $isCompleted('installation'))),
            'delivery'     => false,
            'installation' => false,
        ];

        $progress = collect($ALL_STAGES)->mapWithKeys(function ($stage) use (
            $latest, $currentStage, $currentStatus, $hasDelivery, $hasInstallation, $laterCompleted
        ) {
            // mutual exclusion fork
            if ($stage === 'delivery' && $hasInstallation) {
                return [$stage => ['status' => '', 'accepted_at' => null, 'completed_at' => null, 'duration' => null]];
            }
            if ($stage === 'installation' && $hasDelivery) {
                return [$stage => ['status' => '', 'accepted_at' => null, 'completed_at' => null, 'duration' => null]];
            }

            $row = $latest[$stage] ?? null;
            if ($row) {
                $status      = $row['status'];
                $acceptedAt  = $row['acceptedAt'];
                $completedAt = $row['completedAt'];
            } else {
                $status      = $laterCompleted[$stage] ? '' : 'pending';
                $acceptedAt  = null;
                $completedAt = null;
            }

            if ($currentStage === $stage && $currentStatus === 'in_progress' && !in_array($status, ['completed','rejected'], true)) {
                $status = 'in_progress';
            }

            $duration = null;
            if ($acceptedAt && $completedAt) {
                $start = \Carbon\Carbon::parse($acceptedAt);
                $end   = \Carbon\Carbon::parse($completedAt);
                $duration = $start->diffForHumans($end, [
                    'parts'  => 3,
                    'short'  => true,
                    'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
                ]);
            }

            return [$stage => [
                'status'       => $status,
                'accepted_at'  => $acceptedAt,
                'completed_at' => $completedAt,
                'duration'     => $duration,
            ]];
        });

        // Deliveries list (nulls last)
        $deliveries = $product->deliveryBreakdowns()
            ->orderByRaw('CASE WHEN `date` IS NULL THEN 1 ELSE 0 END, `date` ASC, `time` ASC')
            ->get();

        return view('boss.fulfillment.show', [
            'product'         => $product,
            'order'           => $order,
            'items'           => $product->items,
            'attachments'     => $attachments,
            'progress'        => $progress,
            'deliveries'      => $deliveries,
            'leadAttachments' => $leadAttachments,
            'orderFiles'      => $orderFiles,

            'productCode'     => $productCode,
            'displayOrderId'  => $displayOrderId,
        ]);
    }

    public function updateDeliveries(Request $request, int $productId)
    {
        $data = $request->validate([
            'rows'                        => ['required','array'],
            'rows.*.id'                   => ['nullable','integer'],
            'rows.*.method'               => ['nullable','string','max:255'],
            'rows.*.date'                 => ['nullable','date'],
            'rows.*.time'                 => ['nullable','date_format:H:i'],
            'rows.*.location'             => ['nullable','string','max:255'],
            'rows.*.quantity'             => ['nullable','integer','min:0'],
            'rows.*.deliver_install_type' => ['nullable','string','in:in-house,outsource,both'],
            'rows.*.outsource_cost'       => ['nullable','numeric','min:0'],
            'rows.*._delete'              => ['nullable','boolean'],
        ]);

        // product quantity for server-side guard
        $productQty = (int) DB::table('products')->where('ProductID', $productId)->value('totalQuantity');

        // normalize method to DB values
        $canonMethod = function (?string $m): ?string {
            $m = strtolower(trim((string)$m));
            if ($m === '') return null;
            if ($m === 'courier') return 'courier';
            if (in_array($m, ['self pickup','self_pickup','pickup'], true)) return 'self pickup';
            if ($m === 'delivery_installation' || str_contains($m,'install')) return 'delivery_installation';
            return $m;
        };

        // SERVER-SIDE QUANTITY GUARD (exclude deletions)
        $sum = 0;
        foreach ($data['rows'] as $r) {
            if (!empty($r['_delete'])) continue;
            $sum += (int)($r['quantity'] ?? 0);
        }
        if ($sum > $productQty) {
            return back()->withInput()->withErrors([
                'rows' => "Total delivery quantity ({$sum}) cannot exceed product quantity ({$productQty})."
            ]);
        }

        DB::beginTransaction();
        try {
            foreach ($data['rows'] as $row) {
                $id     = $row['id'] ?? null;
                $method = $canonMethod($row['method'] ?? null);

                $type   = strtolower(trim((string)($row['deliver_install_type'] ?? '')));
                if (!in_array($type, ['in-house','outsource','both'], true)) {
                    $type = null;
                }

                $qty    = isset($row['quantity']) ? (int)$row['quantity'] : null;

                $allowCost = ($method === 'delivery_installation') && in_array($type, ['outsource','both'], true);
                $cost      = $allowCost ? ($row['outsource_cost'] ?? null) : null;

                $payload = [
                    'method'               => $method,
                    'date'                 => $row['date'] ?? null,
                    'time'                 => $row['time'] ?? null,
                    'location'             => $row['location'] ?? null,
                    'quantity'             => $qty,
                    'deliver_install_type' => $type,
                    'outsource_cost'       => $cost,
                    'updated_at'           => now(),
                ];

                if (!empty($row['_delete'])) {
                    if ($id) {
                        DB::table('delivery_breakdowns')
                            ->where('BreakdownID', (int)$id)
                            ->where('ProductID', $productId)
                            ->delete();
                    }
                    continue;
                }

                if ($id) {
                    DB::table('delivery_breakdowns')
                        ->where('BreakdownID', (int)$id)
                        ->where('ProductID', $productId)
                        ->update($payload);
                } else {
                    $payload['ProductID'] = $productId;
                    $payload['created_at'] = now();
                    DB::table('delivery_breakdowns')->insert($payload);
                }
            }

            DB::commit();
            return back()->with('ok','Deliveries updated.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->with('error','Failed to update deliveries.');
        }
    }

    public function storePermit(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'permit'     => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,gif,webp', 'max:20480'], // 20MB
        ]);

        $userId    = $request->user()->id ?? null;
        $productId = (int) $data['product_id'];
        $file      = $request->file('permit');

        // store to storage/app/public/permits/YYYY/mm/
        $path = $file->store('permits/' . date('Y/m'), 'public');
        $originalName = $file->getClientOriginalName();

        // store both the path and original filename together (joined by |)
        $pathWithName = $path . '|' . $originalName;

        DB::table('product_permit')->updateOrInsert(
            ['product_id' => $productId],
            [
                'user_id'     => $userId,
                'permit_file' => $pathWithName,  // store full string: path|filename
                'uploaded_at' => now(),
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );

        return back()->with('success', 'Permit uploaded.');
    }

    public function downloadPermit(int $productId)
    {
        $row = DB::table('product_permit')->where('product_id', $productId)->first();

        if (!$row || empty($row->permit_file)) {
            abort(404, 'Permit not found.');
        }

        // Stored as "path|originalName"
        [$path, $originalName] = array_pad(explode('|', $row->permit_file, 2), 2, null);

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'Permit file missing.');
        }

        return Storage::disk('public')->download($path, $originalName ?: basename($path));
    }
}
