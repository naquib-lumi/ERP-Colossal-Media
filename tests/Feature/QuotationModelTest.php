<?php

use App\Models\Company;
use App\Models\DocumentSequence;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Quotation;
use App\Models\User;

function qm_lead(): Lead
{
    $sales = User::firstOrCreate(['email' => 'salesperson@test.local'], ['name' => 'Test sales', 'password' => bcrypt('x'), 'role' => 'salesperson', 'status' => 'active']);

    return Lead::create(['salesperson_id' => $sales->id, 'company_name' => 'Skechers Malaysia Sdn Bhd', 'name' => 'Chang YK', 'phone' => '0123', 'email' => 'chang@skechers.test', 'status' => 'accept']);
}

function qm_quotation(array $attrs = []): Quotation
{
    $lead = qm_lead();

    return Quotation::create(array_merge([
        'company_id' => Company::default()->id, 'lead_id' => $lead->id, 'salesperson_id' => $lead->salesperson_id,
        'attention' => 'Mr. Chang YK', 'quotation_date' => '2026-10-09', 'grand_total' => 4899,
    ], $attrs));
}

test('quotation numbers are shared by both companies, per year, and start pending', function () {
    $media = qm_quotation();
    $xceed = qm_quotation(['company_id' => Company::where('code', 'colossal-xceed')->value('id')]);
    $next  = qm_quotation(['quotation_date' => '2027-01-04']);

    expect($media->quotation_number)->toBe('ST26-0001')
        ->and($xceed->quotation_number)->toBe('ST26-0002')
        ->and($next->quotation_number)->toBe('ST27-0001')
        ->and($media->status)->toBe(Quotation::STATUS_PENDING)
        ->and($media->isEditable())->toBeTrue();
});

test('numbering can continue the client\'s current sequence', function () {
    DocumentSequence::create(['key' => 'quotation', 'year' => 2026, 'last_number' => 252]);

    expect(qm_quotation()->quotation_number)->toBe('ST26-0253')
        ->and(qm_quotation()->quotation_number)->toBe('ST26-0254');
});

test('a quotation holds products with materials and priced items', function () {
    $q = qm_quotation();
    $banner = $q->products()->create(['product_name' => 'Skechers Big Logo', 'materials' => ['Clear Sticker', 'White Ink', 'Kiss Cut'], 'sort' => 1]);
    $banner->items()->create(['size_width' => 2480, 'size_height' => 979, 'size_unit' => 'mm', 'quantity' => 1, 'unit_price' => 392, 'total' => 392]);
    $q->products()->create(['product_name' => 'Hoarding Board', 'materials' => ['Tarpaulin'], 'sort' => 0])
        ->items()->create(['size_width' => 11992.8, 'size_height' => 2440, 'size_unit' => 'mm', 'quantity' => 1, 'unit_price' => 1261, 'total' => 1261]);

    $q = Quotation::with(['products.items', 'company', 'lead'])->find($q->id);

    expect($q->products->pluck('product_name')->all())->toBe(['Hoarding Board', 'Skechers Big Logo'])
        ->and($q->products[1]->materials)->toBe(['Clear Sticker', 'White Ink', 'Kiss Cut'])
        ->and($q->products[0]->items[0]->size_width)->toBe('11992.80')
        ->and($q->company->name)->toBe('COLOSSAL MEDIA SDN BHD')
        ->and($q->lead->company_name)->toBe('Skechers Malaysia Sdn Bhd')
        ->and($q->lead->quotations()->count())->toBe(1);
});

test('a converted quotation is locked and linked to one order', function () {
    $q = qm_quotation();
    $order = new Order();
    $order->forceFill(['lead_id' => $q->lead_id, 'companyName' => 'Skechers Malaysia Sdn Bhd', 'orderDate' => '2026-10-09', 'orderTitle' => 'Signage'])->save();

    $q->forceFill(['status' => Quotation::STATUS_CONVERTED, 'order_id' => $order->id, 'converted_at' => now()])->save();

    expect($q->refresh()->isConverted())->toBeTrue()
        ->and($q->isEditable())->toBeFalse()
        ->and($order->refresh()->quotation->is($q))->toBeTrue();

    $other = qm_quotation();
    expect(fn () => $other->forceFill(['order_id' => $order->id])->save())->toThrow(\Illuminate\Database\QueryException::class);
});
