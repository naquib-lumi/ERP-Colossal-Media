<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class InstallationCalendarController extends Controller
{
    public function index()
    {
        return view('installation.calendar');
    }

    public function events(Request $request)
    {
        // 1) normalize the range from FullCalendar
        $startDate = \Carbon\Carbon::parse(
            $request->query('start', now()->startOfMonth())
        )->startOfDay();
        $endDate = \Carbon\Carbon::parse(
            $request->query('end', now()->endOfMonth())
        )->endOfDay();

        // 2) subquery: pick the earliest delivery_breakdowns row per product
        //    (so we can also carry its location & quantity)
        $deliverySub = DB::table('delivery_breakdowns as d')
            ->select([
                'd.ProductID',
                'd.BreakdownID      as breakdown_id',
                'd.date       as delivery_date',
                'd.time       as delivery_time',
                'd.method',
                'd.deliver_install_type',
                'd.location   as delivery_location',
                'd.quantity   as delivery_qty',
                'd.outsource_cost as outsource_cost',
            ])
            ->selectRaw(
                'ROW_NUMBER() OVER (' .
                'PARTITION BY d.ProductID ' .
                'ORDER BY d.date ASC, COALESCE(d.time, "00:00:00") ASC' .
                ') as rn'
            );

        // 3) main query
        $rows = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('products as op', 'op.ProductID', '=', 'p.redoOf')
            ->leftJoin('orders as oo', 'oo.id', '=', 'op.OrderID')
            ->leftJoin('users as u', 'u.id', '=', 'o.artist_id')
            ->leftJoinSub($deliverySub, 'd', function ($join) {
                $join->on('d.ProductID', '=', 'p.ProductID');
            })

            ->select([
                'p.ProductID',
                'p.OrderID',
                'p.productName',
                'p.taskType',
                'p.status',
                'p.updated_at',
                'p.redoOf',
                'p.editable',
                'p.totalQuantity',                    // ⬅️ total product qty
                'o.order_number',
                'o.companyName as company_name',
                'o.orderTitle as order_title',
                'o.deadline as order_deadline',
                'oo.order_number as original_order_number',
                'u.name as artist_name',
                'd.delivery_date',
                'd.delivery_time',
                'd.method',
                'd.deliver_install_type',
                'd.delivery_location',                // ⬅️ from earliest breakdown row
                'd.delivery_qty',                     // ⬅️ from earliest breakdown row
                'd.outsource_cost',
                'o.leadName as lead_name',
                'o.lead_id   as lead_id',

                DB::raw('(
                    SELECT pp.permit_file
                    FROM product_permit pp
                    WHERE pp.product_id = p.ProductID
                    ORDER BY pp.uploaded_at DESC
                    LIMIT 1
                ) as permit_attachment'),
            ])
            // 🔴 installation only
            ->whereRaw("LOWER(TRIM(p.taskType)) = 'installation'")

            // 🔴 skip archived/hidden orders
            ->where(function ($q) {
                $q->whereNull('o.status')->orWhere('o.status', 0);
            })

            // 🔴 skip empty/null taskType
            ->whereNotNull('p.taskType')

            // date range filter
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween(DB::raw('DATE(p.updated_at)'), [
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                ])->orWhereBetween(DB::raw('DATE(d.delivery_date)'), [
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                ]);
            })
            ->orderByRaw('COALESCE(d.delivery_date, p.updated_at) ASC')
            ->get();

        // 4) build FC events
        $events = $rows->map(function ($r) {
            // 🔴 1) pick which order number to show
            $orderCode = $r->original_order_number
                ?: $r->order_number
                ?: ('ORD-' . str_pad((string)($r->OrderID ?? 0), 3, '0', STR_PAD_LEFT));

            // 🔴 2) pick which product id to show (redo handling)
            $baseProductId = $r->redoOf ?: $r->ProductID;
            $suffix = ($r->redoOf && (int)($r->editable ?? 0) === 1) ? 'R' : '';
            $productCode = sprintf('%s-P%04d%s', $orderCode, (int) $baseProductId, $suffix);

            // normalize status to match JS filter === 'in_progress'
            $rawStatus  = trim((string)($r->status ?? 'in_progress'));
            $normStatus = strtolower(str_replace([' ', '-'], '_', $rawStatus));

            $methodRaw = strtolower(trim((string)($r->method ?? $r->deliver_install_type ?? '')));
            switch ($methodRaw) {
                case 'delivery_installation':
                case 'installation':
                    $method = 'Delivery & Installation';
                    break;
                case 'self_pickup':
                case 'self-pickup':
                    $method = 'Self Pickup';
                    break;
                default:
                    $method = $r->method ?? $r->deliver_install_type ?? '—';
            }

            // colors
            $palette = [
                'completed'   => ['#22c55e', '#22c55e', '#ffffff'],
                'rejected'    => ['#ef4444', '#ef4444', '#ffffff'],
                'in_progress' => ['#3b82f6', '#3b82f6', '#ffffff'],
            ];
            [$bg, $border, $text] = $palette[$normStatus] ?? ['#3b82f6', '#3b82f6', '#ffffff'];

            // pick a start date: delivery_date > deadline > updated_at
            if ($r->delivery_date) {
                $dt = $r->delivery_date . ($r->delivery_time ? (' ' . $r->delivery_time) : ' 00:00:00');
                $startCarbon = \Carbon\Carbon::parse($dt);
            } elseif ($r->order_deadline) {
                $startCarbon = \Carbon\Carbon::parse($r->order_deadline)->startOfDay();
            } else {
                $startCarbon = \Carbon\Carbon::parse($r->updated_at);
            }

            return [
                'id'              => $r->ProductID, // keep real product id for link
                'title'           => $productCode,  // what user sees in calendar
                'start'           => $startCarbon->toIso8601String(),
                'allDay'          => true,
                'backgroundColor' => $bg,
                'borderColor'     => $border,
                'textColor'       => $text,
                'extendedProps'   => [
                    'type'                  => 'installation',
                    'status'                => $normStatus,
                    'product_name'          => $r->productName,
                    'product_code'          => $productCode,
                    'company_name'          => $r->company_name,
                    'artist_name'           => $r->artist_name,
                    'artist_id'             => null, // add if you join it
                    'product_id'            => $r->ProductID,
                    'order_title'           => $r->order_title,
                    'deadline'              => $r->order_deadline,
                    'delivery_date'         => $r->delivery_date,
                    'delivery_time'         => $r->delivery_time,
                    'method'                => $method,
                    'deliver_install_type'  => $r->deliver_install_type,

                    'lead_name'            => $r->lead_name,
                    'lead_id'              => $r->lead_id,
                    'lead_text'            => ($r->company_name && $r->lead_name)
                                                ? ($r->company_name . ' - ' . $r->lead_name)
                                                : ($r->company_name ?: $r->lead_name),

                    // 🔹 NEW for your modal
                    'delivery_location'     => $r->delivery_location,
                    'product_qty'           => $r->delivery_qty,     // this task’s qty
                    'product_qty_total'     => $r->totalQuantity,     // product total

                    // 🔹 permit (frontend will read this)
                    'permit_attachment'    => $r->permit_attachment,
                    'outsource_cost'       => $r->outsource_cost,

                    // extras
                    'redo_of'               => $r->redoOf,
                    'editable'              => (int)($r->editable ?? 0),
                ],
            ];
        })->values();

        return response()->json($events);
    }

    public function installationDownloadPermit(int $productId)
    {
        // Get the latest permit for this product (or just first if you don't track uploaded_at)
        $row = DB::table('product_permit')
            ->where('product_id', $productId)
            ->orderByDesc('uploaded_at')   // remove this line if you don't have uploaded_at
            ->first();

        if (!$row || empty($row->permit_file)) {
            abort(404, 'Permit not found.');
        }

        // Stored as "path|originalName"
        [$path, $originalName] = array_pad(
            explode('|', $row->permit_file, 2),
            2,
            null
        );

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'Permit file missing.');
        }

        return Storage::disk('public')->download(
            $path,
            $originalName ?: basename($path)
        );
    }

}
