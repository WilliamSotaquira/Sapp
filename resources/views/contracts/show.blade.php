@extends('layouts.app')

@section('title', 'Detalle del Contrato')

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Inicio</a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('contracts.index') }}" class="text-blue-600 hover:text-blue-700">Contratos</a>
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
                        <i class="fas fa-file-contract text-2xl mr-3"></i>
                        <div>
                            <h2 class="text-xl font-bold">Contrato {{ $contract->number }}</h2>
                            <p class="text-blue-100 text-sm">{{ $contract->name ?? 'Sin nombre' }}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $contract->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $contract->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
            </div>

            <div class="p-6 space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-500">Espacio de trabajo</label>
                    <p class="text-gray-900 font-semibold">{{ $contract->company->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Descripción</label>
                    <p class="text-gray-700">{{ $contract->description ?? 'Sin descripción' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700">Familias asociadas</h3>
            </div>
            <div class="p-6">
                @if($contract->serviceFamilies->isEmpty())
                    <p class="text-sm text-gray-500">No hay familias asociadas.</p>
                @else
                    <ul class="divide-y divide-gray-200">
                        @foreach($contract->serviceFamilies as $family)
                            <li class="py-2 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $family->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $family->code }}</p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $family->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $family->is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection
