@extends('layouts.app')

@section('title', 'Seleccionar entidad')

@section('content')
<div class="max-w-2xl mx-auto py-10">
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-blue-100">
            <h2 class="text-xl font-bold text-gray-800">Selecciona una entidad</h2>
            <p class="text-sm text-gray-600 mt-1">Todas las acciones se realizarán dentro de la entidad seleccionada.</p>
        </div>

        <div class="p-6">
            @if (session('error'))
                <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($companies->isEmpty())
                <div class="p-4 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-800">
                    No tienes entidades asignadas. Contacta al administrador.
                </div>
            @else
                <form method="POST" action="{{ route('workspaces.switch') }}" class="space-y-4" id="workspaceSwitchForm">
                    @csrf
                    <div class="space-y-3">
                        <p class="text-sm font-medium text-gray-700">Entidad</p>
                        @foreach ($companies as $company)
                            @php
                                $companyDisplayName = $company->name;
                                $isSelected = (string) old('company_id', $currentCompanyId) === (string) $company->id;
                                $accent = $company->primary_color ?: '#2563EB';
                                $activeContract = $company->activeContract;
                                $contractLabel = $activeContract ? ($activeContract->number ?: $activeContract->name) : 'Sin contrato activo';
                            @endphp

                            <label class="block cursor-pointer">
                                <input
                                    type="radio"
                                    name="company_id"
                                    value="{{ $company->id }}"
                                    class="sr-only peer"
                                    onchange="document.getElementById('workspaceSwitchForm').submit()"
                                    {{ $isSelected ? 'checked' : '' }}
                                >
                                <div class="flex items-center gap-3 px-4 py-3 rounded-2xl border border-gray-200 bg-white shadow-sm transition
                                            peer-checked:ring-2 peer-checked:ring-offset-1"
                                     style="{{ $isSelected ? 'border-color: ' . $accent . '; box-shadow: 0 0 0 2px ' . $accent . '22;' : '' }}">
                                    @if (!empty($company->logo_path))
                                        <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-white/80 ring-1 ring-black/5">
                                            <img src="{{ asset('storage/' . $company->logo_path) }}"
                                                 alt="{{ $companyDisplayName }}"
                                                 class="max-w-[2.25rem] max-h-[2.25rem] object-contain">
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-slate-100 text-slate-600 ring-1 ring-black/5">
                                            <i class="fas fa-building text-base"></i>
                                        </div>
                                    @endif

                                    <div class="min-w-0">
                                        <p class="text-[11px] uppercase tracking-wider text-gray-400">Entidad</p>
                                        <p class="text-sm sm:text-base font-semibold text-gray-900 truncate">
                                            {{ $companyDisplayName }}
                                        </p>
                                        <p class="text-xs text-gray-500 truncate">{{ $contractLabel }}</p>
                                    </div>

                                    @if($isSelected)
                                        <div class="ml-auto text-xs font-semibold" style="color: {{ $accent }};">
                                            Activo
                                        </div>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                        @error('company_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                </form>
            @endif
        </div>
    </div>
</div>
@endsection
