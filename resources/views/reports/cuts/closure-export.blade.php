@extends('layouts.app')

@section('content')
<div class="py-6 max-w-5xl mx-auto">
    <nav class="mb-6" aria-label="Breadcrumb">
        <ol class="flex space-x-2 text-sm text-gray-600">
            <li><a href="{{ route('reports.cuts.closure', $cut) }}" class="hover:text-blue-600">← Volver al cierre</a></li>
        </ol>
    </nav>

    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">Tabla de Obligaciones — {{ $cut->name }}</h1>
        <button onclick="copyTable()" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-semibold rounded-lg hover:bg-purple-700 transition">
            <i class="fas fa-copy mr-2"></i> Copiar tabla
        </button>
    </div>

    <p class="text-sm text-gray-500 mb-4">
        Periodo: {{ $cut->start_date->format('d/m/Y') }} – {{ $cut->end_date->format('d/m/Y') }}
        · {{ $report['total_requests'] }} solicitudes
    </p>

    {{-- Table ready to copy --}}
    <div id="obligationTable" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 w-16">No.</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 w-1/3">Obligación</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Actividad</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700 w-20">%</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($report['obligations'] as $obligation)
                    <tr class="{{ $obligation['request_count'] === 0 ? 'bg-gray-50 opacity-60' : '' }}">
                        <td class="px-4 py-3 text-center font-bold text-gray-800">{{ $obligation['number'] }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $obligation['family_name'] }}</td>
                        <td class="px-4 py-3 text-gray-700 leading-relaxed">{{ $obligation['activity_text'] }}</td>
                        <td class="px-4 py-3 text-center font-semibold {{ $obligation['percentage'] === 100 ? 'text-green-600' : 'text-gray-400' }}">{{ $obligation['percentage'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Stats --}}
    <div class="mt-4 text-xs text-gray-400 text-right">
        Generado: {{ now()->format('d/m/Y H:i') }} · SAPP - {{ $cut->contract?->number ?? '' }}
    </div>
</div>

<script>
function copyTable() {
    var table = document.getElementById('obligationTable');
    var range = document.createRange();
    range.selectNodeContents(table);
    var selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);

    try {
        document.execCommand('copy');
        var btn = event.target.closest('button');
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check mr-2"></i> Copiado';
        setTimeout(function() { btn.innerHTML = orig; }, 2000);
    } catch (e) {
        alert('No se pudo copiar. Selecciona la tabla manualmente con Ctrl+A y luego Ctrl+C.');
    }

    selection.removeAllRanges();
}
</script>
@endsection
