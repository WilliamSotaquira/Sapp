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
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Solicitudes asociadas</h3>
                <span class="text-sm text-gray-600">Total: <span class="font-semibold">{{ $serviceRequests->total() }}</span></span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Ticket</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Título</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Familia</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Creada</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($serviceRequests as $sr)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $sr->ticket_number }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $sr->title }}</td>
                                @php
                                    $family = $sr->subService?->service?->family;
                                    $familyName = $family?->name ?? 'Sin Familia';
                                    $contractNumber = $family?->contract?->number;
                                    $familyLabel = $contractNumber ? ($contractNumber . ' - ' . $familyName) : $familyName;
                                @endphp
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $familyLabel }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $sr->status }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $sr->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <form method="POST" action="{{ route('reports.cuts.requests.remove', [$cut, $sr]) }}" onsubmit="return confirm('¿Remover esta solicitud del corte?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 rounded-lg border border-red-200 text-red-700 hover:bg-red-50">
                                            <i class="fa-solid fa-xmark"></i>
                                            Quitar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $serviceRequests->links() }}</div>
        </div>
    </div>
</div>
@endsection
