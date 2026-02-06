@extends('layouts.app')

@section('content')
<div class="py-6">
    <nav class="mb-6" aria-label="Breadcrumb">
        <ol class="flex space-x-2 text-sm text-gray-600">
            <li><a href="{{ route('dashboard') }}" class="hover:text-blue-600">Inicio</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="{{ route('reports.index') }}" class="hover:text-blue-600">Reportes</a></li>
            <li><span class="mx-2">/</span></li>
            <li class="text-gray-900 font-medium">Cortes</li>
        </ol>
    </nav>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Reportes</p>
                <h2 class="text-xl font-bold text-gray-900">Cortes</h2>
                <p class="text-sm text-gray-600">Agrupa solicitudes por periodos (fecha inicio / fin) según actividad.</p>
            </div>
            <a href="{{ route('reports.cuts.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                <i class="fa-solid fa-plus"></i>
                Nuevo corte
            </a>
        </div>

        @if(session('success'))
            <div class="p-4 bg-green-50 text-green-700 border-b border-green-100">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-red-50 text-red-700 border-b border-red-100">{{ session('error') }}</div>
        @endif

        <div class="p-6">
            @if($cuts->count() === 0)
                <div class="p-6 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-sm text-yellow-800">Aún no hay cortes. Crea el primero para asociar solicitudes por actividad.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Corte</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Contrato</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Rango</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Solicitudes</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($cuts as $cut)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900">{{ $cut->name }}</div>
                                        <div class="text-xs text-gray-500">#{{ $cut->id }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $cut->contract?->number ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $cut->start_date->format('Y-m-d') }} → {{ $cut->end_date->format('Y-m-d') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold">
                                            {{ $cut->service_requests_count }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('reports.cuts.show', $cut) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Ver</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $cuts->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
