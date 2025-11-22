<?php

namespace App\Http\Requests;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requester_id' => 'required|exists:requesters,id',
            'sub_service_id' => 'required|exists:sub_services,id',
            'sla_id' => 'required|exists:service_level_agreements,id',
            'assigned_to' => 'nullable|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'criticality_level' => 'required|in:BAJA,MEDIA,ALTA,CRITICA',
            'entry_channel' => 'required|in:' . implode(',', ServiceRequest::getEntryChannelValidationValues()),
        ];
    }

    public function messages(): array
    {
        return [
            'requester_id.required' => 'El solicitante es obligatorio.',
            'requester_id.exists' => 'El solicitante seleccionado no es válido.',
            'sub_service_id.required' => 'El sub-servicio es obligatorio.',
            'sla_id.required' => 'El SLA es obligatorio.',
            'title.required' => 'El título es obligatorio.',
            'description.required' => 'La descripción es obligatoria.',
            'criticality_level.required' => 'El nivel de criticidad es obligatorio.',
            'entry_channel.required' => 'El canal de entrada es obligatorio.',
            'entry_channel.in' => 'El canal de entrada seleccionado no es válido.',
        ];
    }
}
