<?php

/*
 * Order items may only use materials from the materials list, saved with the
 * list's spelling; renaming a material updates the items that use it.
 */

use App\Models\Material;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function omp_user(string $role): User
{
    $user = User::create(['name' => "Test {$role}", 'email' => "{$role}@test.local", 'password' => bcrypt('x'), 'role' => $role, 'status' => 'active']);
    $user->forceFill(['email_verified_at' => now()])->save();

    return $user;
}

function omp_material(string $name, bool $active = true): Material
{
    $typeId = DB::table('material_types')->value('id')
        ?? DB::table('material_types')->insertGetId(['name' => 'Stickers / Films', 'created_at' => now(), 'updated_at' => now()]);

    return Material::create(['materialName' => $name, 'material_type_id' => $typeId, 'unitCost' => 0.005, 'active' => $active]);
}

/** Order with one product and one item using $itemMaterials. */
function omp_order(array $itemMaterials = ['PVC White Sticker'], ?User $artist = null, ?User $dataEntry = null): array
{
    $order = new Order();
    $order->forceFill([
        'orderDate' => now()->toDateString(), 'orderTitle' => 'Signage', 'orderStatus' => 'in_progress',
        'artist_id' => $artist?->id, 'data_entry_id' => $dataEntry?->id,
    ])->save();

    $product = new Product();
    $product->forceFill(['OrderID' => $order->id, 'productName' => 'Banner', 'totalQuantity' => 1])->save();

    $item = ProductItem::create(['ProductID' => $product->ProductID, 'itemName' => 'Front', 'quantity' => 1, 'material' => $itemMaterials]);

    return [$order, $product, $item];
}

function omp_save(User $user, string $url, Order $order, Product $product, ProductItem $item, array $materials)
{
    return test()->actingAs($user)->putJson(sprintf($url, $order->id), [
        'design_confirmed' => 0,
        'is_draft'         => 1,
        'products'         => [[
            'product_id' => $product->ProductID,
            'items'      => [['id' => $item->ItemID, 'itemName' => 'Front', 'quantity' => 1, 'material' => $materials]],
        ]],
    ]);
}

$editors = [
    'artist'     => ['head-artist', '/artist/orders/%d'],
    'admin'      => ['admin', '/admin/orders/%d'],
    'boss'       => ['boss', '/boss/orders/%d'],
    'data-entry' => ['data-entry', '/data-entry/orders/%d'],
];

foreach ($editors as $key => [$role, $url]) {
    test("{$key} edit rejects a material that is not in the list", function () use ($role, $url) {
        omp_material('PVC White Sticker');
        $user = omp_user($role);
        [$order, $product, $item] = omp_order(['PVC White Sticker'], null, $role === 'data-entry' ? $user : null);

        omp_save($user, $url, $order, $product, $item, ['PVC White Sticker', 'Made Up Vinyl'])
            ->assertStatus(422)
            ->assertJsonFragment(['"Made Up Vinyl" is not in the materials list. Ask an admin to add it first.']);

        expect($item->fresh()->material)->toBe(['PVC White Sticker']);
    });

    test("{$key} edit saves listed materials with the list's spelling", function () use ($role, $url) {
        omp_material('PVC White Sticker');
        omp_material('UV Laminate');
        $user = omp_user($role);
        [$order, $product, $item] = omp_order([], null, $role === 'data-entry' ? $user : null);

        omp_save($user, $url, $order, $product, $item, ['  pvc white STICKER ', 'uv laminate', 'UV Laminate'])
            ->assertOk()->assertJson(['ok' => true]);

        expect($item->fresh()->material)->toBe(['PVC White Sticker', 'UV Laminate']);
    });
}

test('a material already saved on the order stays allowed after it leaves the list', function () {
    omp_material('PVC White Sticker');
    $user = omp_user('head-artist');
    [$order, $product, $item] = omp_order(['Discontinued Vinyl']);

    omp_save($user, '/artist/orders/%d', $order, $product, $item, ['Discontinued Vinyl', 'PVC White Sticker'])
        ->assertOk();

    expect($item->fresh()->material)->toBe(['Discontinued Vinyl', 'PVC White Sticker']);
});

test("another order's unlisted material is not allowed", function () {
    $user = omp_user('head-artist');
    omp_order(['Discontinued Vinyl']);                 // some other order
    [$order, $product, $item] = omp_order([]);

    omp_save($user, '/artist/orders/%d', $order, $product, $item, ['Discontinued Vinyl'])
        ->assertStatus(422);
});

test('inactive materials are still accepted by the server', function () {
    omp_material('Old Board', active: false);
    $user = omp_user('head-artist');
    [$order, $product, $item] = omp_order([]);

    omp_save($user, '/artist/orders/%d', $order, $product, $item, ['Old Board'])->assertOk();
});

test('renaming a material renames it on the items that use it, and only those', function () {
    $material = omp_material('PVC White Sticker');
    [, , $uses]    = omp_order(['pvc white sticker ', 'UV Laminate']);
    [, , $both]    = omp_order(['PVC White Sticker', 'PVC Glossy Sticker']);
    [, , $unrelated] = omp_order(['Art Card']);
    $stamp = DB::table('product_items')->where('ItemID', $uses->ItemID)->value('updated_at');

    $this->travel(5)->minutes();
    $material->update(['materialName' => 'PVC Glossy Sticker']);

    expect($uses->fresh()->material)->toBe(['PVC Glossy Sticker', 'UV Laminate'])
        ->and($both->fresh()->material)->toBe(['PVC Glossy Sticker'])      // no duplicate
        ->and($unrelated->fresh()->material)->toBe(['Art Card'])
        ->and(DB::table('product_items')->where('ItemID', $uses->ItemID)->value('updated_at'))->toBe($stamp);
});

test('renaming through the admin Material Data page renames items too', function () {
    $material = omp_material('PVC White Sticker');
    [, , $item] = omp_order(['PVC White Sticker']);

    $this->actingAs(omp_user('admin'))
        ->putJson("/admin/materials/update/{$material->MaterialID}", ['qeName' => 'PVC Matte Sticker', 'qeNew' => 0.006])
        ->assertOk();

    expect($item->fresh()->material)->toBe(['PVC Matte Sticker']);
});
