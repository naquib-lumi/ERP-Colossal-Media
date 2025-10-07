<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class InstallationHistoryController extends Controller
{
    public function index(Request $request)
    {
        $q     = trim($request->get('q', ''));
        $start = trim($request->get('start', ''));
        $end   = trim($request->get('end', ''));
        $perPage = 8;

        $query = DB::table('products as p')
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin('product_items as pi', 'pi.ProductID', '=', 'p.ProductID')
            ->select([
                'p.ProductID',
                'p.productName as product_name',
                'p.materialRemark as remarks',
                'p.updated_at as completed_date',
                'o.id as order_id',
                'o.order_number',
                'pi.ItemID',
                DB::raw("
                    CASE
                      WHEN o.order_number IS NOT NULL AND o.order_number <> ''
                        THEN CONCAT(o.order_number, '-P', LPAD(p.ProductID, 4, '0'))
                      ELSE CONCAT(o.id, '-P', LPAD(p.ProductID, 4, '0'))
                    END AS product_code
                "),
            ])
            ->where('p.status', 'completed')
            ->whereRaw('LOWER(p.taskType) = ?', ['installation']);

        // optional search box
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('p.productName', 'like', "%{$q}%")
                  ->orWhere('o.order_number', 'like', "%{$q}%")
                  ->orWhere('p.ProductID', 'like', "%{$q}%")
                  ->orWhere('pi.ItemID', 'like', "%{$q}%");
            });
        }

        // date filters by completed_date (products.updated_at)
        $parse = function ($v) {
            $v = trim($v);
            if ($v === '') return null;
            foreach (['m/d/Y','Y-m-d'] as $fmt) {
                try { return Carbon::createFromFormat($fmt, $v); } catch (\Throwable $_) {}
            }
            try { return Carbon::parse($v); } catch (\Throwable $_) {}
            return null;
        };
        $startDate = $parse($start);
        $endDate   = $parse($end);
        if ($startDate && $endDate && $startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        if ($startDate) $query->whereDate('p.updated_at', '>=', $startDate->copy()->startOfDay()->toDateString());
        if ($endDate)   $query->whereDate('p.updated_at', '<=', $endDate->copy()->endOfDay()->toDateString());

        $orders = $query->orderByDesc('p.updated_at')
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
            'p.ProductID','p.OrderID as order_id','p.productName','p.materialRemark',
            'o.order_number','o.companyName','o.artist_id','o.salesperson_id',
            'o.orderTitle','o.created_at as order_created_at','o.deadline','o.approval',
            'p.taskType',
            'ua.name as artist_name',
            'uh.name as head_artist_name',
        ])
        ->first();

    abort_unless($header, 404);

    // 2) Order code (for the page title)
    $orderCode = $header->order_number ?: ('ORD-'.$header->order_id);

    // 3) Product remarks for THIS product (with operation label)
    $remarks = DB::table('product_remarks')
        ->where('ProductID', $productId)
        ->orderBy('created_at')
        ->get(['operation','remark']);

    // 4) All installation products for the SAME order (only taskType='installation')
    $products = DB::table('products')
        ->where('OrderID', $header->order_id)
        ->whereRaw('LOWER(taskType) = ?', ['installation'])
        ->orderBy('ProductID')
        ->select(['ProductID','productName','materialRemark'])
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
                'i.ProductID','i.ItemID','i.itemName','i.quantity','i.sizeWidth','i.sizeHeight','i.sizeUnit',
                'i.bleedTop','i.bleedBottom','i.bleedLeft','i.bleedRight','i.bleedUnit',
                'i.material','i.prime_centre','i.finishing as assemble', // assemble flag if you use it here
                's.printer','s.cutter','s.lamination',
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
    } catch (\Throwable $e) {}

    return view('installation.job_order_show', [
        'header'      => $header,
        'orderCode'   => $orderCode,
        'remarks'     => $remarks,
        'products'    => $products,
        'itemsByProd' => $items,    // Collection keyed by ProductID
        'proofUrl'    => $proofUrl,
    ]);
}

}
