@extends('layouts.app')

@section('title', 'Configuración de Alertas Operativas')

@section('content')
<div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    {{-- Encabezado --}}
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('settings.edit') }}" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-bell text-indigo-600 mr-2" aria-hidden="true"></i>
                Configuración de Alertas Operativas
            </h1>
        </div>
        <p class="text-sm text-gray-600 ml-8">
            Define los umbrales y parámetros que determinan cuándo el sistema genera alertas. Cada tipo de solicitud
            se evalúa según su prioridad y los tiempos configurados aquí.
        </p>
    </div>

    {{-- Mensajes --}}
    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 flex items-center gap-2" role="alert">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700" role="alert">
            <p class="font-medium mb-1"><i class="fas fa-exclamation-circle mr-1" aria-hidden="true"></i> Errores de validación:</p>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Formulario --}}
    <form action="{{ route('settings.alerts.update') }}" method="POST">
        @csrf
        @method('PUT')

        @foreach($groups as $groupKey => $groupInfo)
            @php
                $groupSettings = collect($metadata)->filter(fn($m) => $m['group'] === $groupKey);
            @endphp

            @if($groupSettings->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-5 overflow-hidden">
                <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas {{ $groupInfo['icon'] }} text-indigo-500" aria-hidden="true"></i>
                        {{ $groupInfo['label'] }}
                    </h2>
                </div>

                <div class="px-5 py-4 space-y-4">
                    @foreach($groupSettings as $key => $meta)
                        @php
                            $fieldName = str_replace('.', '_', $key);
                            $currentValue = $settings[$key]['value'] ?? $meta['min'] ?? '';
                        @endphp

                        <div class="flex items-start gap-4 py-2 {{ !$loop->last ? 'border-b border-gray-100 pb-4' : '' }}">
                            <div class="flex-1">
                                <label for="{{ $fieldName }}" class="block text-sm font-medium text-gray-700">
                                    {{ $meta['label'] }}
                                </label>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $meta['description'] }}</p>
                            </div>

                            <div class="flex-shrink-0 w-32">
                                @if($meta['type'] === 'number')
                                    <div class="flex items-center gap-1.5">
                                        <input type="number"
                                               id="{{ $fieldName }}"
                                               name="{{ $fieldName }}"
                                               value="{{ old($fieldName, $currentValue) }}"
                                               min="{{ $meta['min'] }}"
                                               max="{{ $meta['max'] }}"
                                               class="w-20 text-sm border border-gray-300 rounded-lg px-2.5 py-1.5 text-center focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400"
                                               aria-describedby="{{ $fieldName }}_unit">
                                        <span id="{{ $fieldName }}_unit" class="text-xs text-gray-500">{{ $meta['unit'] }}</span>
                                    </div>
                                @elseif($meta['type'] === 'time')
                                    <input type="time"
                                           id="{{ $fieldName }}"
                                           name="{{ $fieldName }}"
                                           value="{{ old($fieldName, $currentValue) }}"
                                           class="w-full text-sm border border-gray-300 rounded-lg px-2.5 py-1.5 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
                                @elseif($meta['type'] === 'boolean')
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="{{ $fieldName }}" value="0">
                                        <input type="checkbox"
                                               id="{{ $fieldName }}"
                                               name="{{ $fieldName }}"
                                               value="1"
                                               {{ old($fieldName, $currentValue) == '1' ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-100 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                    </label>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        @endforeach

        {{-- Botones de acción --}}
        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('settings.edit') }}"
               class="text-sm text-gray-600 hover:text-gray-800 transition">
                <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i> Volver
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-300 focus:outline-none transition">
                <i class="fas fa-save" aria-hidden="true"></i>
                Guardar configuración
            </button>
        </div>
    </form>

    {{-- Nota informativa --}}
    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <p class="text-xs text-blue-700 flex items-start gap-2">
            <i class="fas fa-info-circle mt-0.5 flex-shrink-0" aria-hidden="true"></i>
            <span>
                Los cambios en la configuración se aplican en la próxima evaluación del motor de alertas.
                Las alertas existentes no se ven afectadas retroactivamente. El motor evalúa las solicitudes
                activas diariamente a la hora configurada.
            </span>
        </p>
    </div>
</div>
@endsection
