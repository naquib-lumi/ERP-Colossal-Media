<?php

/*
 * Material stock follows an order through the real endpoints: submit, edit,
 * reject, pass to data entry, delete item, delete order, plus the nightly
 * inventory:reconcile safety net.
 */

use App\Models\Lead;
use App\Models\Material;
use App\Models\MaterialStockMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function osf_user(string $role): User
{
    $user = User::firstOrCreate(['email' => "{$role}@test.local"], ['name' => "Test {$role}", 'password' => bcrypt('x'), 'role' => $role, 'status' => 'active']);
    $user->forceFill(['email_verified_at' => now()])->save();

    return $user;
}

function osf_material(string $name): Material
{
    $typeId = DB::table('material_types')->value('id')
        ?? DB::table('material_types')->insertGetId(['name' => 'Stickers / Films', 'created_at' => now(), 'updated_at' => now()]);

    return Material::create(['materialName' => $name, 'material_type_id' => $typeId, 'unitCost' => 0.005]);
}

/** Draft order assigned to the artist: one product with two 10×10 inch items (Vinyl, Laminate). */
function osf_order(): array
{
    $artist = osf_user('artist');
    $sales  = osf_user('salesperson');
    $lead = Lead::create(['salesperson_id' => $sales->id, 'company_name' => 'Acme', 'name' => 'Ali', 'phone' => '012', 'status' => 'new']);

    $order = new Order();
    $order->forceFill([
        'lead_id' => $lead->id, 'salesperson_id' => $sales->id, 'artist_id' => $artist->id,
        'orderDate' => now()->toDateString(), 'deadline' => now()->addWeek()->toDateString(),
        'orderTitle' => 'Signage', 'orderStatus' => 'in_progress', 'draft' => 1, 'submit' => 0,
    ])->save();

    $product = new Product();
    $product->forceFill(['OrderID' => $order->id, 'productName' => 'Banner', 'totalQuantity' => 2, 'status' => 'in_progress', 'editable' => 1])->save();

    $a = ProductItem::create(['ProductID' => $product->ProductID, 'itemName' => 'Front', 'quantity' => 1, 'sizeWidth' => 10, 'sizeHeight' => 10, 'sizeUnit' => 'inch', 'material' => ['Vinyl']]);
    $b = ProductItem::create(['ProductID' => $product->ProductID, 'itemName' => 'Back', 'quantity' => 1, 'sizeWidth' => 10, 'sizeHeight' => 10, 'sizeUnit' => 'inch', 'material' => ['Laminate']]);

    return [$order->refresh(), $product, $a, $b, $artist];
}

/** Save through an order edit endpoint; $items overrides items (ids kept). */
function osf_save(User $user, string $url, Order $order, Product $product, array $items, bool $submit)
{
    return test()->actingAs($user)->putJson(sprintf($url, $order->id), array_filter([
        'design_confirmed' => 1,
        'is_draft'         => $submit ? 0 : 1,
        'submit'           => $submit ? 1 : null,
        'products'         => [['product_id' => $product->ProductID, 'items' => $items]],
    ], fn ($v) => $v !== null));
}

function osf_items(ProductItem $a, ProductItem $b, int $qtyA = 1, array $matA = ['Vinyl']): array
{
    return [
        ['id' => $a->ItemID, 'itemName' => 'Front', 'quantity' => $qtyA, 'sizeWidth' => 10, 'sizeHeight' => 10, 'sizeUnit' => 'inch', 'material' => $matA, 'printer' => 'Latex 3.2'],
        ['id' => $b->ItemID, 'itemName' => 'Back', 'quantity' => 1, 'sizeWidth' => 10, 'sizeHeight' => 10, 'sizeUnit' => 'inch', 'material' => ['Laminate']],
    ];
}

function osf_vol(Material $m): float
{
    return (float) $m->refresh()->stock_volume;
}

beforeEach(function () {
    $this->vinyl = osf_material('Vinyl');
    $this->lam   = osf_material('Laminate');
});

test('draft saves never touch stock; submit starts tracking and deducts', function () {
    [$order, $product, $a, $b, $artist] = osf_order();

    osf_save($artist, '/artist/orders/%d', $order, $product, osf_items($a, $b), submit: false)->assertOk();
    expect(MaterialStockMovement::count())->toBe(0)
        ->and((bool) $order->refresh()->stock_tracked)->toBeFalse();

    osf_save($artist, '/artist/orders/%d', $order, $product, osf_items($a, $b), submit: true)->assertOk();

    expect((bool) $order->refresh()->stock_tracked)->toBeTrue()
        ->and(osf_vol($this->vinyl))->toBe(-100.0)
        ->and(osf_vol($this->lam))->toBe(-100.0)
        ->and(MaterialStockMovement::first()->reason)->toContain('submitted')
        ->and(MaterialStockMovement::first()->user_id)->toBe($artist->id);
});

test('an edit after submit records only the change', function () {
    [$order, $product, $a, $b, $artist] = osf_order();
    osf_save($artist, '/artist/orders/%d', $order, $product, osf_items($a, $b), submit: true);

    osf_save(osf_user('admin'), '/admin/orders/%d', $order, $product, osf_items($a, $b, qtyA: 3), submit: true)->assertOk();

    expect(osf_vol($this->vinyl))->toBe(-300.0)
        ->and(osf_vol($this->lam))->toBe(-100.0)
        ->and(MaterialStockMovement::count())->toBe(3);
});

test('printing reject returns the stock; resubmitting takes it again once', function () {
    [$order, $product, $a, $b, $artist] = osf_order();
    osf_save($artist, '/artist/orders/%d', $order, $product, osf_items($a, $b), submit: true);
    expect(strtolower((string) $product->refresh()->taskType))->toBe('printing');

    $this->actingAs(osf_user('operations-printing'))
        ->post("/printing/jobs/{$product->ProductID}/reject", ['reason' => 'Wrong size'])
        ->assertRedirect();

    expect(osf_vol($this->vinyl))->toBe(0.0)->and(osf_vol($this->lam))->toBe(0.0)
        ->and(MaterialStockMovement::latest('id')->first()->reason)->toContain('rejected by printing');

    // artist fixes and resubmits (opening edit revives the rejected order)
    $this->actingAs($artist)->get("/artist/orders/{$order->id}/edit")->assertOk();
    osf_save($artist, '/artist/orders/%d', $order, $product, osf_items($a, $b), submit: true)->assertOk();

    expect(osf_vol($this->vinyl))->toBe(-100.0)->and(osf_vol($this->lam))->toBe(-100.0);
});

test('passing to data entry returns stock until data entry submits', function () {
    [$order, $product, $a, $b, $artist] = osf_order();
    osf_save($artist, '/artist/orders/%d', $order, $product, osf_items($a, $b), submit: true);
    $dataEntry = osf_user('data-entry');

    $this->actingAs($artist)->postJson("/artist/orders/{$order->id}/pass-to-data-entry", ['user_id' => $dataEntry->id])->assertOk()->assertJson(['ok' => true]);
    expect(osf_vol($this->vinyl))->toBe(0.0);

    osf_save($dataEntry, '/data-entry/orders/%d', $order, $product, osf_items($a, $b, qtyA: 2), submit: true)->assertOk();
    expect(osf_vol($this->vinyl))->toBe(-200.0);

    $this->actingAs($dataEntry)->patch("/data-entry/orders/{$order->id}/begin")->assertRedirect();
    expect(osf_vol($this->vinyl))->toBe(0.0)->and(osf_vol($this->lam))->toBe(0.0);
});

test('deleting an item or product of a submitted order returns its stock', function () {
    [$order, $product, $a, $b, $artist] = osf_order();
    osf_save($artist, '/artist/orders/%d', $order, $product, osf_items($a, $b), submit: true);

    $this->actingAs(osf_user('boss'))->deleteJson("/boss/orders/{$order->id}/items/{$b->ItemID}")->assertOk();
    expect(osf_vol($this->lam))->toBe(0.0)->and(osf_vol($this->vinyl))->toBe(-100.0);

    $this->actingAs(osf_user('admin'))->deleteJson("/admin/orders/{$order->id}/products/{$product->ProductID}")->assertOk();
    expect(osf_vol($this->vinyl))->toBe(0.0);
});

test('deleting a submitted order gives its stock back before the order goes', function () {
    [$order, $product, $a, $b, $artist] = osf_order();
    osf_save($artist, '/artist/orders/%d', $order, $product, osf_items($a, $b), submit: true);

    $this->actingAs(osf_user('salesperson'))->deleteJson("/orders/{$order->id}")->assertOk();

    expect(Order::find($order->id))->toBeNull()
        ->and(osf_vol($this->vinyl))->toBe(0.0)
        ->and(osf_vol($this->lam))->toBe(0.0)
        ->and(MaterialStockMovement::latest('id')->first()->reason)->toContain('order deleted');
});

test('the nightly reconcile fixes a change made without syncing, and is quiet otherwise', function () {
    [$order, $product, $a, $b, $artist] = osf_order();
    osf_save($artist, '/artist/orders/%d', $order, $product, osf_items($a, $b), submit: true);

    $this->artisan('inventory:reconcile')->expectsOutputToContain('0 correction(s)')->assertSuccessful();

    DB::table('product_items')->where('ItemID', $a->ItemID)->update(['quantity' => 4]); // changed behind the app's back

    $this->artisan('inventory:reconcile')->expectsOutputToContain('1 correction(s)')->assertSuccessful();
    expect(osf_vol($this->vinyl))->toBe(-400.0);

    $this->artisan('inventory:reconcile')->expectsOutputToContain('0 correction(s)')->assertSuccessful();
});
