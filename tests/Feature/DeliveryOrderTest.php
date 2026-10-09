<?php

use App\Mail\DeliveryOrderMail;
use App\Models\Company;
use App\Models\DeliveryBreakdown;
use App\Models\DeliveryOrder;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\DeliveryOrderService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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
        ->assertSee(['COLOSSAL MEDIA SDN BHD', '658233-U', 'Alam Premier Industrial Park', 'Fax: 03-5103 6610', 'assets/img/companies/colossal-media.png'])
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
            && $mail->deliveryOrder->is($do)
            && $mail->envelope()->subject === "Delivery Order {$do->do_number} – COLOSSAL MEDIA SDN BHD";
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

test('the two issuing companies exist and Colossal Media is the default', function () {
    expect(Company::orderBy('id')->pluck('name')->all())->toBe(['COLOSSAL MEDIA SDN BHD', 'COLOSSAL XCEED SDN BHD'])
        ->and(Company::default()->code)->toBe('colossal-media')
        ->and(Company::all()->every(fn (Company $c) => $c->logoPath() !== null))->toBeTrue();
});

test('only dispatch, delivery & installation, admin and boss can print or download a DO', function () {
    [$order] = dlo_order();
    $do = dlo_sync($order)->firstWhere('location_key', 'shop kl');

    foreach (['admin', 'boss', 'operations-dispatch-control', 'operations-delivery-installation'] as $role) {
        $this->actingAs(dlo_user($role))->get("/delivery-orders/{$do->id}/pdf")->assertOk();
        $this->actingAs(dlo_user($role))->get("/delivery-orders/{$do->id}")->assertOk()->assertSee('window.print()', false);
    }
    foreach (['salesperson', 'head-salesperson'] as $role) {
        $this->actingAs(dlo_user($role))->get("/delivery-orders/{$do->id}/pdf")->assertForbidden();
        $this->actingAs(dlo_user($role))->get("/delivery-orders/{$do->id}")->assertOk()->assertDontSee('window.print()', false);
    }
});

test('delivery staff mark a DO delivered with a photo of the signed DO', function () {
    Storage::fake('local');
    [$order] = dlo_order();
    $do = dlo_sync($order)->firstWhere('location_key', 'shop kl');
    $driver = dlo_user('operations-delivery-installation');

    $this->actingAs($driver)->get("/delivery-orders/{$do->id}/status")->assertOk()->assertSee('Mark as delivered');

    $this->actingAs($driver)
        ->post("/delivery-orders/{$do->id}/deliver", [
            'delivered_at' => now()->timezone('Asia/Kuala_Lumpur')->subHour()->format('Y-m-d\TH:i'),
            'signed_photo' => UploadedFile::fake()->image('signed.jpg', 3000, 2000),
            'delivery_remarks' => 'Received by store manager',
        ])
        ->assertRedirect("/delivery-orders/{$do->id}/status");

    $do->refresh();
    expect($do->isDelivered())->toBeTrue()
        ->and($do->delivered_by)->toBe($driver->id)
        ->and($do->delivery_remarks)->toBe('Received by store manager')
        ->and(Storage::disk('local')->exists($do->signed_photo_path))->toBeTrue()
        ->and(getimagesizefromstring(Storage::disk('local')->get($do->signed_photo_path))[0])->toBe(1600) // scaled down
        ->and($do->events()->pluck('event')->all())->toContain('delivered', 'created');

    // Sales can see the status and the photo, but not mark anything.
    $this->actingAs(dlo_user('salesperson'))->get("/delivery-orders/{$do->id}/status")
        ->assertOk()->assertSee('Delivered')->assertSee('Received by store manager')->assertDontSee('Mark as delivered');
    $this->actingAs(dlo_user('salesperson'))->get("/delivery-orders/{$do->id}/signed-photo")->assertOk();

    // A second confirmation is refused.
    $this->actingAs($driver)->post("/delivery-orders/{$do->id}/deliver", [
        'delivered_at' => now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d\TH:i'),
        'signed_photo' => UploadedFile::fake()->image('again.jpg'),
    ])->assertSessionHas('error');
});

test('marking delivered: sales are refused, a photo is required, no future time', function () {
    Storage::fake('local');
    [$order] = dlo_order();
    $do = dlo_sync($order)->firstWhere('location_key', 'shop kl');
    $dispatch = dlo_user('operations-dispatch-control');
    $nowKl = now()->timezone('Asia/Kuala_Lumpur');

    $this->actingAs(dlo_user('salesperson'))->post("/delivery-orders/{$do->id}/deliver", [
        'delivered_at' => $nowKl->format('Y-m-d\TH:i'), 'signed_photo' => UploadedFile::fake()->image('s.jpg'),
    ])->assertForbidden();

    $this->actingAs($dispatch)->from("/delivery-orders/{$do->id}/status")
        ->post("/delivery-orders/{$do->id}/deliver", ['delivered_at' => $nowKl->format('Y-m-d\TH:i')])
        ->assertSessionHasErrors('signed_photo');

    $this->actingAs($dispatch)->from("/delivery-orders/{$do->id}/status")
        ->post("/delivery-orders/{$do->id}/deliver", ['delivered_at' => $nowKl->addDay()->format('Y-m-d\TH:i'), 'signed_photo' => UploadedFile::fake()->image('s.jpg')])
        ->assertSessionHasErrors('delivered_at');

    expect($do->refresh()->isDelivered())->toBeFalse();
});

test('a delivered DO is frozen: order changes no longer update or cancel it', function () {
    [$order, $rows] = dlo_order();
    $kl = dlo_sync($order)->firstWhere('location_key', 'shop kl');
    $kl->forceFill(['delivered_at' => now(), 'delivered_by' => dlo_user('operations-dispatch-control')->id])->save();
    $hash = $kl->content_hash;

    $rows['kl']->update(['quantity' => 99]);          // change the delivered location
    $rows['pj']->update(['quantity' => 7]);           // and another one
    $service = app(DeliveryOrderService::class);
    expect($service->isOutOfDate($order))->toBeTrue();   // PJ changed
    $service->sync($order);
    expect($service->isOutOfDate($order))->toBeFalse()   // KL difference ignored once delivered
        ->and($kl->refresh()->content_hash)->toBe($hash)
        ->and(DeliveryOrder::where('location_key', 'branch pj')->first()->events()->pluck('event')->all())->toContain('updated');

    // Removing the delivered location does not cancel its DO.
    DeliveryBreakdown::whereIn('BreakdownID', [$rows['kl']->BreakdownID, $rows['kl2']->BreakdownID])->delete();
    $service->sync($order);
    expect($kl->refresh()->isCancelled())->toBeFalse();
});
