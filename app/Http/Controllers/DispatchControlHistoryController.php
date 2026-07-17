<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DispatchControlHistoryController extends Controller
{
    public function index(Request $request)
    {
        // --- inputs (same names you used elsewhere)
        $pid    = trim((string) $request->query('pid', ''));            // Product ID / code
        $q      = trim((string) $request->query('q', ''));              // Order title / company / product / remarks
        $artist = (string) $request->query('artist', '');               // orders.artist_id
        $start  = trim((string) $request->query('start', ''));          // mm/dd/yyyy or yyyy-mm-dd
        $end    = trim((string) $request->query('end', ''));            // mm/dd/yyyy or yyyy-mm-dd
        $sort   = strtolower((string) $request->query('sort', 'desc')); // completed date sort: asc|desc
        $dir    = strtolower((string) $request->get('dir', 'desc'));
        $dir    = $dir === 'asc' ? 'asc' : 'desc';

        $yearValueExpr = "CAST(COALESCE(
            YEAR(o.orderDate),
            YEAR(o.created_at)
        ) AS CHAR)";

        $orderIdValueExpr = "CAST(COALESCE(o.redo, o.id) AS CHAR)";
        $productIdValueExpr = "CAST(COALESCE(p.redoOf, p.ProductID) AS CHAR)";

        $yearCodeExpr = "
            CONCAT(
                REPEAT('0', GREATEST(0, 4 - CHAR_LENGTH($yearValueExpr))),
                $yearValueExpr
            )
        ";

        $orderCodeExpr = "
            CONCAT(
                REPEAT('0', GREATEST(0, 3 - CHAR_LENGTH($orderIdValueExpr))),
                $orderIdValueExpr
            )
        ";

        $productCodeNumberExpr = "
            CONCAT(
                REPEAT('0', GREATEST(0, 4 - CHAR_LENGTH($productIdValueExpr))),
                $productIdValueExpr
            )
        ";

        $formattedCodeExpr = "
            CONCAT(
                '#ORD-',
                $yearCodeExpr,
                '-',
                $orderCodeExpr,
                '-P',
                $productCodeNumberExpr
            )
        ";

        // Normalize date.
        $toYmd = static function (?string $v): ?string {
            if (!$v) {
                return null;
            }

            try {
                return \Carbon\Carbon::parse($v)->toDateString();
            } catch (\Throwable $e) {
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $v, $m)) {
                    return $m[3]
                        . '-'
                        . str_pad($m[1], 2, '0', STR_PAD_LEFT)
                        . '-'
                        . str_pad($m[2], 2, '0', STR_PAD_LEFT);
                }

                return null;
            }
        };

        $startYmd = $toYmd($start);
        $endYmd   = $toYmd($end);

        // Artists dropdown: limit to artist + head-artist.
        $artists = DB::table('users')
            ->whereIn('role', ['artist', 'head-artist'])
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $fpLatest = DB::table('fulfillment_progress')
            ->selectRaw('ProductID, MAX(completedAt) AS completed_date')
            ->where('stage', 'delivery')
            ->where(function ($w) {
                $w->whereIn('status', ['completed', 'rejected'])
                    ->orWhereNotNull('completedAt');
            })
            ->groupBy('ProductID');

        // Editable redo children (for trailing R).
        $redoChildren = DB::raw('(
            SELECT DISTINCT redoOf
            FROM products
            WHERE editable = 1
            AND redoOf IS NOT NULL
        ) rr');

        // -------- base query (Dispatch Control history = stage delivery) --------
        $base = DB::table('products as p')
            ->joinSub($fpLatest, 'fpx', function ($j) {
                $j->on('fpx.ProductID', '=', 'p.ProductID');
            })
            ->leftJoin('orders as o', 'o.id', '=', 'p.OrderID')
            ->leftJoin($redoChildren, 'rr.redoOf', '=', 'p.ProductID')
            ->where(function ($w) {
                $w->whereNull('o.status')
                    ->orWhere('o.status', '!=', 1);
            })
            ->when(
                $pid !== '',
                function ($qb) use ($pid, $formattedCodeExpr) {
                    $like = "%{$pid}%";

                    $qb->where(function ($w) use ($pid, $like, $formattedCodeExpr) {
                        // Exact numeric matches without forcing the value into a PHP integer.
                        if (ctype_digit($pid)) {
                            $w->orWhere('o.id', $pid)
                                ->orWhere('o.redo', $pid)
                                ->orWhere('p.ProductID', $pid)
                                ->orWhere('p.redoOf', $pid);
                        }

                        // Partial/fuzzy matches.
                        $w->orWhere('o.id', 'like', $like)
                            ->orWhere('o.redo', 'like', $like)
                            ->orWhere('p.ProductID', 'like', $like)
                            ->orWhere('p.redoOf', 'like', $like)
                            ->orWhere('o.order_number', 'like', $like)

                            // Match the formatted code without the optional trailing R.
                            ->orWhere(
                                DB::raw($formattedCodeExpr),
                                'like',
                                $like
                            );
                    });
                }
            )
            ->select([
                'p.ProductID',
                'o.orderTitle as product_name',
                'p.materialRemark',
                DB::raw('fpx.completed_date'),
                'o.id as order_id',
                'o.order_number',
                DB::raw("
                    CONCAT(
                        $formattedCodeExpr,
                        CASE
                            WHEN (
                                (p.redoOf IS NOT NULL AND p.editable = 1)
                                OR rr.redoOf IS NOT NULL
                            ) THEN 'R'
                            ELSE ''
                        END
                    ) AS product_code
                "),
            ]);

        if ($q !== '') {
            $like = "%{$q}%";

            $base->where(function ($w) use ($q, $like, $formattedCodeExpr) {
                $w->where('o.orderTitle', 'like', $like)
                    ->orWhere('o.companyName', 'like', $like)
                    ->orWhere('p.productName', 'like', $like)
                    ->orWhere('o.order_number', 'like', $like)
                    ->orWhere('p.materialRemark', 'like', $like);

                if (preg_match('/^\d+$/', $q)) {
                    $w->orWhere('p.ProductID', $q);
                } else {
                    $w->orWhere('p.ProductID', 'like', $like);
                }

                $w->orWhereExists(function ($sub) use ($like) {
                    $sub->from('product_items as pi')
                        ->join('specifications as s', 's.ItemID', '=', 'pi.ItemID')
                        ->whereColumn('pi.ProductID', 'p.ProductID')
                        ->where('s.printer', 'like', $like);
                });

                $w->orWhere(
                    DB::raw($formattedCodeExpr),
                    'like',
                    $like
                );
            });
        }

        if ($artist !== '') {
            $base->where('o.artist_id', $artist);
        }

        if ($startYmd) {
            $base->whereDate('fpx.completed_date', '>=', $startYmd);
        }

        if ($endYmd) {
            $base->whereDate('fpx.completed_date', '<=', $endYmd);
        }

        // Sort by completed date; default is descending.
        $base->orderByRaw('fpx.completed_date IS NULL')
            ->orderBy('fpx.completed_date', $dir)
            ->orderBy('o.id')
            ->orderBy('p.ProductID');

        // Paginate.
        $orders = $base->paginate(10)->withQueryString();

        // Details link.
        $orders->getCollection()->transform(function ($row) {
            $row->details_url = route('dispatchcontrol.history.show', $row->ProductID);
            return $row;
        });

        return view('dispatchcontrol.history', [
            'orders'  => $orders,
            'pid'     => $pid,
            'q'       => $q,
            'artist'  => $artist,
            'start'   => $start,
            'end'     => $end,
            'sort'    => $sort,
            'artists' => $artists,
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
                'uploaded_at' => $r->created_at ? \Carbon\Carbon::parse($r->created_at)->timezone('Asia/Kuala_Lumpur')->format('M d, Y H:i') : null,
            ];
        });

        return response()->json(['ok' => true, 'files' => $files]);
    }

    public function storeProofs(Request $request, int $product)
    {
        // validate files
        $request->validate([
            'files'   => 'required',
            'files.*' => 'file|mimes:jpg,jpeg,png,gif,webp,pdf|max:8192',
        ]);

        // get Product + Order so we can fill installation_proofs
        $productRow = DB::table('products')
            ->select('ProductID', 'OrderID')
            ->where('ProductID', $product)
            ->first();

        if (!$productRow) {
            return response()->json([
                'ok'      => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $userId = Auth::id();
        $now    = now();

        $uploaded = [];

        foreach ($request->file('files', []) as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            // store file on public disk
            $storedPath = $file->store('installation_proofs', 'public');

            // write to installation_proofs
            $id = DB::table('installation_proofs')->insertGetId([
                'ProductID'     => $productRow->ProductID,
                'OrderID'       => $productRow->OrderID,
                'file_path'     => $storedPath,
                'original_name' => $file->getClientOriginalName(),
                'mime'          => $file->getClientMimeType(),
                'size'          => $file->getSize(),
                'uploaded_by'   => $userId,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            $uploaded[] = [
                'id'          => $id,
                'name'        => $file->getClientOriginalName(),
                'url'         => Storage::disk('public')->url($storedPath),
                'mime'        => $file->getClientMimeType(),
                'size'        => $file->getSize(),
                'uploaded_at' => $now->format('M d, Y H:i'),
            ];
        }

        if (!count($uploaded)) {
            return response()->json([
                'ok'      => false,
                'message' => 'No valid files uploaded.',
            ], 422);
        }

        return response()->json([
            'ok'    => true,
            'files' => $uploaded,
        ]);
    }

}
