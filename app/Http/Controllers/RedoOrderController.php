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
        // 1) Base/original order (even if you came from a redo)
        $baseOrder = $order->redo ? Order::findOrFail((int) $order->redo) : $order;

        // 2) Products to pick from the CURRENT order (as before)
        $products = Product::where('OrderID', $order->id)
            ->orderBy('ProductID')
            ->get(['ProductID','productName','totalQuantity','taskType']);

        // 3) Latest redo reason (same)
        $latestRedoReason = DB::table('report_redo')
            ->where('OrderID', $baseOrder->id)
            ->orderByDesc('created_at')
            ->value('reason');

        // 4) How many redo children exist for the BASE
        $existingCount = Order::where('redo', $baseOrder->id)->count();

        // 5) Build labels
        $baseNumber = $this->normalizeOrderNumber($baseOrder->order_number); // strip # and any trailing R/R1…
        $headerOrderNumber = '#' . $baseNumber . ($existingCount > 0 ? 'R' : ''); // show R only if has any redo already
        $nextRedoNumber    = '#' . $baseNumber . 'R'; // the id that will be created if user submits

        return view('artist.orders.redo', [
            'order'              => $order,
            'products'           => $products,
            'latestRedoReason'   => $latestRedoReason,
            'headerOrderNumber'  => $headerOrderNumber, // <-- use in page header
            'nextRedoNumber'     => $nextRedoNumber,    // <-- use in “What happens next?”
        ]);
    }


    public function store(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason'     => ['required', 'string', 'max:255'],
            'reason_alt' => ['nullable', 'string', 'max:2000', 'required_if:reason,Others'],
            'products'   => ['nullable', 'array'],
            'products.*' => ['integer'],
        ]);

        $selectedCurrentIds = collect($validated['products'] ?? [])->filter()->unique()->values();

        // Build final reason text
        $reasonRaw = trim((string)($validated['reason'] ?? ''));
        $reasonAlt = trim((string)($validated['reason_alt'] ?? ''));
        $isOthers  = strcasecmp($reasonRaw, 'Others') === 0;
        $reasonText = $isOthers ? $reasonAlt : ($reasonAlt !== '' ? "{$reasonRaw}: {$reasonAlt}" : $reasonRaw);

        $actorId = auth()->id();

        DB::transaction(function () use ($order, $selectedCurrentIds, $reasonText, $actorId) {

            $baseId    = $order->redo ? (int) $order->redo : (int) $order->id;
            $baseOrder = $order->redo ? Order::findOrFail($baseId) : $order;

            // 🔴 Archive ALL existing redos for this base so they’re hidden in lists
            Order::where('redo', $baseId)->update(['status' => 1]);

            // Count again (after archiving is fine too; count is only for numbering)
            $existingCount = Order::lockForUpdate()->where('redo', $baseId)->count();

            // Create a brand-new redo order
            $redoOrder = $baseOrder->replicate([
                'id','order_number','created_at','updated_at','submit','draft','status','redo','orderStatus'
            ]);
            $redoOrder->order_number = $this->nextRedoNumber($baseOrder->order_number);
            $redoOrder->redo         = $baseId;
            $redoOrder->draft        = 1;
            $redoOrder->submit       = 0;
            $redoOrder->orderStatus  = 'in_progress';
            $redoOrder->status       = 0;          // 🔵 keep the latest redo visible
            $redoOrder->data_entry_id       = null;          // 🔵 keep the latest redo visible
            $redoOrder->created_at   = now();
            $redoOrder->updated_at   = now();

            if ($reasonText !== '') {
                $redoOrder->orderDetail = trim(($baseOrder->orderDetail ? $baseOrder->orderDetail . "\n\n" : '') . "REDO Reason: " . $reasonText);
            }
            $redoOrder->save();

            if ($reasonText !== '') {
                DB::table('report_redo')->insert([
                    'OrderID'    => $baseId,
                    'user_id'    => $actorId,
                    'reason'     => $reasonText,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Duplicate products/items/specs/remarks/deliveries/progress from BASE
            $baseProducts = Product::with(['items.spec','remarks','deliveryBreakdowns','progress'])
                ->where('OrderID', $baseOrder->id)
                ->orderBy('ProductID')
                ->get();

            $selectedOriginIds = collect();
            if ($selectedCurrentIds->isNotEmpty()) {
                $selectedOriginIds = Product::whereIn('ProductID', $selectedCurrentIds)
                    ->pluck(DB::raw('COALESCE(redoOf, ProductID)'))
                    ->unique()
                    ->values();
            }

            foreach ($baseProducts as $origin) {
                $originId = $origin->ProductID;
                $editable = $selectedOriginIds->contains($originId) ? 1 : 0;

                $np = $origin->replicate(['ProductID','OrderID','created_at','updated_at']);
                $np->OrderID    = $redoOrder->id;
                $np->redoOf     = $originId;
                $np->editable   = $editable;
                $np->created_at = now();
                $np->updated_at = now();
                $np->accepted = null;
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
                    $npgr->ProductID  = $np->ProductID;
                    $npgr->created_at = now();
                    $npgr->updated_at = now();
                    $npgr->save();
                }
            }

            // First redo on the base order → bump status (optional rule)
            if ($existingCount === 0 && $order->id === $baseOrder->id) {
                $order->forceFill(['status' => 1])->save();
            }
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
        $baseProductsQuery   = Product::with(['items.spec', 'remarks', 'deliveryBreakdowns', 'progress'])
            ->where('OrderID', $baseId);

        $allBaseProducts     = $baseProductsQuery->clone()->get(['ProductID', 'productName']);
        $affectedOriginIds   = $selectedCurrentIds->isNotEmpty()
            ? Product::whereIn('ProductID', $selectedCurrentIds)->pluck(DB::raw('COALESCE(redoOf, ProductID)'))->unique()
            : $allBaseProducts->pluck('ProductID');

        $affectedProducts    = $allBaseProducts->whereIn('ProductID', $affectedOriginIds);
        $productCount        = $affectedProducts->count();

        // ------- Business recipients (head-artist, head-salesperson, admin, boss) -------
        $businessRecipients = User::whereIn('role', ['head-artist', 'head-salesperson', 'admin', 'boss'])->get();

        // Role-aware order URL (point them to the redo order container)
        $orderUrlFor = function (User $u) use ($redoOrder, $baseOrder) {
            $orderId = $redoOrder?->id ?? $baseOrder?->id ?? 0;
            return match (strtolower($u->role)) {
                'artist', 'head-artist'            => url("/artist/orders/{$orderId}"),
                'salesperson', 'head-salesperson'  => url("/orders/{$orderId}"),
                'admin'                           => url("/admin/orders/{$orderId}"),
                'boss'                            => url("/boss/orders/{$orderId}"),
                default                           => url('/'),
            };
        };

        // One concise message for business roles
        // $reasonText   = trim((string)$request->input('reason','') . ' ' . (string)$request->input('reason_alt',''));
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
            foreach (['assigned_user_id', 'operator_user_id', 'printing_user_id', 'furnishing_user_id'] as $col) {
                if (isset($p->{$col}) && $p->{$col}) {
                    $candidateUserId = (int)$p->{$col};
                    break;
                }
            }

            // Fallback: latest progress row with any of these columns
            if (!$candidateUserId && $p->relationLoaded('progress')) {
                $latest = $p->progress->sortByDesc('created_at')->first();
                if ($latest) {
                    foreach (['user_id', 'operator_id', 'assigned_to'] as $col) {
                        if (isset($latest->{$col}) && $latest->{$col}) {
                            $candidateUserId = (int)$latest->{$col};
                            break;
                        }
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
    protected function normalizeOrderNumber(string $orderNo): string
    {
        $n = ltrim($orderNo, '#');
        return preg_replace('/R\d*$/i', '', $n);
    }
}
