<?php

/*
 * Characterisation tests for the order edit ("update") endpoints of the four
 * roles that save products, items, specifications, deliveries and remarks.
 *
 * Each case saves the same edit through one endpoint and compares the
 * resulting database state with a snapshot in __snapshots__/. The snapshots
 * were recorded from the code before the save logic was moved into
 * App\Services\OrderProductService, so they pin down existing behaviour.
 * Delete a snapshot file only when a behaviour change is intended.
 */

use App\Models\DeliveryBreakdown;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\ProductRemark;
use App\Models\Specification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function oput_user(string $role): User
{
    $user = User::create([
        'name'     => "Test {$role}",
        'email'    => "{$role}@test.local",
        'password' => bcrypt('secret'),
        'role'     => $role,
        'status'   => 'active',
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    return $user;
}

/** Order with two products; the first has two items (with specs), two deliveries and three remarks. */
function oput_fixture(): array
{
    $users = [];
    foreach (['admin', 'boss', 'head-artist', 'artist', 'data-entry', 'salesperson'] as $role) {
        $users[$role] = oput_user($role);
    }

    $lead = Lead::create([
        'salesperson_id' => $users['salesperson']->id,
        'company_name'   => 'Acme Sdn Bhd',
        'name'           => 'Ali',
        'phone'          => '0123456789',
        'status'         => 'new',
    ]);

    $order = new Order();
    $order->forceFill([
        'lead_id'        => $lead->id,
        'salesperson_id' => $users['salesperson']->id,
        'artist_id'      => $users['artist']->id,
        'data_entry_id'  => $users['data-entry']->id,
        'orderDate'      => '2026-09-01',
        'deadline'       => '2026-09-30',
        'orderTitle'     => 'Shop signage',
        'orderStatus'    => 'in_progress',
    ])->save();

    $p1 = new Product();
    $p1->forceFill([
        'OrderID' => $order->id, 'productName' => 'Banner', 'totalQuantity' => 10,
        'materialRemark' => 'Vinyl', 'permit' => 0, 'accepted' => 1, 'installation_accepted' => 1,
    ])->save();

    $p2 = new Product();
    $p2->forceFill([
        'OrderID' => $order->id, 'productName' => 'Untouched sticker', 'totalQuantity' => 5,
        'materialRemark' => 'Sticker',
    ])->save();

    $itemKeep = ProductItem::create([
        'ProductID' => $p1->ProductID, 'itemName' => 'Front', 'quantity' => 4,
        'sizeWidth' => 100, 'sizeHeight' => 50, 'sizeUnit' => 'cm', 'finishing' => 'Eyelet',
        'material' => ['PVC White Sticker'], 'prime_centre' => false,
    ]);
    Specification::create(['ItemID' => $itemKeep->ItemID, 'lamination' => 'Matte', 'printer' => 'Latex 3.2', 'cutter' => null]);

    $itemDrop = ProductItem::create([
        'ProductID' => $p1->ProductID, 'itemName' => 'Back', 'quantity' => 6,
        'sizeWidth' => 200, 'sizeHeight' => 80, 'sizeUnit' => 'cm', 'material' => ['Backlit Fabric'],
    ]);

    $delKeep = DeliveryBreakdown::create([
        'ProductID' => $p1->ProductID, 'method' => 'delivery', 'quantity' => 5,
        'date' => '2026-09-20', 'time' => '10:00:00', 'location' => 'Old address',
    ]);
    $delDrop = DeliveryBreakdown::create([
        'ProductID' => $p1->ProductID, 'method' => 'self pickup', 'quantity' => 5,
        'date' => '2026-09-21', 'time' => '11:00:00', 'location' => 'Shop',
    ]);

    $remEdit = ProductRemark::create(['ProductID' => $p1->ProductID, 'operation' => 'printing', 'remark' => 'Print glossy', 'user_id' => $users['salesperson']->id]);
    $remSame = ProductRemark::create(['ProductID' => $p1->ProductID, 'operation' => 'furnishing', 'remark' => 'Unchanged', 'user_id' => $users['salesperson']->id]);
    $remDel  = ProductRemark::create(['ProductID' => $p1->ProductID, 'operation' => 'installation', 'remark' => 'Delete me', 'user_id' => $users['salesperson']->id]);

    return compact('users', 'order', 'p1', 'p2', 'itemKeep', 'itemDrop', 'delKeep', 'delDrop', 'remEdit', 'remSame', 'remDel');
}

/** A per-product edit touching every part of the save logic. */
function oput_full_payload(array $f, bool $submit): array
{
    $payload = [
        'design_confirmed' => 1,
        'is_draft'         => $submit ? 0 : 1,
        'products' => [
            [
                'product_id' => $f['p1']->ProductID,
                'name'       => 'Banner v2',
                'qty_total'  => '12',
                'material'   => '',
                'permit'     => '1',
                'items' => [
                    [   // existing item: edited, material as array, spec changed
                        'id' => $f['itemKeep']->ItemID, 'itemName' => 'Front v2', 'quantity' => '8',
                        'sizeWidth' => '120', 'sizeHeight' => '60', 'sizeUnit' => 'cm',
                        'bleedTop' => '5', 'bleedBottom' => '5', 'bleedLeft' => '', 'bleedRight' => '', 'bleedUnit' => 'mm',
                        'finishing' => '', 'material' => ['PVC White Sticker', 'UV Laminate', ''],
                        'prime_centre' => 'yes', 'lamination' => 'Gloss', 'printer' => 'HT 1 RTR 3.2', 'cutter' => 'Laser 1 300W',
                    ],
                    [   // new item: material as comma string, no spec keys
                        'itemName' => 'Side', 'quantity' => '2', 'sizeWidth' => '30', 'sizeHeight' => '30',
                        'sizeUnit' => 'inch', 'material' => 'Art Card, Synthetic Paper', 'prime_centre' => '-',
                    ],
                    [   // blank row: ignored
                        'itemName' => '', 'quantity' => '', 'material' => ['x'],
                    ],
                    // itemDrop is not posted: deleted
                ],
                'deliveries' => [
                    [   // existing delivery updated from a datetime value
                        'id' => $f['delKeep']->BreakdownID, 'method' => ' delivery ', 'location' => ' New address ',
                        'quantity' => '7', 'datetime' => '2026-09-25 14:30', 'deliver_install_type' => ' Outsource ', 'outsource_cost' => '150.5',
                    ],
                    [   // new delivery with separate date and time
                        'method' => 'courier', 'location' => 'Branch KL', 'quantity' => '5',
                        'date' => '2026-09-26', 'time' => '09:15', 'outsource_cost' => '',
                    ],
                    [   // blank row: ignored
                        'method' => '', 'location' => '', 'quantity' => '',
                    ],
                    // delDrop is not posted: deleted
                ],
                'remarks' => [
                    ['id' => $f['remEdit']->RemarkID, 'operation' => 'Printing', 'remark' => 'Print matte'],
                    ['id' => $f['remSame']->RemarkID, 'operation' => 'furnishing', 'remark' => 'Unchanged'],
                    ['operation' => 'Delivery', 'remark' => 'Call before delivery'],
                    ['operation' => 'artist', 'remark' => 'Fix the logo'],
                    ['operation' => '', 'remark' => ''],
                ],
                'delete_remarks' => [$f['remDel']->RemarkID],
            ],
            [   // product that does not belong to this order: ignored
                'product_id' => 999999, 'name' => 'Hacked',
            ],
        ],
    ];

    if ($submit) {
        $payload['submit'] = 1;
    }

    return $payload;
}

/** Edit through the top "header" fields only (no per-product name/qty/material keys). */
function oput_header_payload(array $f): array
{
    return [
        'design_confirmed' => 0,
        'is_draft'         => 1,
        'product' => ['name' => 'Header name', 'qty_total' => '99', 'material' => 'Header material'],
        'products' => [
            [
                'product_id' => $f['p1']->ProductID,
                'items' => [
                    ['id' => $f['itemKeep']->ItemID, 'itemName' => 'Only item', 'quantity' => '1'],
                ],
            ],
        ],
    ];
}

/** Database state of the order, with ids replaced by stable positions. */
function oput_state(Order $order): array
{
    $users = DB::table('users')->pluck('role', 'id');

    $productRows = DB::table('products')->where('OrderID', $order->id)->orderBy('ProductID')->get();

    $products = $productRows->map(function ($p) use ($users) {
        $items = DB::table('product_items')->where('ProductID', $p->ProductID)->orderBy('ItemID')->get()
            ->map(function ($i) {
                $spec = DB::table('specifications')->where('ItemID', $i->ItemID)->first();

                return [
                    'itemName' => $i->itemName, 'quantity' => $i->quantity,
                    'sizeWidth' => $i->sizeWidth, 'sizeHeight' => $i->sizeHeight, 'sizeUnit' => $i->sizeUnit,
                    'bleedTop' => $i->bleedTop, 'bleedBottom' => $i->bleedBottom,
                    'bleedLeft' => $i->bleedLeft, 'bleedRight' => $i->bleedRight, 'bleedUnit' => $i->bleedUnit,
                    'finishing' => $i->finishing, 'material' => $i->material, 'prime_centre' => $i->prime_centre,
                    'spec' => $spec ? ['lamination' => $spec->lamination, 'printer' => $spec->printer, 'cutter' => $spec->cutter] : null,
                ];
            })->all();

        $deliveries = DB::table('delivery_breakdowns')->where('ProductID', $p->ProductID)->orderBy('BreakdownID')->get()
            ->map(fn ($d) => [
                'method' => $d->method, 'location' => $d->location, 'quantity' => $d->quantity,
                'date' => $d->date, 'time' => $d->time,
                'deliver_install_type' => $d->deliver_install_type, 'outsource_cost' => $d->outsource_cost,
            ])->all();

        $remarks = DB::table('product_remarks')->where('ProductID', $p->ProductID)->orderBy('RemarkID')->get()
            ->map(fn ($r) => [
                'operation' => $r->operation, 'remark' => $r->remark, 'by' => $users[$r->user_id] ?? null,
            ])->all();

        return [
            'productName' => $p->productName, 'totalQuantity' => $p->totalQuantity,
            'materialRemark' => $p->materialRemark, 'permit' => $p->permit,
            'accepted' => $p->accepted, 'installation_accepted' => $p->installation_accepted,
            'taskType' => $p->taskType, 'status' => $p->status, 'editable' => $p->editable,
            'items' => $items, 'deliveries' => $deliveries, 'remarks' => $remarks,
        ];
    })->all();

    $o = DB::table('orders')->where('id', $order->id)->first();

    return [
        'order' => [
            'orderStatus' => $o->orderStatus, 'submit' => $o->submit, 'draft' => $o->draft, 'approval' => $o->approval,
            'submitted_record' => DB::table('order_records')->where('order_id', $order->id)->whereNotNull('submitted_at')->exists(),
        ],
        'products' => $products,
    ];
}

function oput_match_snapshot(string $name, array $state): void
{
    $dir  = __DIR__ . '/__snapshots__';
    $file = "{$dir}/{$name}.json";
    $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

    if (! file_exists($file)) {
        @mkdir($dir, 0777, true);
        file_put_contents($file, $json);
        test()->markTestIncomplete("Snapshot recorded: {$name}.json — run the tests again to compare.");
    }

    expect($json)->toBe(file_get_contents($file));
}

$endpoints = [
    'artist'     => ['head-artist', '/artist/orders/%d'],
    'admin'      => ['admin', '/admin/orders/%d'],
    'boss'       => ['boss', '/boss/orders/%d'],
    'data-entry' => ['data-entry', '/data-entry/orders/%d'],
];

foreach ($endpoints as $key => [$role, $url]) {
    foreach (['draft' => false, 'submit' => true] as $mode => $submit) {
        test("{$key} update saves products, items, deliveries and remarks ({$mode})", function () use ($key, $role, $url, $mode, $submit) {
            $f = oput_fixture();

            $this->actingAs($f['users'][$role])
                ->putJson(sprintf($url, $f['order']->id), oput_full_payload($f, $submit))
                ->assertOk()
                ->assertJson(['ok' => true]);

            oput_match_snapshot("{$key}-full-{$mode}", oput_state($f['order']));
        });
    }

    test("{$key} update saves the header product fields", function () use ($key, $role, $url) {
        $f = oput_fixture();

        $this->actingAs($f['users'][$role])
            ->putJson(sprintf($url, $f['order']->id), oput_header_payload($f))
            ->assertOk()
            ->assertJson(['ok' => true]);

        oput_match_snapshot("{$key}-header", oput_state($f['order']));
    });
}
