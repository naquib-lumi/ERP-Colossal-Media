<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
