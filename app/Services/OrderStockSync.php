<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the stock used by an order in line with the order itself.
 *
 * It works out what the order should be using right now and compares that
 * with what the stock log already holds for the order, then records only the
 * difference (a deduction or a return). Running it again changes nothing, so
 * it is safe to call after any change to an order, and the nightly
 * inventory:reconcile command runs it over every tracked order.
 *
 * An order uses stock while it is tracked, submitted and not rejected: for
 * every item of a product that is not rejected, and every material on the
 * item, width × height × quantity square inches ("piece" items: quantity by
 * count). Bleed is not counted, same as the costing report.
 */
class OrderStockSync
{
    public function __construct(private MaterialStockService $stock)
    {
    }

    /**
     * @param  bool  $markTracked  set on submit: starts tracking an order submitted after go-live
     * @param  bool  $release      give everything back (call before deleting the order)
     */
    public function syncOrder(Order|int $order, bool $markTracked = false, bool $release = false, ?string $note = null): void
    {
        $orderId = $order instanceof Order ? $order->getKey() : $order;

        DB::transaction(function () use ($orderId, $markTracked, $release, $note) {
            // Lock the order so two saves of the same order cannot both record the same difference.
            $row = DB::table('orders')->where('id', $orderId)->lockForUpdate()
                ->first(['id', 'order_number', 'submit', 'orderStatus', 'stock_tracked']);
            if (! $row) {
                return;
            }

            if ($markTracked && ! $row->stock_tracked && (int) $row->submit === 1) {
                DB::table('orders')->where('id', $orderId)->update(['stock_tracked' => 1]);
                $row->stock_tracked = 1;
            }
            if (! $row->stock_tracked) {
                return;
            }

            $wanted = $release ? [] : $this->wantedUsage($row);
            $booked = $this->bookedUsage($orderId);

            foreach (array_keys($wanted + $booked) as $materialId) {
                $diffQty = round(($wanted[$materialId]['qty'] ?? 0) - ($booked[$materialId]['qty'] ?? 0), 2);
                $diffVol = round(($wanted[$materialId]['vol'] ?? 0) - ($booked[$materialId]['vol'] ?? 0), 2);

                $this->book((int) $materialId, $orderId, (string) $row->order_number, $diffQty, $diffVol, $note);
            }
        });
    }

    /**
     * Stock the order should be using now: material_id => ['qty' => count, 'vol' => sq inch].
     *
     * @return array<int, array{qty: float, vol: float}>
     */
    public function wantedUsage(object $order): array
    {
        if ((int) $order->submit !== 1 || strtolower((string) $order->orderStatus) === 'rejected') {
            return [];
        }

        $items = DB::table('product_items as pi')
            ->join('products as p', 'p.ProductID', '=', 'pi.ProductID')
            ->where('p.OrderID', $order->id)
            ->where(fn ($q) => $q->whereNull('p.status')->orWhere('p.status', '!=', 'rejected'))
            ->whereNotNull('pi.material')
            ->get(['pi.quantity', 'pi.sizeWidth', 'pi.sizeHeight', 'pi.sizeUnit', 'pi.material']);

        $ids = $this->materialIdsByName();
        $usage = [];

        foreach ($items as $item) {
            $qty = (float) $item->quantity;
            if ($qty <= 0) {
                continue;
            }

            $isPiece = strtolower(trim((string) $item->sizeUnit)) === 'piece';
            $area = $isPiece ? 0.0 : $this->toInches($item->sizeWidth, $item->sizeUnit) * $this->toInches($item->sizeHeight, $item->sizeUnit);

            foreach ((array) json_decode((string) $item->material, true) as $name) {
                $materialId = $ids[mb_strtolower(trim((string) $name))] ?? null;
                if (! $materialId) {
                    continue; // not in the materials list (older data): nothing to deduct
                }

                $usage[$materialId]['qty'] = ($usage[$materialId]['qty'] ?? 0) + ($isPiece ? $qty : 0);
                $usage[$materialId]['vol'] = ($usage[$materialId]['vol'] ?? 0) + $qty * $area;
            }
        }

        return $usage;
    }

    /** Stock already booked against the order in the log (deductions minus returns), as positive usage. */
    private function bookedUsage(int $orderId): array
    {
        return DB::table('material_stock_movements')
            ->where('order_id', $orderId)
            ->whereNotNull('material_id')
            ->whereIn('type', [StockMovementType::OrderDeduct->value, StockMovementType::OrderReturn->value])
            ->groupBy('material_id')
            ->selectRaw('material_id, -SUM(quantity_change) as qty, -SUM(volume_change) as vol')
            ->get()
            ->mapWithKeys(fn ($r) => [(int) $r->material_id => ['qty' => (float) $r->qty, 'vol' => (float) $r->vol]])
            ->all();
    }

    /** Record the difference; quantity and volume moving in opposite directions become two movements. */
    private function book(int $materialId, int $orderId, string $orderNumber, float $diffQty, float $diffVol, ?string $note): void
    {
        $parts = [];
        if ($diffQty > 0 || $diffVol > 0) {
            $parts[] = [StockMovementType::OrderDeduct, -max($diffQty, 0), -max($diffVol, 0)];
        }
        if ($diffQty < 0 || $diffVol < 0) {
            $parts[] = [StockMovementType::OrderReturn, -min($diffQty, 0), -min($diffVol, 0)];
        }

        foreach ($parts as [$type, $qtyChange, $volChange]) {
            $reason = trim("Order {$orderNumber}" . ($note ? " · {$note}" : ''));
            $this->stock->record($materialId, $type, $qtyChange, $volChange, $reason, auth()->id(), ['order_id' => $orderId]);
        }
    }

    /** lower-cased material name => MaterialID; an active material wins over an inactive one of the same name. */
    private function materialIdsByName(): array
    {
        $ids = [];
        foreach (DB::table('materials')->orderBy('active')->orderByDesc('MaterialID')->get(['MaterialID', 'materialName']) as $m) {
            $ids[mb_strtolower(trim((string) $m->materialName))] = (int) $m->MaterialID;
        }

        return $ids;
    }

    /** Same conversion as the costing report (BossDataManagementController); unknown units count as inches. */
    private function toInches($value, $unit): float
    {
        $v = (float) $value;
        if ($v <= 0) {
            return 0.0;
        }

        return match (strtolower(trim((string) $unit))) {
            'ft', 'foot', 'feet'                => $v * 12.0,
            'cm', 'centimeter', 'centimeters'   => $v * 0.3937007874,
            'mm', 'millimeter', 'millimeters'   => $v * 0.03937007874,
            'm', 'meter', 'meters'              => $v * 39.37007874,
            default                              => $v,
        };
    }
}
