<?php

use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\MaterialStockMovement;
use App\Models\User;
use App\Services\MaterialStockService;
use Illuminate\Support\Facades\DB;

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

function mss_user(): User
{
    return User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => bcrypt('x'), 'role' => 'admin', 'status' => 'active']);
}

test('restock and deduct update balances and log each movement with the balance after', function () {
    $material = mss_material();
    $user = mss_user();
    $stock = app(MaterialStockService::class);

    $stock->record($material, StockMovementType::Restock, 5, 50000, 'Supplier delivery', $user->id);
    $stock->record($material, StockMovementType::Adjustment, -1, -12000.456, 'Damaged roll', $user->id);

    $material->refresh();
    expect((float) $material->stock_quantity)->toBe(4.0)
        ->and((float) $material->stock_volume)->toBe(37999.54);

    $moves = MaterialStockMovement::orderBy('id')->get();
    expect($moves)->toHaveCount(2)
        ->and($moves[0]->type)->toBe(StockMovementType::Restock)
        ->and((float) $moves[0]->quantity_after)->toBe(5.0)
        ->and((float) $moves[0]->volume_after)->toBe(50000.0)
        ->and($moves[1]->type)->toBe(StockMovementType::Adjustment)
        ->and((float) $moves[1]->volume_change)->toBe(-12000.46)
        ->and((float) $moves[1]->volume_after)->toBe(37999.54)
        ->and($moves[1]->reason)->toBe('Damaged roll')
        ->and($moves[1]->user_id)->toBe($user->id)
        ->and($moves[1]->material_name)->toBe('PVC White Sticker');
});

test('balances always equal the sum of logged changes', function () {
    $material = mss_material();
    $stock = app(MaterialStockService::class);

    foreach ([[3, 1000], [-1, -250.5], [0, 99.25], [2, 0]] as [$q, $v]) {
        $stock->record($material, StockMovementType::Adjustment, $q, $v);
    }

    $material->refresh();
    expect((float) $material->stock_quantity)->toBe((float) MaterialStockMovement::sum('quantity_change'))
        ->and((float) $material->stock_volume)->toBe((float) MaterialStockMovement::sum('volume_change'));
});

test('stock may go negative instead of blocking', function () {
    $material = mss_material();

    app(MaterialStockService::class)->record($material, StockMovementType::OrderDeduct, 0, -500);

    expect((float) $material->refresh()->stock_volume)->toBe(-500.0);
});

test('a movement that changes nothing is rejected', function () {
    $material = mss_material();

    expect(fn () => app(MaterialStockService::class)->record($material, StockMovementType::Adjustment, 0, 0.001))
        ->toThrow(InvalidArgumentException::class);

    expect(MaterialStockMovement::count())->toBe(0);
});

test('balance and log are rolled back together when the caller fails', function () {
    $material = mss_material();

    try {
        DB::transaction(function () use ($material) {
            app(MaterialStockService::class)->record($material, StockMovementType::Restock, 1, 100);
            throw new RuntimeException('caller failed');
        });
    } catch (RuntimeException) {
    }

    expect((float) $material->refresh()->stock_quantity)->toBe(0.0)
        ->and(MaterialStockMovement::count())->toBe(0);
});

test('history survives deleting the material', function () {
    $material = mss_material();
    app(MaterialStockService::class)->record($material, StockMovementType::Restock, 1, 100);

    $material->delete();

    $move = MaterialStockMovement::first();
    expect($move->material_id)->toBeNull()
        ->and($move->material_name)->toBe('PVC White Sticker');
});

test('balances cannot be set through mass assignment', function () {
    $material = mss_material();

    $material->update(['stock_quantity' => 999, 'stock_volume' => 999, 'low_stock_volume' => 10]);

    $material->refresh();
    expect((float) $material->stock_quantity)->toBe(0.0)
        ->and((float) $material->stock_volume)->toBe(0.0)
        ->and((float) $material->low_stock_volume)->toBe(10.0);
});

test('low stock is flagged only against alert levels that are set', function () {
    $material = mss_material();
    $stock = app(MaterialStockService::class);
    $stock->record($material, StockMovementType::Restock, 2, 1000);

    expect($material->refresh()->isLowStock())->toBeFalse(); // no alert levels set

    $material->update(['low_stock_volume' => 1000]);
    expect($material->refresh()->isLowStock())->toBeTrue(); // at the level

    $stock->record($material, StockMovementType::Restock, 0, 1);
    expect($material->refresh()->isLowStock())->toBeFalse(); // above it

    $material->update(['low_stock_quantity' => 2]);
    expect($material->refresh()->isLowStock())->toBeTrue(); // quantity at its level
});
