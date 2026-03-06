<?php
declare(strict_types=1);
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestockRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required','string','uuid'],
            'quantity'     => ['required','integer','min:1'],
            'reason'       => ['required','string','max:255'],
        ];
    }
}
