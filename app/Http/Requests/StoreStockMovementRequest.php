<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Manual stock change from the Inventory page. Volume is entered in square feet. */
class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role is checked by the route middleware
    }

    public function rules(): array
    {
        return [
            'direction'   => ['required', 'in:add,deduct'],
            'type'        => ['required', 'in:restock,adjustment'],
            'quantity'    => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'volume_sqft' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'reason'      => ['required', 'string', 'max:500'],
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
                if (round((float) $this->input('quantity'), 2) == 0 && round((float) $this->input('volume_sqft'), 2) == 0) {
                    $validator->errors()->add('quantity', 'Enter a quantity or a volume to change.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Please give a reason for this stock change.',
        ];
    }
}
