<?php

use App\Mail\DeliveryOrderMail;
use App\Models\DeliveryBreakdown;
use App\Models\DeliveryOrder;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\DeliveryOrderService;
use Illuminate\Support\Facades\Mail;

function dlo_user(string $role, ?string $email = null): User
{
    $user = User::firstOrCreate(['email' => $email ?? "{$role}@test.local"], ['name' => "Test {$role}", 'password' => bcrypt('x'), 'role' => $role, 'status' => 'active']);
    $user->forceFill(['email_verified_at' => now()])->save();

    return $user;
}

function dlo_product(Order $order, string $name, string $status = 'in_progress'): Product
{
    $p = new Product();
    $p->forceFill(['OrderID' => $order->id, 'productName' => $name, 'totalQuantity' => 1, 'status' => $status])->save();

    return $p;
}

function dlo_delivery(Product $p, string $location, int $qty, ?string $date = '2026-10-10', string $method = 'delivery'): DeliveryBreakdown
{
    return DeliveryBreakdown::create(['ProductID' => $p->ProductID, 'method' => $method, 'location' => $location, 'quantity' => $qty, 'date' => $date, 'time' => '10:00:00']);
}

/**
 * Submitted order: Banner → Shop KL ×3 (10 Oct) and Branch PJ ×2; Sticker → " shop  kl " ×5 (9 Oct, courier);
 * a rejected product and an empty location that must be left out.
 */
function dlo_order(array $attrs = []): array
{
    $sales = dlo_user('salesperson');
    $lead = Lead::create(['salesperson_id' => $sales->id, 'company_name' => 'Acme Sdn Bhd', 'name' => 'Ali', 'phone' => '0123', 'email' => 'ali@acme.test', 'status' => 'accept']);

    $order = new Order();
    $order->forceFill(array_merge([
        'lead_id' => $lead->id, 'salesperson_id' => $sales->id, 'companyName' => 'Acme Sdn Bhd', 'leadName' => 'Ali', 'leadEmail' => 'ali@acme.test',
        'orderDate' => '2026-10-01', 'orderTitle' => 'Shop signage', 'orderStatus' => 'completed', 'submit' => 1, 'draft' => 0,
    ], $attrs))->save();

    $banner  = dlo_product($order, 'Banner');
    $sticker = dlo_product($order, 'Sticker');
    $rejected = dlo_product($order, 'Rejected job', 'rejected');

    $rows = [
        'kl'  => dlo_delivery($banner, 'Shop KL', 3),
        'pj'  => dlo_delivery($banner, 'Branch PJ', 2),
        'kl2' => dlo_delivery($sticker, ' shop  kl ', 5, '2026-10-09', 'courier'),
    ];
    dlo_delivery($rejected, 'Shop KL', 9);
    dlo_delivery($sticker, '  ', 1);

    return [$order->refresh(), $rows];
}

function dlo_sync(Order $order)
{
    return app(DeliveryOrderService::class)->sync($order);
}

test('one delivery order per location, merging spelling differences and leaving out rejected products', function () {
    [$order] = dlo_order();

    $dos = dlo_sync($order);

    expect($dos)->toHaveCount(2);
    [$kl, $pj] = [$dos->firstWhere('location_key', 'shop kl'), $dos->firstWhere('location_key', 'branch pj')];

    expect($kl->do_number)->toMatch('/^DO-\d{4}-\d{4}$/')
        ->and($kl->location)->toBe('Shop KL')
        ->and($kl->lines->pluck('description')->all())->toBe(['Banner', 'Sticker'])
        ->and($kl->totalQuantity())->toBe(8)
        ->and($kl->methods)->toBe('delivery, courier')
        ->and($kl->delivery_date->toDateString())->toBe('2026-10-09') // earliest line
        ->and($pj->lines)->toHaveCount(1)
        ->and($pj->totalQuantity())->toBe(2);
});

test('updating keeps numbers, cancels removed locations and brings them back', function () {
    [$order, $rows] = dlo_order();
    $service = app(DeliveryOrderService::class);
    $first = dlo_sync($order)->keyBy('location_key');
    expect($service->isOutOfDate($order))->toBeFalse();

    $stamp = DeliveryOrder::orderBy('id')->pluck('updated_at', 'id');
    $this->travel(1)->minutes();
    dlo_sync($order); // nothing changed
    expect(DeliveryOrder::orderBy('id')->pluck('updated_at', 'id'))->toEqual($stamp);

    $rows['kl']->update(['quantity' => 10]);
    $rows['pj']->delete();
    expect($service->isOutOfDate($order))->toBeTrue();

    $now = dlo_sync($order);
    expect($now)->toHaveCount(1)
        ->and($now->first()->do_number)->toBe($first['shop kl']->do_number)
        ->and($now->first()->fresh()->totalQuantity())->toBe(15)
        ->and(DeliveryOrder::where('location_key', 'branch pj')->value('status'))->toBe('cancelled');

    dlo_delivery(Product::where('productName', 'Banner')->first(), 'BRANCH PJ', 1);
    dlo_sync($order);
    $pj = DeliveryOrder::where('location_key', 'branch pj')->sole();
    expect($pj->status)->toBe('issued')->and($pj->do_number)->toBe($first['branch pj']->do_number);
});

test('delivery orders are only created for submitted orders', function () {
    [$order] = dlo_order(['submit' => 0, 'draft' => 1, 'orderStatus' => 'in_progress']);

    $this->actingAs(dlo_user('admin'))->from("/orders/{$order->id}/delivery-orders")
        ->post("/orders/{$order->id}/delivery-orders/sync")
        ->assertSessionHas('error', 'Delivery orders can be created once the order is submitted.');

    expect(DeliveryOrder::count())->toBe(0);
});

test('who may view, create and email delivery orders', function () {
    [$order] = dlo_order();

    foreach (['admin', 'boss', 'operations-dispatch-control'] as $role) {
        $this->actingAs(dlo_user($role))->post("/orders/{$order->id}/delivery-orders/sync")->assertRedirect()->assertSessionHas('success');
    }
    $do = DeliveryOrder::first();

    foreach (['operations-delivery-installation', 'head-salesperson', 'salesperson'] as $role) {
        $this->actingAs(dlo_user($role))->post("/orders/{$order->id}/delivery-orders/sync")->assertForbidden();
        $this->actingAs(dlo_user($role))->get("/orders/{$order->id}/delivery-orders")->assertOk();
        $this->actingAs(dlo_user($role))->get("/delivery-orders/{$do->id}")->assertOk();
    }

    $this->actingAs(dlo_user('salesperson', 'other@test.local'))->get("/delivery-orders/{$do->id}")->assertForbidden();
    $this->actingAs(dlo_user('artist'))->get("/delivery-orders/{$do->id}")->assertForbidden();
    $this->actingAs(dlo_user('operations-printing'))->get('/delivery-orders')->assertForbidden();

    Mail::fake();
    $this->actingAs(dlo_user('operations-delivery-installation'))->post("/delivery-orders/{$do->id}/email", ['to' => 'ali@acme.test'])->assertForbidden();
    Mail::assertNothingSent();
});

test('print page shows the delivery order and the PDF is a real PDF', function () {
    [$order] = dlo_order();
    $do = dlo_sync($order)->firstWhere('location_key', 'shop kl');
    $admin = dlo_user('admin');

    $this->actingAs($admin)->get("/delivery-orders/{$do->id}")
        ->assertOk()
        ->assertSeeInOrder(['DELIVERY ORDER', $do->do_number, $order->order_number, 'Acme Sdn Bhd', 'Shop KL', 'Banner', 'Sticker', 'Total quantity', '8', 'Received in good order'])
        ->assertSee('Courier')
        ->assertDontSee('Rejected job');

    $pdf = $this->actingAs($admin)->get("/delivery-orders/{$do->id}/pdf");
    $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
    expect(substr($pdf->getContent(), 0, 4))->toBe('%PDF')
        ->and($pdf->headers->get('content-disposition'))->toContain("{$do->do_number}.pdf");
});

test('emailing sends the PDF, records it, and flags later changes', function () {
    Mail::fake();
    [$order, $rows] = dlo_order();
    $do = dlo_sync($order)->firstWhere('location_key', 'shop kl');

    $this->actingAs(dlo_user('salesperson'))
        ->post("/delivery-orders/{$do->id}/email", ['to' => 'ali@acme.test'])
        ->assertRedirect()->assertSessionHas('success');

    Mail::assertSent(DeliveryOrderMail::class, function (DeliveryOrderMail $mail) use ($do) {
        $pdf = collect($mail->attachments())->first(fn ($a) => $a->as === "{$do->do_number}.pdf");

        return $mail->hasTo('ali@acme.test')
            && $pdf !== null
            && $pdf->mime === 'application/pdf'
            && $mail->deliveryOrder->is($do);
    });
    Mail::assertSentCount(1);

    $do->refresh();
    expect($do->emailed_to)->toBe('ali@acme.test')->and($do->emailed_at)->not->toBeNull()->and($do->changedSinceEmailed())->toBeFalse();

    $rows['kl']->update(['quantity' => 4]);
    dlo_sync($order);
    expect($do->fresh()->changedSinceEmailed())->toBeTrue();

    $this->actingAs(dlo_user('admin'))->post("/delivery-orders/{$do->id}/email", ['to' => 'not-an-email'])->assertSessionHasErrors('to');
});

test('cancelled delivery orders cannot be emailed', function () {
    Mail::fake();
    [$order, $rows] = dlo_order();
    $do = dlo_sync($order)->firstWhere('location_key', 'branch pj');
    $rows['pj']->delete();
    dlo_sync($order);

    $this->actingAs(dlo_user('admin'))->post("/delivery-orders/{$do->id}/email", ['to' => 'ali@acme.test'])
        ->assertSessionHas('error');
    Mail::assertNothingSent();
});

test('the list shows delivery orders, a salesperson only for their own leads', function () {
    [$order] = dlo_order();
    dlo_sync($order);
    $numbers = DeliveryOrder::pluck('do_number');

    $this->actingAs(dlo_user('operations-dispatch-control'))->get('/delivery-orders')->assertOk()->assertSee($numbers->all());
    $this->actingAs(dlo_user('salesperson'))->get('/delivery-orders')->assertOk()->assertSee($numbers->all());
    $this->actingAs(dlo_user('salesperson', 'other@test.local'))->get('/delivery-orders')->assertOk()->assertDontSee($numbers->first());
    $this->actingAs(dlo_user('admin'))->get("/delivery-orders?q=branch")->assertOk()->assertSee('Branch PJ')->assertDontSee('Shop KL');

    $this->actingAs(dlo_user('admin'))->get("/admin/orders/{$order->id}")->assertOk()->assertSee('Delivery orders');
});
