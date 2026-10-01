<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Order;
use App\Services\OrderCopyService;
use Illuminate\Http\Request;

/** Auto-fill: "Copy as new order" from an order's page, then adjust it in the edit page. */
class OrderCopyController extends Controller
{
    public function store(Request $request, Order $order, OrderCopyService $copier)
    {
        $user = $request->user();
        abort_unless(OrderCopyService::canCopy($user, $order), 403);

        $copy = $copier->copy($order, $user);

        $editRoute = match ($user->role) {
            Role::Salesperson->value, Role::HeadSalesperson->value => route('orders.edit', $copy->id),
            Role::Artist->value, Role::HeadArtist->value           => route('artist.orders.edit', $copy->id),
            Role::Boss->value                                      => route('boss.orders.edit', $copy->id),
            default                                                => route('admin.orders.edit', $copy->id),
        };

        return redirect($editRoute)->with(
            'success',
            "Copied from {$order->order_number} as new draft {$copy->order_number}. Set the deadline and delivery dates, adjust anything else, then save."
        );
    }
}
