<?php

namespace App\Http\Controllers;

use App\Services\AlertConfigService;
use Illuminate\Http\Request;

class AlertSettingsController extends Controller
{
    public function __construct(private AlertConfigService $configService)
    {
    }

    /**
     * Mostrar la vista de configuración de alertas.
     */
    public function edit()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $settings = $this->configService->all();
        $metadata = AlertConfigService::getSettingsMetadata();

        // Agrupar por categoría
        $groups = [
            'sla' => ['label' => 'Acuerdos de Nivel de Servicio (SLA)', 'icon' => 'fa-handshake'],
            'inactividad' => ['label' => 'Umbrales de Inactividad por Prioridad', 'icon' => 'fa-hourglass-half'],
            'estados' => ['label' => 'Control de Estados', 'icon' => 'fa-traffic-light'],
            'tareas' => ['label' => 'Monitoreo de Tareas', 'icon' => 'fa-tasks'],
            'sistema' => ['label' => 'Configuración del Sistema', 'icon' => 'fa-cogs'],
        ];

        return view('settings.alerts', compact('settings', 'metadata', 'groups'));
    }

    /**
     * Actualizar la configuración de alertas.
     */
    public function update(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $metadata = AlertConfigService::getSettingsMetadata();
        $rules = [];

        foreach ($metadata as $key => $meta) {
            $fieldName = str_replace('.', '_', $key);

            switch ($meta['type']) {
                case 'number':
                    $rules[$fieldName] = "required|numeric|min:{$meta['min']}|max:{$meta['max']}";
                    break;
                case 'time':
                    $rules[$fieldName] = 'required|date_format:H:i';
                    break;
                case 'boolean':
                    $rules[$fieldName] = 'nullable|in:0,1';
                    break;
            }
        }

        $validated = $request->validate($rules);

        foreach ($metadata as $key => $meta) {
            $fieldName = str_replace('.', '_', $key);
            $value = $validated[$fieldName] ?? '0';

            $this->configService->set($key, $value);
        }

        $this->configService->clearCache();

        return redirect()
            ->route('settings.alerts.edit')
            ->with('success', 'Configuración de alertas actualizada correctamente.');
    }
}
