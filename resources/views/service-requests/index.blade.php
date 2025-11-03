@extends('layouts.app')

@section('title', 'Solicitudes de Servicio')

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Dashboard</a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Solicitudes de Servicio</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-xl font-semibold">Lista de Solicitudes de Servicio</h2>
        <a href="{{ route('service-requests.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Nueva Solicitud
        </a>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sub-Servicio</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criticidad</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solicitante</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($serviceRequests as $request)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-blue-600">{{ $request->ticket_number }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $request->title }}</div>
                            <div class="text-sm text-gray-500">{{ Str::limit($request->description, 50) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $request->subService->name ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-500">{{ $request->subService->service->family->name ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $criticalityColors = [
                                    'BAJA' => 'bg-green-100 text-green-800',
                                    'MEDIA' => 'bg-yellow-100 text-yellow-800',
                                    'ALTA' => 'bg-orange-100 text-orange-800',
                                    'CRITICA' => 'bg-red-100 text-red-800'
                                ];
                            @endphp
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $criticalityColors[$request->criticality_level] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $request->criticality_level }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                                    'ACEPTADA' => 'bg-blue-100 text-blue-800',
                                    'EN_PROCESO' => 'bg-purple-100 text-purple-800',
                                    'PAUSADA' => 'bg-orange-100 text-orange-800', // ESTADO AGREGADO
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
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $request->requester->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $request->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('service-requests.show', $request) }}" class="text-blue-600 hover:text-blue-900 mr-3" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($request->status === 'PENDIENTE')
                                <a href="{{ route('service-requests.edit', $request) }}" class="text-green-600 hover:text-green-900 mr-3" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endif
                            @if(in_array($request->status, ['PENDIENTE', 'CANCELADA']))
                                <form action="{{ route('service-requests.destroy', $request) }}" method="POST" class="inline" onsubmit="return confirmDelete('¿Está seguro de que desea eliminar esta solicitud?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                            No se encontraron solicitudes de servicio.
                            <a href="{{ route('service-requests.create') }}" class="text-blue-600 hover:text-blue-800 ml-2">
                                Crear la primera solicitud
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $serviceRequests->links() }}
    </div>
@endsection
