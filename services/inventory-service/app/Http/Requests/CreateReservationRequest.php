<?php
declare(strict_types=1);
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateReservationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'saga_id'             => ['required','string','uuid'],
            'order_id'            => ['required','string','uuid'],
            'items'               => ['required','array','min:1'],
            'items.*.product_id'  => ['required','string','uuid'],
            'items.*.quantity'    => ['required','integer','min:1'],
            'items.*.unit_price'  => ['required','numeric','min:0'],
        ];
    }
}
