@extends('layouts.app')

@section('content')
<div class="py-6">
    <nav class="mb-6" aria-label="Breadcrumb">
        <ol class="flex space-x-2 text-sm text-gray-600">
            <li><a href="{{ route('dashboard') }}" class="hover:text-blue-600">Inicio</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="{{ route('reports.index') }}" class="hover:text-blue-600">Reportes</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="{{ route('reports.cuts.index') }}" class="hover:text-blue-600">Cortes</a></li>
            <li><span class="mx-2">/</span></li>
            <li class="text-gray-900 font-medium">{{ $cut->name }}</li>
        </ol>
    </nav>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-start justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Corte #{{ $cut->id }}</p>
                <h2 class="text-xl font-bold text-gray-900">{{ $cut->name }}</h2>
                <p class="text-sm text-gray-600">{{ $cut->start_date->format('Y-m-d') }} → {{ $cut->end_date->format('Y-m-d') }}</p>
                @if($cut->contract)
                    <p class="text-xs text-gray-500 mt-1">Contrato: {{ $cut->contract->number }}</p>
                @endif
                <p class="text-xs text-gray-500 mt-1">
                    Criterio de asociación: solicitudes con actividad en el rango (creación o actualización de la solicitud/tareas, y creación de evidencias/historiales).
                </p>
                @if($cut->notes)
                    <p class="text-sm text-gray-700 mt-2">{{ $cut->notes }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('reports.cuts.requests', $cut) }}" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-list-check"></i>
                    Gestionar solicitudes
                </a>
                <form method="POST" action="{{ route('reports.cuts.sync', $cut) }}">
                    @csrf
                    <button type="submit" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                        <i class="fa-solid fa-rotate"></i>
                        Actualizar
                    </button>
                </form>
                <a href="{{ route('reports.cuts.export-pdf', $cut) }}" class="px-3 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">
                    <i class="fa-solid fa-file-pdf"></i>
                    PDF
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-green-50 text-green-700 border-b border-green-100">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-red-50 text-red-700 border-b border-red-100">{{ session('error') }}</div>
        @endif

        <div class="p-6">
            <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Editar fechas del corte</h3>
                <form method="POST" action="{{ route('reports.cuts.update', $cut) }}" class="flex flex-col md:flex-row md:items-end gap-3">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="start_date" class="block text-xs font-medium text-gray-700 mb-1">Fecha inicio</label>
                        <input
                            type="date"
                            id="start_date"
                            name="start_date"
                            value="{{ old('start_date', optional($cut->start_date)->format('Y-m-d')) }}"
                            class="px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                        @error('start_date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="end_date" class="block text-xs font-medium text-gray-700 mb-1">Fecha fin</label>
                        <input
                            type="date"
                            id="end_date"
                            name="end_date"
                            value="{{ old('end_date', optional($cut->end_date)->format('Y-m-d')) }}"
                            class="px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                        @error('end_date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
                            Guardar fechas
                        </button>
                    </div>
                </form>
                <p class="mt-2 text-xs text-gray-500">Al guardar, se recalculan automáticamente las solicitudes asociadas al corte según el nuevo rango.</p>
            </div>

            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Solicitudes asociadas</h3>
                <span class="text-sm text-gray-600">Total: <span class="font-semibold">{{ $serviceRequests->total() }}</span></span>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200" role="region" aria-label="Tabla de solicitudes asociadas del corte">
                <table class="w-full table-fixed divide-y divide-gray-200">
                    <caption class="sr-only">
                        Listado de solicitudes asociadas, ordenado por estado activo primero y luego por fecha de creación más reciente.
                    </caption>
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="w-[14%] px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">Ticket</th>
                            <th scope="col" class="w-[36%] px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Título</th>
                            <th scope="col" class="w-[24%] px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Familia</th>
                            <th scope="col" class="w-[10%] px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">Estado</th>
                            <th scope="col" class="w-[10%] px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">Creada</th>
                            <th scope="col" class="w-[8%] px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($serviceRequests as $sr)
                            @php
                                $family = $sr->subService?->service?->family;
                                $familyName = $family?->name ?? 'Sin Familia';
                                $contractNumber = $family?->contract?->number;
                                $familyLabel = $contractNumber ? ($contractNumber . ' - ' . $familyName) : $familyName;

                                $statusMap = [
                                    'PENDIENTE' => ['label' => 'Pendiente', 'classes' => 'bg-amber-100 text-amber-800 border-amber-200'],
                                    'ACEPTADA' => ['label' => 'Aceptada', 'classes' => 'bg-blue-100 text-blue-800 border-blue-200'],
                                    'EN_PROCESO' => ['label' => 'En proceso', 'classes' => 'bg-indigo-100 text-indigo-800 border-indigo-200'],
                                    'PAUSADA' => ['label' => 'Pausada', 'classes' => 'bg-orange-100 text-orange-800 border-orange-200'],
                                    'RESUELTA' => ['label' => 'Resuelta', 'classes' => 'bg-emerald-100 text-emerald-800 border-emerald-200'],
                                    'CERRADA' => ['label' => 'Cerrada', 'classes' => 'bg-gray-100 text-gray-800 border-gray-200'],
                                    'CANCELADA' => ['label' => 'Cancelada', 'classes' => 'bg-rose-100 text-rose-800 border-rose-200'],
                                    'RECHAZADA' => ['label' => 'Rechazada', 'classes' => 'bg-red-100 text-red-800 border-red-200'],
                                ];
                                $statusData = $statusMap[$sr->status] ?? ['label' => $sr->status, 'classes' => 'bg-slate-100 text-slate-700 border-slate-200'];
                            @endphp
                            <tr class="align-top hover:bg-gray-50">
                                <th scope="row" class="w-[14%] px-4 py-3 text-sm font-semibold text-gray-900 whitespace-nowrap">
                                    <a href="{{ route('service-requests.show', $sr) }}" class="inline-block whitespace-nowrap hover:text-blue-700 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500 rounded" aria-label="Ver solicitud {{ $sr->ticket_number }}">
                                        {{ $sr->ticket_number }}
                                    </a>
                                </th>
                                <td class="w-[36%] px-4 py-3 text-sm text-gray-700">
                                    <span class="block truncate" title="{{ $sr->title }}">{{ $sr->title }}</span>
                                </td>
                                <td class="w-[24%] px-4 py-3 text-sm text-gray-700">
                                    <span class="block truncate" title="{{ $familyLabel }}">{{ $familyLabel }}</span>
                                </td>
                                <td class="w-[10%] px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusData['classes'] }}">
                                        {{ $statusData['label'] }}
                                    </span>
                                </td>
                                <td class="w-[10%] px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                    @if($sr->created_at)
                                        <time datetime="{{ $sr->created_at->toIso8601String() }}">{{ $sr->created_at->format('Y-m-d H:i') }}</time>
                                    @else
                                        <span class="text-gray-400">Sin fecha</span>
                                    @endif
                                </td>
                                <td class="w-[8%] px-4 py-3 text-sm whitespace-nowrap">
                                    <form method="POST" action="{{ route('reports.cuts.requests.remove', [$cut, $sr]) }}" onsubmit="return confirm('¿Remover esta solicitud del corte?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center px-2.5 py-1.5 rounded-lg border border-red-200 text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1" aria-label="Quitar solicitud {{ $sr->ticket_number }} del corte">
                                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                            <span class="sr-only">Quitar</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">
                                    No hay solicitudes asociadas a este corte.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $serviceRequests->links() }}</div>
        </div>
    </div>
</div>
@endsection
