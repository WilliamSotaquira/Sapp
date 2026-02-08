@extends('layouts.app')

@section('title', 'Detalle de Entidad')

@section('breadcrumb')
<nav class="flex" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
            <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Inicio</a>
        </li>
        <li>
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <a href="{{ route('companies.index') }}" class="text-blue-600 hover:text-blue-700">Entidades</a>
            </div>
        </li>
        <li aria-current="page">
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <span class="text-gray-500">Detalle</span>
            </div>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="bg-blue-600 text-white px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-building text-2xl mr-3"></i>
                    <div>
                        <h2 class="text-xl font-bold">{{ $company->name }}</h2>
                        <p class="text-blue-100 text-sm">Detalle de entidad</p>
                    </div>
                </div>
                <a href="{{ route('companies.edit', $company) }}"
                   class="bg-white/20 hover:bg-white/30 text-white px-3 py-2 rounded text-sm">
                    <i class="fas fa-edit mr-1"></i>Editar
                </a>
            </div>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-500">Logo</label>
                <div class="mt-2">
                    @if($company->logo_path)
                        <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo de {{ $company->name }}" class="h-16 w-16 object-contain border border-gray-200 rounded bg-white">
                    @else
                        <p class="text-gray-900 font-semibold">N/A</p>
                    @endif
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">NIT</label>
                <p class="text-gray-900 font-semibold">{{ $company->nit ?: 'N/A' }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">Dirección de contacto</label>
                <p class="text-gray-900 font-semibold">{{ $company->address ?: 'N/A' }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">Color principal</label>
                <div class="flex items-center gap-2 mt-1">
                    <span class="inline-block w-5 h-5 rounded border border-gray-300" style="background-color: {{ $company->primary_color ?: '#ffffff' }}"></span>
                    <p class="text-gray-900 font-semibold">{{ $company->primary_color ?: 'N/A' }}</p>
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">Color alterno</label>
                <div class="flex items-center gap-2 mt-1">
                    <span class="inline-block w-5 h-5 rounded border border-gray-300" style="background-color: {{ $company->alternate_color ?: '#ffffff' }}"></span>
                    <p class="text-gray-900 font-semibold">{{ $company->alternate_color ?: 'N/A' }}</p>
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">Color de contraste</label>
                <div class="flex items-center gap-2 mt-1">
                    <span class="inline-block w-5 h-5 rounded border border-gray-300" style="background-color: {{ $company->contrast_color ?: '#ffffff' }}"></span>
                    <p class="text-gray-900 font-semibold">{{ $company->contrast_color ?: 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700">Resumen de relación</h3>
        </div>
        <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div class="bg-gray-50 rounded p-3">
                <p class="text-xs text-gray-500 uppercase">Contratos</p>
                <p class="text-xl font-bold text-gray-900">{{ $company->contracts_count }}</p>
            </div>
            <div class="bg-gray-50 rounded p-3">
                <p class="text-xs text-gray-500 uppercase">Solicitudes</p>
                <p class="text-xl font-bold text-gray-900">{{ $company->service_requests_count }}</p>
            </div>
            <div class="bg-gray-50 rounded p-3">
                <p class="text-xs text-gray-500 uppercase">Solicitantes</p>
                <p class="text-xl font-bold text-gray-900">{{ $company->requesters_count }}</p>
            </div>
            <div class="bg-gray-50 rounded p-3">
                <p class="text-xs text-gray-500 uppercase">Usuarios</p>
                <p class="text-xl font-bold text-gray-900">{{ $company->users_count }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
