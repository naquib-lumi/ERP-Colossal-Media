<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Material;
use App\Models\MaterialStockMovement;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Materials an order needs, taken from its order items, with current stock,
 * and the manual stock deductions that were linked to it. Read-only: stock is
 * never deducted automatically.
 */
class OrderMaterialsController extends Controller
{
    public static function canView(User $user, Order $order): bool
    {
        return match ($user->role) {
            Role::Admin->value, Role::Boss->value, Role::HeadSalesperson->value,
            Role::Artist->value, Role::HeadArtist->value, Role::DataEntry->value,
            Role::OperationsPrinting->value, Role::OperationsFurnishing->value => true,
            Role::Salesperson->value => (int) ($order->lead?->salesperson_id ?? $order->salesperson_id) === (int) $user->id,
            default => false,
        };
    }

    public function show(Request $request, Order $order)
    {
        abort_unless(self::canView($request->user(), $order), 403);

        $order->load(['products' => fn ($q) => $q->orderBy('ProductID'), 'products.items' => fn ($q) => $q->orderBy('ItemID')]);

        $known = Material::get(['MaterialID', 'materialName', 'stock_quantity', 'quantity_unit', 'low_stock_quantity'])
            ->keyBy(fn (Material $m) => mb_strtolower(trim($m->materialName)));

        // One row per material: how many items and pieces use it, and on which products.
        $needs = [];
        foreach ($order->products as $product) {
            foreach ($product->items as $item) {
                foreach ((array) $item->material as $name) {
                    if (! is_string($name) || trim($name) === '') {
                        continue;
                    }
                    $key = mb_strtolower(trim($name));
                    $needs[$key] ??= ['name' => trim($name), 'material' => $known->get($key), 'items' => 0, 'pieces' => 0, 'products' => []];
                    $needs[$key]['items']++;
                    $needs[$key]['pieces'] += (int) $item->quantity;
                    $needs[$key]['products'][$product->ProductID] = $product->productName;
                }
            }
        }
        ksort($needs);

        return view('orders.materials', [
            'order'      => $order,
            'needs'      => array_values($needs),
            'deductions' => MaterialStockMovement::with('user:id,name')->where('order_id', $order->id)->latest('id')->get(),
        ]);
    }
}
