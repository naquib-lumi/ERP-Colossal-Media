<?php

use App\Enums\StockMovementType;
use App\Models\Material;
use App\Models\MaterialStockMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductItem;
use App\Services\OrderStockSync;
use Illuminate\Support\Facades\DB;

function oss_material(string $name, bool $active = true): Material
{
    $typeId = DB::table('material_types')->value('id')
        ?? DB::table('material_types')->insertGetId(['name' => 'Stickers / Films', 'created_at' => now(), 'updated_at' => now()]);

    return Material::create(['materialName' => $name, 'material_type_id' => $typeId, 'unitCost' => 0.005, 'active' => $active]);
}

function oss_order(array $attrs = []): Order
{
    $order = new Order();
    $order->forceFill(array_merge(['orderDate' => now()->toDateString(), 'orderTitle' => 'Signage', 'orderStatus' => 'completed', 'submit' => 1], $attrs))->save();

    return $order->refresh();
}

function oss_product(Order $order, array $attrs = []): Product
{
    $p = new Product();
    $p->forceFill(array_merge(['OrderID' => $order->id, 'productName' => 'Banner', 'totalQuantity' => 1, 'status' => 'in_progress'], $attrs))->save();

    return $p;
}

function oss_item(Product $product, array $attrs): ProductItem
{
    return ProductItem::create(array_merge(['ProductID' => $product->ProductID, 'itemName' => 'Item', 'quantity' => 1, 'sizeUnit' => 'inch'], $attrs));
}

function oss_sync(Order $order, ...$args): void
{
    app(OrderStockSync::class)->syncOrder($order, ...$args);
}

function oss_stock(Material $m): array
{
    $m->refresh();

    return [(float) $m->stock_quantity, (float) $m->stock_volume];
}

test('orders that were never marked tracked do not touch stock', function () {
    $vinyl = oss_material('Vinyl');
    $order = oss_order();
    oss_item(oss_product($order), ['sizeWidth' => 10, 'sizeHeight' => 10, 'material' => ['Vinyl']]);

    oss_sync($order); // e.g. the nightly run over an order submitted before go-live

    expect(MaterialStockMovement::count())->toBe(0)
        ->and(oss_stock($vinyl))->toBe([0.0, 0.0]);
});

test('a draft cannot be marked tracked', function () {
    $order = oss_order(['submit' => 0, 'orderStatus' => 'in_progress']);

    oss_sync($order, markTracked: true);

    expect((bool) $order->refresh()->stock_tracked)->toBeFalse();
});

test('submitting deducts width × height × quantity per material, with unit conversion, pieces by count', function () {
    $vinyl = oss_material('Vinyl');
    $lam   = oss_material('Laminate');
    $board = oss_material('Foam Board');
    $order = oss_order();
    $p = oss_product($order);

    oss_item($p, ['quantity' => 2, 'sizeWidth' => 10, 'sizeHeight' => 20, 'sizeUnit' => 'inch', 'material' => ['Vinyl', 'Laminate']]); // 2 × 200 = 400 each
    oss_item($p, ['quantity' => 1, 'sizeWidth' => 2, 'sizeHeight' => 3, 'sizeUnit' => 'ft', 'material' => ['vinyl ']]);             // 24 × 36 = 864
    oss_item($p, ['quantity' => 3, 'sizeWidth' => 254, 'sizeHeight' => 127, 'sizeUnit' => 'mm', 'material' => ['Laminate']]);      // 3 × 10 × 5 = 150
    oss_item($p, ['quantity' => 5, 'sizeWidth' => 9, 'sizeHeight' => 9, 'sizeUnit' => 'piece', 'material' => ['Foam Board']]);    // 5 pieces
    oss_item($p, ['quantity' => 4, 'sizeWidth' => 10, 'sizeHeight' => 10, 'material' => ['Not A Listed Material']]);             // skipped
    oss_item($p, ['quantity' => 0, 'sizeWidth' => 10, 'sizeHeight' => 10, 'material' => ['Vinyl']]);                             // qty 0: skipped

    oss_sync($order, markTracked: true, note: 'submitted');

    expect((bool) $order->refresh()->stock_tracked)->toBeTrue()
        ->and(oss_stock($vinyl))->toBe([0.0, -1264.0])
        ->and(oss_stock($lam))->toBe([0.0, -550.0])
        ->and(oss_stock($board))->toBe([-5.0, 0.0])
        ->and(MaterialStockMovement::where('type', StockMovementType::OrderDeduct)->count())->toBe(3)
        ->and(MaterialStockMovement::first()->reason)->toBe("Order {$order->order_number} · submitted")
        ->and(MaterialStockMovement::first()->order_id)->toBe($order->id);
});

test('running it again changes nothing', function () {
    $vinyl = oss_material('Vinyl');
    $order = oss_order();
    oss_item(oss_product($order), ['sizeWidth' => 10, 'sizeHeight' => 10, 'material' => ['Vinyl']]);

    oss_sync($order, markTracked: true);
    oss_sync($order);
    oss_sync($order);

    expect(MaterialStockMovement::count())->toBe(1)->and(oss_stock($vinyl))->toBe([0.0, -100.0]);
});

test('editing a submitted order records only the difference', function () {
    $vinyl = oss_material('Vinyl');
    $lam   = oss_material('Laminate');
    $order = oss_order();
    $item  = oss_item(oss_product($order), ['quantity' => 2, 'sizeWidth' => 10, 'sizeHeight' => 10, 'material' => ['Vinyl']]);
    oss_sync($order, markTracked: true); // vinyl -200

    $item->update(['quantity' => 3, 'material' => ['Laminate']]); // swap material, qty up
    oss_sync($order);

    expect(oss_stock($vinyl))->toBe([0.0, 0.0])      // all 200 returned
        ->and(oss_stock($lam))->toBe([0.0, -300.0])   // 300 deducted
        ->and(MaterialStockMovement::where('type', StockMovementType::OrderReturn)->sum('volume_change'))->toEqual(200);
});

test('rejected products and deleted items stop using stock', function () {
    $vinyl = oss_material('Vinyl');
    $order = oss_order();
    $keep  = oss_product($order);
    $rej   = oss_product($order);
    oss_item($keep, ['sizeWidth' => 10, 'sizeHeight' => 10, 'material' => ['Vinyl']]);
    $gone = oss_item($keep, ['sizeWidth' => 5, 'sizeHeight' => 10, 'material' => ['Vinyl']]);
    oss_item($rej, ['sizeWidth' => 10, 'sizeHeight' => 20, 'material' => ['Vinyl']]);
    oss_sync($order, markTracked: true);
    expect(oss_stock($vinyl))->toBe([0.0, -350.0]); // 100 + 50 + 200

    DB::table('products')->where('ProductID', $rej->ProductID)->update(['status' => 'rejected']);
    $gone->delete();
    oss_sync($order);

    expect(oss_stock($vinyl))->toBe([0.0, -100.0]);
});

test('un-submitting or rejecting the order returns everything; resubmitting deducts once again', function () {
    $vinyl = oss_material('Vinyl');
    $order = oss_order();
    oss_item(oss_product($order), ['sizeWidth' => 10, 'sizeHeight' => 10, 'material' => ['Vinyl']]);
    oss_sync($order, markTracked: true);

    DB::table('orders')->where('id', $order->id)->update(['submit' => 0, 'draft' => 1]); // e.g. passed to data entry
    oss_sync($order);
    expect(oss_stock($vinyl))->toBe([0.0, 0.0]);

    DB::table('orders')->where('id', $order->id)->update(['submit' => 1, 'orderStatus' => 'completed']);
    oss_sync($order, markTracked: true);
    oss_sync($order, markTracked: true);
    expect(oss_stock($vinyl))->toBe([0.0, -100.0]);

    DB::table('orders')->where('id', $order->id)->update(['orderStatus' => 'rejected']);
    oss_sync($order);
    expect(oss_stock($vinyl))->toBe([0.0, 0.0]);
});

test('release gives everything back, e.g. before deleting the order', function () {
    $vinyl = oss_material('Vinyl');
    $board = oss_material('Board');
    $order = oss_order();
    $p = oss_product($order);
    oss_item($p, ['sizeWidth' => 10, 'sizeHeight' => 10, 'material' => ['Vinyl']]);
    oss_item($p, ['quantity' => 2, 'sizeUnit' => 'piece', 'material' => ['Board']]);
    oss_sync($order, markTracked: true);

    oss_sync($order, release: true, note: 'order deleted');

    expect(oss_stock($vinyl))->toBe([0.0, 0.0])
        ->and(oss_stock($board))->toBe([0.0, 0.0])
        ->and(MaterialStockMovement::latest('id')->first()->reason)->toContain('order deleted');
});

test('a change that moves quantity and volume in opposite directions is split into a deduction and a return', function () {
    $m = oss_material('Board');
    $order = oss_order();
    $item = oss_item(oss_product($order), ['quantity' => 2, 'sizeUnit' => 'piece', 'material' => ['Board']]);
    oss_sync($order, markTracked: true); // qty -2

    $item->update(['sizeUnit' => 'inch', 'sizeWidth' => 10, 'sizeHeight' => 10]); // now 2 × 100 sq in, no pieces
    oss_sync($order);

    $last = MaterialStockMovement::orderByDesc('id')->take(2)->get();
    expect($last->pluck('type')->all())->toEqualCanonicalizing([StockMovementType::OrderDeduct, StockMovementType::OrderReturn])
        ->and(oss_stock($m))->toBe([0.0, -200.0]);
});

test('stock booked to a renamed material still returns correctly', function () {
    $vinyl = oss_material('Vinyl');
    $order = oss_order();
    oss_item(oss_product($order), ['sizeWidth' => 10, 'sizeHeight' => 10, 'material' => ['Vinyl']]);
    oss_sync($order, markTracked: true);

    $vinyl->update(['materialName' => 'Vinyl Gloss']); // items follow the rename
    oss_sync($order);
    expect(MaterialStockMovement::count())->toBe(1);  // nothing to change

    oss_sync($order, release: true);
    expect(oss_stock($vinyl))->toBe([0.0, 0.0]);
});
