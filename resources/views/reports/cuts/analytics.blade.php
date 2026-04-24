@extends('layouts.app')

@section('content')
<div class="py-6 space-y-6">
    <nav aria-label="Breadcrumb">
        <ol class="flex space-x-2 text-sm text-gray-600">
            <li><a href="{{ route('dashboard') }}" class="hover:text-blue-600">Inicio</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="{{ route('reports.index') }}" class="hover:text-blue-600">Reportes</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="{{ route('reports.cuts.index') }}" class="hover:text-blue-600">Cortes</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="{{ route('reports.cuts.show', $cut) }}" class="hover:text-blue-600">{{ $cut->name }}</a></li>
            <li><span class="mx-2">/</span></li>
            <li class="text-gray-900 font-medium">Informe analitico</li>
        </ol>
    </nav>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Informe por corte</p>
                <h2 class="text-2xl font-bold text-gray-900">Informe analitico de gestion y registro</h2>
                <p class="text-sm text-gray-600 mt-1">{{ $cut->name }} | {{ $cut->start_date->format('Y-m-d') }} a {{ $cut->end_date->format('Y-m-d') }}</p>
                @if($cut->contract)
                    <p class="text-xs text-gray-500 mt-1">Contrato: {{ $cut->contract->number }}</p>
                @endif
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('reports.cuts.analytics.export.csv', ['cut' => $cut, 'families' => $selectedFamilyIds]) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-emerald-300 text-emerald-700 hover:bg-emerald-50">
                    <i class="fa-solid fa-file-csv"></i>
                    Exportar CSV
                </a>
                <a href="{{ route('reports.cuts.analytics.export.pdf', ['cut' => $cut, 'families' => $selectedFamilyIds]) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-red-300 text-red-700 hover:bg-red-50">
                    <i class="fa-solid fa-file-pdf"></i>
                    Exportar PDF
                </a>
                <a href="{{ route('reports.cuts.show', $cut) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-arrow-left"></i>
                    Volver al corte
                </a>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <form method="GET" action="{{ route('reports.cuts.analytics', $cut) }}" class="p-4 rounded-xl border border-gray-200 bg-gray-50 space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Filtro por familia</h3>
                        <p class="text-xs text-gray-500">Si no seleccionas ninguna, el informe toma todas las familias con solicitudes dentro del corte.</p>
                    </div>
                    <button type="button" id="toggleAllFamilies" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Seleccionar todas</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @foreach($families as $family)
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50 cursor-pointer">
                            <input type="checkbox" name="families[]" value="{{ $family->id }}" class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" {{ in_array($family->id, $selectedFamilyIds, true) ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700">
                                {{ $family->contract?->number ? $family->contract->number . ' - ' . $family->name : $family->name }}
                            </span>
                        </label>
                    @endforeach
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        <i class="fa-solid fa-filter"></i>
                        Aplicar filtro
                    </button>
                    <a href="{{ route('reports.cuts.analytics', $cut) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-white">
                        <i class="fa-solid fa-rotate-left"></i>
                        Limpiar
                    </a>
                </div>
            </form>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Total</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $analytics['summary']['total'] }}</p>
                    <p class="mt-1 text-sm text-slate-600">Solicitudes asociadas al corte</p>
                </div>
                <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-green-600">Cerradas / resueltas</p>
                    <p class="mt-2 text-3xl font-bold text-green-900">{{ $analytics['summary']['completed'] }}</p>
                    <p class="mt-1 text-sm text-green-700">Cumplimiento: {{ $analytics['summary']['completion_rate'] }}%</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-amber-600">Activas</p>
                    <p class="mt-2 text-3xl font-bold text-amber-900">{{ $analytics['summary']['active'] }}</p>
                    <p class="mt-1 text-sm text-amber-700">Pendientes, aceptadas, en proceso o pausadas</p>
                </div>
                <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-indigo-600">Cobertura del dato</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-900">{{ $analytics['summary']['distinct_areas'] }}</p>
                    <p class="mt-1 text-sm text-indigo-700">{{ $analytics['summary']['distinct_channels'] }} canal(es) y {{ $analytics['summary']['distinct_routes'] }} ruta(s)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                @foreach([
                    'status' => 'Estado',
                    'channels' => 'Canal de entrada',
                    'areas' => 'Area solicitante',
                    'families' => 'Familia',
                    'services' => 'Servicio',
                    'subservices' => 'Subservicio',
                    'routes' => 'Ruta principal web',
                ] as $key => $title)
                    <section class="rounded-xl border border-gray-200 bg-white">
                        <div class="px-5 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                        </div>
                        <div class="p-5">
                            @if($analytics['distributions'][$key]->isEmpty())
                                <p class="text-sm text-gray-500">No hay datos para este corte.</p>
                            @else
                                <div class="space-y-3">
                                    @foreach($analytics['distributions'][$key]->take(8) as $row)
                                        <div>
                                            <div class="flex items-center justify-between gap-3 text-sm">
                                                <span class="font-medium text-gray-800">{{ $row['label'] }}</span>
                                                <span class="text-gray-500">{{ $row['count'] }} | {{ $row['percentage'] }}%</span>
                                            </div>
                                            <div class="mt-2 h-2.5 rounded-full bg-gray-100 overflow-hidden">
                                                <div class="h-full rounded-full bg-blue-600" style="width: {{ min(100, $row['percentage']) }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </section>
                @endforeach
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <section class="rounded-xl border border-gray-200 bg-white">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Hallazgos</h3>
                    </div>
                    <div class="p-5">
                        <ul class="space-y-3 text-sm text-gray-700">
                            @foreach($analytics['findings'] as $finding)
                                <li class="flex items-start gap-3">
                                    <span class="mt-1 h-2.5 w-2.5 rounded-full bg-blue-600 flex-shrink-0"></span>
                                    <span>{{ $finding }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Recomendaciones</h3>
                    </div>
                    <div class="p-5">
                        <ul class="space-y-3 text-sm text-gray-700">
                            @foreach($analytics['recommendations'] as $recommendation)
                                <li class="flex items-start gap-3">
                                    <span class="mt-1 h-2.5 w-2.5 rounded-full bg-emerald-600 flex-shrink-0"></span>
                                    <span>{{ $recommendation }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </section>
            </div>

            <section class="rounded-xl border border-gray-200 bg-white">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Equivalencias con los datos actuales del sistema</h3>
                </div>
                <div class="p-5">
                    <ul class="space-y-3 text-sm text-gray-700">
                        @foreach($analytics['assumptions'] as $assumption)
                            <li class="flex items-start gap-3">
                                <span class="mt-1 h-2.5 w-2.5 rounded-full bg-slate-500 flex-shrink-0"></span>
                                <span>{{ $assumption }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Detalle operacional del corte</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Ticket</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Titulo</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Area</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Canal</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Servicio</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Subservicio</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Estado</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Ruta</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Creada</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($analytics['detail_rows'] as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $row['ticket'] }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $row['title'] }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $row['area'] }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $row['channel'] }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $row['service'] }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $row['subservice'] }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $row['status'] }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $row['route'] }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $row['created_at'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-6 text-center text-gray-500">No hay solicitudes asociadas al corte para el filtro actual.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleButton = document.getElementById('toggleAllFamilies');
    const checkboxes = Array.from(document.querySelectorAll('input[name="families[]"]'));

    if (!toggleButton || checkboxes.length === 0) {
        return;
    }

    const updateLabel = () => {
        const allChecked = checkboxes.every((checkbox) => checkbox.checked);
        toggleButton.textContent = allChecked ? 'Deseleccionar todas' : 'Seleccionar todas';
    };

    toggleButton.addEventListener('click', function () {
        const allChecked = checkboxes.every((checkbox) => checkbox.checked);
        checkboxes.forEach((checkbox) => {
            checkbox.checked = !allChecked;
        });
        updateLabel();
    });

    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateLabel));
    updateLabel();
});
</script>
@endsection
