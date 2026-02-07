@extends('layouts.app')

@section('title', 'Reasignar Técnico - ' . $service_request->ticket_number)

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Reasignar Técnico</h1>
                        <p class="text-gray-600">Solicitud #{{ $service_request->ticket_number }}</p>
                    </div>
                    <div class="text-right">
                        <span
                            class="inline-block px-3 py-1 text-sm font-semibold rounded-full
                        {{ $service_request->status === 'PENDIENTE' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $service_request->status === 'ACEPTADA' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $service_request->status === 'EN_PROCESO' ? 'bg-purple-100 text-purple-800' : '' }}
                        {{ $service_request->status === 'PAUSADA' ? 'bg-orange-100 text-orange-800' : '' }}">
                            {{ $service_request->status }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="font-semibold">Título:</p>
                        <p class="text-gray-700">{{ $service_request->title }}</p>
                    </div>
                    <div>
                        <p class="font-semibold">Técnico Actual:</p>
                        <p class="text-gray-700">
                            {{ $service_request->assignee ? $service_request->assignee->name : 'Sin asignar' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Formulario de Reasignación -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <form action="{{ route('service-requests.reassign-submit', $service_request) }}" method="POST">
                    @csrf

                    <!-- Selección de Técnico -->
                    <div class="mb-6">
                        <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-2">
                            Nuevo Técnico *
                        </label>
                        <select name="assigned_to" id="assigned_to" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecciona un técnico...</option>
                            @foreach ($technicians as $technician)
                                @php
                                    $specialization = $technician->technician?->specialization;
                                    $specializationLabel = $specialization ? (' - ' . $specialization) : '';
                                    $openTasks = $technician->technician?->open_tasks_count;
                                    $openTasksLabel = is_null($openTasks) ? '' : (' · Carga: ' . $openTasks);
                                @endphp
                                <option value="{{ $technician->id }}"
                                    {{ old('assigned_to', $service_request->assigned_to) == $technician->id ? 'selected' : '' }}>
                                    {{ $technician->name }} - {{ $technician->email }}{{ $specializationLabel }}{{ $openTasksLabel }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_to')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Si hay notificaciones habilitadas, el técnico recibirá el aviso de reasignación.</p>
                    </div>

                    <!-- Razón de Reasignación -->
                    <div class="mb-6">
                        <label for="reassignment_reason" class="block text-sm font-medium text-gray-700 mb-2">
                            Motivo de la Reasignación *
                        </label>
                        <textarea name="reassignment_reason" id="reassignment_reason" rows="4" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Describe el motivo de la reasignación (mínimo 10 caracteres)...">{{ old('reassignment_reason') }}</textarea>
                        @error('reassignment_reason')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">
                            Mínimo 10 caracteres
                        </p>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('service-requests.show', $service_request) }}"
                            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancelar
                        </a>
                        <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Confirmar Reasignación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
