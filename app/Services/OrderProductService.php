<?php

namespace App\Services;

use App\Models\DeliveryBreakdown;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\ProductRemark;
use App\Models\Specification;
use Illuminate\Support\Facades\DB;

/**
 * Saves the products of an order from the order edit form: the header product,
 * then per product its fields, items (with specifications), deliveries and
 * remarks. Shared by the artist, admin, boss and data-entry order editors.
 *
 * Call it inside the caller's DB::transaction().
 */
class OrderProductService
{
    /**
     * @param  array  $products       the posted products[] groups
     * @param  array  $header         the posted product[] header fields (name, qty_total, material)
     * @param  array  $deleteRemarks  remark ids posted at the top level (delete_remarks[])
     * @param  bool   $savePermit     whether products[*].permit is saved
     * @param  bool   $syncRemarks    whether products[*].remarks / delete_remarks are saved
     */
    public function syncProducts(
        Order $order,
        array $products,
        array $header,
        array $deleteRemarks,
        int $authorId,
        bool $savePermit = true,
        bool $syncRemarks = true,
    ): void {
        $postedProducts = collect($products)->values();
        $hasPerProductHeader = $postedProducts->contains(function ($g) {
            return is_array($g) && (
                array_key_exists('name', $g) ||
                array_key_exists('qty_total', $g) ||
                array_key_exists('material', $g)
            );
        });

        // ----- “Header” product (only if no per-product header present) -----
        if (!$hasPerProductHeader) {
            $hdr = $header;
            $productHdr = Product::where('OrderID', $order->id)->first()
                        ?: new Product(['OrderID' => $order->id]);

            if (array_key_exists('name', $hdr))      $productHdr->productName    = $hdr['name'];
            if (array_key_exists('qty_total', $hdr)) $productHdr->totalQuantity  = $hdr['qty_total'];
            if (array_key_exists('material', $hdr))  $productHdr->materialRemark = $hdr['material'];
            $productHdr->save();
        }

        // ----- Per-product payload -----
        foreach ($postedProducts as $group) {
            $pid = (int) ($group['product_id'] ?? 0);
            if (!$pid) continue;

            $productRow = Product::where('OrderID', $order->id)
                            ->where('ProductID', $pid)
                            ->first();
            if (!$productRow) continue;

            // Snapshot original acceptance flags directly from DB
            $originalFlags = DB::table('products')
                ->where('ProductID', $productRow->ProductID)
                ->select('accepted', 'installation_accepted')
                ->first();

            // --- per-product header fields (safe) ---
            if (array_key_exists('name', $group)) {
                $productRow->productName = $group['name'] === '' ? null : $group['name'];
            }
            if (array_key_exists('qty_total', $group)) {
                $q = $group['qty_total'];
                $productRow->totalQuantity = ($q === '' || $q === null) ? null : (int) $q;
            }
            if (array_key_exists('material', $group)) {
                $productRow->materialRemark = $group['material'] === '' ? null : $group['material'];
            }
            if ($savePermit && array_key_exists('permit', $group)) {
                $raw = $group['permit'];
                $productRow->permit = ($raw === '' || $raw === null) ? null : (int) $raw;
            }

            $productRow->save();

            $this->syncItems($productRow, $group);
            $this->syncDeliveries($productRow, $group);

            if ($syncRemarks) {
                $this->syncRemarks($order, $productRow, $group, $deleteRemarks, $authorId);
            }

            // Force-restore original accepted flags at the very end
            if ($originalFlags) {
                DB::table('products')
                    ->where('ProductID', $productRow->ProductID)
                    ->update([
                        'accepted'             => $originalFlags->accepted,
                        'installation_accepted'=> $originalFlags->installation_accepted,
                    ]);
            }

            $productRow->syncTaskTypeFromSpecs();
        }
    }

    private function syncItems(Product $productRow, array $group): void
    {
        $keepItemIds = [];

        collect($group['items'] ?? [])
            ->filter(fn($row) => is_array($row))
            ->each(function ($row) use ($productRow, &$keepItemIds) {

                $isEmpty = collect($row)->except(['id','material'])
                        ->filter(fn($v) => $v !== '' && $v !== null)->isEmpty();
                if ($isEmpty) return;

                $item = null;
                if (!empty($row['id'])) {
                    $item = ProductItem::where('ItemID', (int)$row['id'])
                            ->where('ProductID', $productRow->ProductID)
                            ->first();
                }
                if (!$item) {
                    $item = new ProductItem();
                    $item->ProductID = $productRow->ProductID;
                }

                foreach ([
                    'itemName','quantity',
                    'sizeWidth','sizeHeight','sizeUnit',
                    'bleedTop','bleedBottom','bleedLeft','bleedRight', 'bleedUnit',
                    'finishing'
                ] as $k) {
                    if (array_key_exists($k, $row)) {
                        $item->{$k} = $row[$k] === '' ? null : $row[$k];
                    }
                }

                if (array_key_exists('material', $row)) {
                    $vals = is_array($row['material'])
                        ? $row['material']
                        : array_map('trim', explode(',', (string)$row['material']));
                    $vals = array_values(array_filter($vals, fn($v) => $v !== ''));

                    if (method_exists($item, 'hasCast') && $item->hasCast('material', 'array')) {
                        $item->material = $vals ?: null;
                    } else {
                        $item->material = $vals ? json_encode($vals) : null;
                    }
                }

                // prime_centre (boolean yes/no)
                if (array_key_exists('prime_centre', $row)) {
                    $v = strtolower((string)$row['prime_centre']);
                    $item->prime_centre = in_array($v, ['1','true','on','yes'], true) ? 1 : 0;
                }

                $item->save();
                $keepItemIds[] = $item->ItemID;

                // ---- Spec (optional) ----
                if (array_key_exists('lamination',$row) ||
                    array_key_exists('printer',$row)    ||
                    array_key_exists('cutter',$row) ) {
                    $spec = Specification::firstOrNew(['ItemID' => $item->ItemID]);
                    $spec->lamination = $row['lamination'] ?? null;
                    $spec->printer    = $row['printer']    ?? null;
                    $spec->cutter     = $row['cutter']     ?? null;
                    $spec->save();
                }

            });

        // delete items not posted (including “all removed” case)
        if (array_key_exists('items', $group)) {
            ProductItem::where('ProductID', $productRow->ProductID)
                ->when(count($keepItemIds) > 0, fn($q) => $q->whereNotIn('ItemID', $keepItemIds))
                ->when(count($keepItemIds) === 0, fn($q) => $q) // delete all
                ->delete();
        }
    }

    private function syncDeliveries(Product $productRow, array $group): void
    {
        $deliveries = collect($group['deliveries'] ?? [])
            ->filter(fn($row) => is_array($row))
            ->map(function ($row) {
                $row  = array_change_key_case($row, CASE_LOWER);

                // split/normalize datetime
                $date = $row['date'] ?? null;
                $time = $row['time'] ?? null;
                if ((!$date || !$time) && !empty($row['datetime'])) {
                    try {
                        $dt   = \Carbon\Carbon::parse($row['datetime']);
                        $date = $date ?: $dt->toDateString();
                        $time = $time ?: $dt->format('H:i:s');
                    } catch (\Throwable $e) {}
                }

                // normalize fields
                $method      = trim((string)($row['method'] ?? ''));
                $location    = trim((string)($row['location'] ?? ''));
                $qty         = $row['quantity'] ?? null;

                // installation type & cost (normalized)
                $installType = strtolower(trim((string)($row['deliver_install_type'] ?? '')));
                $installType = $installType !== '' ? $installType : null;

                $costRaw = $row['outsource_cost'] ?? null;
                $cost    = ($costRaw === '' || $costRaw === null) ? null : (float)$costRaw;

                // empty row guard
                if ($method === '' && $location === '' &&
                    ($qty === null || $qty === '') && !$date && !$time) {
                    return null;
                }

                return [
                    'id'                   => isset($row['id']) ? (int)$row['id'] : null,
                    'method'               => $method !== '' ? $method : null,
                    'location'             => $location !== '' ? $location : null,
                    'quantity'             => (int)($qty ?? 0),
                    'date'                 => $date ?: null,
                    'time'                 => $time ?: null,
                    'deliver_install_type' => $installType,
                    'outsource_cost'       => $cost,
                ];
            })
            ->filter()
            ->values();

        $keepDeliveryIds = [];
        foreach ($deliveries as $d) {
            $bd = null;
            if (!empty($d['id'])) {
                $bd = DeliveryBreakdown::where('BreakdownID', $d['id'])
                    ->where('ProductID', $productRow->ProductID)
                    ->first();
            }
            if (!$bd) {
                $bd = new DeliveryBreakdown();
                $bd->ProductID = $productRow->ProductID;
            }
            $bd->method   = $d['method'];
            $bd->location = $d['location'];
            $bd->quantity = $d['quantity'];
            $bd->date     = $d['date'];
            $bd->time     = $d['time'];
            $bd->deliver_install_type = $d['deliver_install_type'] ?? null;
            $bd->outsource_cost       = array_key_exists('outsource_cost', $d) && $d['outsource_cost'] !== ''
                                        ? (float) $d['outsource_cost']
                                        : null;
            $bd->save();

            $keepDeliveryIds[] = $bd->BreakdownID;
        }

        if (array_key_exists('deliveries', $group)) {
            DeliveryBreakdown::where('ProductID', $productRow->ProductID)
                ->when(count($keepDeliveryIds) > 0, fn($q) => $q->whereNotIn('BreakdownID', $keepDeliveryIds))
                ->when(count($keepDeliveryIds) === 0, fn($q) => $q)
                ->delete();
        }
    }

    private function syncRemarks(Order $order, Product $productRow, array $group, array $deleteRemarks, int $authorId): void
    {
        $keepRemarkIds = [];

        foreach (collect($group['remarks'] ?? [])->filter(fn ($v) => is_array($v)) as $row) {
            $op  = strtolower(trim((string)($row['operation'] ?? '')));
            $txt = trim((string)($row['remark'] ?? ''));

            // skip empty rows
            if ($op === '' && $txt === '') {
                continue;
            }

            // block "artist" when no artist assigned
            if ($op === 'artist' && empty($order->artist_id)) {
                continue;
            }

            // normalize to null / canonical values
            $newOp  = $op ?: null;
            $newTxt = $txt ?: null;

            $remark = null;
            if (!empty($row['id'])) {
                $remark = ProductRemark::where('RemarkID', (int)$row['id'])
                    ->where('ProductID', $productRow->ProductID)
                    ->first();
            }

            if (!$remark) {
                // NEW REMARK → set creator
                $remark = new ProductRemark();
                $remark->ProductID = $productRow->ProductID;
                $remark->operation = $newOp;
                $remark->remark    = $newTxt;
                $remark->user_id   = $authorId;     // creator only on create
                $remark->save();
            } else {
                // EXISTING REMARK → only change user_id if content changed
                $dirty = false;

                if ($remark->operation !== $newOp) {
                    $remark->operation = $newOp;
                    $dirty = true;
                }
                if ($remark->remark !== $newTxt) {
                    $remark->remark = $newTxt;
                    $dirty = true;
                }

                if ($dirty) {
                    // content changed → attribute the edit to current user
                    $remark->user_id = $authorId;
                    $remark->save();
                }
                // if not dirty, leave user_id (creator) untouched
            }

            $keepRemarkIds[] = $remark->RemarkID;
        }

        $toDelete = collect($group['delete_remarks'] ?? [])
            ->merge($deleteRemarks)
            ->map(fn ($id) => (int)$id)
            ->filter();

        if ($toDelete->isNotEmpty()) {
            ProductRemark::where('ProductID', $productRow->ProductID)
                ->whereIn('RemarkID', $toDelete)
                ->delete();
        }

        if (array_key_exists('remarks', $group)) {
            ProductRemark::where('ProductID', $productRow->ProductID)
                ->when(count($keepRemarkIds) > 0, fn($q) => $q->whereNotIn('RemarkID', $keepRemarkIds))
                ->delete();
        }
    }
}
