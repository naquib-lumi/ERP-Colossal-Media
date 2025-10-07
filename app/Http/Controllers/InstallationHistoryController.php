<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class InstallationHistoryController extends Controller
{
    public function index(Request $request)
    {
        $q     = trim($request->get('q', ''));
        $start = trim($request->get('start', ''));  // mm/dd/yyyy
        $end   = trim($request->get('end', ''));    // mm/dd/yyyy
        $perPage = 8;

        $query = DB::table('fulfillment_progress as fp')
            ->join('products as p', 'p.ProductID', '=', 'fp.ProductID')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->whereRaw('LOWER(fp.stage) = ?', ['installation'])
            ->whereRaw('LOWER(fp.status) = ?', ['completed'])
            ->select([
                'p.ProductID',
                'p.productName as product_name',
                'p.materialRemark as remarks',
                'o.id as order_id',
                'o.order_number',
                'fp.completedAt as completed_date',   // ✅ Completed Date comes from progress table
            ]);

        if ($q !== '') {
            $qLower = mb_strtolower($q);
            $query->where(function ($w) use ($q, $qLower) {
                // product name / remarks
                $w->where('p.productName', 'like', "%{$q}%")
                ->orWhere('p.materialRemark', 'like', "%{$q}%")
                // completed date text search (yyyy-mm-dd or part of it)
                ->orWhereRaw('DATE(fp.completedAt) LIKE ?', ["%{$qLower}%"])
                // order number too (often useful)
                ->orWhere('o.order_number', 'like', "%{$q}%");
            });
        }

        // Date range (Completed Date = fp.completedAt)
        if ($start !== '') {
            try {
                $startDate = Carbon::createFromFormat('m/d/Y', $start)->startOfDay();
                $query->where('fp.completedAt', '>=', $startDate);
            } catch (\Throwable $e) {}
        }
        if ($end !== '') {
            try {
                $endDate = Carbon::createFromFormat('m/d/Y', $end)->endOfDay();
                $query->where('fp.completedAt', '<=', $endDate);
            } catch (\Throwable $e) {}
        }

        $orders = $query
            ->orderByDesc('fp.completedAt')
            ->paginate($perPage)
            ->appends($request->query());

        return view('installation.history', compact('orders', 'q', 'start', 'end'));
    }

    public function show($productId)
    {
        // 1) Header for the selected product + its order
        $header = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('users as ua', 'ua.id', '=', 'o.artist_id')
            ->leftJoin('users as uh', 'uh.id', '=', 'o.salesperson_id')
            ->where('p.ProductID', $productId)
            ->select([
                'p.ProductID',
                'p.OrderID as order_id',
                'p.productName',
                'p.materialRemark',
                'o.order_number',
                'o.companyName',
                'o.artist_id',
                'o.salesperson_id',
                'o.orderTitle',
                'o.created_at as order_created_at',
                'o.deadline',
                'o.approval',
                'p.taskType',
                'ua.name as artist_name',
                'uh.name as head_artist_name',
            ])
            ->first();

        abort_unless($header, 404);

        // 2) Order code (for the page title)
        $orderCode = $header->order_number ?: ('ORD-' . $header->order_id);

        // 3) Product remarks for THIS product (with operation label)
        $remarks = DB::table('product_remarks')
            ->where('ProductID', $productId)
            ->orderBy('created_at')
            ->get(['operation', 'remark']);

        // 4) All installation products for the SAME order (only taskType='installation')
        $products = DB::table('products')
            ->where('OrderID', $header->order_id)
            ->whereRaw('LOWER(taskType) = ?', ['installation'])
            ->orderBy('ProductID')
            ->select(['ProductID', 'productName', 'materialRemark'])
            ->get();

        $productIds = $products->pluck('ProductID')->all();
        // If the current product wasn’t installation, still include it if user clicked it
        if (!in_array($productId, $productIds, true) && strtolower($header->taskType ?? '') === 'installation') {
            $productIds[] = $productId;
        }

        // 5) Items + specs for all those products (grouped)
        $items = collect();
        if (!empty($productIds)) {
            $items = DB::table('product_items as i')
                ->leftJoin('specifications as s', 's.ItemID', '=', 'i.ItemID')
                ->whereIn('i.ProductID', $productIds)
                ->orderBy('i.ProductID')->orderBy('i.ItemID')
                ->select([
                    'i.ProductID',
                    'i.ItemID',
                    'i.itemName',
                    'i.quantity',
                    'i.sizeWidth',
                    'i.sizeHeight',
                    'i.sizeUnit',
                    'i.bleedTop',
                    'i.bleedBottom',
                    'i.bleedLeft',
                    'i.bleedRight',
                    'i.bleedUnit',
                    'i.material',
                    'i.prime_centre',
                    'i.finishing as assemble', // assemble flag if you use it here
                    's.printer',
                    's.cutter',
                    's.lamination',
                ])
                ->get()
                ->groupBy('ProductID');
        }

        // 6) Proof (optional)
        $proofUrl = null;
        try {
            $orderRow = DB::table('orders')->where('id', $header->order_id)->select('orderAttachment')->first();
            if ($orderRow && $orderRow->orderAttachment) {
                $proofUrl = Storage::url($orderRow->orderAttachment);
            }
        } catch (\Throwable $e) {
        }

        // ---- Delivery breakdowns ------------------------------------------------
    $rawBreaks = DB::table('delivery_breakdowns')
        ->where('ProductID', $productId)
        ->orderBy('date')
        ->orderBy('time')
        ->get();

    // helper to normalize the 'method' -> one of: courier | pickup | delivery | delivery_installation | installation
    $normalizeMethod = function (?string $m): string {
        $m = strtolower(trim((string)$m));
        if ($m === '' || $m === 'null') return 'delivery';
        if (Str::contains($m, 'courier')) return 'courier';
        if (in_array($m, ['self pickup','self_pickup','pickup'])) return 'pickup';
        if ($m === 'delivery_installation' || Str::contains($m, 'delivery and installation')) return 'delivery_installation';
        if ($m === 'installation') return 'installation';
        return 'delivery';
    };

    $iconFor = function (string $m): string {
        return match ($m) {
            'courier'               => 'bi-box-seam',
            'pickup'                => 'bi-person-check',
            'delivery_installation',
            'installation',
            'delivery'              => 'bi-truck',
            default                 => 'bi-geo-alt',
        };
    };

    $deliveries = [];
    foreach ($rawBreaks as $i => $b) {
        $method = $normalizeMethod($b->method ?? '');
        $date   = $b->date ? Carbon::parse($b->date) : null;
        $time   = $b->time ? Carbon::parse($b->time) : null;

        $datetime = '—';
        if ($date && $time) {
            $datetime = $date->format('Y-m-d') . ' ' . $time->format('H:i');
        } elseif ($date) {
            $datetime = $date->format('Y-m-d');
        }

        // presentable install value if any
        $installTxt = null;
        if (!empty($b->deliver_install_type)) {
            $installTxt = match (strtolower($b->deliver_install_type)) {
                'outsource' => 'Outsource',
                'both'      => 'Both',
                'inhouse', 'in-house' => 'In-house',
                default     => ucfirst((string)$b->deliver_install_type),
            };
        }

        $deliveries[] = [
            'title'     => 'Delivery #' . ($i + 1),
            'icon'      => $iconFor($method),
            'method'    => $method,                         // used for the badge style in your blade
            'quantity'  => (int)($b->quantity ?? 0),
            'delivered' => (int)($b->quantity ?? 0),        // if you later track actual delivered, replace here
            'address'   => $b->location ?: '—',
            'datetime'  => $datetime,
            'install'   => $installTxt,                     // only shown if not null
            'cost'      => isset($b->outsource_cost) ? ('RM' . number_format((float)$b->outsource_cost, 2)) : null,
        ];
    }

        return view('installation.job_order_show', [
            'header'      => $header,
            'orderCode'   => $orderCode,
            'remarks'     => $remarks,
            'products'    => $products,
            'itemsByProd' => $items,    // Collection keyed by ProductID
            'proofUrl'    => $proofUrl,
            'deliveries' => $deliveries,
        ]);
    }
}
