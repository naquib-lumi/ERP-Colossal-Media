<?php

use App\Enums\StockMovementType;
use App\Models\Lead;
use App\Models\Material;
use App\Models\MaterialStockMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\User;
use App\Services\MaterialStockService;
use Illuminate\Support\Facades\DB;

function om_user(string $role, ?string $email = null): User
{
    return User::firstOrCreate(['email' => $email ?? "{$role}@test.local"], ['name' => "Test {$role}", 'password' => bcrypt('x'), 'role' => $role, 'status' => 'active']);
}

function om_material(string $name, int $stock = 0): Material
{
    $typeId = DB::table('material_types')->value('id')
        ?? DB::table('material_types')->insertGetId(['name' => 'Stickers / Films', 'created_at' => now(), 'updated_at' => now()]);
    $material = Material::create(['materialName' => $name, 'material_type_id' => $typeId, 'unitCost' => 0.01, 'quantity_unit' => 'roll']);
    if ($stock !== 0) {
        app(MaterialStockService::class)->record($material, StockMovementType::Restock, $stock);
    }

    return $material->refresh();
}

/** Order with a Banner (2 items on Tarpaulin, one also White Ink) and a Sticker (1 item on Clear Sticker + an unknown material). */
function om_order(): Order
{
    $sales = om_user('salesperson');
    $lead = Lead::create(['salesperson_id' => $sales->id, 'company_name' => 'Acme Sdn Bhd', 'name' => 'Ali', 'phone' => '0123', 'email' => 'ali@acme.test', 'status' => 'accept']);
    $order = new Order();
    $order->forceFill(['lead_id' => $lead->id, 'salesperson_id' => $sales->id, 'companyName' => 'Acme Sdn Bhd', 'orderDate' => '2026-10-06', 'orderTitle' => 'Shop signage', 'submit' => 1, 'draft' => 0])->save();

    $items = [
        'Banner'  => [['Front', 2, ['Tarpaulin', 'White Ink']], ['Side', 3, ['Tarpaulin']]],
        'Sticker' => [['Logo', 10, ['Clear Sticker', 'Mystery Film']]],
    ];
    foreach ($items as $productName => $rows) {
        $p = new Product();
        $p->forceFill(['OrderID' => $order->id, 'productName' => $productName, 'totalQuantity' => 1, 'status' => 'in_progress'])->save();
        foreach ($rows as [$name, $qty, $materials]) {
            $i = new ProductItem();
            $i->forceFill(['ProductID' => $p->ProductID, 'itemName' => $name, 'quantity' => $qty, 'sizeWidth' => 1000, 'sizeHeight' => 500, 'sizeUnit' => 'mm', 'material' => $materials])->save();
        }
    }

    return $order->refresh();
}

test('order materials page lists what the items need, with stock', function () {
    om_material('Tarpaulin', 4);
    om_material('White Ink', 1);
    om_material('Clear Sticker');
    $order = om_order();

    $this->actingAs(om_user('operations-printing'))->get("/orders/{$order->id}/materials")
        ->assertOk()
        ->assertSeeInOrder(['Clear Sticker', 'Sticker', '1', '10', '0 roll'])
        ->assertSeeInOrder(['Mystery Film', 'Not in the material list'])
        ->assertSeeInOrder(['Tarpaulin', 'Banner', '2', '5', '4 roll'])
        ->assertSeeInOrder(['White Ink', 'Banner', '1', '2', '1 roll']);
});

test('order materials page access', function () {
    $order = om_order();

    foreach (['admin', 'boss', 'head-salesperson', 'artist', 'data-entry', 'operations-printing', 'operations-furnishing'] as $role) {
        $this->actingAs(om_user($role))->get("/orders/{$order->id}/materials")->assertOk();
    }
    $this->actingAs(om_user('salesperson'))->get("/orders/{$order->id}/materials")->assertOk(); // owns the lead
    $this->actingAs(om_user('salesperson', 'other-sales@test.local'))->get("/orders/{$order->id}/materials")->assertForbidden();
    $this->actingAs(om_user('operations-dispatch-control'))->get("/orders/{$order->id}/materials")->assertForbidden();
});

test('a manual deduction can name the order; it shows on the history and the order page', function () {
    $tarp = om_material('Tarpaulin', 5);
    $order = om_order();
    $admin = om_user('admin');
    $number = ltrim($order->order_number, '#');

    $this->actingAs($admin)
        ->post("/inventory/{$tarp->MaterialID}/movements", ['direction' => 'deduct', 'type' => 'adjustment', 'quantity' => 2, 'reason' => 'Printed banner', 'order_ref' => $number])
        ->assertSessionHas('success');

    $mv = MaterialStockMovement::latest('id')->first();
    expect($mv->order_id)->toBe($order->id)->and($tarp->refresh()->stock_quantity)->toBe(3);

    $this->actingAs($admin)->get("/inventory/{$tarp->MaterialID}")->assertOk()->assertSee($order->order_number);
    $this->actingAs($admin)->get("/orders/{$order->id}/materials")->assertOk()->assertSeeInOrder(['Stock deducted for this order', 'Tarpaulin', '-2', 'Printed banner']);
});

test('an unknown order number is rejected; additions never link an order', function () {
    $tarp = om_material('Tarpaulin', 5);
    $order = om_order();
    $admin = om_user('admin');

    $this->actingAs($admin)->from('/inventory')
        ->post("/inventory/{$tarp->MaterialID}/movements", ['direction' => 'deduct', 'type' => 'adjustment', 'quantity' => 1, 'reason' => 'x', 'order_ref' => 'ORD-1999-9999'])
        ->assertSessionHasErrors('order_ref');

    $this->actingAs($admin)
        ->post("/inventory/{$tarp->MaterialID}/movements", ['direction' => 'add', 'type' => 'restock', 'quantity' => 1, 'reason' => 'x', 'order_ref' => $order->order_number])
        ->assertSessionHas('success');

    expect(MaterialStockMovement::latest('id')->first()->order_id)->toBeNull()
        ->and($tarp->refresh()->stock_quantity)->toBe(6);
});
