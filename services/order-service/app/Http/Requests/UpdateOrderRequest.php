<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the payload for updating an existing order.
 */
class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'items'                    => ['sometimes', 'array', 'min:1'],
            'items.*.product_id'       => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity'         => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price'       => ['required_with:items', 'numeric', 'min:0'],
            'shipping_address'         => ['sometimes', 'nullable', 'array'],
            'shipping_address.street'  => ['nullable', 'string', 'max:255'],
            'shipping_address.city'    => ['nullable', 'string', 'max:100'],
            'shipping_address.state'   => ['nullable', 'string', 'max:100'],
            'shipping_address.zip'     => ['nullable', 'string', 'max:20'],
            'shipping_address.country' => ['nullable', 'string', 'max:2'],
            'currency'                 => ['sometimes', 'string', 'size:3'],
            'notes'                    => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
