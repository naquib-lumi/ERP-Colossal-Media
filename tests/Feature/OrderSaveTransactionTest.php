<?php

/*
 * Order create / add-product / sales-edit endpoints save all-or-nothing:
 * a failure part-way through must leave no partial order or product behind.
 */

use App\Models\Lead;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

function ostx_user(string $role, string $email = null): User
{
    $user = User::create([
        'name'     => "Test {$role}",
        'email'    => $email ?? "{$role}@test.local",
        'password' => bcrypt('secret'),
        'role'     => $role,
        'status'   => 'active',
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    return $user;
}

function ostx_lead(User $salesperson): Lead
{
    return Lead::create([
        'salesperson_id' => $salesperson->id,
        'company_name'   => 'Acme Sdn Bhd',
        'name'           => 'Ali',
        'phone'          => '0123456789',
        'status'         => 'new',
    ]);
}

function ostx_create_payload(Lead $lead, array $extra = []): array
{
    return array_merge([
        'lead_id'     => $lead->id,
        'orderTitle'  => 'Shop signage',
        'deadline'    => now()->addDays(7)->toDateString(),
        'approval'    => 1,
        'permit'      => 0,
        'orderDetail' => 'Two banners',
        'products'    => [[
            'product_name'  => 'Banner',
            'quantity'      => 2,
            'material_info' => 'Vinyl',
            'remarks'       => [['operation' => 'printing', 'remark' => 'Glossy']],
            'deliveries'    => [['method' => 'delivery', 'location' => 'Shop KL', 'date_time' => now()->addDays(6)->format('Y-m-d H:i')]],
        ]],
        'attachments' => [UploadedFile::fake()->create('artwork.pdf', 10, 'application/pdf')],
    ], $extra);
}

function ostx_counts(): array
{
    return [
        'orders'              => DB::table('orders')->count(),
        'products'            => DB::table('products')->count(),
        'product_remarks'     => DB::table('product_remarks')->count(),
        'delivery_breakdowns' => DB::table('delivery_breakdowns')->count(),
        'order_records'       => DB::table('order_records')->count(),
        'order_attachments'   => DB::table('order_attachments')->count(),
    ];
}

/** Makes the next product insert fail after the row is written. */
function ostx_fail_after_product_insert(): void
{
    Product::created(function () {
        throw new RuntimeException('Simulated failure after product insert');
    });
}

beforeEach(function () {
    Storage::fake('public');
});

$creators = [
    'artist' => ['artist', '/artist/orders'],
    'admin'  => ['admin', '/admin/orders'],
    'boss'   => ['boss', '/boss/orders'],
];

foreach ($creators as $key => [$role, $url]) {
    $extraFor = function () use ($role) {
        return $role === 'boss'
            ? ['assignee_artist_id' => ostx_user('artist', 'assignee@test.local')->id]
            : [];
    };

    test("{$key} create order saves the order and its products", function () use ($role, $url, $extraFor) {
        $lead = ostx_lead(ostx_user('salesperson'));
        $user = ostx_user($role);
        $extra = $extraFor();

        $this->actingAs($user)->post($url, ostx_create_payload($lead, $extra))
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionMissing('error');

        expect(DB::table('orders')->count())->toBe(1)
            ->and(DB::table('products')->where('productName', 'Banner')->count())->toBe(1)
            ->and(DB::table('product_remarks')->count())->toBe(1);
    });

    test("{$key} create order leaves nothing behind when saving fails", function () use ($role, $url, $extraFor) {
        $lead = ostx_lead(ostx_user('salesperson'));
        $user = ostx_user($role);
        $extra = $extraFor();
        $before = ostx_counts();

        ostx_fail_after_product_insert();

        $this->actingAs($user)->post($url, ostx_create_payload($lead, $extra))
            ->assertRedirect()
            ->assertSessionHas('error');

        expect(ostx_counts())->toBe($before);
    });
}

$productAdders = [
    'artist' => ['artist', '/artist/orders/%d/products'],
    'admin'  => ['admin', '/admin/orders/%d/products'],
    'boss'   => ['boss', '/boss/orders/%d/products'],
];

foreach ($productAdders as $key => [$role, $url]) {
    $makeOrder = function () {
        $order = new Order();
        $order->forceFill(['orderDate' => now()->toDateString(), 'orderTitle' => 'Existing', 'orderStatus' => 'in_progress'])->save();

        return $order;
    };

    $payload = [
        'product_name'  => 'Sticker',
        'quantity'      => 3,
        'material_info' => 'PVC',
        'remarks'       => [['operation' => 'printing', 'remark' => 'Matte']],
    ];

    test("{$key} add product saves the product and its remarks", function () use ($role, $url, $makeOrder, $payload) {
        $order = $makeOrder();

        $this->actingAs(ostx_user($role))->post(sprintf($url, $order->id), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        expect(DB::table('products')->where('OrderID', $order->id)->count())->toBe(1)
            ->and(DB::table('product_remarks')->count())->toBe(1);
    });

    test("{$key} add product leaves nothing behind when saving fails", function () use ($role, $url, $makeOrder, $payload) {
        $order = $makeOrder();
        ostx_fail_after_product_insert();

        $this->withoutExceptionHandling();

        expect(fn () => $this->actingAs(ostx_user($role))->post(sprintf($url, $order->id), $payload))
            ->toThrow(RuntimeException::class, 'Simulated failure after product insert');

        expect(DB::table('products')->where('OrderID', $order->id)->count())->toBe(0)
            ->and(DB::table('product_remarks')->count())->toBe(0);
    });
}

test('sales edit order rolls back every change when a later product fails', function () {
    $sales = ostx_user('salesperson');
    $lead  = ostx_lead($sales);

    $order = new Order();
    $order->forceFill([
        'lead_id' => $lead->id, 'salesperson_id' => $sales->id, 'orderDate' => now()->toDateString(),
        'orderTitle' => 'Original title', 'orderStatus' => 'to_assign', 'draft' => 0,
    ])->save();

    $mine = new Product();
    $mine->forceFill(['OrderID' => $order->id, 'productName' => 'Original product', 'totalQuantity' => 1])->save();
    $removed = new Product();
    $removed->forceFill(['OrderID' => $order->id, 'productName' => 'Will be removed', 'totalQuantity' => 1])->save();

    $otherOrder = new Order();
    $otherOrder->forceFill(['orderDate' => now()->toDateString(), 'orderTitle' => 'Other'])->save();
    $foreign = new Product();
    $foreign->forceFill(['OrderID' => $otherOrder->id, 'productName' => 'Not yours', 'totalQuantity' => 1])->save();

    $this->actingAs($sales)->put("/orders/{$order->id}", [
        'save_type'  => 'draft',
        'orderTitle' => 'Changed title',
        'products'   => [
            ['id' => $mine->ProductID, 'product_name' => 'Changed product', 'quantity' => 5],
            // belongs to another order: firstOrFail() throws after the changes above
            ['id' => $foreign->ProductID, 'product_name' => 'Hijack', 'quantity' => 1],
        ],
    ])->assertRedirect()->assertSessionHas('error');

    expect(DB::table('orders')->where('id', $order->id)->value('orderTitle'))->toBe('Original title')
        ->and(DB::table('products')->where('ProductID', $mine->ProductID)->value('productName'))->toBe('Original product')
        ->and(DB::table('products')->where('ProductID', $removed->ProductID)->exists())->toBeTrue()
        ->and(DB::table('products')->where('ProductID', $foreign->ProductID)->value('productName'))->toBe('Not yours');
});

test('sales edit order saves changes', function () {
    $sales = ostx_user('salesperson');
    $lead  = ostx_lead($sales);

    $order = new Order();
    $order->forceFill([
        'lead_id' => $lead->id, 'salesperson_id' => $sales->id, 'orderDate' => now()->toDateString(),
        'orderTitle' => 'Original title', 'orderStatus' => 'to_assign', 'draft' => 0,
    ])->save();

    $mine = new Product();
    $mine->forceFill(['OrderID' => $order->id, 'productName' => 'Original product', 'totalQuantity' => 1])->save();

    $this->actingAs($sales)->put("/orders/{$order->id}", [
        'save_type'  => 'draft',
        'orderTitle' => 'Changed title',
        'products'   => [
            ['id' => $mine->ProductID, 'product_name' => 'Changed product', 'quantity' => 5,
             'remarks' => [['operation' => 'printing', 'remark' => 'Glossy']],
             'deliveries' => [['method' => 'courier', 'location' => 'KL', 'datetime' => '2026-10-10 10:00']]],
        ],
    ])->assertRedirect()->assertSessionHas('success');

    expect(DB::table('orders')->where('id', $order->id)->value('orderTitle'))->toBe('Changed title')
        ->and(DB::table('products')->where('ProductID', $mine->ProductID)->value('productName'))->toBe('Changed product')
        ->and(DB::table('product_remarks')->where('ProductID', $mine->ProductID)->count())->toBe(1)
        ->and(DB::table('delivery_breakdowns')->where('ProductID', $mine->ProductID)->count())->toBe(1);
});
