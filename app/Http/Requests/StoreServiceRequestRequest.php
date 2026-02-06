<?php

namespace App\Http\Requests;

use App\Models\ServiceRequest;
use App\Models\StandardTask;
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
            'company_id' => 'required|exists:companies,id',
            'requester_id' => 'required|exists:requesters,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'sub_service_id' => 'required|exists:sub_services,id',
            'criticality_level' => 'required|in:BAJA,MEDIA,ALTA,URGENTE,CRITICA',
            'service_id' => 'required|exists:services,id',
            'family_id' => 'required|exists:service_families,id',
            'sla_id' => 'required|exists:service_level_agreements,id',
            'requested_by' => 'required|exists:users,id',
            'entry_channel' => 'required|in:' . implode(',', ServiceRequest::getEntryChannelValidationValues()),
            'web_routes' => 'required|string',
            'is_reportable' => 'sometimes|boolean',

            // Corte (opcional)
            'cut_id' => 'nullable|exists:cuts,id',

            // Tareas (opcional)
            'tasks_template' => 'nullable|in:none,subservice_standard',
            'tasks' => 'nullable|array',
            'tasks.*.title' => 'nullable|string|max:255',
            'tasks.*.description' => 'nullable|string',
            'tasks.*.type' => 'nullable|in:impact,regular',
            'tasks.*.priority' => 'nullable|in:urgent,high,medium,low',
            'tasks.*.estimated_minutes' => 'nullable|integer|min:0|max:9999',
            'tasks.*.estimated_hours' => 'nullable|numeric|min:0|max:99.9',
            'tasks.*.estimate_mode' => 'nullable|in:auto,manual',
            'tasks.*.standard_task_id' => 'nullable|exists:standard_tasks,id',

            // Subtareas (opcional dentro de cada tarea)
            'tasks.*.subtasks' => 'nullable|array',
            'tasks.*.subtasks.*.title' => 'nullable|string|max:255',
            'tasks.*.subtasks.*.notes' => 'nullable|string',
            'tasks.*.subtasks.*.priority' => 'nullable|in:high,medium,low',
            'tasks.*.subtasks.*.estimated_minutes' => 'nullable|integer|min:0|max:9999',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'company_id.required' => 'Debe seleccionar una empresa.',
            'company_id.exists' => 'La empresa seleccionada no es válida.',
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
            'entry_channel.required' => 'Debe seleccionar un canal de entrada.',
            'entry_channel.in' => 'El canal de entrada seleccionado no es válido.',
            'web_routes.required' => 'Las rutas web son obligatorias.',
            'cut_id.exists' => 'El corte seleccionado no es válido.',
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

                if ($familyId && $family && (string) $family->id !== (string) $familyId) {
                    $validator->errors()->add('family_id', 'La familia no corresponde al subservicio seleccionado.');
                }

                if ($activeContractId && $family && (string) $family->contract_id !== (string) $activeContractId) {
                    $validator->errors()->add('sub_service_id', 'El subservicio no pertenece al contrato activo del espacio de trabajo.');
                }
            }

            $cutId = $this->input('cut_id');
            if ($cutId && $activeContractId) {
                $cutContractId = \App\Models\Cut::where('id', $cutId)->value('contract_id');
                if ($cutContractId && (string) $cutContractId !== (string) $activeContractId) {
                    $validator->errors()->add('cut_id', 'El corte no corresponde al contrato activo del espacio de trabajo.');
                }
            }

            $requesterId = $this->input('requester_id');
            if ($requesterId && $companyId) {
                $requesterCompanyId = \App\Models\Requester::where('id', $requesterId)->value('company_id');
                if ($requesterCompanyId && (string) $requesterCompanyId !== (string) $companyId) {
                    $validator->errors()->add('requester_id', 'El solicitante no pertenece al espacio de trabajo actual.');
                }
            }

            $tasks = $this->input('tasks');
            $tasksTemplate = $this->input('tasks_template');

            $hasValidTask = false;
            if (is_array($tasks)) {
                foreach ($tasks as $task) {
                    if (!is_array($task)) {
                        continue;
                    }

                    $title = trim((string)($task['title'] ?? ''));
                    $standardTaskId = $task['standard_task_id'] ?? null;

                    if ($title !== '' || !empty($standardTaskId)) {
                        $hasValidTask = true;
                        break;
                    }
                }
            }

            if (!$hasValidTask) {
                if ($tasksTemplate === 'subservice_standard') {
                    $subServiceId = $this->input('sub_service_id');
                    $standardCount = 0;
                    if (!empty($subServiceId)) {
                        $standardCount = StandardTask::query()
                            ->where('sub_service_id', $subServiceId)
                            ->active()
                            ->count();
                    }

                    if ($standardCount === 0) {
                        $validator->errors()->add(
                            'tasks_template',
                            'El subservicio no tiene tareas predefinidas configuradas. Agrega al menos una tarea manual.'
                        );
                    }
                } else {
                    $validator->errors()->add('tasks', 'Debes agregar al menos una tarea.');
                }
            }

            if (!is_array($tasks)) {
                return;
            }

            foreach ($tasks as $i => $task) {
                if (!is_array($task)) {
                    continue;
                }

                $standardTaskId = $task['standard_task_id'] ?? null;
                $description = $task['description'] ?? null;

                // Consistente con otras pantallas: mínimo 10 caracteres.
                // Solo aplica a tareas manuales (sin standard_task_id) y cuando se envía descripción.
                if (empty($standardTaskId) && is_string($description)) {
                    $len = mb_strlen(trim($description));
                    if ($len > 0 && $len < 10) {
                        $validator->errors()->add(
                            "tasks.$i.description",
                            'La descripción de la tarea debe tener al menos 10 caracteres.'
                        );
                    }
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (!$this->has('company_id')) {
            $this->merge(['company_id' => session('current_company_id')]);
        }

        // Procesar web_routes (puede venir como string JSON o como array)
        if ($this->has('web_routes')) {
            if (is_array($this->web_routes)) {
                $this->merge(['web_routes' => json_encode($this->web_routes)]);
            } elseif (is_string($this->web_routes)) {
                $decoded = json_decode($this->web_routes, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge(['web_routes' => json_encode($decoded)]);
                }
            }
        }

        // Normalizar decimales con coma en tareas (ej: "0,92" => "0.92")
        $tasks = $this->input('tasks');
        if (is_array($tasks)) {
            foreach ($tasks as $i => $task) {
                if (!is_array($task)) {
                    continue;
                }

                if (array_key_exists('estimated_hours', $task) && is_string($task['estimated_hours'])) {
                    $v = trim($task['estimated_hours']);
                    if ($v !== '') {
                        $tasks[$i]['estimated_hours'] = str_replace(',', '.', $v);
                    }
                }
            }

            $this->merge(['tasks' => $tasks]);
        }
    }
}
