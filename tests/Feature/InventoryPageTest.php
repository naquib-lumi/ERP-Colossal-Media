<?php

use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\MaterialStockMovement;
use App\Models\User;
use App\Services\MaterialStockService;
use Illuminate\Support\Facades\DB;

function inv_user(string $role): User
{
    $user = User::create(['name' => "Test {$role}", 'email' => "{$role}@test.local", 'password' => bcrypt('x'), 'role' => $role, 'status' => 'active']);
    $user->forceFill(['email_verified_at' => now()])->save();

    return $user;
}

function inv_material(string $name = 'PVC White Sticker', array $attrs = []): Material
{
    $typeId = DB::table('material_types')->where('name', 'Stickers / Films')->value('id')
        ?? DB::table('material_types')->insertGetId(['name' => 'Stickers / Films', 'created_at' => now(), 'updated_at' => now()]);

    return Material::create(array_merge(['materialName' => $name, 'material_type_id' => $typeId, 'unitCost' => 0.0069, 'quantity_unit' => 'roll'], $attrs));
}

$viewers  = ['admin', 'boss', 'salesperson', 'head-salesperson', 'artist', 'head-artist', 'data-entry'];
$adjusters = ['admin', 'salesperson', 'head-salesperson', 'artist', 'head-artist', 'data-entry'];

test('inventory list and history open for the allowed roles only', function () use ($viewers) {
    $material = inv_material();

    foreach ($viewers as $role) {
        $user = inv_user($role);
        $this->actingAs($user)->get('/inventory')->assertOk()->assertSee('PVC White Sticker');
        $this->actingAs($user)->get("/inventory/{$material->MaterialID}")->assertOk();
    }

    foreach (['operations-printing', 'operations-furnishing', 'operations-delivery-installation', 'operations-dispatch-control'] as $role) {
        $this->actingAs(inv_user($role))->get('/inventory')->assertForbidden();
    }
});

test('each adjusting role can add and deduct stock in whole units', function () use ($adjusters) {
    $material = inv_material();

    foreach ($adjusters as $role) {
        $this->actingAs(inv_user($role))
            ->post("/inventory/{$material->MaterialID}/movements", [
                'direction' => 'add', 'type' => 'restock', 'quantity' => 2, 'reason' => "Delivery by {$role}",
            ])
            ->assertRedirect()->assertSessionHas('success');
    }

    $this->actingAs(User::where('role', 'artist')->first())
        ->post("/inventory/{$material->MaterialID}/movements", [
            'direction' => 'deduct', 'type' => 'adjustment', 'quantity' => 1, 'reason' => 'Damaged',
        ])->assertSessionHas('success');

    // 6 roles × 2 rolls − 1 roll
    expect($material->refresh()->stock_quantity)->toBe(11)
        ->and(MaterialStockMovement::count())->toBe(7);

    $last = MaterialStockMovement::latest('id')->first();
    expect($last->type)->toBe(StockMovementType::Adjustment)
        ->and($last->quantity_change)->toBe(-1)
        ->and($last->user->role)->toBe('artist');
});

test('boss can view but not change stock', function () {
    $material = inv_material();

    $this->actingAs(inv_user('boss'))
        ->post("/inventory/{$material->MaterialID}/movements", ['direction' => 'add', 'type' => 'restock', 'quantity' => 1, 'reason' => 'x'])
        ->assertForbidden();

    $this->actingAs(User::where('role', 'boss')->first())->get('/inventory')->assertOk()->assertDontSee('data-move="add"', false);
    expect(MaterialStockMovement::count())->toBe(0);
});

test('stock change validation', function (array $input, string $errorField) {
    $material = inv_material();

    $this->actingAs(inv_user('admin'))
        ->from('/inventory')
        ->post("/inventory/{$material->MaterialID}/movements", $input)
        ->assertRedirect('/inventory')
        ->assertSessionHasErrors($errorField);

    expect(MaterialStockMovement::count())->toBe(0)
        ->and($material->refresh()->stock_quantity)->toBe(0);
})->with([
    'reason is required'     => [['direction' => 'add', 'type' => 'restock', 'quantity' => 1], 'reason'],
    'quantity is required'   => [['direction' => 'add', 'type' => 'restock', 'reason' => 'x'], 'quantity'],
    'zero changes nothing'   => [['direction' => 'add', 'type' => 'restock', 'quantity' => 0, 'reason' => 'x'], 'quantity'],
    'no decimals'            => [['direction' => 'add', 'type' => 'restock', 'quantity' => 1.5, 'reason' => 'x'], 'quantity'],
    'restock cannot deduct'  => [['direction' => 'deduct', 'type' => 'restock', 'quantity' => 1, 'reason' => 'x'], 'type'],
    'no negative amounts'    => [['direction' => 'add', 'type' => 'adjustment', 'quantity' => -5, 'reason' => 'x'], 'quantity'],
    'unknown movement type'  => [['direction' => 'add', 'type' => 'order_deduct', 'quantity' => 1, 'reason' => 'x'], 'type'],
]);

test('only admin can change stock settings; the alert level is a whole number', function () {
    $material = inv_material();

    $this->actingAs(inv_user('salesperson'))
        ->patch("/inventory/{$material->MaterialID}/settings", ['quantity_unit' => 'sheet'])
        ->assertForbidden();

    $admin = inv_user('admin');
    $this->actingAs($admin)
        ->patch("/inventory/{$material->MaterialID}/settings", ['quantity_unit' => ' sheet ', 'low_stock_quantity' => 3])
        ->assertSessionHas('success');

    $material->refresh();
    expect($material->quantity_unit)->toBe('sheet')
        ->and($material->low_stock_quantity)->toBe(3)
        ->and($material->stock_quantity)->toBe(0); // balance untouched

    $this->actingAs($admin)
        ->patch("/inventory/{$material->MaterialID}/settings", ['quantity_unit' => 'sheet', 'low_stock_quantity' => 2.5])
        ->assertSessionHasErrors('low_stock_quantity');

    $this->actingAs($admin)
        ->patch("/inventory/{$material->MaterialID}/settings", ['quantity_unit' => '', 'low_stock_quantity' => ''])
        ->assertSessionHas('success');

    expect($material->refresh()->low_stock_quantity)->toBeNull();
});

test('low and negative filters', function () {
    $stock = app(MaterialStockService::class);
    $ok  = inv_material('Healthy', ['low_stock_quantity' => 1]);
    $low = inv_material('Running low', ['low_stock_quantity' => 5]);
    $neg = inv_material('Overdrawn');
    $stock->record($ok, StockMovementType::Restock, 5);
    $stock->record($low, StockMovementType::Restock, 4);
    $stock->record($neg, StockMovementType::Adjustment, -1);

    $admin = inv_user('admin');

    $this->actingAs($admin)->get('/inventory?status=low')
        ->assertSee('Running low')->assertDontSee('Healthy')->assertDontSee('Overdrawn');

    $this->actingAs($admin)->get('/inventory?status=negative')
        ->assertSee('Overdrawn')->assertDontSee('Healthy')->assertDontSee('Running low');

    $this->actingAs($admin)->get('/inventory?q=heal')
        ->assertSee('Healthy')->assertDontSee('Overdrawn');
});

test('pages show whole units, no square feet', function () {
    $material = inv_material('PVC White Sticker', ['low_stock_quantity' => 2]);
    $admin = inv_user('admin');
    $stock = app(MaterialStockService::class);
    $stock->record($material, StockMovementType::Restock, 12, 'First delivery', $admin->id);
    $stock->record($material, StockMovementType::Adjustment, -3, 'Roll damaged', $admin->id);

    $this->actingAs($admin)->get('/inventory')
        ->assertOk()->assertSee('9 roll')->assertDontSee('sq ft');

    $this->actingAs($admin)->get("/inventory/{$material->MaterialID}")
        ->assertOk()
        ->assertSeeInOrder(['-3', 'Roll damaged', '+12', 'First delivery']) // newest first
        ->assertSee('alert at 2')
        ->assertSee('Test admin')
        ->assertDontSee('sq ft');
});
