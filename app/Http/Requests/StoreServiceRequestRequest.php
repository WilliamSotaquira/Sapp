<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequestRequest extends FormRequest
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
            'requester_id' => 'required|exists:requesters,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'sub_service_id' => 'required|exists:sub_services,id',
            'criticality_level' => 'required|in:BAJA,MEDIA,ALTA,URGENTE,CRITICA',
            'service_id' => 'required|exists:services,id',
            'family_id' => 'required|exists:service_families,id',
            'sla_id' => 'required|exists:service_level_agreements,id',
            'requested_by' => 'required|exists:users,id',
            'web_routes' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'requester_id.required' => 'Debe seleccionar un solicitante.',
            'requester_id.exists' => 'El solicitante seleccionado no es válido.',
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no debe exceder los 255 caracteres.',
            'description.required' => 'La descripción es obligatoria.',
            'sub_service_id.required' => 'Debe seleccionar un sub-servicio.',
            'sub_service_id.exists' => 'El sub-servicio seleccionado no es válido.',
            'criticality_level.required' => 'Debe seleccionar un nivel de criticidad.',
            'criticality_level.in' => 'El nivel de criticidad seleccionado no es válido.',
            'service_id.required' => 'Debe seleccionar un servicio.',
            'service_id.exists' => 'El servicio seleccionado no es válido.',
            'family_id.required' => 'Debe seleccionar una familia de servicios.',
            'family_id.exists' => 'La familia de servicios seleccionada no es válida.',
            'sla_id.required' => 'Debe seleccionar un SLA.',
            'sla_id.exists' => 'El SLA seleccionado no es válido.',
            'requested_by.required' => 'Debe especificar quién solicita.',
            'requested_by.exists' => 'El usuario que solicita no es válido.',
            'web_routes.required' => 'Las rutas web son obligatorias.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Procesar web_routes si viene como JSON string
        if ($this->has('web_routes') && is_string($this->web_routes)) {
            $decoded = json_decode($this->web_routes, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['web_routes' => json_encode($decoded)]);
            }
        }
    }
}
