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
        // products for the checkbox list (so user can choose what to carry over)
        $products = Product::where('OrderID', $order->id)
            ->select('ProductID','productName','totalQuantity','taskType','status')
            ->orderBy('ProductID')
            ->get();

        return view('artist.orders.redo', compact('order','products'));
    }

public function store(Request $request, Order $order)
{
    $validated = $request->validate([
        'reason'     => ['nullable','string','max:255'],
        'reason_alt' => ['nullable','string','max:2000'],
        'products'   => ['nullable','array'],   // array of ProductID
        'products.*' => ['integer'],
    ]);

    // Which products should be editable in the redo copy?
    $editablePids = collect($validated['products'] ?? [])->filter()->unique()->values();

    DB::transaction(function () use ($order, $validated, $editablePids) {

        // 1) Create the new order by replicating the old one
        $newOrder = $order->replicate([
            'id','order_number','created_at','updated_at','submit','draft','status','redo','orderStatus'
        ]);

        $newOrder->order_number = $this->nextRedoNumber($order->order_number); // e.g. #ORD-...005R / R2
        $newOrder->redo         = $order->id; // link back to original order
        $newOrder->draft        = 1;
        $newOrder->submit       = 0;
        $newOrder->orderStatus  = 'in_progress';
        $newOrder->status       = 0;
        $newOrder->created_at   = now();
        $newOrder->updated_at   = now();

        // Optional: append reason to orderDetail so it’s visible in the order too
        $reasonText = trim(($validated['reason'] ?? '') . ' ' . ($validated['reason_alt'] ?? ''));
        if ($reasonText !== '') {
            $newOrder->orderDetail = trim(($order->orderDetail ? $order->orderDetail . "\n\n" : '') . "REDO Reason: " . $reasonText);
        }

        $newOrder->save();

        // Optional: persist the reason in a separate redo report table
        if ($reasonText !== '') {
            // Adjust table name/columns if your migration used a different naming
            DB::table('report_redo')->insert([
                // 'report_id' is auto-increment id
                'OrderID'   => $order->id,
                'reason'     => $reasonText,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2) Copy ALL products from the original order (no whereIn filter!)
        $productsToCopy = Product::with([
            'items.spec',
            'remarks',
            'deliveryBreakdowns',
            'progress'
        ])->where('OrderID', $order->id)->get();

        // 3) Copy products and all their relations
        foreach ($productsToCopy as $product) {
            $newProduct = $product->replicate(['ProductID','OrderID','created_at','updated_at']);
            $newProduct->OrderID    = $newOrder->id;
            $newProduct->redoOf     = $product->ProductID; // link to original product
            $newProduct->editable   = $editablePids->contains($product->ProductID) ? 1 : 0; // only selected are editable
            $newProduct->created_at = now();
            $newProduct->updated_at = now();
            $newProduct->save();

            // Items
            foreach ($product->items as $item) {
                $newItem = $item->replicate(['ItemID','ProductID','created_at','updated_at']);
                $newItem->ProductID   = $newProduct->ProductID;
                $newItem->created_at  = now();
                $newItem->updated_at  = now();
                $newItem->save();

                if ($item->spec) {
                    $newSpec = $item->spec->replicate(['SpecificationID','ItemID','created_at','updated_at']);
                    $newSpec->ItemID     = $newItem->ItemID;
                    $newSpec->created_at = now();
                    $newSpec->updated_at = now();
                    $newSpec->save();
                }
            }

            // Remarks
            foreach ($product->remarks as $rm) {
                $nrm = $rm->replicate(['RemarkID','ProductID','created_at','updated_at']);
                $nrm->ProductID   = $newProduct->ProductID;
                $nrm->created_at  = now();
                $nrm->updated_at  = now();
                $nrm->save();
            }

            // Delivery breakdowns
            foreach ($product->deliveryBreakdowns as $dbreak) {
                $ndb = $dbreak->replicate(['BreakdownID','ProductID','created_at','updated_at']);
                $ndb->ProductID   = $newProduct->ProductID;
                $ndb->created_at  = now();
                $ndb->updated_at  = now();
                $ndb->save();
            }

            // Fulfillment progress
            foreach ($product->progress as $fp) {
                $nfp = $fp->replicate(['ProgressID','ProductID','created_at','updated_at']);
                $nfp->ProductID   = $newProduct->ProductID;
                $nfp->created_at  = now();
                $nfp->updated_at  = now();
                $nfp->save();
            }
        }

        // 4) Mark the OLD order as “redone”
        $order->forceFill(['status' => 1])->save();
    });

    return redirect()
        ->route('artist.orders') // listing route
        ->with('success', 'Redo order created.');
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
