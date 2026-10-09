<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Delivery orders: one per delivery location of an order.
 *
 * sync() groups the order's delivery rows (products not rejected, location
 * set) by location and creates or updates one DO per location. A DO keeps
 * its number when updated; a DO whose location no longer appears is marked
 * cancelled (kept for the record) and comes back if the location returns.
 */
class DeliveryOrderService
{
    // ---- who may do what ------------------------------------------------

    public static function canView(User $user, Order $order): bool
    {
        return match ($user->role) {
            Role::Admin->value, Role::Boss->value, Role::HeadSalesperson->value,
            Role::OperationsDispatchControl->value, Role::OperationsDeliveryInstallation->value => true,
            Role::Salesperson->value => self::ownsLead($user, $order),
            default => false,
        };
    }

    /** Print, download the PDF, and confirm delivery (client answer no. 7): not sales. */
    public static function canPrint(User $user): bool
    {
        return in_array($user->role, [
            Role::Admin->value, Role::Boss->value,
            Role::OperationsDispatchControl->value, Role::OperationsDeliveryInstallation->value,
        ], true);
    }

    public static function canConfirmDelivery(User $user): bool
    {
        return self::canPrint($user);
    }

    public static function canGenerate(User $user): bool
    {
        return in_array($user->role, [Role::Admin->value, Role::Boss->value, Role::OperationsDispatchControl->value], true);
    }

    public static function canEmail(User $user, Order $order): bool
    {
        return match ($user->role) {
            Role::Admin->value, Role::Boss->value, Role::HeadSalesperson->value, Role::OperationsDispatchControl->value => true,
            Role::Salesperson->value => self::ownsLead($user, $order),
            default => false,
        };
    }

    private static function ownsLead(User $user, Order $order): bool
    {
        return (int) ($order->lead?->salesperson_id ?? $order->salesperson_id) === (int) $user->id;
    }

    // ---- building ---------------------------------------------------------

    public static function locationKey(?string $location): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $location)));
    }

    /** Delivery orders can be created once the order has been submitted. */
    public function canBuildFor(Order $order): bool
    {
        return (int) $order->submit === 1 || strtolower((string) $order->orderStatus) === 'completed';
    }

    /**
     * What the DOs should contain now: location_key => [location, lines[]].
     *
     * @return Collection<string, array{location: string, lines: array<int, array>}>
     */
    public function plan(Order $order): Collection
    {
        $rows = DB::table('delivery_breakdowns as d')
            ->join('products as p', 'p.ProductID', '=', 'd.ProductID')
            ->where('p.OrderID', $order->id)
            ->where(fn ($q) => $q->whereNull('p.status')->orWhere('p.status', '!=', 'rejected'))
            ->whereNotNull('d.location')
            ->whereRaw("TRIM(d.location) <> ''")
            ->orderBy('p.ProductID')->orderBy('d.BreakdownID')
            ->get(['d.BreakdownID', 'd.ProductID', 'd.method', 'd.quantity', 'd.date', 'd.time', 'd.location', 'p.productName']);

        return $rows->groupBy(fn ($r) => self::locationKey($r->location))
            ->map(fn (Collection $group) => [
                'location' => trim(preg_replace('/\s+/u', ' ', $group->first()->location)),
                'lines'    => $group->map(fn ($r) => [
                    'product_id'            => (int) $r->ProductID,
                    'delivery_breakdown_id' => (int) $r->BreakdownID,
                    'description'           => (string) ($r->productName ?: 'Product #' . $r->ProductID),
                    'method'                => $r->method,
                    'quantity'              => (int) $r->quantity,
                    'delivery_date'         => $r->date,
                    'delivery_time'         => $r->time,
                ])->values()->all(),
            ]);
    }

    /** True when the saved DOs no longer match the order's delivery rows. */
    public function isOutOfDate(Order $order): bool
    {
        $delivered = DeliveryOrder::where('order_id', $order->id)->whereNotNull('delivered_at')->pluck('location_key')->all();
        $plan = $this->plan($order)->except($delivered)->map(fn ($g) => $this->hash($g['lines']));
        $saved = DeliveryOrder::where('order_id', $order->id)->where('status', DeliveryOrder::STATUS_ISSUED)
            ->whereNull('delivered_at')
            ->pluck('content_hash', 'location_key');

        return $plan->sortKeys()->all() !== $saved->sortKeys()->all();
    }

    /** Create / update / cancel the order's DOs. Returns the order's issued DOs. */
    public function sync(Order $order, ?int $userId = null): Collection
    {
        return DB::transaction(function () use ($order, $userId) {
            DB::table('orders')->where('id', $order->id)->lockForUpdate()->first(['id']);

            $plan = $this->plan($order);
            $existing = DeliveryOrder::where('order_id', $order->id)->get()->keyBy('location_key');

            foreach ($plan as $key => $group) {
                $lines = $group['lines'];
                $first = collect($lines)->sortBy(fn ($l) => ($l['delivery_date'] ?? '9999-12-31') . ' ' . ($l['delivery_time'] ?? ''))->first();

                $attrs = [
                    'location'      => $group['location'],
                    'methods'       => collect($lines)->pluck('method')->filter()->unique()->implode(', ') ?: null,
                    'delivery_date' => $first['delivery_date'] ?? null,
                    'delivery_time' => $first['delivery_time'] ?? null,
                    'status'        => DeliveryOrder::STATUS_ISSUED,
                    'content_hash'  => $this->hash($lines),
                ];

                /** @var DeliveryOrder|null $do */
                $do = $existing->get($key);
                if ($do?->isDelivered()) {
                    continue; // frozen once delivered
                }
                if ($do && $do->content_hash === $attrs['content_hash'] && ! $do->isCancelled()
                    && $do->location === $attrs['location']) {
                    continue; // unchanged
                }

                if ($do) {
                    $do->update($attrs);
                    $do->lines()->delete();
                    $do->log('updated', $userId);
                } else {
                    $do = DeliveryOrder::create($attrs + ['order_id' => $order->id, 'location_key' => $key, 'created_by' => $userId]);
                    $do->log('created', $userId);
                }
                $do->lines()->createMany($lines);
            }

            // Locations that are gone: keep the DO for the record, but cancelled.
            $gone = DeliveryOrder::where('order_id', $order->id)
                ->whereNotIn('location_key', $plan->keys()->all() ?: [''])
                ->where('status', DeliveryOrder::STATUS_ISSUED)
                ->whereNull('delivered_at')
                ->get();
            foreach ($gone as $do) {
                $do->update(['status' => DeliveryOrder::STATUS_CANCELLED]);
                $do->log('cancelled', $userId);
            }

            return DeliveryOrder::where('order_id', $order->id)->where('status', DeliveryOrder::STATUS_ISSUED)
                ->orderBy('id')->get();
        });
    }

    /** Fingerprint of a DO's lines (what the client sees). */
    private function hash(array $lines): string
    {
        $norm = array_map(fn ($l) => [
            $l['product_id'], $l['description'], $l['method'], (int) $l['quantity'],
            $l['delivery_date'] ? substr((string) $l['delivery_date'], 0, 10) : null,
            $l['delivery_time'] ? substr((string) $l['delivery_time'], 0, 8) : null,
        ], $lines);

        return hash('sha256', json_encode($norm));
    }
}
