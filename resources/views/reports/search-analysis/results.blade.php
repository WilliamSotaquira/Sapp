@extends('layouts.app')

@section('title', 'Resultados de Búsqueda y Análisis')

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Dashboard</a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('reports.index') }}" class="text-blue-600 hover:text-blue-700">Informes</a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('reports.search-analysis.index') }}" class="text-blue-600 hover:text-blue-700">Búsqueda y Análisis</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Resultados</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
<div class="py-6">
    <!-- Header with export buttons -->
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Resultados de Búsqueda</h1>
            <p class="text-gray-600">
                @if(!empty($searchTerms))
                    Términos: <span class="font-medium">{{ implode(', ', $searchTerms) }}</span>
                @endif
                @if(!empty($selectedFamilies) || !empty($selectedServices) || !empty($selectedSubServices))
                    @if(!empty($searchTerms)) | @endif
                    Filtros de servicio aplicados
                @endif
            </p>
        </div>
        @if($summary['total_matches'] > 0)
            <div class="flex space-x-2">
                <a href="{{ route('reports.search-analysis.export', ['format' => 'pdf']) }}?{{ http_build_query(request()->except('page')) }}"
                   class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 inline-flex items-center">
                    <i class="fas fa-file-pdf mr-2"></i>PDF
                </a>
                <a href="{{ route('reports.search-analysis.export', ['format' => 'csv']) }}?{{ http_build_query(request()->except('page')) }}"
                   class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 inline-flex items-center">
                    <i class="fas fa-file-csv mr-2"></i>CSV
                </a>
            </div>
        @endif
    </div>

    @if($summary['total_matches'] > 0)
        <!-- Summary Panel -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Matches -->
            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-indigo-100 rounded-full p-3">
                        <i class="fa-solid fa-hashtag text-indigo-600 text-lg"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Coincidencias</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($summary['total_matches']) }}</p>
                    </div>
                </div>
            </div>

            <!-- By Status -->
            <div class="bg-white rounded-lg shadow p-5">
                <h4 class="text-sm font-medium text-gray-500 mb-2">Por Estado</h4>
                <div class="space-y-1">
                    @php
                        $statusColors = [
                            'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                            'ACEPTADA' => 'bg-blue-100 text-blue-800',
                            'EN_PROCESO' => 'bg-purple-100 text-purple-800',
                            'RESUELTA' => 'bg-green-100 text-green-800',
                            'CERRADA' => 'bg-gray-100 text-gray-800',
                            'CANCELADA' => 'bg-red-100 text-red-800',
                            'RECHAZADA' => 'bg-orange-100 text-orange-800',
                        ];
                    @endphp
                    @foreach($summary['by_status'] as $status => $count)
                        <div class="flex justify-between items-center">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $status }}
                            </span>
                            <span class="text-sm font-semibold text-gray-700">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- By Family -->
            <div class="bg-white rounded-lg shadow p-5">
                <h4 class="text-sm font-medium text-gray-500 mb-2">Por Familia</h4>
                <div class="space-y-1">
                    @foreach($summary['by_family'] as $family => $count)
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-700 truncate mr-2" title="{{ $family }}">{{ Str::limit($family, 20) }}</span>
                            <span class="text-sm font-semibold text-gray-700">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- By Criticality -->
            <div class="bg-white rounded-lg shadow p-5">
                <h4 class="text-sm font-medium text-gray-500 mb-2">Por Criticidad</h4>
                <div class="space-y-1">
                    @foreach($summary['by_criticality'] as $level => $count)
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-700">{{ $level ?: 'Sin definir' }}</span>
                            <span class="text-sm font-semibold text-gray-700">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    Solicitudes encontradas
                    <span class="text-sm font-normal text-gray-500">({{ $results->firstItem() }}–{{ $results->lastItem() }} de {{ $results->total() }})</span>
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticket</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Título</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Servicio</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha Creación</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha Resolución</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($results as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-indigo-600">
                                        {{ $item->ticket_number }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-900" title="{{ $item->title }}">
                                        {{ Str::limit($item->title, 50) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColor = $statusColors[$item->status] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColor }}">
                                        {{ $item->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-600">
                                        {{ $item->subService?->service?->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-600">
                                        {{ $item->created_at ? $item->created_at->format('d/m/Y') : '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-600">
                                        {{ $item->resolved_at ? \Carbon\Carbon::parse($item->resolved_at)->format('d/m/Y') : '-' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $results->links() }}
            </div>
        </div>
    @else
        <!-- No Results Message -->
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <i class="fa-solid fa-magnifying-glass text-gray-400 text-5xl mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No se encontraron resultados</h3>
            <p class="text-gray-600 mb-4">
                No se encontraron solicitudes que coincidan con los criterios de búsqueda.
            </p>

            <!-- Applied criteria -->
            <div class="inline-block text-left bg-gray-50 rounded-lg p-4 mt-2">
                <p class="text-sm font-medium text-gray-700 mb-2">Criterios aplicados:</p>
                @if(!empty($searchTerms))
                    <p class="text-sm text-gray-600">
                        <i class="fa-solid fa-keyboard text-gray-400 mr-1"></i>
                        Términos: <span class="font-medium">{{ implode(', ', $searchTerms) }}</span>
                    </p>
                @endif
                @if(!empty($selectedFamilies))
                    <p class="text-sm text-gray-600 mt-1">
                        <i class="fa-solid fa-folder text-gray-400 mr-1"></i>
                        Familias: {{ $families->whereIn('id', $selectedFamilies)->pluck('name')->implode(', ') }}
                    </p>
                @endif
                @if(!empty($selectedServices))
                    <p class="text-sm text-gray-600 mt-1">
                        <i class="fa-solid fa-cogs text-gray-400 mr-1"></i>
                        Servicios: {{ $services->whereIn('id', $selectedServices)->pluck('name')->implode(', ') }}
                    </p>
                @endif
                @if(!empty($selectedSubServices))
                    <p class="text-sm text-gray-600 mt-1">
                        <i class="fa-solid fa-sitemap text-gray-400 mr-1"></i>
                        Sub-servicios: {{ $subServices->whereIn('id', $selectedSubServices)->pluck('name')->implode(', ') }}
                    </p>
                @endif
            </div>

            <div class="mt-6">
                <a href="{{ route('reports.search-analysis.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Nueva búsqueda
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
