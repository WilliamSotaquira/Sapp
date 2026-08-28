@extends('layouts.app')

@section('title', 'Seleccionar contrato')

@section('content')
<div class="max-w-2xl mx-auto py-10">
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-blue-100">
            <h2 class="text-xl font-bold text-gray-800">Selecciona un contrato</h2>
            <p class="text-sm text-gray-600 mt-1">Todas las acciones se realizarán dentro del contrato seleccionado.</p>
        </div>

        <div class="p-6">
            @if (session('error'))
                <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($contractsByCompany->isEmpty())
                <div class="p-4 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-800">
                    No tienes contratos activos asignados. Contacta al administrador.
                </div>
            @else
                <form method="POST" action="{{ route('workspaces.switch') }}" class="space-y-6" id="workspaceSwitchForm">
                    @csrf
                    @foreach ($contractsByCompany as $companyId => $contracts)
                        @php
                            $company = $contracts->first()->company;
                            $companyName = $company->name ?? 'Entidad';
                            $accent = $company->primary_color ?? '#2563EB';
                        @endphp

                        {{-- Grupo por entidad --}}
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                @if (!empty($company->logo_path))
                                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-white ring-1 ring-black/5">
                                        <img src="{{ asset('storage/' . $company->logo_path) }}"
                                             alt="{{ $companyName }}"
                                             class="max-w-[1.5rem] max-h-[1.5rem] object-contain">
                                    </div>
                                @else
                                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-600 ring-1 ring-black/5">
                                        <i class="fas fa-building text-sm"></i>
                                    </div>
                                @endif
                                <p class="text-sm font-semibold text-gray-800">{{ $companyName }}</p>
                            </div>

                            {{-- Contratos de la entidad --}}
                            <div class="space-y-2 pl-2">
                                @foreach ($contracts as $contract)
                                    @php
                                        $isSelected = (string) old('contract_id', $currentContractId) === (string) $contract->id;
                                        $contractLabel = $contract->number ?: $contract->name;
                                    @endphp
                                    <label class="block cursor-pointer">
                                        <input
                                            type="radio"
                                            name="contract_id"
                                            value="{{ $contract->id }}"
                                            class="sr-only peer"
                                            onchange="document.getElementById('workspaceSwitchForm').submit()"
                                            {{ $isSelected ? 'checked' : '' }}
                                        >
                                        <div class="flex items-center gap-3 px-4 py-3 rounded-2xl border border-gray-200 bg-white shadow-sm transition
                                                    peer-checked:ring-2 peer-checked:ring-offset-1"
                                             style="{{ $isSelected ? 'border-color: ' . $accent . '; box-shadow: 0 0 0 2px ' . $accent . '22;' : '' }}">
                                            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-50 text-slate-500 ring-1 ring-black/5">
                                                <i class="fas fa-file-contract text-base"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[11px] uppercase tracking-wider text-gray-400">Contrato</p>
                                                <p class="text-sm sm:text-base font-semibold text-gray-900 truncate">
                                                    {{ $contractLabel }}
                                                </p>
                                                @if ($contract->name && $contract->number)
                                                    <p class="text-xs text-gray-500 truncate">{{ $contract->name }}</p>
                                                @endif
                                            </div>
                                            @if($isSelected)
                                                <div class="ml-auto text-xs font-semibold" style="color: {{ $accent }};">
                                                    Activo
                                                </div>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @error('contract_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
