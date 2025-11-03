@extends('layouts.app')

@section('title', $serviceFamily->name)

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Inicio</a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('service-families.index') }}" class="text-blue-600 hover:text-blue-700">Familias de Servicio</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">{{ $serviceFamily->name }}</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="max-w-7xl mx-auto">
        <!-- Header con Acciones -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center mb-4 md:mb-0">
                        <div class="bg-white bg-opacity-20 p-3 rounded-lg mr-4">
                            <i class="fas fa-layer-group text-2xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">{{ $serviceFamily->name }}</h1>
                            <div class="flex items-center space-x-4 mt-2">
                                <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">
                                    <i class="fas fa-hashtag mr-1"></i>{{ $serviceFamily->code }}
                                </span>
                                <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm flex items-center">
                                    <i class="fas fa-clock mr-1"></i>
                                    Creada: {{ $serviceFamily->created_at->format('d/m/Y') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ route('service-families.edit', $serviceFamily) }}"
                           class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition duration-150 flex items-center">
                            <i class="fas fa-edit mr-2"></i>Editar
                        </a>
                        <a href="{{ route('services.create') }}?family={{ $serviceFamily->id }}"
                           class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition duration-150 flex items-center">
                            <i class="fas fa-plus mr-2"></i>Agregar Servicio
                        </a>
                        <a href="{{ route('slas.create') }}?family={{ $serviceFamily->id }}"
                           class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg transition duration-150 flex items-center">
                            <i class="fas fa-handshake mr-2"></i>Agregar SLA
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Columna Izquierda - Información General -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Tarjeta de Información General -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                            Información General
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Descripción</label>
                                <p class="text-gray-800 bg-gray-50 p-3 rounded-lg min-h-[80px]">
                                    {{ $serviceFamily->description ?? 'Sin descripción' }}
                                </p>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Estado</label>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $serviceFamily->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        @if($serviceFamily->is_active)
                                            <i class="fas fa-check-circle mr-1"></i>Activa
                                        @else
                                            <i class="fas fa-times-circle mr-1"></i>Inactiva
                                        @endif
                                    </span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Fecha de Creación</label>
                                    <p class="text-gray-800">{{ $serviceFamily->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Última Actualización</label>
                                    <p class="text-gray-800">{{ $serviceFamily->updated_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de Servicios -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-cogs text-green-500 mr-2"></i>
                            Servicios ({{ $serviceFamily->services->count() }})
                        </h2>
                        <a href="{{ route('services.create') }}?family={{ $serviceFamily->id }}"
                           class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition duration-150 flex items-center">
                            <i class="fas fa-plus mr-1"></i>Agregar
                        </a>
                    </div>
                    <div class="p-6">
                        @if($serviceFamily->services->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($serviceFamily->services as $service)
                                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition duration-150">
                                        <div class="flex justify-between items-start mb-3">
                                            <div>
                                                <h3 class="font-semibold text-gray-900 flex items-center">
                                                    <i class="fas fa-cog text-green-500 mr-2"></i>
                                                    {{ $service->name }}
                                                </h3>
                                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">{{ $service->code }}</span>
                                            </div>
                                            <span class="text-xs px-2 py-1 rounded {{ $service->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $service->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </div>

                                        @if($service->description)
                                            <p class="text-sm text-gray-600 mb-3">{{ Str::limit($service->description, 100) }}</p>
                                        @endif

                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-500 flex items-center">
                                                <i class="fas fa-list-alt mr-1"></i>
                                                {{ $service->subServices->count() }} sub-servicios
                                            </span>
                                            <div class="flex space-x-2">
                                                <a href="{{ route('services.show', $service) }}"
                                                   class="text-blue-600 hover:text-blue-800 transition duration-150"
                                                   title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('services.edit', $service) }}"
                                                   class="text-green-600 hover:text-green-800 transition duration-150"
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <i class="fas fa-cogs text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500 mb-4">No hay servicios registrados para esta familia</p>
                                <a href="{{ route('services.create') }}?family={{ $serviceFamily->id }}"
                                   class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition duration-150 flex items-center justify-center">
                                    <i class="fas fa-plus mr-2"></i>Crear Primer Servicio
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Columna Derecha - Estadísticas y SLAs -->
            <div class="space-y-6">
                <!-- Tarjeta de Estadísticas -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-chart-bar text-purple-500 mr-2"></i>
                            Estadísticas
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total Servicios</span>
                                <span class="font-semibold text-gray-900">{{ $serviceFamily->services->count() }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Servicios Activos</span>
                                <span class="font-semibold text-green-600">{{ $serviceFamily->activeServices()->count() }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total Sub-Servicios</span>
                                <span class="font-semibold text-gray-900">{{ $serviceFamily->services->sum(function($service) { return $service->subServices->count(); }) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">SLAs Configurados</span>
                                <span class="font-semibold text-blue-600">{{ $serviceFamily->serviceLevelAgreements->count() }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">SLAs Activos</span>
                                <span class="font-semibold text-green-600">{{ $serviceFamily->activeSlas()->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de SLAs -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-handshake text-orange-500 mr-2"></i>
                            Acuerdos de Nivel de Servicio
                        </h2>
                        <a href="{{ route('slas.create') }}?family={{ $serviceFamily->id }}"
                           class="bg-orange-600 text-white px-3 py-1 rounded text-sm hover:bg-orange-700 transition duration-150 flex items-center">
                            <i class="fas fa-plus mr-1"></i>Agregar
                        </a>
                    </div>
                    <div class="p-6">
                        @if($serviceFamily->serviceLevelAgreements->count() > 0)
                            <div class="space-y-4">
                                @foreach($serviceFamily->serviceLevelAgreements as $sla)
                                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-sm transition duration-150">
                                        <div class="flex justify-between items-start mb-2">
                                            <h4 class="font-medium text-gray-900">{{ $sla->name }}</h4>
                                            @php
                                                $criticalityColors = [
                                                    'BAJA' => 'bg-green-100 text-green-800',
                                                    'MEDIA' => 'bg-yellow-100 text-yellow-800',
                                                    'ALTA' => 'bg-orange-100 text-orange-800',
                                                    'CRITICA' => 'bg-red-100 text-red-800'
                                                ];
                                            @endphp
                                            <span class="text-xs px-2 py-1 rounded-full font-semibold {{ $criticalityColors[$sla->criticality_level] }}">
                                                {{ $sla->criticality_level }}
                                            </span>
                                        </div>

                                        <div class="space-y-2 text-sm text-gray-600">
                                            <div class="flex justify-between">
                                                <span>Aceptación:</span>
                                                <span class="font-medium">{{ $sla->acceptance_time_minutes }} min</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span>Respuesta:</span>
                                                <span class="font-medium">{{ $sla->response_time_minutes }} min</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span>Resolución:</span>
                                                <span class="font-medium">{{ $sla->resolution_time_minutes }} min</span>
                                            </div>
                                        </div>

                                        <div class="flex justify-between items-center mt-3">
                                            <span class="text-xs px-2 py-1 rounded {{ $sla->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $sla->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                            <a href="{{ route('slas.show', $sla) }}"
                                               class="text-blue-600 hover:text-blue-800 text-sm transition duration-150">
                                                <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-6">
                                <i class="fas fa-handshake text-3xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500 text-sm mb-4">No hay SLAs configurados para esta familia</p>
                                <a href="{{ route('slas.create') }}?family={{ $serviceFamily->id }}"
                                   class="bg-orange-600 text-white px-3 py-2 rounded text-sm hover:bg-orange-700 transition duration-150 flex items-center justify-center">
                                    <i class="fas fa-plus mr-2"></i>Configurar Primer SLA
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Tarjeta de Acciones Rápidas -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                            Acciones Rápidas
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <a href="{{ route('services.create') }}?family={{ $serviceFamily->id }}"
                               class="w-full bg-green-50 hover:bg-green-100 text-green-700 px-4 py-3 rounded-lg transition duration-150 flex items-center justify-center">
                                <i class="fas fa-plus-circle mr-2"></i>Nuevo Servicio
                            </a>
                            <a href="{{ route('slas.create') }}?family={{ $serviceFamily->id }}"
                               class="w-full bg-orange-50 hover:bg-orange-100 text-orange-700 px-4 py-3 rounded-lg transition duration-150 flex items-center justify-center">
                                <i class="fas fa-handshake mr-2"></i>Nuevo SLA
                            </a>
                            <a href="{{ route('service-families.edit', $serviceFamily) }}"
                               class="w-full bg-blue-50 hover:bg-blue-100 text-blue-700 px-4 py-3 rounded-lg transition duration-150 flex items-center justify-center">
                                <i class="fas fa-edit mr-2"></i>Editar Familia
                            </a>
                            @if($serviceFamily->services->count() === 0)
                                <form action="{{ route('service-families.destroy', $serviceFamily) }}" method="POST"
                                      onsubmit="return confirm('¿Está seguro de que desea eliminar esta familia de servicio?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="w-full bg-red-50 hover:bg-red-100 text-red-700 px-4 py-3 rounded-lg transition duration-150 flex items-center justify-center">
                                        <i class="fas fa-trash mr-2"></i>Eliminar Familia
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animación para las tarjetas al cargar
        const cards = document.querySelectorAll('.bg-white');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';

            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // Tooltips básicos
        const tooltipElements = document.querySelectorAll('[title]');
        tooltipElements.forEach(el => {
            el.addEventListener('mouseenter', function() {
                // Podrías implementar un sistema de tooltips más avanzado aquí
                console.log(this.title);
            });
        });
    });
</script>
@endsection
