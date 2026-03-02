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
            <li><a href="{{ route('reports.cuts.show', $cut) }}" class="hover:text-blue-600">{{ $cut->name }}</a></li>
            <li><span class="mx-2">/</span></li>
            <li class="text-gray-900 font-medium">Solicitudes asociadas</li>
        </ol>
    </nav>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-start justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Corte #{{ $cut->id }}</p>
                <h2 class="text-xl font-bold text-gray-900">Solicitudes asociadas al corte</h2>
                <p class="text-sm text-gray-600">{{ $cut->start_date->format('Y-m-d') }} → {{ $cut->end_date->format('Y-m-d') }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    @if($family)
                        <span class="inline-block px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded-full">
                            Familia: {{ $family->contract?->number ? $family->contract->number . ' - ' : '' }}{{ $family->name }}
                        </span>
                    @endif
                    <span class="inline-block px-2 py-0.5 text-xs bg-gray-100 text-gray-700 rounded-full">
                        {{ $totalAssociated }} solicitud{{ $totalAssociated !== 1 ? 'es' : '' }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('reports.cuts.show', $cut) }}" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Volver al corte</a>
            </div>
        </div>

        <div class="p-6">
            <section aria-labelledby="filters-heading" class="mb-4">
                <h3 id="filters-heading" class="text-sm font-semibold text-gray-900 mb-2">Filtros</h3>
                <p id="search-help" class="text-xs text-gray-500 mb-3">Busca por ticket, título o correo del solicitante.</p>
            <form method="GET" action="{{ route('reports.cuts.associated-requests', $cut) }}" class="flex flex-col md:flex-row gap-3">
                @if($familyId > 0)
                    <input type="hidden" name="family_id" value="{{ $familyId }}">
                @endif
                <div class="flex-1">
                    <label for="q" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <input id="q" type="search" name="q" value="{{ $search }}" placeholder="Ticket, título o email" aria-describedby="search-help" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        Filtrar
                    </button>
                    <a href="{{ route('reports.cuts.associated-requests', ['cut' => $cut, 'family_id' => $familyId > 0 ? $familyId : null]) }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Limpiar</a>
                </div>
            </form>
            </section>

            <div class="mb-3 text-sm text-gray-600" aria-live="polite">
                @if($serviceRequests->total() > 0)
                    Mostrando {{ $serviceRequests->firstItem() }}-{{ $serviceRequests->lastItem() }} de {{ $serviceRequests->total() }} solicitud{{ $serviceRequests->total() !== 1 ? 'es' : '' }}.
                @else
                    No hay resultados para los filtros actuales.
                @endif
            </div>

            <div class="border border-gray-200 rounded-lg" role="region" aria-label="Tabla de solicitudes asociadas">
                <table class="min-w-full table-auto divide-y divide-gray-200">
                    <caption class="sr-only">Listado de solicitudes asociadas al corte con filtros aplicados.</caption>
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Ticket</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Título</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Subservicio</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Solicitante</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Asignado</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Creada</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($serviceRequests as $sr)
                            @php
                                $statusColors = [
                                    'EN_PROCESO' => 'bg-blue-100 text-blue-700',
                                    'ACEPTADA' => 'bg-cyan-100 text-cyan-700',
                                    'PENDIENTE' => 'bg-amber-100 text-amber-700',
                                    'PAUSADA' => 'bg-orange-100 text-orange-700',
                                    'RESUELTA' => 'bg-emerald-100 text-emerald-700',
                                    'CERRADA' => 'bg-gray-100 text-gray-700',
                                    'CANCELADA' => 'bg-rose-100 text-rose-700',
                                    'RECHAZADA' => 'bg-red-100 text-red-700',
                                ];
                                $statusClass = $statusColors[$sr->status] ?? 'bg-slate-100 text-slate-700';
                            @endphp
                            <tr class="align-top hover:bg-gray-50">
                                <th scope="row" class="px-4 py-3 text-sm font-semibold text-gray-900 whitespace-nowrap">
                                    <a
                                        href="{{ route('service-requests.show', $sr) }}"
                                        class="text-blue-700 hover:text-blue-900 hover:underline underline-offset-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 rounded-sm"
                                        title="Abrir solicitud {{ $sr->ticket_number }}"
                                    >
                                        {{ $sr->ticket_number }}
                                    </a>
                                </th>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-normal break-words leading-5">{{ $sr->title }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-normal break-words leading-5">{{ $sr->subService?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-normal break-words leading-5">
                                    {{ $sr->requester?->name ?: ($sr->requester?->email ?? '-') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-normal break-words leading-5">{{ $sr->assignee?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                        {{ $sr->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">{{ $sr->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-sm text-gray-500 text-center">No hay solicitudes asociadas con estos filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4" aria-label="Paginación de solicitudes asociadas">
                {{ $serviceRequests->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
