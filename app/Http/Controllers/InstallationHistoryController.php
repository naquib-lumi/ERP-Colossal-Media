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
    // 🔎 Filters (same names/behavior as Printing)
    $pid     = trim((string)$request->query('pid', ''));      // product id or code
    $q       = trim((string)$request->query('q', ''));        // order title / company / product / remarks
    $artist  = trim((string)$request->query('artist', ''));   // orders.artist_id
    $start   = trim((string)$request->query('start', ''));    // mm/dd/yyyy or yyyy-mm-dd
    $end     = trim((string)$request->query('end', ''));      // mm/dd/yyyy or yyyy-mm-dd
    $sortBy  = $request->query('sort_by', 'completed');       // only "completed" for this page
    $sortDir = strtolower($request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

    // Parse date -> Y-m-d (accepts mm/dd/yyyy too)
    $toYmd = static function (?string $v): ?string {
        if (!$v) return null;
        try { return \Carbon\Carbon::parse($v)->toDateString(); } catch (\Throwable $e) {
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $v, $m)) {
                return "{$m[3]}-".str_pad($m[1],2,'0',STR_PAD_LEFT)."-".str_pad($m[2],2,'0',STR_PAD_LEFT);
            } return null;
        }
    };
    $startY = $toYmd($start);
    $endY   = $toYmd($end);

    // Artists dropdown (same as printing)
    $artists = DB::table('users')
    ->select('id', 'name')
    ->whereIn('role', ['artist', 'head-artist'])
    ->orderBy('name')
    ->get();

    // ---------- YOUR ORIGINAL BASE QUERY (keep proof-related fields) ----------
    $base = DB::table('fulfillment_progress as fp')
        ->join('products as p', 'p.ProductID', '=', 'fp.ProductID')
        ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
        ->select([
            'p.ProductID',
            'p.productName',
            'p.materialRemark',
            'o.id as order_id',
            'o.order_number',
            'o.orderTitle',
            'o.companyName',
            'o.artist_id',
            DB::raw('fp.completedAt as completed_date'),

            // Your page wants the printed style code like #ORD-YYYY-OOO-PXXXX
            DB::raw("
                CONCAT('#ORD-',
                       LPAD(COALESCE(YEAR(o.orderDate), YEAR(o.created_at)), 4, '0'),
                       '-', LPAD(o.id, 3, '0'),
                       '-P', LPAD(p.ProductID, 4, '0')
                ) as product_code
            "),

            // ⬇️ keep any extra proof columns you already had (e.g. URLs, counts, etc.)
        ])
        ->where('fp.stage', 'installation')
        ->where(function ($w) {
            $w->whereIn('fp.status', ['completed','rejected'])
              ->orWhereNotNull('fp.completedAt');
        });

    // 🔎 keyword filter (Order Title / Company / Product / Remarks / ProductID)
    if ($q !== '') {
        $like = "%{$q}%";
        $base->where(function ($w) use ($like, $q) {
            $w->where('o.orderTitle', 'like', $like)
              ->orWhere('o.companyName', 'like', $like)
              ->orWhere('p.productName', 'like', $like)
              ->orWhere('p.materialRemark', 'like', $like);

            if (preg_match('/^\d+$/', $q)) $w->orWhere('p.ProductID', (int)$q);
            else $w->orWhere('p.ProductID', 'like', $like);
        });
    }

    // 🔎 product id/code (search all pages)
    if ($pid !== '') {
        $like = '%'.strtolower($pid).'%';
        $base->where(function ($w) use ($like) {
            $w->whereRaw('LOWER(p.ProductID) LIKE ?', [$like])
              ->orWhereRaw("
                LOWER(CONCAT('#ORD-',
                       LPAD(COALESCE(YEAR(o.orderDate), YEAR(o.created_at)), 4, '0'),
                       '-', LPAD(o.id, 3, '0'),
                       '-P', LPAD(p.ProductID, 4, '0')
                )) LIKE ?", [$like]);
        });
    }

    // 🔎 artist
    if ($artist !== '') $base->where('o.artist_id', $artist);

    // 🔎 completed range
    if ($startY) $base->whereDate('fp.completedAt', '>=', $startY);
    if ($endY)   $base->whereDate('fp.completedAt', '<=', $endY);

    // ⏱ sort (ASC/DESC) on completed date
    if ($sortBy === 'completed') $base->orderBy('fp.completedAt', $sortDir)->orderBy('p.ProductID');

    $orders = $base->paginate(10)->withQueryString();

    // (Optional) Attach details URL used by row double-click
    $orders->getCollection()->transform(function ($r) {
        $r->details_url = route('installation.job.show', $r->ProductID);
        return $r;
    });

    return view('installation.history', [
        'orders'   => $orders,
        'pid'      => $pid,
        'q'        => $q,
        'artist'   => $artist,
        'start'    => $start,
        'end'      => $end,
        'sort_by'  => $sortBy,
        'sort_dir' => $sortDir,
        'artists'  => $artists,
    ]);
}

    public function show($productId)
    {
        // Reuse the same data as FurnishingProductOrderController@show
        $data = app(\App\Http\Controllers\InstallationProductOrderController::class)->show($productId);

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
