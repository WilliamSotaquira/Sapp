<?php

namespace App\Http\Requests;

use App\Models\ServiceRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('company_id')) {
            $this->merge(['company_id' => session('current_company_id')]);
        }

        // Evitar depender de hidden fields del frontend para cambios de subservicio.
        // Si llega sub_service_id, derivamos family_id, service_id y sla_id desde BD.
        $subServiceId = $this->input('sub_service_id');
        if ($subServiceId) {
            $subService = \App\Models\SubService::with('service.family')->find($subServiceId);
            if ($subService) {
                $this->merge([
                    'service_id' => $subService->service_id,
                    'family_id' => $subService->service?->family?->id,
                ]);

                $criticalityLevel = (string) ($this->input('criticality_level') ?: 'MEDIA');
                $serviceSubserviceIds = \App\Models\ServiceSubservice::where('sub_service_id', $subService->id)
                    ->pluck('id');

                if ($serviceSubserviceIds->isNotEmpty()) {
                    $slaQuery = \App\Models\ServiceLevelAgreement::query()
                        ->whereIn('service_subservice_id', $serviceSubserviceIds)
                        ->where('is_active', true);

                    $matchedByCriticality = (clone $slaQuery)
                        ->where('criticality_level', $criticalityLevel)
                        ->orderBy('id')
                        ->first();

                    $fallbackSla = $matchedByCriticality ?: (clone $slaQuery)->orderBy('id')->first();

                    if ($fallbackSla) {
                        $this->merge([
                            'sla_id' => $fallbackSla->id,
                        ]);
                    }
                }
            }
        }
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'requester_id' => 'required|exists:requesters,id',
            'sub_service_id' => 'required|exists:sub_services,id',
            'sla_id' => 'required|exists:service_level_agreements,id',
            'assigned_to' => 'nullable|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'criticality_level' => 'required|in:BAJA,MEDIA,ALTA,URGENTE,CRITICA',
            'entry_channel' => 'required|in:' . implode(',', ServiceRequest::getEntryChannelValidationValues()),
            'is_reportable' => 'sometimes|boolean',
            'due_date' => 'nullable|date',
            'created_at' => 'sometimes|required|date|before_or_equal:now',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $subServiceId = $this->input('sub_service_id');
            $familyId = $this->input('family_id');
            $companyId = $this->input('company_id') ?: session('current_company_id');
            $activeContractId = $companyId
                ? \App\Models\Company::where('id', $companyId)->value('active_contract_id')
                : null;

            if ($subServiceId) {
                $subService = \App\Models\SubService::with('service.family')->find($subServiceId);
                $family = $subService?->service?->family;
                if ($activeContractId && $family && (string) $family->contract_id !== (string) $activeContractId) {
                    $validator->errors()->add('sub_service_id', 'El subservicio no pertenece al contrato activo del espacio de trabajo.');
                }

                $serviceSubserviceIds = \App\Models\ServiceSubservice::query()
                    ->where('sub_service_id', $subServiceId)
                    ->pluck('id');

                $hasAnyActiveSla = $serviceSubserviceIds->isNotEmpty()
                    && \App\Models\ServiceLevelAgreement::query()
                        ->whereIn('service_subservice_id', $serviceSubserviceIds)
                        ->where('is_active', true)
                        ->exists();

                if (!$hasAnyActiveSla) {
                    $validator->errors()->add('sub_service_id', 'El subservicio seleccionado no tiene un SLA activo configurado.');
                    return;
                }

                $slaId = $this->input('sla_id');
                if ($slaId) {
                    $hasMatchingSla = \App\Models\ServiceSubservice::query()
                        ->where('sub_service_id', $subServiceId)
                        ->whereHas('serviceLevelAgreements', function ($q) use ($slaId) {
                            $q->where('id', $slaId)->where('is_active', true);
                        })
                        ->exists();

                    if (!$hasMatchingSla) {
                        $validator->errors()->add('sla_id', 'El SLA no corresponde al subservicio seleccionado.');
                    }
                }
            }

            $requesterId = $this->input('requester_id');
            if ($requesterId && $companyId) {
                $requesterCompanyId = \App\Models\Requester::where('id', $requesterId)->value('company_id');
                if ($requesterCompanyId && (string) $requesterCompanyId !== (string) $companyId) {
                    $validator->errors()->add('requester_id', 'El solicitante no pertenece al espacio de trabajo actual.');
                }
            }

            $routeServiceRequest = $this->route('service_request') ?? $this->route('serviceRequest');
            if ($routeServiceRequest instanceof ServiceRequest && $this->filled('created_at')) {
                try {
                    $createdAt = Carbon::parse($this->input('created_at'));
                    foreach (['accepted_at', 'responded_at', 'resolved_at', 'closed_at'] as $field) {
                        $timestamp = $routeServiceRequest->{$field};
                        if ($timestamp && $createdAt->gt($timestamp)) {
                            $validator->errors()->add(
                                'created_at',
                                'La fecha de la solicitud no puede ser posterior a fechas ya registradas en la solicitud.'
                            );
                            break;
                        }
                    }
                } catch (\Throwable $e) {
                    // La regla date reporta el error de formato.
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'La empresa es obligatoria.',
            'company_id.exists' => 'La empresa seleccionada no es válida.',
            'requester_id.required' => 'El solicitante es obligatorio.',
            'requester_id.exists' => 'El solicitante seleccionado no es válido.',
            'sub_service_id.required' => 'El sub-servicio es obligatorio.',
            'sla_id.required' => 'El SLA es obligatorio.',
            'title.required' => 'El título es obligatorio.',
            'description.required' => 'La descripción es obligatoria.',
            'criticality_level.required' => 'El nivel de criticidad es obligatorio.',
            'criticality_level.in' => 'El nivel de criticidad seleccionado no es válido.',
            'entry_channel.required' => 'El canal de entrada es obligatorio.',
            'entry_channel.in' => 'El canal de entrada seleccionado no es válido.',
            'due_date.date' => 'La fecha de vencimiento no tiene un formato válido.',
            'created_at.required' => 'La fecha de la solicitud es obligatoria.',
            'created_at.date' => 'La fecha de la solicitud no tiene un formato válido.',
            'created_at.before_or_equal' => 'La fecha de la solicitud no puede ser futura.',
        ];
    }
}
