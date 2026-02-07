@props([
    'serviceRequest' => null,
])

@php
    $cuts = $serviceRequest->cuts ?? collect();
@endphp

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 px-6 py-4 border-b border-indigo-100">
        <h3 class="sr-card-title text-gray-800">Cortes Asociados</h3>
    </div>
    <div class="p-6">
        @if ($cuts->isEmpty())
            <div class="flex items-center justify-center py-8">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2 text-gray-500">No hay cortes asociados</p>
                </div>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($cuts as $cut)
                    <div class="flex items-center justify-between p-4 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-all duration-200">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-800">{{ $cut->name }}</div>
                            <div class="text-sm text-gray-600">
                                <i class="far fa-calendar text-indigo-500 mr-1"></i>
                                {{ $cut->start_date->format('d/m/Y') }} — {{ $cut->end_date->format('d/m/Y') }}
                            </div>
                            @if ($cut->notes)
                                <div class="text-sm text-gray-500 mt-2">
                                    <span class="inline-block bg-gray-100 px-2 py-1 rounded">{{ $cut->notes }}</span>
                                </div>
                            @endif
                        </div>
                        <div class="ml-4">
                            <a href="{{ route('reports.cuts.show', $cut) }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition text-sm font-medium">
                                <i class="fas fa-eye"></i>
                                Ver
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
