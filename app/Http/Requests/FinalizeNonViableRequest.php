<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeNonViableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'non_viable_reason' => 'required|string|min:10|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'non_viable_reason.required' => 'El concepto de no viabilidad es obligatorio.',
            'non_viable_reason.min' => 'El concepto debe tener al menos 10 caracteres.',
            'non_viable_reason.max' => 'El concepto no debe exceder los 1000 caracteres.',
        ];
    }
}
