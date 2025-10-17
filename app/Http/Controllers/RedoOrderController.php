<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Helpers\Helpers;
use Carbon\Carbon;

class RedoOrderController extends Controller
{
    public function create(Order $order)
    {
        $baseOrder = $order->redo ? Order::findOrFail($order->redo) : $order;

        $products = Product::where('OrderID', $baseOrder->id)
            ->select('ProductID', 'productName', 'totalQuantity', 'taskType', 'status')
            ->orderBy('ProductID')
            ->get();

        $redoOrder = Order::where('redo', $baseOrder->id)->first();
        $alreadyInRedo = $redoOrder
            ? Product::where('OrderID', $redoOrder->id)->pluck('redoOf')->filter()->unique()->values()->toArray()
            : [];
            
        $hasRedo = (bool) $redoOrder;
        $displayOrderNumber = ($order->redo || $hasRedo)
            ? ($baseOrder->order_number . 'R')
            : $baseOrder->order_number;

        return view('artist.orders.redo', compact('order','products') + [
            'baseOrder'          => $baseOrder,
            'alreadyInRedo'      => $alreadyInRedo,
            'displayOrderNumber' => $displayOrderNumber,
        ]);
    }


    public function store(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason'     => ['nullable', 'string', 'max:255'],
            'reason_alt' => ['nullable', 'string', 'max:2000'],
            'products'   => ['nullable', 'array'],
            'products.*' => ['integer'],
        ]);

        $selectedCurrentIds = collect($validated['products'] ?? [])->filter()->unique()->values();

        DB::transaction(function () use ($order, $validated, $selectedCurrentIds) {

            // Figure out base order & whether a redo bucket already exists
            $baseId    = $order->redo ? (int) $order->redo : (int) $order->id;
            $baseOrder = $order->redo ? Order::findOrFail($baseId) : $order;

            $redoOrder = Order::lockForUpdate()->where('redo', $baseId)->first();
            $redoExistedBefore = (bool) $redoOrder;

            $reasonText = trim(($validated['reason'] ?? '') . ' ' . ($validated['reason_alt'] ?? ''));

            if (!$redoOrder) {
                // --- FIRST REDO: create the single redo order ---
                $redoOrder = $baseOrder->replicate([
                    'id','order_number','created_at','updated_at','submit','draft','status','redo','orderStatus'
                ]);
                $redoOrder->order_number = $this->nextRedoNumber($baseOrder->order_number); // e.g. #ORD-xxxxR
                $redoOrder->redo         = $baseId;
                $redoOrder->draft        = 1;
                $redoOrder->submit       = 0;
                $redoOrder->orderStatus  = 'in_progress';
                $redoOrder->status       = 0;
                $redoOrder->created_at   = now();
                $redoOrder->updated_at   = now();

                if ($reasonText !== '') {
                    $redoOrder->orderDetail = trim(($baseOrder->orderDetail ? $baseOrder->orderDetail . "\n\n" : '') . "REDO Reason: " . $reasonText);
                }
                $redoOrder->save();

            } else {
                // --- SUBSEQUENT REDO: reuse the existing redo order; don't touch statuses ---
                if (in_array(strtolower((string)$redoOrder->orderStatus), ['completed', 'complete'])) {
                    $redoOrder->orderStatus = 'in_progress';
                    $redoOrder->draft       = 1;
                    $redoOrder->submit      = 0;
                    $redoOrder->updated_at  = now();
                }

                if ($reasonText !== '') {
                    $redoOrder->orderDetail = trim(($redoOrder->orderDetail ? $redoOrder->orderDetail . "\n\n" : '') . "REDO Reason: " . $reasonText);
                }

                // Save only if anything changed
                if ($redoOrder->isDirty()) {
                    $redoOrder->save();
                }
            }

            // Optional reason log (unchanged)
            if ($reasonText !== '') {
                DB::table('report_redo')->insert([
                    'OrderID'    => $order->id,   // or $baseId if you prefer
                    'reason'     => $reasonText,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // --- PRODUCTS ---
            // Always base on ALL products from the *base* order
            $baseProducts = Product::with(['items.spec','remarks','deliveryBreakdowns','progress'])
                ->where('OrderID', $baseOrder->id)
                ->orderBy('ProductID')
                ->get();

            // Normalize selection to origin ids
            $selectedOriginIds = collect();
            if ($selectedCurrentIds->isNotEmpty()) {
                $selectedOriginIds = Product::whereIn('ProductID', $selectedCurrentIds)
                    ->pluck(DB::raw('COALESCE(redoOf, ProductID)'))
                    ->unique()->values();
            }

            foreach ($baseProducts as $origin) {
                $originId = $origin->ProductID;

                $redoProduct = Product::where('OrderID', $redoOrder->id)
                    ->where('redoOf', $originId)
                    ->first();

                $editable = $selectedOriginIds->contains($originId) ? 1 : 0;

                if ($redoProduct) {
                    // PROMOTION-ONLY: once editable => always editable
                    // If user selected this product now and it's not yet editable, set to 1.
                    if ($selectedOriginIds->contains($originId) && (int) $redoProduct->editable !== 1) {
                        $redoProduct->editable   = 1;
                        $redoProduct->updated_at = now();
                        $redoProduct->save();
                    }
                    // If not selected this time, LEAVE AS-IS (do not demote to 0)
                    continue;
                }

                // Not copied yet → copy now (even if not selected; just mark editable accordingly)
                $np = $origin->replicate(['ProductID','OrderID','created_at','updated_at']);
                $np->OrderID    = $redoOrder->id;
                $np->redoOf     = $originId;
                $np->editable   = $editable;
                $np->created_at = now();
                $np->updated_at = now();
                $np->save();

                foreach ($origin->items as $it) {
                    $ni = $it->replicate(['ItemID','ProductID','created_at','updated_at']);
                    $ni->ProductID  = $np->ProductID;
                    $ni->created_at = now();
                    $ni->updated_at = now();
                    $ni->save();

                    if ($it->spec) {
                        $ns = $it->spec->replicate(['SpecificationID','ItemID','created_at','updated_at']);
                        $ns->ItemID     = $ni->ItemID;
                        $ns->created_at = now();
                        $ns->updated_at = now();
                        $ns->save();
                    }
                }
                foreach ($origin->remarks as $rm) {
                    $nr = $rm->replicate(['RemarkID','ProductID','created_at','updated_at']);
                    $nr->ProductID  = $np->ProductID;
                    $nr->created_at = now();
                    $nr->updated_at = now();
                    $nr->save();
                }
                foreach ($origin->deliveryBreakdowns as $db) {
                    $nd = $db->replicate(['BreakdownID','ProductID','created_at','updated_at']);
                    $nd->ProductID  = $np->ProductID;
                    $nd->created_at = now();
                    $nd->updated_at = now();
                    $nd->save();
                }
                foreach ($origin->progress as $pg) {
                    $npgr = $pg->replicate(['ProgressID','ProductID','created_at','updated_at']);
                    $npgr->ProductID = $np->ProductID;
                    $npgr->created_at = now();
                    $npgr->updated_at = now();
                    $npgr->save();
                }
            }

            // --- STATUS UPDATE RULE ---
            // Only for the very first redo, and only for the *base* order we started from.
            if (!$redoExistedBefore && $order->id === $baseOrder->id) {
                $order->forceFill(['status' => 1])->save();
            }
            // On subsequent redoes → do nothing (leave statuses as-is).
        });

        $actor      = auth()->user();
        $actorName  = $actor?->name ?? 'System';
        $actorRole  = str_replace('-', ' ', strtolower($actor?->role ?? 'user'));

        $baseId     = $order->redo ? (int)$order->redo : (int)$order->id;
        $baseOrder  = \App\Models\Order::find($baseId);
        $redoOrder  = \App\Models\Order::where('redo', $baseId)->latest('id')->first();

        $deadline   = $baseOrder?->deadline
            ? Carbon::parse($baseOrder->deadline)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d')
            : '-';

        $selectedCurrentIds  = collect($request->input('products', []))->filter()->unique()->values();
        $baseProductsQuery   = Product::with(['items.spec','remarks','deliveryBreakdowns','progress'])
                                ->where('OrderID', $baseId);

        $allBaseProducts     = $baseProductsQuery->clone()->get(['ProductID','productName']);
        $affectedOriginIds   = $selectedCurrentIds->isNotEmpty()
            ? Product::whereIn('ProductID', $selectedCurrentIds)->pluck(DB::raw('COALESCE(redoOf, ProductID)'))->unique()
            : $allBaseProducts->pluck('ProductID');

        $affectedProducts    = $allBaseProducts->whereIn('ProductID', $affectedOriginIds);
        $productCount        = $affectedProducts->count();

        // ------- Business recipients (head-artist, head-salesperson, admin, boss) -------
        $businessRecipients = User::whereIn('role', ['head-artist','head-salesperson','admin','boss'])->get();

        // Role-aware order URL (point them to the redo order container)
        $orderUrlFor = function (User $u) use ($redoOrder, $baseOrder) {
            $orderId = $redoOrder?->id ?? $baseOrder?->id ?? 0;
            return match (strtolower($u->role)) {
                'artist','head-artist'            => url("/artist/orders/{$orderId}"),
                'salesperson','head-salesperson'  => url("/orders/{$orderId}"),
                'admin'                           => url("/admin/orders/{$orderId}"),
                'boss'                            => url("/boss/orders/{$orderId}"),
                default                           => url('/'),
            };
        };

        // One concise message for business roles
        $reasonText   = trim((string)$request->input('reason','') . ' ' . (string)$request->input('reason_alt',''));
        $idsPreview   = $affectedProducts->pluck('ProductID')->take(5)->implode(', ');
        $orderNoBase  = (string)($baseOrder?->order_number ?? '');
        $orderNoRedo  = (string)($redoOrder?->order_number ?? '');

        $businessMsg = "Redo for Order {$orderNoBase} → **{$orderNoRedo}** by {$actorName} ({$actorRole}). "
                    . "{$productCount} product(s)"
                    . ($idsPreview ? " (#{$idsPreview})" : '')
                    . ". Deadline: {$deadline}"
                    . ($reasonText ? ". Reason: {$reasonText}" : ".");

        // Send to business recipients (deduped by id)
        $businessRecipients->unique('id')->each(function (User $u) use ($businessMsg, $orderUrlFor) {
            Helpers::notify($u, $businessMsg, $orderUrlFor($u), ['database']);
        });

        // ------- Assigned operations users (per affected product) -------
        $opsUsersById = collect();         
        foreach ($affectedProducts as $p) {
            $candidateUserId = null;

            // Try common direct columns (adjust if your schema names differ)
            foreach (['assigned_user_id','operator_user_id','printing_user_id','furnishing_user_id'] as $col) {
                if (isset($p->{$col}) && $p->{$col}) { $candidateUserId = (int)$p->{$col}; break; }
            }

            // Fallback: latest progress row with any of these columns
            if (!$candidateUserId && $p->relationLoaded('progress')) {
                $latest = $p->progress->sortByDesc('created_at')->first();
                if ($latest) {
                    foreach (['user_id','operator_id','assigned_to'] as $col) {
                        if (isset($latest->{$col}) && $latest->{$col}) { $candidateUserId = (int)$latest->{$col}; break; }
                    }
                }
            }

            if ($candidateUserId) {
                $opsUsersById[$candidateUserId] = array_values(array_unique(
                    array_merge($opsUsersById[$candidateUserId] ?? [], [(int)$p->ProductID])
                ));
            }
        }

        // Notify each assigned ops user exactly once, with the list of their product IDs.
        // URL is product-focused (first product for that user), mapped by the user's role.
        $mapOpsUrl = function (User $u, int $productId) {
            return match (strtolower($u->role)) {
                'operations-printing'               => url("/printing/jobs/{$productId}"),
                'operations-furnishing'             => url("/furnishing/jobs/{$productId}"),
                'operations-dispatch-control'       => url("/dispatchcontrol/job/{$productId}"),
                'operations-delivery-installation'  => url("/installation/job/{$productId}"),
                default                             => url("/"),
            };
        };

        foreach ($opsUsersById as $uid => $pids) {
            $opsUser = User::find($uid);
            if (!$opsUser) continue;

            sort($pids);
            $firstPid   = (int)($pids[0] ?? 0);
            $prodList   = implode(', ', array_slice($pids, 0, 5));
            $msgOps = "Redo requested in Order {$orderNoBase} by {$actorName} ({$actorRole}). "
                    . "Your assigned product"
                    . (count($pids) > 1 ? "s (IDs: {$prodList}) have" : " (ID: {$prodList}) has")
                    . " been sent for **redo**. Deadline: {$deadline}.";

            Helpers::notify($opsUser, $msgOps, $mapOpsUrl($opsUser, $firstPid), ['database']);
        }

        return redirect()->route('artist.orders')->with('success', 'Redo updated.');
    }

    /**
     * Generate a redo order number:
     *  Try "<old>R"; if taken, "<old>R2", "<old>R3", ...
     */
    private function nextRedoNumber(string $old): string
    {
        $try = $old.'R';
        $i = 1;
        while (Order::where('order_number', $try)->exists()) {
            $i++;
            $try = $old.'R'.$i;
        }
        return $try;
    }
}
