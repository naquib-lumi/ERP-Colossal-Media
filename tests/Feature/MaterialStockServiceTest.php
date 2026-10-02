<?php

use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\MaterialStockMovement;
use App\Models\User;
use App\Notifications\GenericNotification;
use App\Services\MaterialStockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

function mss_material(array $attrs = []): Material
{
    $typeId = DB::table('material_types')->insertGetId(['name' => 'Stickers / Films', 'created_at' => now(), 'updated_at' => now()]);

    return Material::create(array_merge([
        'materialName'     => 'PVC White Sticker',
        'material_type_id' => $typeId,
        'unitCost'         => 0.0069,
        'quantity_unit'    => 'roll',
    ], $attrs));
}

function mss_user(string $role = 'admin', string $status = 'active'): User
{
    return User::create(['name' => "Test {$role}", 'email' => "{$role}.{$status}@test.local", 'password' => bcrypt('x'), 'role' => $role, 'status' => $status]);
}

test('restock and deduct update the balance and log each movement with the balance after', function () {
    $material = mss_material();
    $user = mss_user();
    $stock = app(MaterialStockService::class);

    $stock->record($material, StockMovementType::Restock, 5, 'Supplier delivery', $user->id);
    $stock->record($material, StockMovementType::Adjustment, -1, 'Damaged roll', $user->id);

    expect($material->refresh()->stock_quantity)->toBe(4);

    $moves = MaterialStockMovement::orderBy('id')->get();
    expect($moves)->toHaveCount(2)
        ->and($moves[0]->type)->toBe(StockMovementType::Restock)
        ->and($moves[0]->quantity_after)->toBe(5)
        ->and($moves[1]->type)->toBe(StockMovementType::Adjustment)
        ->and($moves[1]->quantity_change)->toBe(-1)
        ->and($moves[1]->quantity_after)->toBe(4)
        ->and($moves[1]->reason)->toBe('Damaged roll')
        ->and($moves[1]->user_id)->toBe($user->id)
        ->and($moves[1]->material_name)->toBe('PVC White Sticker');
});

test('the balance always equals the sum of logged changes', function () {
    $material = mss_material();
    $stock = app(MaterialStockService::class);

    foreach ([3, -1, 7, -4, 2] as $q) {
        $stock->record($material, StockMovementType::Adjustment, $q);
    }

    expect($material->refresh()->stock_quantity)->toBe(7)
        ->and((int) MaterialStockMovement::sum('quantity_change'))->toBe(7);
});

test('stock may go negative instead of blocking', function () {
    $material = mss_material();

    app(MaterialStockService::class)->record($material, StockMovementType::Adjustment, -2);

    expect($material->refresh()->stock_quantity)->toBe(-2);
});

test('a movement that changes nothing is rejected', function () {
    $material = mss_material();

    expect(fn () => app(MaterialStockService::class)->record($material, StockMovementType::Adjustment, 0))
        ->toThrow(InvalidArgumentException::class);

    expect(MaterialStockMovement::count())->toBe(0);
});

test('balance and log are rolled back together when the caller fails', function () {
    $material = mss_material();

    try {
        DB::transaction(function () use ($material) {
            app(MaterialStockService::class)->record($material, StockMovementType::Restock, 1);
            throw new RuntimeException('caller failed');
        });
    } catch (RuntimeException) {
    }

    expect($material->refresh()->stock_quantity)->toBe(0)
        ->and(MaterialStockMovement::count())->toBe(0);
});

test('history survives deleting the material', function () {
    $material = mss_material();
    app(MaterialStockService::class)->record($material, StockMovementType::Restock, 1);

    $material->delete();

    $move = MaterialStockMovement::first();
    expect($move->material_id)->toBeNull()
        ->and($move->material_name)->toBe('PVC White Sticker');
});

test('the balance cannot be set through mass assignment', function () {
    $material = mss_material();

    $material->update(['stock_quantity' => 999, 'low_stock_quantity' => 10]);

    $material->refresh();
    expect($material->stock_quantity)->toBe(0)
        ->and($material->low_stock_quantity)->toBe(10);
});

test('low stock is flagged only when an alert level is set', function () {
    $material = mss_material();
    $stock = app(MaterialStockService::class);
    $stock->record($material, StockMovementType::Restock, 2);

    expect($material->refresh()->isLowStock())->toBeFalse(); // no alert level

    $material->update(['low_stock_quantity' => 2]);
    expect($material->refresh()->isLowStock())->toBeTrue(); // at the level

    $stock->record($material, StockMovementType::Restock, 1);
    expect($material->refresh()->isLowStock())->toBeFalse(); // above it
});

test('dropping to the alert level emails and notifies every active admin once', function () {
    Notification::fake();
    $admin = mss_user('admin');
    $admin2 = User::create(['name' => 'Admin Two', 'email' => 'admin2@test.local', 'password' => bcrypt('x'), 'role' => 'admin', 'status' => 'active']);
    $inactive = mss_user('admin', 'inactive');
    $boss = mss_user('boss');
    $material = mss_material(['low_stock_quantity' => 3]);
    $stock = app(MaterialStockService::class);

    $stock->record($material, StockMovementType::Restock, 5);
    Notification::assertNothingSent(); // 5 is above the level

    $stock->record($material, StockMovementType::Adjustment, -2); // 5 → 3: crosses the level

    foreach ([$admin, $admin2] as $user) {
        Notification::assertSentTo($user, GenericNotification::class, function (GenericNotification $n) use ($material) {
            return $n->via === ['database', 'mail']
                && $n->message === 'Low stock: PVC White Sticker has 3 roll left (alert level 3).'
                && $n->url === route('inventory.show', $material);
        });
    }
    Notification::assertNotSentTo([$inactive, $boss], GenericNotification::class);
    Notification::assertCount(2);

    $stock->record($material, StockMovementType::Adjustment, -1); // already low: no repeat
    Notification::assertCount(2);

    $stock->record($material, StockMovementType::Restock, 10);  // back above (12)
    $stock->record($material, StockMovementType::Adjustment, -10); // 12 → 2: crosses again
    Notification::assertCount(4);
});

test('no alert when the material has no alert level', function () {
    Notification::fake();
    mss_user('admin');
    $material = mss_material();

    app(MaterialStockService::class)->record($material, StockMovementType::Adjustment, -5);

    Notification::assertNothingSent();
});

test('a failing alert never undoes the stock change', function () {
    mss_user('admin');
    $material = mss_material(['low_stock_quantity' => 1]);
    config(['mail.default' => 'failing']);
    config(['mail.mailers.failing' => ['transport' => 'smtp', 'host' => '127.0.0.1', 'port' => 1, 'timeout' => 1]]);

    app(MaterialStockService::class)->record($material, StockMovementType::Restock, 2);
    app(MaterialStockService::class)->record($material, StockMovementType::Adjustment, -1);

    expect($material->refresh()->stock_quantity)->toBe(1)
        ->and(MaterialStockMovement::count())->toBe(2);
});
