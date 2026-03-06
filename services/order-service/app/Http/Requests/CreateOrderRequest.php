<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the payload for creating a new order.
 */
class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'customer_id'              => ['required', 'string', 'max:255'],
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.product_id'       => ['required', 'string', 'max:255'],
            'items.*.quantity'         => ['required', 'integer', 'min:1'],
            'items.*.unit_price'       => ['required', 'numeric', 'min:0'],
            'shipping_address'         => ['nullable', 'array'],
            'shipping_address.street'  => ['nullable', 'string', 'max:255'],
            'shipping_address.city'    => ['nullable', 'string', 'max:100'],
            'shipping_address.state'   => ['nullable', 'string', 'max:100'],
            'shipping_address.zip'     => ['nullable', 'string', 'max:20'],
            'shipping_address.country' => ['nullable', 'string', 'max:2'],
            'currency'                 => ['nullable', 'string', 'size:3'],
            'notes'                    => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.required'              => 'At least one order item is required.',
            'items.*.product_id.required' => 'Each item must have a product_id.',
            'items.*.quantity.min'        => 'Item quantity must be at least 1.',
            'items.*.unit_price.min'      => 'Item unit price cannot be negative.',
        ];
    }
}
