<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        /*
         * FullCalendar sends:
         * start = first visible calendar date
         * end   = next date after the visible range
         */
        $startDate = Carbon::parse(
            $request->query('start', now()->startOfMonth())
        )->startOfDay();

        $endDate = Carbon::parse(
            $request->query('end', now()->endOfMonth())
        )->endOfDay();

        /*
         * IMPORTANT:
         *
         * Start from delivery_breakdowns table.
         *
         * Calendar display condition:
         * - delivery_breakdowns.method = delivery
         * - delivery_breakdowns.method = installation
         *
         * Do NOT filter by:
         * - products.taskType
         * - products.installation_task_type
         * - products.status
         *
         * products.status is only used for color/display.
         */
        $rows = DB::table('delivery_breakdowns as d')
            ->leftJoin('products as p', 'p.ProductID', '=', 'd.ProductID')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('products as op', 'op.ProductID', '=', 'p.redoOf')
            ->leftJoin('orders as oo', 'oo.id', '=', 'op.OrderID')
            ->leftJoin('users as u', 'u.id', '=', 'o.artist_id')
            ->select([
                'd.BreakdownID',
                'd.ProductID',
                'd.method',
                'd.deliver_install_type',
                'd.outsource_cost',
                'd.quantity as delivery_qty',
                'd.date as delivery_date',
                'd.time as delivery_time',
                'd.location as delivery_location',
                'd.created_at as breakdown_created_at',
                'd.updated_at as breakdown_updated_at',

                'p.OrderID',
                'p.productName',
                'p.taskType',
                'p.installation_task_type',
                'p.installation_status',
                'p.status',
                'p.updated_at as product_updated_at',
                'p.redoOf',
                'p.editable',
                'p.totalQuantity',

                'o.order_number',
                'o.companyName as company_name',
                'o.orderTitle as order_title',
                'o.deadline as order_deadline',

                'oo.order_number as original_order_number',

                'u.id as artist_id',
                'u.name as artist_name',

                'o.leadName as lead_name',
                'o.lead_id as lead_id',

                DB::raw('(
                    SELECT pp.permit_file
                    FROM product_permit pp
                    WHERE pp.product_id = d.ProductID
                    ORDER BY pp.uploaded_at DESC
                    LIMIT 1
                ) as permit_attachment'),
            ])

            /*
             * Only display delivery_breakdowns with these methods.
             * products.taskType can be NULL, empty, installation, delivery, or anything.
             * It does not affect the calendar display.
             */
            ->whereIn(DB::raw('LOWER(TRIM(d.method))'), [
                'delivery',
                'installation',
            ])

            /*
             * Calendar range condition:
             * Use delivery_breakdowns.date only.
             */
            ->whereNotNull('d.date')
            ->whereBetween(DB::raw('DATE(d.date)'), [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])

            ->orderBy('d.date', 'ASC')
            ->orderByRaw('COALESCE(d.time, "00:00:00") ASC')
            ->orderBy('d.BreakdownID', 'ASC')
            ->get();

        $events = $rows->map(function ($r) {
            /*
             * Pick order number.
             * If this product is redo, show original order number first.
             */
            $orderCode = $r->original_order_number
                ?: $r->order_number
                ?: ('ORD-' . str_pad((string) ($r->OrderID ?? 0), 3, '0', STR_PAD_LEFT));

            /*
             * Product code handling.
             */
            $baseProductId = $r->redoOf ?: $r->ProductID;
            $suffix = ($r->redoOf && (int) ($r->editable ?? 0) === 1) ? 'R' : '';
            $productCode = sprintf('%s-P%04d%s', $orderCode, (int) $baseProductId, $suffix);

            /*
             * Normalize method from delivery_breakdowns.method.
             * This is used for display and JS filtering only.
             * It is NOT used for color.
             */
            $methodRaw = strtolower(trim((string) ($r->method ?? '')));

            switch ($methodRaw) {
                case 'installation':
                    $method = 'Installation';
                    break;

                case 'delivery':
                    $method = 'Delivery';
                    break;

                default:
                    $method = $r->method ?: 'Delivery / Installation';
                    break;
            }

            /*
             * Normalize product status.
             *
             * This controls event color only.
             *
             * Display condition is still delivery_breakdowns.method.
             */
            $rawStatus = trim((string) ($r->status ?? 'in_progress'));

            $normStatus = strtolower(
                str_replace([' ', '-'], '_', $rawStatus)
            );

            if ($normStatus === '' || $normStatus === 'null') {
                $normStatus = 'in_progress';
            }

            /*
             * Color by products.status:
             *
             * in_progress = blue
             * completed   = green
             * rejected    = red
             *
             * The method delivery / installation will NOT affect color.
             */
            $palette = [
                'in_progress' => ['#3b82f6', '#3b82f6', '#ffffff'],
                'progress'    => ['#3b82f6', '#3b82f6', '#ffffff'],
                'pending'     => ['#3b82f6', '#3b82f6', '#ffffff'],
                'scheduled'   => ['#3b82f6', '#3b82f6', '#ffffff'],

                'completed'   => ['#22c55e', '#22c55e', '#ffffff'],
                'complete'    => ['#22c55e', '#22c55e', '#ffffff'],
                'done'        => ['#22c55e', '#22c55e', '#ffffff'],

                'rejected'    => ['#ef4444', '#ef4444', '#ffffff'],
                'reject'      => ['#ef4444', '#ef4444', '#ffffff'],
                'cancelled'   => ['#ef4444', '#ef4444', '#ffffff'],
                'canceled'    => ['#ef4444', '#ef4444', '#ffffff'],
            ];

            [$bg, $border, $text] = $palette[$normStatus] ?? $palette['in_progress'];

            /*
             * Build start datetime from delivery_breakdowns.date + time.
             */
            $deliveryDate = $r->delivery_date
                ? Carbon::parse($r->delivery_date)->toDateString()
                : null;

            if (!$deliveryDate) {
                return null;
            }

            $deliveryTime = $r->delivery_time
                ? Carbon::parse($r->delivery_time)->format('H:i:s')
                : '00:00:00';

            $startCarbon = Carbon::parse($deliveryDate . ' ' . $deliveryTime);

            /*
             * Use BreakdownID as calendar event ID.
             *
             * This is important because one product can have multiple breakdown rows.
             * Example:
             * ProductID 641 has delivery and installation on the same date.
             */
            return [
                'id' => 'breakdown_' . $r->BreakdownID,

                /*
                 * Keep existing calendar title style.
                 */
                'title' => $productCode,

                /*
                 * Use full datetime.
                 */
                'start' => $startCarbon->format('Y-m-d\TH:i:s'),
                'allDay' => false,

                /*
                 * Color comes from products.status only.
                 */
                'backgroundColor' => $bg,
                'borderColor' => $border,
                'textColor' => $text,

                'extendedProps' => [
                    /*
                     * Main identifiers.
                     */
                    'breakdown_id' => $r->BreakdownID,
                    'product_id' => $r->ProductID,
                    'order_id' => $r->OrderID,

                    /*
                     * Product/order display.
                     */
                    'type' => $methodRaw,
                    'status' => $normStatus,
                    'product_name' => $r->productName,
                    'product_code' => $productCode,
                    'company_name' => $r->company_name,
                    'artist_name' => $r->artist_name,
                    'artist_id' => $r->artist_id,
                    'order_title' => $r->order_title,
                    'deadline' => $r->order_deadline,

                    /*
                     * Important fields for updated JS filter.
                     */
                    'method' => $methodRaw,
                    'delivery_breakdown_method' => $methodRaw,
                    'delivery_method' => $methodRaw,
                    'deliver_install_type' => $r->deliver_install_type,
                    'method_label' => $method,

                    /*
                     * Delivery breakdown data.
                     */
                    'delivery_date' => $deliveryDate,
                    'date' => $deliveryDate,
                    'delivery_time' => $deliveryTime,
                    'time' => $deliveryTime,
                    'delivery_location' => $r->delivery_location,
                    'location' => $r->delivery_location,

                    /*
                     * Quantity.
                     */
                    'product_qty' => $r->delivery_qty,
                    'quantity' => $r->delivery_qty,
                    'qty' => $r->delivery_qty,
                    'product_qty_total' => $r->totalQuantity,

                    /*
                     * Lead.
                     */
                    'lead_name' => $r->lead_name,
                    'lead_id' => $r->lead_id,
                    'lead_text' => ($r->company_name && $r->lead_name)
                        ? ($r->company_name . ' - ' . $r->lead_name)
                        : ($r->company_name ?: $r->lead_name),

                    /*
                     * Permit.
                     */
                    'permit_attachment' => $r->permit_attachment,
                    'outsource_cost' => $r->outsource_cost,

                    /*
                     * Keep product fields for reference only.
                     * These are NOT used as calendar filter.
                     */
                    'taskType' => $r->taskType,
                    'installation_task_type' => $r->installation_task_type,
                    'installation_status' => $r->installation_status,

                    /*
                     * Extras.
                     */
                    'redo_of' => $r->redoOf,
                    'editable' => (int) ($r->editable ?? 0),
                ],
            ];
        })
            ->filter()
            ->values();

        return response()->json($events);
    }

    public function installationDownloadPermit(int $productId)
    {
        $row = DB::table('product_permit')
            ->where('product_id', $productId)
            ->orderByDesc('uploaded_at')
            ->first();

        if (!$row || empty($row->permit_file)) {
            abort(404, 'Permit not found.');
        }

        /*
         * Stored as "path|originalName".
         */
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