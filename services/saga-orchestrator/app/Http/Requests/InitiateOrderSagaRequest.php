<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for the POST /api/sagas/order endpoint.
 */
class InitiateOrderSagaRequest extends FormRequest
{
    /**
     * Determine if the user is authorised to make this request.
     *
     * Authentication / authorisation is enforced by ApiAuthMiddleware, so we
     * return true here and let the middleware handle it.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'customer_id'           => ['required', 'string', 'max:255'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.product_id'    => ['required', 'string', 'max:255'],
            'items.*.quantity'      => ['required', 'integer', 'min:1'],
            'items.*.unit_price'    => ['required', 'numeric', 'min:0'],
            'total'                 => ['required', 'numeric', 'min:0'],
            'currency'              => ['sometimes', 'string', 'size:3'],
            'payment_method'        => ['required', 'string', 'in:credit_card,debit_card,paypal,bank_transfer'],
            'billing_address'       => ['required', 'array'],
            'billing_address.street'=> ['required', 'string', 'max:255'],
            'billing_address.city'  => ['required', 'string', 'max:100'],
            'billing_address.state' => ['required', 'string', 'max:100'],
            'billing_address.zip'   => ['required', 'string', 'max:20'],
            'billing_address.country' => ['required', 'string', 'size:2'],
            'shipping_address'      => ['sometimes', 'array'],
            'notification_channels' => ['sometimes', 'array'],
            'notification_channels.*' => ['string', 'in:email,sms,push'],
            'metadata'              => ['sometimes', 'array'],
        ];
    }

    /**
     * Human-readable attribute names for error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_id'                  => 'customer ID',
            'items'                        => 'order items',
            'items.*.product_id'           => 'product ID',
            'items.*.quantity'             => 'quantity',
            'items.*.unit_price'           => 'unit price',
            'total'                        => 'order total',
            'currency'                     => 'currency',
            'payment_method'               => 'payment method',
            'billing_address.street'       => 'billing street',
            'billing_address.city'         => 'billing city',
            'billing_address.state'        => 'billing state',
            'billing_address.zip'          => 'billing postal code',
            'billing_address.country'      => 'billing country',
            'notification_channels'        => 'notification channels',
        ];
    }

    /**
     * Custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'billing_address.country.size' => 'The billing country must be a 2-letter ISO country code.',
            'currency.size'                => 'The currency must be a 3-letter ISO 4217 code.',
            'payment_method.in'            => 'The selected payment method is not supported.',
        ];
    }
}
