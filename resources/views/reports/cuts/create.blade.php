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
            <li class="text-gray-900 font-medium">Nuevo corte</li>
        </ol>
    </nav>

    @if(!$hasActiveContract)
        <div class="bg-white rounded-xl shadow-md overflow-hidden p-6">
            <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-4">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5 mr-3"></i>
                    <div>
                        <h3 class="text-sm font-medium text-yellow-800">Sin contrato activo</h3>
                        <p class="mt-1 text-sm text-yellow-700">No es posible crear un corte sin un contrato activo en el espacio de trabajo actual.</p>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('reports.cuts.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Volver a cortes</a>
            </div>
        </div>
    @else
        {{-- ============================================================ --}}
        {{-- ACCIÓN PRINCIPAL: Cerrar corte actual y crear nuevo          --}}
        {{-- ============================================================ --}}
        @if($currentOpenCut)
            <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900">Cerrar corte y crear siguiente</h2>
                    <p class="text-sm text-gray-600 mt-1">Cierra el corte vigente con la fecha de hoy y crea automáticamente el siguiente.</p>
                </div>

                <div class="p-6">
                    @if(session('error'))
                        <div class="rounded-lg bg-red-50 border border-red-200 p-4 mb-4" role="alert">
                            <p class="text-sm text-red-700"><i class="fas fa-times-circle mr-2"></i>{{ session('error') }}</p>
                        </div>
                    @endif

                    {{-- Info del corte actual --}}
                    <div class="rounded-lg bg-blue-50 border border-blue-200 p-4 mb-6">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                <i class="fas fa-circle text-[6px] mr-1 animate-pulse"></i> Abierto
                            </span>
                            <span class="font-semibold text-gray-900">{{ $currentOpenCut->name }}</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Inicio:</span>
                                <span class="font-medium text-gray-900">{{ $currentOpenCut->start_date->format('d/m/Y') }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Solicitudes:</span>
                                <span class="font-medium text-gray-900">{{ $currentOpenCut->service_requests_count }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Contrato:</span>
                                <span class="font-medium text-gray-900">{{ $activeContract->number }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Acción --}}
                    <form method="POST" action="{{ route('reports.cuts.close', $currentOpenCut) }}" onsubmit="return confirm('¿Cerrar el corte \'{{ $currentOpenCut->name }}\' con fecha de hoy y crear el siguiente automáticamente?')">
                        @csrf
                        <div class="flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center px-5 py-3 rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition">
                                <i class="fas fa-lock mr-2"></i>
                                Cerrar "{{ $currentOpenCut->name }}" y crear siguiente
                            </button>
                            <span class="text-sm text-gray-500">
                                El nuevo corte iniciará desde hoy.
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        @else
            {{-- No hay corte abierto: se necesita crear el primero --}}
            <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900">Crear primer corte</h2>
                    <p class="text-sm text-gray-600 mt-1">No hay un corte abierto para este contrato. Crea el primero para comenzar a registrar solicitudes.</p>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('reports.cuts.store') }}">
                        @csrf
                        <input type="hidden" name="start_date" value="{{ now()->format('Y-m-d\TH:i') }}">
                        <input type="hidden" name="end_date" value="{{ now()->addDays(30)->format('Y-m-d\TH:i') }}">

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del corte</label>
                            <input type="text" name="name" value="{{ ucfirst(now()->locale('es')->translatedFormat('F Y')) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                        </div>

                        <button type="submit" class="inline-flex items-center px-5 py-3 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Crear corte inicial
                        </button>
                    </form>
                </div>
            </div>
        @endif

        {{-- ============================================================ --}}
        {{-- MODO MANUAL (colapsado)                                       --}}
        {{-- ============================================================ --}}
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <button type="button" onclick="document.getElementById('manual-form').classList.toggle('hidden'); this.querySelector('i.fa-chevron-down')?.classList.toggle('rotate-180')" class="w-full px-6 py-4 border-b border-gray-200 flex items-center justify-between hover:bg-gray-50 transition">
                <div class="text-left">
                    <h3 class="text-base font-semibold text-gray-700">Creación manual</h3>
                    <p class="text-xs text-gray-500">Para cortes retroactivos o con fechas específicas</p>
                </div>
                <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200"></i>
            </button>

            <div id="manual-form" class="hidden">
                <form method="POST" action="{{ route('reports.cuts.store') }}" class="p-6 space-y-6">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contrato</label>
                        <div class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700">
                            {{ $activeContract->number }}{{ $activeContract->name ? ' - ' . $activeContract->name : '' }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror" required placeholder="Ej: Agosto 2026">
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha inicio <span class="text-red-500">*</span></label>
                            <input type="datetime-local" name="start_date" value="{{ old('start_date', $dateSuggestion ? $dateSuggestion->startDate->format('Y-m-d\TH:i') : '') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('start_date') border-red-500 @enderror" required>
                            @error('start_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha fin <span class="text-red-500">*</span></label>
                            <input type="datetime-local" name="end_date" value="{{ old('end_date', $dateSuggestion ? $dateSuggestion->endDate->format('Y-m-d\TH:i') : '') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 @error('end_date') border-red-500 @enderror" required>
                            @error('end_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notas</label>
                        <textarea name="notes" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('reports.cuts.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancelar</a>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                            Crear corte manual
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
