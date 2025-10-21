<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class DispatchControlHistoryController extends Controller
{
    public function index(Request $request)
{
    // --- inputs (same names you used elsewhere)
    $pid    = trim((string) $request->query('pid', ''));           // Product ID / code
    $q      = trim((string) $request->query('q', ''));             // Order title / company / product / remarks
    $artist = (string) $request->query('artist', '');              // orders.artist_id
    $start  = trim((string) $request->query('start', ''));         // mm/dd/yyyy or yyyy-mm-dd
    $end    = trim((string) $request->query('end', ''));           // mm/dd/yyyy or yyyy-mm-dd
    $sort   = strtolower((string) $request->query('sort', 'desc')); // completed date sort: asc|desc

    // normalize date
    $toYmd = static function (?string $v): ?string {
        if (!$v) return null;
        try { return \Carbon\Carbon::parse($v)->toDateString(); } catch (\Throwable $e) {
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $v, $m)) {
                return $m[3] . '-' . str_pad($m[1],2,'0',STR_PAD_LEFT) . '-' . str_pad($m[2],2,'0',STR_PAD_LEFT);
            }
            return null;
        }
    };
    $startY = $toYmd($start);
    $endY   = $toYmd($end);

    // artists dropdown: limit to artist + head-artist
    $artists = DB::table('users')
        ->whereIn('role', ['artist', 'head-artist'])
        ->select('id','name')
        ->orderBy('name')
        ->get();

    // -------- base query (Dispatch Control history = stage 'delivery') --------
    $base = DB::table('fulfillment_progress as fp')
        ->join('products as p', 'p.ProductID', '=', 'fp.ProductID')
        ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
        ->select([
            'p.ProductID',
            'p.productName as product_name',
            'p.materialRemark',
            'o.id as order_id',
            'o.order_number',
            'o.orderTitle',
            'o.companyName',
            'o.artist_id',
            DB::raw('fp.completedAt as completed_date'),

            // product code like "#ORD-2025-014-P0023"
            DB::raw("
                CONCAT(
                  '#ORD-',
                  LPAD(COALESCE(YEAR(o.orderDate), YEAR(o.created_at)), 4, '0'),
                  '-',
                  LPAD(o.id, 3, '0'),
                  '-P',
                  LPAD(p.ProductID, 4, '0')
                ) as product_code
            "),
        ])
        ->where('fp.stage', 'delivery')
        ->where(function ($w) {
            // show records that are actually finished (or rejected but have timestamp)
            $w->whereIn('fp.status', ['completed', 'rejected'])
              ->orWhereNotNull('fp.completedAt');
        });

    // --- filters ---
    if ($pid !== '') {
        $needle = mb_strtolower($pid);
        $base->where(function ($w) use ($needle) {
            $w->orWhere('p.ProductID', 'like', "%{$needle}%")
              ->orWhere(DB::raw("
                CONCAT('#ORD-', LPAD(COALESCE(YEAR(o.orderDate), YEAR(o.created_at)),4,'0'),
                       '-', LPAD(o.id,3,'0'), '-P', LPAD(p.ProductID,4,'0'))
              "), 'like', "%{$needle}%");
        });
    }

    if ($q !== '') {
        $like = "%{$q}%";
        $base->where(function ($w) use ($like) {
            $w->orWhere('o.orderTitle',    'like', $like)
              ->orWhere('o.companyName',   'like', $like)
              ->orWhere('p.productName',   'like', $like)
              ->orWhere('p.materialRemark','like', $like);
        });
    }

    if ($artist !== '') {
        $base->where('o.artist_id', $artist);
    }

    if ($startY) $base->whereDate('fp.completedAt', '>=', $startY);
    if ($endY)   $base->whereDate('fp.completedAt', '<=', $endY);

    // --- sort (default desc) ---
    $sort = in_array($sort, ['asc','desc'], true) ? $sort : 'desc';
    $base->orderBy('fp.completedAt', $sort)->orderBy('p.ProductID', 'asc');

    // paginate
    $orders = $base->paginate(10)->withQueryString();

    // details link (adjust route if yours differs)
    $orders->getCollection()->transform(function ($row) {
        $row->details_url = route('dispatchcontrol.history.show', $row->ProductID);
        return $row;
    });

    return view('dispatchcontrol.history', [
        'orders' => $orders,
        // echo filters back to UI
        'pid'    => $pid,
        'q'      => $q,
        'artist' => $artist,
        'start'  => $start,
        'end'    => $end,
        'sort'   => $sort,
        'artists'=> $artists,
    ]);
}

    public function show($productId)
    {
        // Reuse the same data as FurnishingProductOrderController@show
        $data = app(\App\Http\Controllers\DispatchControlProductOrderController::class)->show($productId);

        // If show() in the original controller returns a view,
        // we can just re-render that but hide the actionbar using a flag.
        return $data->with('isHistoryView', true);
    }

    public function proofs(Request $request, int $product)
    {
        $rows = DB::table('installation_proofs')
            ->where('ProductID', $product)
            ->orderByDesc('id')
            ->get(['id','ProductID','OrderID','file_path','original_name','mime','size','created_at']);

        $files = $rows->map(function ($r) {
            return [
                'id'   => (int)$r->id,
                'name' => $r->original_name ?: basename($r->file_path),
                'url'  => Storage::disk('public')->url($r->file_path),
                'mime' => $r->mime,
                'size' => (int)$r->size,
            ];
        });

        return response()->json(['ok' => true, 'files' => $files]);
    }

}
