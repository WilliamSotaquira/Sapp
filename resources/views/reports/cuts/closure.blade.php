@extends('layouts.app')

@section('content')
<div class="py-6 max-w-6xl mx-auto">
    <nav class="mb-6" aria-label="Breadcrumb">
        <ol class="flex space-x-2 text-sm text-gray-600">
            <li><a href="{{ route('dashboard') }}" class="hover:text-blue-600">Inicio</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="{{ route('reports.cuts.index') }}" class="hover:text-blue-600">Cortes</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="{{ route('reports.cuts.show', $cut) }}" class="hover:text-blue-600">{{ $cut->name }}</a></li>
            <li><span class="mx-2">/</span></li>
            <li class="text-gray-900 font-medium">Cierre</li>
        </ol>
    </nav>

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Cierre de Corte: {{ $cut->name }}</h1>
        <p class="text-sm text-gray-600 mt-1">
            Periodo: {{ $cut->start_date->format('d/m/Y') }} – {{ $cut->end_date->format('d/m/Y') }}
            · Contrato: {{ $cut->contract?->number ?? 'N/A' }}
        </p>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
        </div>
    @endif

    {{-- ================================================================ --}}
    {{-- STEP 1: VALIDATION --}}
    {{-- ================================================================ --}}
    <section class="mb-8 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-800 flex items-center">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold mr-2.5">1</span>
                Validación
            </h2>
            @if($validation['ready'])
                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
                    <i class="fas fa-check mr-1"></i> Listo
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">
                    <i class="fas fa-exclamation-triangle mr-1"></i> Revisar
                </span>
            @endif
        </div>
        <div class="p-5">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <div class="p-3 bg-gray-50 rounded-lg text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ $validation['total_requests'] }}</p>
                    <p class="text-xs text-gray-500">Solicitudes en corte</p>
                </div>
                <div class="p-3 {{ $validation['orphans_count'] > 0 ? 'bg-amber-50' : 'bg-green-50' }} rounded-lg text-center">
                    <p class="text-2xl font-bold {{ $validation['orphans_count'] > 0 ? 'text-amber-700' : 'text-green-700' }}">{{ $validation['orphans_count'] }}</p>
                    <p class="text-xs text-gray-500">Huérfanas</p>
                </div>
                <div class="p-3 {{ $validation['empty_families_count'] > 0 ? 'bg-blue-50' : 'bg-green-50' }} rounded-lg text-center">
                    <p class="text-2xl font-bold {{ $validation['empty_families_count'] > 0 ? 'text-blue-700' : 'text-green-700' }}">{{ $validation['empty_families_count'] }}</p>
                    <p class="text-xs text-gray-500">Familias vacías</p>
                </div>
            </div>

            @if(!empty($validation['issues']))
                <div class="space-y-2">
                    @foreach($validation['issues'] as $issue)
                        <div class="flex items-start gap-2 p-3 rounded-lg
                            {{ $issue['severity'] === 'error' ? 'bg-red-50 text-red-700' : '' }}
                            {{ $issue['severity'] === 'warning' ? 'bg-amber-50 text-amber-700' : '' }}
                            {{ $issue['severity'] === 'info' ? 'bg-blue-50 text-blue-700' : '' }}">
                            <i class="fas {{ $issue['severity'] === 'error' ? 'fa-times-circle' : ($issue['severity'] === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle') }} mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-sm font-medium">{{ $issue['message'] }}</p>
                                @if($issue['type'] === 'orphans' && $issue['data']->isNotEmpty())
                                    <ul class="mt-1 text-xs opacity-80">
                                        @foreach($issue['data']->take(5) as $orphan)
                                            <li>{{ $orphan->ticket_number }} — {{ Str::limit($orphan->title, 50) }}</li>
                                        @endforeach
                                        @if($issue['data']->count() > 5)
                                            <li>... y {{ $issue['data']->count() - 5 }} más</li>
                                        @endif
                                    </ul>
                                @endif
                            </div>
                            @if($issue['type'] === 'orphans')
                                <form action="{{ route('reports.cuts.closure.fix-orphans', $cut) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-amber-600 text-white text-xs font-semibold rounded-lg hover:bg-amber-700 transition">
                                        Asignar al corte
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-green-600"><i class="fas fa-check-circle mr-1"></i> Sin problemas detectados.</p>
            @endif
        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- STEP 2: OBLIGATION REPORT --}}
    {{-- ================================================================ --}}
    <section class="mb-8 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-800 flex items-center">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-purple-100 text-purple-700 text-xs font-bold mr-2.5">2</span>
                Reporte de Obligaciones
            </h2>
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500">{{ $report['total_requests'] }} solicitudes · {{ count($report['obligations']) }} obligaciones</span>
                <button type="button" id="generateAiBtn" onclick="generateWithAI()"
                        class="inline-flex items-center px-3 py-1.5 bg-purple-600 text-white text-xs font-semibold rounded-lg hover:bg-purple-700 transition">
                    <i class="fas fa-magic mr-1.5"></i> Generar con IA
                </button>
            </div>
        </div>
        <div class="p-5">
            <div class="space-y-4">
                @foreach($report['obligations'] as $obligation)
                    <div class="border border-gray-100 rounded-lg overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-purple-600 text-white text-[10px] font-bold">
                                    {{ $obligation['number'] }}
                                </span>
                                <span class="text-sm font-semibold text-gray-800">{{ $obligation['family_name'] }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">{{ $obligation['request_count'] }} solicitud(es)</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                    {{ $obligation['percentage'] === 100 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $obligation['percentage'] }}%
                                </span>
                            </div>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-sm text-gray-700 leading-relaxed" data-obligation-family-id="{{ $obligation['family_id'] }}">{{ $obligation['activity_text'] }}</p>
                            @if($obligation['request_count'] > 0)
                                <details class="mt-2">
                                    <summary class="text-xs text-blue-600 cursor-pointer hover:text-blue-800">Ver solicitudes</summary>
                                    <ul class="mt-2 space-y-1">
                                        @foreach($obligation['requests'] as $sr)
                                            <li class="text-xs text-gray-600 flex items-center gap-2">
                                                <span class="font-mono text-gray-400">{{ $sr->ticket_number }}</span>
                                                <span>{{ Str::limit($sr->title, 60) }}</span>
                                                <span class="text-gray-400 ml-auto">{{ $sr->resolved_at ? $sr->resolved_at->format('d/m') : '' }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- STEP 3: ACTIONS --}}
    {{-- ================================================================ --}}
    <section class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-800 flex items-center">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 text-green-700 text-xs font-bold mr-2.5">3</span>
                Exportar
            </h2>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Download evidences ZIP --}}
                <form action="{{ route('reports.cuts.closure.package', $cut) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex flex-col items-center gap-2 p-4 border-2 border-dashed border-gray-200 rounded-xl hover:border-blue-300 hover:bg-blue-50 transition group">
                        <i class="fas fa-file-archive text-2xl text-gray-400 group-hover:text-blue-500"></i>
                        <span class="text-sm font-semibold text-gray-700 group-hover:text-blue-700">Evidencias ZIP</span>
                        <span class="text-xs text-gray-400">Por familia y ticket</span>
                    </button>
                </form>

                {{-- Export table --}}
                <a href="{{ route('reports.cuts.closure.export-table', $cut) }}" class="flex flex-col items-center gap-2 p-4 border-2 border-dashed border-gray-200 rounded-xl hover:border-purple-300 hover:bg-purple-50 transition group">
                    <i class="fas fa-table text-2xl text-gray-400 group-hover:text-purple-500"></i>
                    <span class="text-sm font-semibold text-gray-700 group-hover:text-purple-700">Tabla Obligaciones</span>
                    <span class="text-xs text-gray-400">Copiar/pegar en informe</span>
                </a>

                {{-- Download PDF report --}}
                <a href="{{ route('reports.cuts.show', ['cut' => $cut, 'families' => collect($report['obligations'])->pluck('family_id')->all()]) }}" class="flex flex-col items-center gap-2 p-4 border-2 border-dashed border-gray-200 rounded-xl hover:border-red-300 hover:bg-red-50 transition group">
                    <i class="fas fa-file-pdf text-2xl text-gray-400 group-hover:text-red-500"></i>
                    <span class="text-sm font-semibold text-gray-700 group-hover:text-red-700">Reporte PDF</span>
                    <span class="text-xs text-gray-400">Ver en vista de corte</span>
                </a>
            </div>
        </div>
    </section>
</div>

<script>
function generateWithAI() {
    var btn = document.getElementById('generateAiBtn');
    var originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Generando...';

    fetch('{{ route("reports.cuts.closure.generate-ai", $cut) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success && data.generated) {
            // Update each obligation's activity text in the DOM
            var obligations = document.querySelectorAll('[data-obligation-family-id]');
            obligations.forEach(function(el) {
                var familyId = el.dataset.obligationFamilyId;
                if (data.generated[familyId]) {
                    el.textContent = data.generated[familyId];
                    el.classList.add('bg-purple-50', 'p-2', 'rounded');
                }
            });
            btn.innerHTML = '<i class="fas fa-check mr-1.5"></i> Generado';
            btn.classList.remove('bg-purple-600', 'hover:bg-purple-700');
            btn.classList.add('bg-green-600');
            setTimeout(function() {
                btn.innerHTML = '<i class="fas fa-magic mr-1.5"></i> Regenerar con IA';
                btn.classList.remove('bg-green-600');
                btn.classList.add('bg-purple-600', 'hover:bg-purple-700');
                btn.disabled = false;
            }, 3000);
        } else {
            alert('No se pudo generar. Verifica que el servicio de IA esté configurado.');
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    })
    .catch(function(err) {
        console.error(err);
        alert('Error de conexión. Intenta de nuevo.');
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });
}
</script>
@endsection
