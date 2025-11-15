<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PauseServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pause_reason' => 'required|string|min:10|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'pause_reason.required' => 'La razón de pausa es obligatoria.',
            'pause_reason.min' => 'La razón debe tener al menos 10 caracteres.',
            'pause_reason.max' => 'La razón no debe exceder los 500 caracteres.',
        ];
    }
}
