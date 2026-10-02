<?php

/*
 * Auto-fill: "Copy as new order" makes a new draft from an earlier order.
 */

use App\Models\Lead;
use App\Models\Material;
use App\Models\MaterialStockMovement;
use App\Models\Order;
use App\Models\OrderAttachment;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\ProductRemark;
use App\Models\Specification;
use App\Models\User;
use App\Models\DeliveryBreakdown;
use Illuminate\Support\Facades\DB;

function ocp_user(string $role, ?string $email = null): User
{
    $user = User::firstOrCreate(['email' => $email ?? "{$role}@test.local"], ['name' => "Test {$role}", 'password' => bcrypt('x'), 'role' => $role, 'status' => 'active']);
    $user->forceFill(['email_verified_at' => now()])->save();

    return $user;
}

/** A completed, submitted order with one product, one item (+spec), a remark, a delivery and an attachment. */
function ocp_source(array $orderAttrs = []): Order
{
    $sales = ocp_user('salesperson');
    $lead = Lead::create(['salesperson_id' => $sales->id, 'company_name' => 'Acme Sdn Bhd', 'name' => 'Ali', 'phone' => '0123', 'email' => 'ali@acme.test', 'status' => 'accept']);

    $typeId = DB::table('material_types')->insertGetId(['name' => 'Stickers', 'created_at' => now(), 'updated_at' => now()]);
    Material::create(['materialName' => 'Vinyl', 'material_type_id' => $typeId, 'unitCost' => 0.005]);

    $order = new Order();
    $order->forceFill(array_merge([
        'lead_id' => $lead->id, 'salesperson_id' => $sales->id, 'artist_id' => ocp_user('artist')->id,
        'leadName' => 'Ali', 'leadPhone' => '0123', 'leadEmail' => 'ali@acme.test', 'companyName' => 'Acme Sdn Bhd',
        'orderDate' => '2026-01-10', 'deadline' => '2026-01-20', 'orderTitle' => 'Shop signage', 'orderDetail' => 'Monthly banners',
        'orderStatus' => 'completed', 'draft' => 0, 'submit' => 1, 'approval' => 1, 'pending' => 0, 'status' => 0,
        'data_entry_id' => ocp_user('data-entry')->id,
    ], $orderAttrs))->save();

    $p = new Product();
    $p->forceFill([
        'OrderID' => $order->id, 'productName' => 'Banner', 'totalQuantity' => 4, 'materialRemark' => 'Outdoor vinyl',
        'taskType' => 'printing', 'permit' => 1, 'status' => 'completed', 'accepted' => 1, 'installation_accepted' => 1, 'editable' => 0,
    ])->save();

    $it = ProductItem::create(['ProductID' => $p->ProductID, 'itemName' => 'Front', 'quantity' => 2, 'sizeWidth' => 36, 'sizeHeight' => 24, 'sizeUnit' => 'inch', 'finishing' => 'Eyelet', 'material' => ['Vinyl'], 'prime_centre' => true]);
    Specification::create(['ItemID' => $it->ItemID, 'lamination' => 'Matte', 'printer' => 'Latex 3.2', 'cutter' => null]);
    ProductRemark::create(['ProductID' => $p->ProductID, 'operation' => 'printing', 'remark' => 'Print glossy', 'user_id' => $sales->id]);
    DeliveryBreakdown::create(['ProductID' => $p->ProductID, 'method' => 'delivery', 'location' => 'Shop KL', 'quantity' => 4, 'date' => '2026-01-19', 'time' => '10:00:00', 'deliver_install_type' => 'outsource', 'outsource_cost' => 80]);
    OrderAttachment::create(['order_id' => $order->id, 'user_id' => $sales->id, 'file_path' => 'orders/x/artwork.pdf', 'original_name' => 'artwork.pdf']);

    return $order->refresh();
}

function ocp_copy(User $user, Order $source)
{
    return test()->actingAs($user)->post("/orders/{$source->id}/copy");
}

function ocp_latest(): Order
{
    return Order::latest('id')->first();
}

test('copy makes a new draft with the products, items, specs, remarks and delivery details', function () {
    $source = ocp_source();
    $artist = User::where('role', 'artist')->first();

    $response = ocp_copy($artist, $source);
    $copy = ocp_latest();

    $response->assertRedirect(route('artist.orders.edit', $copy->id))->assertSessionHas('success');

    expect($copy->id)->not->toBe($source->id)
        ->and($copy->order_number)->not->toBe($source->order_number)
        ->and($copy->only(['lead_id', 'leadName', 'leadPhone', 'leadEmail', 'companyName', 'salesperson_id', 'orderTitle', 'orderDetail']))
            ->toBe($source->only(['lead_id', 'leadName', 'leadPhone', 'leadEmail', 'companyName', 'salesperson_id', 'orderTitle', 'orderDetail']))
        ->and($copy->orderDate->toDateString())->toBe(now()->toDateString())
        ->and($copy->deadline)->toBeNull()
        ->and([(int) $copy->draft, (int) $copy->submit, (int) $copy->approval])->toBe([1, 0, 0])
        ->and($copy->orderStatus)->toBe('in_progress')
        ->and($copy->data_entry_id)->toBeNull()
        ->and(OrderAttachment::where('order_id', $copy->id)->count())->toBe(0);

    $np = Product::where('OrderID', $copy->id)->sole();
    expect($np->only(['productName', 'totalQuantity', 'materialRemark', 'permit']))->toBe(['productName' => 'Banner', 'totalQuantity' => 4, 'materialRemark' => 'Outdoor vinyl', 'permit' => 1])
        ->and([$np->status, $np->accepted, $np->installation_accepted, (int) $np->editable])->toBe(['in_progress', null, null, 1]);

    $ni = ProductItem::where('ProductID', $np->ProductID)->sole();
    expect($ni->only(['itemName', 'quantity', 'sizeUnit', 'finishing', 'material', 'prime_centre']))
        ->toBe(['itemName' => 'Front', 'quantity' => 2, 'sizeUnit' => 'inch', 'finishing' => 'Eyelet', 'material' => ['Vinyl'], 'prime_centre' => true])
        ->and((float) $ni->sizeWidth)->toBe(36.0)
        ->and(Specification::where('ItemID', $ni->ItemID)->sole()->only(['lamination', 'printer']))->toBe(['lamination' => 'Matte', 'printer' => 'Latex 3.2'])
        ->and(ProductRemark::where('ProductID', $np->ProductID)->sole()->remark)->toBe('Print glossy');

    $nd = DeliveryBreakdown::where('ProductID', $np->ProductID)->sole();
    expect($nd->only(['method', 'location', 'quantity', 'deliver_install_type']))->toBe(['method' => 'delivery', 'location' => 'Shop KL', 'quantity' => 4, 'deliver_install_type' => 'outsource'])
        ->and((float) $nd->outsource_cost)->toBe(80.0)
        ->and($nd->date)->toBeNull()
        ->and($nd->time)->toBeNull();
});

test('the source order is left untouched', function () {
    $source = ocp_source();
    $before = [
        DB::table('orders')->where('id', $source->id)->first(),
        DB::table('products')->where('OrderID', $source->id)->get(),
    ];

    ocp_copy(ocp_user('admin'), $source)->assertRedirect();

    expect([DB::table('orders')->where('id', $source->id)->first(), DB::table('products')->where('OrderID', $source->id)->get()])->toEqual($before);
});

test('each role lands on its own edit page and owns the copy as its create form would', function () {
    $source = ocp_source();

    ocp_copy(User::where('role', 'salesperson')->first(), $source)->assertRedirect(route('orders.edit', ocp_latest()->id));
    expect(ocp_latest()->only(['salesperson_id', 'artist_id', 'orderStatus']))
        ->toBe(['salesperson_id' => User::where('role', 'salesperson')->first()->id, 'artist_id' => null, 'orderStatus' => 'in_progress']);

    $head = ocp_user('head-artist');
    ocp_copy($head, $source)->assertRedirect(route('artist.orders.edit', ocp_latest()->id));
    expect(ocp_latest()->artist_id)->toBe($head->id);

    // admin / boss keep the source's (normal) artist as "assigned"
    ocp_copy(ocp_user('admin'), $source)->assertRedirect(route('admin.orders.edit', ocp_latest()->id));
    expect(ocp_latest()->only(['artist_id', 'orderStatus', 'pending']))
        ->toBe(['artist_id' => $source->artist_id, 'orderStatus' => 'assigned', 'pending' => true]); // Order casts pending to bool

    ocp_copy(ocp_user('boss'), $source)->assertRedirect(route('boss.orders.edit', ocp_latest()->id));

    // ...and fall back to "to assign" when that artist is no longer active
    User::whereKey($source->artist_id)->update(['status' => 'inactive']);
    ocp_copy(User::where('role', 'admin')->first(), $source);
    expect(ocp_latest()->only(['artist_id', 'orderStatus']))->toBe(['artist_id' => null, 'orderStatus' => 'to_assign']);
});

test('who may copy which order', function () {
    $source = ocp_source();

    ocp_copy(ocp_user('salesperson', 'other-sales@test.local'), $source)->assertForbidden();  // not their lead
    ocp_copy(ocp_user('artist', 'other-artist@test.local'), $source)->assertForbidden();      // not their order
    ocp_copy(User::where('role', 'data-entry')->first(), $source)->assertForbidden();
    ocp_copy(ocp_user('operations-printing'), $source)->assertForbidden();

    ocp_copy(ocp_user('head-salesperson'), $source)->assertRedirect();
    ocp_copy(User::where('role', 'salesperson')->first(), $source)->assertRedirect();      // their lead

    expect(Order::count())->toBe(3);
});

test('the copy opens in the edit page with the hint, and the button shows only for allowed users', function () {
    $source = ocp_source();
    $sales = User::where('role', 'salesperson')->first();

    $this->actingAs($sales)->get("/orders/{$source->id}")->assertOk()->assertSee('Copy as new order');

    $this->followingRedirects()->actingAs($sales)->post("/orders/{$source->id}/copy")
        ->assertOk()
        ->assertSee('Set the deadline and delivery dates');

    $this->actingAs(ocp_user('head-artist'))->get("/artist/orders/{$source->id}")->assertOk()->assertSee('Copy as new order');
    $this->actingAs(ocp_user('admin'))->get("/admin/orders/{$source->id}")->assertOk()->assertSee('Copy as new order');
    $this->actingAs(ocp_user('boss'))->get("/boss/orders/{$source->id}")->assertOk()->assertSee('Copy as new order');
});

test('orders never change stock: copying and submitting leave it alone (stock is manual only)', function () {
    $source = ocp_source();
    $head = ocp_user('head-artist');

    ocp_copy($head, $source)->assertRedirect();
    $copy = ocp_latest();
    expect(MaterialStockMovement::count())->toBe(0);

    $np = Product::where('OrderID', $copy->id)->sole();
    $ni = ProductItem::where('ProductID', $np->ProductID)->sole();

    $this->actingAs($head)->putJson("/artist/orders/{$copy->id}", [
        'design_confirmed' => 1, 'is_draft' => 0, 'submit' => 1,
        'products' => [['product_id' => $np->ProductID, 'items' => [[
            'id' => $ni->ItemID, 'itemName' => 'Front', 'quantity' => 2, 'sizeWidth' => 36, 'sizeHeight' => 24, 'sizeUnit' => 'inch', 'material' => ['Vinyl'],
        ]]]],
    ])->assertOk();

    expect(Material::where('materialName', 'Vinyl')->value('stock_quantity'))->toBe(0)
        ->and(MaterialStockMovement::count())->toBe(0);
});

test('all five order detail pages render one after another (shared view helpers)', function () {
    $source = ocp_source();
    $dataEntry = User::where('role', 'data-entry')->first(); // assigned to the source order
    DeliveryBreakdown::query()->update(['method' => 'self_pickup']);

    $pages = [
        [User::where('role', 'salesperson')->first(), "/orders/{$source->id}"],
        [User::where('role', 'artist')->first(), "/artist/orders/{$source->id}"],
        [ocp_user('admin'), "/admin/orders/{$source->id}"],
        [ocp_user('boss'), "/boss/orders/{$source->id}"],
        [$dataEntry, "/data-entry/orders/{$source->id}"],
    ];

    foreach ($pages as [$user, $url]) {
        $this->actingAs($user)->get($url)->assertOk()->assertSee('Self Pickup'); // from dd_method_label()
    }
});
