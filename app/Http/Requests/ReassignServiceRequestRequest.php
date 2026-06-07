<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReassignServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('assign-service-requests');
    }

    public function rules(): array
    {
        return [
            'assigned_to' => [
                'required',
                'exists:users,id',
                Rule::exists('technicians', 'user_id')
                    ->where(fn ($query) => $query->where('status', 'active')->whereNull('deleted_at')),
            ],
            'reassignment_reason' => 'required|string|min:10|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.required' => 'El técnico asignado es obligatorio.',
            'assigned_to.exists' => 'El técnico seleccionado no es válido o no tiene un registro de técnico activo.',
            'reassignment_reason.required' => 'La razón de reasignación es obligatoria.',
            'reassignment_reason.string' => 'La razón de reasignación debe ser un texto válido.',
            'reassignment_reason.min' => 'La razón de reasignación debe tener al menos 10 caracteres.',
            'reassignment_reason.max' => 'La razón de reasignación no debe exceder los 500 caracteres.',
        ];
    }
}
