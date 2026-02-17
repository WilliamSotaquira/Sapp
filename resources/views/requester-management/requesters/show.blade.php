@extends('layouts.app')

@section('title', 'Detalle del Solicitante')

@section('breadcrumb')
<nav class="flex mb-4" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
            <a href="{{ url('/') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-red-600">
                <i class="fas fa-home mr-2"></i>
                Inicio
            </a>
        </li>
        <li>
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <a href="{{ route('requester-management.requesters.index') }}" class="text-sm font-medium text-gray-700 hover:text-red-600">
                    Gestión de Solicitantes
                </a>
            </div>
        </li>
        <li aria-current="page">
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Detalle</span>
            </div>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">{{ $requester->name }}</h1>
                <p class="text-sm text-slate-600 mt-1">Información general del solicitante</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('requester-management.requesters.edit', $requester) }}"
                   class="inline-flex items-center px-3 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 text-sm">
                    <i class="fas fa-edit mr-2"></i>Editar
                </a>
                <a href="{{ route('requester-management.requesters.index') }}"
                   class="inline-flex items-center px-3 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-slate-500">Correo</p>
                <p class="text-slate-900 font-medium break-all">{{ $requester->email ?: 'Sin correo' }}</p>
            </div>
            <div>
                <p class="text-slate-500">Teléfono</p>
                <p class="text-slate-900 font-medium">{{ $requester->phone ?: 'Sin teléfono' }}</p>
            </div>
            <div>
                <p class="text-slate-500">Departamento</p>
                <p class="text-slate-900 font-medium">{{ $requester->department ?: 'Sin departamento' }}</p>
            </div>
            <div>
                <p class="text-slate-500">Cargo</p>
                <p class="text-slate-900 font-medium">{{ $requester->position ?: 'Sin cargo' }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
            <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Solicitudes asociadas</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Ticket</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Título</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Servicio</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Acción</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($serviceRequests as $request)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $request->ticket_number }}</td>
                            <td class="px-6 py-4 text-slate-700">{{ $request->title }}</td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ $request->subService?->service?->family?->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $request->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ optional($request->created_at)->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('service-requests.show', $request) }}"
                                   class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye mr-1"></i>Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                                Este solicitante no tiene solicitudes registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($serviceRequests->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $serviceRequests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
