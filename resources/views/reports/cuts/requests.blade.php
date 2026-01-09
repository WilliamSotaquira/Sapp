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
            <li class="text-gray-900 font-medium">Gestionar solicitudes</li>
        </ol>
    </nav>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-start justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Corte #{{ $cut->id }}</p>
                <h2 class="text-xl font-bold text-gray-900">Gestionar solicitudes</h2>
                <p class="text-sm text-gray-600">{{ $cut->start_date->format('Y-m-d') }} → {{ $cut->end_date->format('Y-m-d') }}</p>
                <p class="text-xs text-gray-500 mt-1">Tip: puedes filtrar por ticket/título/email. Al guardar, se reemplaza la selección actual del corte.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('reports.cuts.show', $cut) }}" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Volver</a>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-green-50 text-green-700 border-b border-green-100">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-red-50 text-red-700 border-b border-red-100">{{ session('error') }}</div>
        @endif

        <div class="p-6 space-y-6">
            <form method="GET" action="{{ route('reports.cuts.requests', $cut) }}" class="flex flex-col md:flex-row gap-3">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Ticket, título o email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        Filtrar
                    </button>
                    <a href="{{ route('reports.cuts.requests', $cut) }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Limpiar</a>
                </div>
            </form>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2">
                    <form method="POST" action="{{ route('reports.cuts.requests.update', $cut) }}">
                        @csrf

                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-base font-semibold text-gray-900">Seleccionar solicitudes</h3>
                            <div class="text-xs text-gray-500">Marcadas: <span id="selectedCount" class="font-semibold">0</span></div>
                        </div>

                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Sel</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Ticket</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Título</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Solicitante</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Creada</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($serviceRequests as $sr)
                                        @php $checked = in_array($sr->id, $selectedIds, true); @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3">
                                                <input type="checkbox" name="service_request_ids[]" value="{{ $sr->id }}" class="sr-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded" {{ $checked ? 'checked' : '' }}>
                                            </td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $sr->ticket_number }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $sr->title }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $sr->requester?->email ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $sr->status }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $sr->created_at?->format('Y-m-d H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <div>{{ $serviceRequests->withQueryString()->links() }}</div>
                            <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                                <i class="fa-solid fa-floppy-disk"></i>
                                Guardar selección
                            </button>
                        </div>
                    </form>
                </div>

                <div class="lg:col-span-1">
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Agregar rápido por ticket</h3>
                        <p class="text-xs text-gray-500 mb-3">Agrega sin reemplazar la selección (adjunta al corte).</p>
                        <form method="POST" action="{{ route('reports.cuts.requests.add-ticket', $cut) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ticket</label>
                                <input type="text" name="ticket_number" placeholder="SR-1234" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <button type="submit" class="w-full px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                                <i class="fa-solid fa-plus"></i>
                                Agregar
                            </button>
                        </form>
                    </div>

                    <div class="border border-gray-200 rounded-lg p-4 mt-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Seleccionadas actualmente</h3>
                        <p class="text-xs text-gray-500 mb-3">Total en el corte: <span class="font-semibold">{{ $cut->serviceRequests()->count() }}</span></p>
                        <form method="POST" action="{{ route('reports.cuts.sync', $cut) }}">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                                <i class="fa-solid fa-rotate"></i>
                                Recalcular automáticamente
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function updateCount() {
        var boxes = document.querySelectorAll('.sr-checkbox');
        var count = 0;
        boxes.forEach(function (b) { if (b.checked) count++; });
        var el = document.getElementById('selectedCount');
        if (el) el.textContent = String(count);
    }

    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList && e.target.classList.contains('sr-checkbox')) {
            updateCount();
        }
    });

    updateCount();
})();
</script>
@endsection
