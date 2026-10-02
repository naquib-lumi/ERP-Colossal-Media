<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Manual stock change from the Inventory page, in whole units of the material. */
class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role is checked by the route middleware
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', 'in:add,deduct'],
            'type'      => ['required', 'in:restock,adjustment'],
            'quantity'  => ['required', 'integer', 'min:1', 'max:1000000000'],
            'reason'    => ['required', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                if ($this->input('type') === 'restock' && $this->input('direction') !== 'add') {
                    $validator->errors()->add('type', 'A restock can only add stock. Use an adjustment to deduct.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Enter the quantity to change.',
            'quantity.integer'  => 'Quantity must be a whole number.',
            'quantity.min'      => 'Quantity must be at least 1.',
            'reason.required'   => 'Please give a reason for this stock change.',
        ];
    }
}
