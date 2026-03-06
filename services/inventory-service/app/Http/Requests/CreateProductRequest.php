<?php
declare(strict_types=1);
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'sku'                  => ['required','string','max:100'],
            'name'                 => ['required','string','max:255'],
            'description'          => ['nullable','string'],
            'category'             => ['nullable','string','max:100'],
            'unit_of_measure'      => ['required','string','max:50'],
            'minimum_stock_level'  => ['required','integer','min:0'],
            'is_active'            => ['boolean'],
            'attributes'           => ['nullable','array'],
        ];
    }
}
