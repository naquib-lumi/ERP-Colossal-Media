<?php

use App\Models\Company;
use App\Models\Lead;
use App\Models\Material;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function qp_user(string $role, ?string $email = null): User
{
    return User::firstOrCreate(['email' => $email ?? "{$role}@test.local"], ['name' => "Test {$role}", 'password' => bcrypt('x'), 'role' => $role, 'status' => 'active']);
}

function qp_lead(User $sales, string $company = 'Skechers Malaysia Sdn Bhd'): Lead
{
    return Lead::create(['salesperson_id' => $sales->id, 'company_name' => $company, 'name' => 'Chang YK', 'phone' => '0123', 'email' => 'chang@x.test', 'status' => 'accept']);
}

function qp_materials(): void
{
    $typeId = DB::table('material_types')->value('id')
        ?? DB::table('material_types')->insertGetId(['name' => 'Stickers / Films', 'created_at' => now(), 'updated_at' => now()]);
    foreach (['Tarpaulin', 'Clear Sticker', 'White Ink'] as $name) {
        Material::create(['materialName' => $name, 'material_type_id' => $typeId, 'unitCost' => 0.01]);
    }
}

function qp_payload(Lead $lead, array $over = []): array
{
    return array_replace_recursive([
        'company_id' => Company::where('code', 'colossal-xceed')->value('id'),
        'lead_id' => $lead->id, 'attention' => 'Mr. Chang YK', 'quotation_date' => '2026-10-09', 'terms' => 'COD',
        'subtotal' => 1653, 'discount' => 0, 'tax' => 0, 'grand_total' => 1653,
        'products' => [
            ['product_name' => 'Skechers Big Logo', 'materials' => ['clear sticker ', 'White Ink'], 'items' => [
                ['description' => 'Logo', 'size_width' => 2480, 'size_height' => 979, 'size_unit' => 'mm', 'quantity' => 1, 'quantity_unit' => 'pcs', 'unit_price' => 392, 'total' => 392],
            ]],
            ['product_name' => 'Hoarding Board', 'materials' => ['Tarpaulin'], 'items' => [
                ['size_width' => 11992.8, 'size_height' => 2440, 'size_unit' => 'mm', 'quantity' => 1, 'unit_price' => 1261, 'total' => 1261],
            ]],
        ],
    ], $over);
}

test('a salesperson creates a quotation with products, materials and typed prices', function () {
    qp_materials();
    $sales = qp_user('salesperson');
    $lead = qp_lead($sales);

    $this->actingAs($sales)->get('/quotations/create')->assertOk()->assertSee('Skechers Malaysia Sdn Bhd')->assertSee('Tarpaulin');

    $response = $this->actingAs($sales)->post('/quotations', qp_payload($lead));
    $q = Quotation::with('products.items')->latest('id')->first();
    $response->assertRedirect("/quotations/{$q->id}");

    expect($q->quotation_number)->toBe('ST26-0001')
        ->and($q->company->code)->toBe('colossal-xceed')
        ->and($q->salesperson_id)->toBe($sales->id)
        ->and($q->created_by)->toBe($sales->id)
        ->and($q->grand_total)->toBe('1653.00')
        ->and($q->products->pluck('product_name')->all())->toBe(['Skechers Big Logo', 'Hoarding Board'])
        ->and($q->products[0]->materials)->toBe(['Clear Sticker', 'White Ink'])   // list spelling
        ->and($q->products[1]->items[0]->size_width)->toBe('11992.80');

    $this->actingAs($sales)->get("/quotations/{$q->id}")->assertOk()
        ->assertSeeInOrder(['ST26-0001', 'COLOSSAL XCEED SDN BHD', 'Skechers Big Logo', 'Clear Sticker + White Ink', '392.00', 'Hoarding Board', '1,261.00', 'TOTAL (RM)', '1,653.00']);
    $this->actingAs($sales)->get('/quotations')->assertOk()->assertSee('ST26-0001')->assertSee('Pending');
});

test('editing replaces products and items; converted quotations are locked', function () {
    qp_materials();
    $artist = qp_user('artist');
    $lead = qp_lead(qp_user('salesperson'));
    $this->actingAs($artist)->post('/quotations', qp_payload($lead));
    $q = Quotation::latest('id')->first();

    $edit = qp_payload($lead, ['grand_total' => 500]);
    $edit['products'] = [['product_name' => 'Wall Sticker', 'materials' => ['Tarpaulin'], 'items' => [['quantity' => 2, 'unit_price' => 250, 'total' => 500]]]];
    $this->actingAs($artist)->get("/quotations/{$q->id}/edit")->assertOk()->assertSee('Skechers Big Logo', false);
    $this->actingAs($artist)->put("/quotations/{$q->id}", $edit)->assertRedirect("/quotations/{$q->id}");

    $q->refresh()->load('products.items');
    expect($q->products)->toHaveCount(1)
        ->and($q->products[0]->items[0]->quantity)->toBe(2)
        ->and($q->grand_total)->toBe('500.00')
        ->and($q->quotation_number)->toBe('ST26-0001')
        ->and($q->salesperson_id)->toBe($lead->salesperson_id);

    $q->forceFill(['status' => Quotation::STATUS_CONVERTED])->save();
    $this->actingAs($artist)->get("/quotations/{$q->id}/edit")->assertRedirect("/quotations/{$q->id}");
    $this->actingAs($artist)->put("/quotations/{$q->id}", qp_payload($lead, ['grand_total' => 1]))->assertRedirect("/quotations/{$q->id}");
    expect($q->refresh()->grand_total)->toBe('500.00');
});

test('validation: customer, products, items, materials from the list, whole quantities', function () {
    qp_materials();
    $sales = qp_user('salesperson');
    $lead = qp_lead($sales);
    $otherLead = qp_lead(qp_user('salesperson', 'other@test.local'), 'Other Sdn Bhd');

    $bad = qp_payload($lead);
    $bad['products'][0]['materials'] = ['Unobtainium'];
    $bad['products'][1]['items'][0]['quantity'] = 1.5;
    $this->actingAs($sales)->from('/quotations/create')->post('/quotations', $bad)
        ->assertSessionHasErrors(['products.0.materials.0', 'products.1.items.0.quantity']);

    $this->actingAs($sales)->from('/quotations/create')->post('/quotations', qp_payload($otherLead))->assertSessionHasErrors('lead_id');

    $none = qp_payload($lead);
    $none['products'] = [];
    $this->actingAs($sales)->from('/quotations/create')->post('/quotations', $none)->assertSessionHasErrors('products');

    expect(Quotation::count())->toBe(0);
});

test('who sees and edits quotations', function () {
    qp_materials();
    $mine  = qp_user('salesperson');
    $other = qp_user('salesperson', 'other@test.local');
    $this->actingAs($mine)->post('/quotations', qp_payload(qp_lead($mine)));
    $q = Quotation::latest('id')->first();

    $this->actingAs($other)->get("/quotations/{$q->id}")->assertForbidden();
    $this->actingAs($other)->get('/quotations')->assertOk()->assertDontSee('ST26-0001');

    foreach (['head-salesperson', 'head-artist', 'artist'] as $role) {
        $this->actingAs(qp_user($role))->get("/quotations/{$q->id}")->assertOk()->assertSee('Edit');
    }
    foreach (['admin', 'boss'] as $role) {
        $this->actingAs(qp_user($role))->get("/quotations/{$q->id}")->assertOk();
        $this->actingAs(qp_user($role))->get('/quotations/create')->assertForbidden();
        $this->actingAs(qp_user($role))->get("/quotations/{$q->id}/edit")->assertForbidden();
    }
    foreach (['data-entry', 'operations-printing', 'operations-dispatch-control'] as $role) {
        $this->actingAs(qp_user($role))->get('/quotations')->assertForbidden();
    }
});
