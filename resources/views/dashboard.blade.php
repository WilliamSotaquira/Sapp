@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Dashboard Principal</h1>

    <!-- Estadísticas Principales -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Familias de Servicio -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-layer-group text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Familias de Servicio</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ \App\Models\ServiceFamily::count() }}</p>
                </div>
            </div>
        </div>

        <!-- Servicios -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-cogs text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Servicios</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ \App\Models\Service::count() }}</p>
                </div>
            </div>
        </div>

        <!-- Sub-Servicios -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-list-alt text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Sub-Servicios</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ \App\Models\SubService::count() }}</p>
                </div>
            </div>
        </div>

        <!-- Total Solicitudes -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500">
            <div class="flex items-center">
                <div class="p-3 bg-orange-100 rounded-lg">
                    <i class="fas fa-tasks text-orange-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Solicitudes</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ \App\Models\ServiceRequest::count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Dos Columnas: Acciones Rápidas y Resumen de Solicitudes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Acciones Rápidas -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Acciones Rápidas</h2>
            <div class="space-y-3">
                <a href="{{ route('service-requests.create') }}" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition border border-blue-200">
                    <i class="fas fa-plus-circle text-blue-600 text-lg mr-3"></i>
                    <div>
                        <p class="font-medium text-blue-800">Nueva Solicitud</p>
                        <p class="text-sm text-blue-600">Crear una nueva solicitud de servicio</p>
                    </div>
                </a>

                <a href="{{ route('service-families.create') }}" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition border border-green-200">
                    <i class="fas fa-layer-group text-green-600 text-lg mr-3"></i>
                    <div>
                        <p class="font-medium text-green-800">Nueva Familia</p>
                        <p class="text-sm text-green-600">Agregar familia de servicio</p>
                    </div>
                </a>

                <a href="{{ route('service-requests.index') }}" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition border border-purple-200">
                    <i class="fas fa-list text-purple-600 text-lg mr-3"></i>
                    <div>
                        <p class="font-medium text-purple-800">Ver Solicitudes</p>
                        <p class="text-sm text-purple-600">Gestionar todas las solicitudes</p>
                    </div>
                </a>

                <a href="{{ route('slas.index') }}" class="flex items-center p-3 bg-orange-50 rounded-lg hover:bg-orange-100 transition border border-orange-200">
                    <i class="fas fa-handshake text-orange-600 text-lg mr-3"></i>
                    <div>
                        <p class="font-medium text-orange-800">Gestionar SLAs</p>
                        <p class="text-sm text-orange-600">Configurar acuerdos de nivel de servicio</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Resumen de Solicitudes por Estado -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Resumen de Solicitudes</h2>
            <div class="space-y-4">
                @php
                    $statuses = [
                        'PENDIENTE' => ['color' => 'yellow', 'icon' => 'fas fa-clock', 'count' => \App\Models\ServiceRequest::where('status', 'PENDIENTE')->count()],
                        'ACEPTADA' => ['color' => 'blue', 'icon' => 'fas fa-check-circle', 'count' => \App\Models\ServiceRequest::where('status', 'ACEPTADA')->count()],
                        'EN_PROCESO' => ['color' => 'purple', 'icon' => 'fas fa-play-circle', 'count' => \App\Models\ServiceRequest::where('status', 'EN_PROCESO')->count()],
                        'PAUSADA' => ['color' => 'orange', 'icon' => 'fas fa-pause-circle', 'count' => \App\Models\ServiceRequest::where('status', 'PAUSADA')->count()],
                        'RESUELTA' => ['color' => 'green', 'icon' => 'fas fa-check-double', 'count' => \App\Models\ServiceRequest::where('status', 'RESUELTA')->count()],
                    ];
                @endphp

                @foreach($statuses as $status => $data)
                <div class="flex items-center justify-between p-3 bg-{{ $data['color'] }}-50 rounded-lg border border-{{ $data['color'] }}-200">
                    <div class="flex items-center">
                        <i class="{{ $data['icon'] }} text-{{ $data['color'] }}-600 text-lg mr-3"></i>
                        <span class="font-medium text-{{ $data['color'] }}-800">{{ $status }}</span>
                    </div>
                    <span class="text-2xl font-bold text-{{ $data['color'] }}-600">{{ $data['count'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Solicitudes Recientes -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Solicitudes Recientes</h2>
        </div>

        @php
            $recentRequests = \App\Models\ServiceRequest::with(['subService.service.family', 'requester'])
                ->latest()
                ->take(8)
                ->get();
        @endphp

        @if($recentRequests->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticket</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Título</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Servicio</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentRequests as $request)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('service-requests.show', $request) }}" class="font-medium text-blue-600 hover:text-blue-900">
                                        {{ $request->ticket_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ Str::limit($request->title, 35) }}</div>
                                    <div class="text-xs text-gray-500">{{ Str::limit($request->description, 50) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $request->subService->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                                            'ACEPTADA' => 'bg-blue-100 text-blue-800',
                                            'EN_PROCESO' => 'bg-purple-100 text-purple-800',
                                            'PAUSADA' => 'bg-orange-100 text-orange-800',
                                            'RESUELTA' => 'bg-green-100 text-green-800',
                                            'CERRADA' => 'bg-gray-100 text-gray-800',
                                            'CANCELADA' => 'bg-red-100 text-red-800'
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$request->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $request->status }}
                                        @if($request->is_paused && $request->status === 'PAUSADA')
                                            <i class="fas fa-pause ml-1"></i>
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $request->created_at->format('d/m/Y') }}<br>
                                    <span class="text-xs">{{ $request->created_at->format('H:i') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <a href="{{ route('service-requests.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                    Ver todas las solicitudes →
                </a>
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <i class="fas fa-inbox text-gray-300 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No hay solicitudes</h3>
                <p class="text-gray-500 mb-4">Aún no se han creado solicitudes de servicio.</p>
                <a href="{{ route('service-requests.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>Crear Primera Solicitud
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
