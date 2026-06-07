<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommitmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:1|max:255',
            'description' => 'required|string|min:1|max:2000',
            'technician_id' => 'required|exists:users,id',
            'due_date' => 'required|date|after_or_equal:today',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título del compromiso es obligatorio.',
            'title.min' => 'El título debe tener al menos 1 carácter.',
            'title.max' => 'El título no debe exceder los 255 caracteres.',
            'description.required' => 'La descripción del compromiso es obligatoria.',
            'description.min' => 'La descripción debe tener al menos 1 carácter.',
            'description.max' => 'La descripción no debe exceder los 2000 caracteres.',
            'technician_id.required' => 'Debe asignar un responsable para el compromiso.',
            'technician_id.exists' => 'El responsable seleccionado no es válido.',
            'due_date.required' => 'La fecha de vencimiento es obligatoria.',
            'due_date.date' => 'La fecha de vencimiento no tiene un formato válido.',
            'due_date.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a hoy.',
        ];
    }
}
