<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\DeliveryBreakdown;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\ProductRemark;
use App\Models\Specification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Auto-fill: copy an earlier order into a new draft that the user adjusts
 * before submitting. Copies the client details, products, items (sizes,
 * materials, specs), remarks and delivery details. Does not copy the order
 * number, status, dates (deadline and delivery dates are left for the user),
 * attachments, acceptance/progress or stock tracking. Nothing touches stock
 * until the new order is submitted.
 */
class OrderCopyService
{
    /** Whether $user may copy $order (same rules as viewing it for their role). */
    public static function canCopy(User $user, Order $order): bool
    {
        return match ($user->role) {
            Role::Admin->value, Role::Boss->value,
            Role::HeadSalesperson->value, Role::HeadArtist->value => true,
            Role::Salesperson->value => (int) ($order->lead?->salesperson_id ?? $order->salesperson_id) === (int) $user->id,
            Role::Artist->value      => (int) $order->artist_id === (int) $user->id,
            default                  => false,
        };
    }

    public function copy(Order $source, User $actor): Order
    {
        return DB::transaction(function () use ($source, $actor) {
            $order = new Order();
            $order->forceFill(array_merge([
                'lead_id'        => $source->lead_id,
                'leadName'       => $source->leadName,
                'leadPhone'      => $source->leadPhone,
                'leadEmail'      => $source->leadEmail,
                'companyName'    => $source->companyName,
                'salesperson_id' => $source->salesperson_id,
                'orderTitle'     => $source->orderTitle,
                'orderDetail'    => $source->orderDetail,
                'orderDate'      => now()->toDateString(),
                'deadline'       => null,
                'approval'       => 0,
                'draft'          => 1,
                'submit'         => 0,
                'pending'        => 0,
                'status'         => 0,
                'redo'           => null,
                'data_entry_id'  => null,
            ], $this->ownership($source, $actor)));
            $order->save(); // the model's created hook assigns the order number

            $products = Product::with(['items.spec', 'remarks', 'deliveryBreakdowns'])
                ->where('OrderID', $source->id)
                ->orderBy('ProductID')
                ->get();

            foreach ($products as $p) {
                $np = new Product();
                $np->forceFill([
                    'OrderID'        => $order->id,
                    'productName'    => $p->productName,
                    'totalQuantity'  => $p->totalQuantity,
                    'materialRemark' => $p->materialRemark,
                    'productRemark'  => $p->productRemark,
                    'taskType'       => $p->taskType,
                    'permit'         => $p->permit,
                    'packaging'      => $p->packaging,
                    'status'         => 'in_progress',
                    'editable'       => 1,
                ])->save();

                foreach ($p->items as $it) {
                    $ni = $it->replicate(['ItemID', 'ProductID', 'created_at', 'updated_at']);
                    $ni->ProductID = $np->ProductID;
                    $ni->save();

                    if ($it->spec) {
                        Specification::create([
                            'ItemID'     => $ni->ItemID,
                            'lamination' => $it->spec->lamination,
                            'printer'    => $it->spec->printer,
                            'cutter'     => $it->spec->cutter,
                        ]);
                    }
                }

                foreach ($p->remarks as $rm) {
                    ProductRemark::create([
                        'ProductID' => $np->ProductID,
                        'operation' => $rm->operation,
                        'remark'    => $rm->remark,
                        'user_id'   => $rm->user_id,
                    ]);
                }

                foreach ($p->deliveryBreakdowns as $d) {
                    DeliveryBreakdown::create([
                        'ProductID'            => $np->ProductID,
                        'method'               => $d->method,
                        'location'             => $d->location,
                        'quantity'             => $d->quantity,
                        'deliver_install_type' => $d->deliver_install_type,
                        'outsource_cost'       => $d->outsource_cost,
                        // date and time are left for the user: the old ones are in the past
                    ]);
                }
            }

            return $order;
        });
    }

    /** Owner and starting status, matching what each role's "create order" sets. */
    private function ownership(Order $source, User $actor): array
    {
        return match ($actor->role) {
            Role::Salesperson->value, Role::HeadSalesperson->value => [
                // A sales draft: editable on the sales edit page until it is sent for assignment.
                'salesperson_id' => $actor->hasRole(Role::Salesperson) ? $actor->id : $source->salesperson_id,
                'artist_id'      => null,
                'orderStatus'    => 'in_progress',
            ],
            Role::Artist->value, Role::HeadArtist->value => [
                'artist_id'   => $actor->id,
                'orderStatus' => 'in_progress',
            ],
            default => $this->keepArtist($source), // admin, boss
        };
    }

    /** Admin/boss: keep the source order's artist when still active, like their create form's assignment. */
    private function keepArtist(Order $source): array
    {
        $artist = $source->artist_id
            ? User::whereKey($source->artist_id)->whereIn('role', [Role::Artist->value, Role::HeadArtist->value])->active()->first()
            : null;

        if (! $artist) {
            return ['artist_id' => null, 'orderStatus' => 'to_assign'];
        }

        return $artist->hasRole(Role::HeadArtist)
            ? ['artist_id' => $artist->id, 'orderStatus' => 'in_progress']
            : ['artist_id' => $artist->id, 'orderStatus' => 'assigned', 'pending' => 1];
    }
}
