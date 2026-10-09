<?php

namespace App\Http\Requests;

use App\Rules\KnownMaterial;
use App\Services\QuotationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Quotation create / edit form. Access is checked in the controller. */
class QuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $money = ['nullable', 'numeric', 'min:0', 'max:99999999.99'];

        return [
            'company_id'     => ['required', 'exists:companies,id'],
            'lead_id'        => ['required', Rule::in(QuotationService::leadsFor($this->user())->pluck('id')->all())],
            'attention'      => ['nullable', 'string', 'max:255'],
            'quotation_date' => ['required', 'date'],
            'terms'          => ['nullable', 'string', 'max:255'],
            'po_number'      => ['nullable', 'string', 'max:50'],
            'subtotal'       => $money,
            'discount'       => $money,
            'tax'            => $money,
            'grand_total'    => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'notes'          => ['nullable', 'string', 'max:2000'],

            'products'                          => ['required', 'array', 'min:1'],
            'products.*.product_name'           => ['required', 'string', 'max:255'],
            'products.*.description'            => ['nullable', 'string', 'max:2000'],
            'products.*.materials'              => ['nullable', 'array'],
            'products.*.materials.*'            => ['string', 'max:255', new KnownMaterial()],
            'products.*.items'                  => ['required', 'array', 'min:1'],
            'products.*.items.*.description'    => ['nullable', 'string', 'max:255'],
            'products.*.items.*.size_width'     => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'products.*.items.*.size_height'    => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'products.*.items.*.size_unit'      => ['nullable', Rule::in(QuotationService::SIZE_UNITS)],
            'products.*.items.*.quantity'       => ['required', 'integer', 'min:1', 'max:1000000'],
            'products.*.items.*.quantity_unit'  => ['nullable', Rule::in(QuotationService::QUANTITY_UNITS)],
            'products.*.items.*.unit_price'     => $money,
            'products.*.items.*.total'          => $money,
        ];
    }

    public function messages(): array
    {
        return [
            'lead_id.in'                       => 'Pick one of your customers.',
            'products.required'                => 'Add at least one product.',
            'products.*.product_name.required' => 'Every product needs a name.',
            'products.*.items.required'        => 'Every product needs at least one item.',
            'products.*.items.*.quantity.required' => 'Every item needs a quantity.',
            'products.*.items.*.quantity.integer'  => 'Quantity must be a whole number.',
        ];
    }
}
