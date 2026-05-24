@extends('layouts.app')

@section('title', 'Búsqueda y Análisis')

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Dashboard</a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('reports.index') }}" class="text-blue-600 hover:text-blue-700">Informes</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Búsqueda y Análisis</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
<div class="py-6">
    <!-- Main Card -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Búsqueda y Análisis</p>
                <h2 class="text-xl font-bold text-gray-900">Buscar solicitudes por términos</h2>
                <p class="text-sm text-gray-600">Ingrese términos de búsqueda y/o seleccione tipos de servicio para analizar solicitudes.</p>
            </div>
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold">
                <i class="fa-solid fa-magnifying-glass-chart"></i> Análisis
            </span>
        </div>

        <form action="{{ route('reports.search-analysis.search') }}" method="GET" id="searchForm" class="p-6 space-y-8">

            <!-- Paso 1: Términos de búsqueda -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center text-sm font-bold">1</span>
                    <h4 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                        <i class="fa-solid fa-keyboard text-indigo-600" aria-hidden="true"></i>
                        Términos de búsqueda
                    </h4>
                </div>

                <div>
                    <label for="terms" class="block text-sm font-medium text-gray-700 mb-2">
                        Términos (separados por coma)
                    </label>
                    <input
                        type="text"
                        id="terms"
                        name="terms"
                        value="{{ old('terms') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors @error('terms') border-red-500 @enderror"
                        placeholder="Ej: red, servidor, correo electrónico"
                        aria-describedby="terms_help"
                    >
                    @error('terms')
                        <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                    <p id="terms_help" class="mt-1 text-xs text-gray-500">
                        Máximo 10 términos, cada uno de hasta 100 caracteres. Se busca coincidencia parcial (OR entre términos).
                    </p>
                </div>
            </div>

            <!-- Paso 2: Filtros de tipo de servicio -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center text-sm font-bold">2</span>
                    <h4 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                        <i class="fa-solid fa-layer-group text-indigo-600" aria-hidden="true"></i>
                        Filtros de servicio (opcional)
                    </h4>
                </div>

                <p class="text-sm text-gray-600 mb-4">
                    Seleccione familias, servicios o sub-servicios para restringir la búsqueda. Si no selecciona ninguno, se busca en todas las solicitudes.
                </p>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Familias -->
                    <div>
                        <label for="families" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fa-solid fa-folder text-gray-400 mr-1" aria-hidden="true"></i>
                            Familias de servicio
                        </label>
                        <select
                            id="families"
                            name="families[]"
                            multiple
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            size="6"
                            aria-describedby="families_help"
                        >
                            @foreach($families as $family)
                                <option value="{{ $family->id }}" {{ in_array($family->id, old('families', [])) ? 'selected' : '' }}>
                                    {{ $family->name }}
                                </option>
                            @endforeach
                        </select>
                        <p id="families_help" class="mt-1 text-xs text-gray-500">Ctrl+click para selección múltiple</p>
                    </div>

                    <!-- Servicios -->
                    <div>
                        <label for="services" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fa-solid fa-cogs text-gray-400 mr-1" aria-hidden="true"></i>
                            Servicios
                        </label>
                        <select
                            id="services"
                            name="services[]"
                            multiple
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            size="6"
                            aria-describedby="services_help"
                        >
                            @foreach($services as $service)
                                <option value="{{ $service->id }}" {{ in_array($service->id, old('services', [])) ? 'selected' : '' }}>
                                    {{ $service->name }}
                                </option>
                            @endforeach
                        </select>
                        <p id="services_help" class="mt-1 text-xs text-gray-500">Ctrl+click para selección múltiple</p>
                    </div>

                    <!-- Sub-servicios -->
                    <div>
                        <label for="sub_services" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fa-solid fa-sitemap text-gray-400 mr-1" aria-hidden="true"></i>
                            Sub-servicios
                        </label>
                        <select
                            id="sub_services"
                            name="sub_services[]"
                            multiple
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            size="6"
                            aria-describedby="sub_services_help"
                        >
                            @foreach($subServices as $subService)
                                <option value="{{ $subService->id }}" {{ in_array($subService->id, old('sub_services', [])) ? 'selected' : '' }}>
                                    {{ $subService->name }}
                                </option>
                            @endforeach
                        </select>
                        <p id="sub_services_help" class="mt-1 text-xs text-gray-500">Ctrl+click para selección múltiple</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200">
                <button
                    type="submit"
                    class="flex-1 sm:flex-initial inline-flex items-center justify-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all"
                    id="searchBtn"
                >
                    <i class="fa-solid fa-magnifying-glass mr-2" aria-hidden="true"></i>
                    Buscar
                </button>

                <a
                    href="{{ route('reports.index') }}"
                    class="flex-1 sm:flex-initial inline-flex items-center justify-center px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all"
                >
                    <i class="fa-solid fa-arrow-left mr-2" aria-hidden="true"></i>
                    Volver
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
