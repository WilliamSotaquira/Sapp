@extends('layouts.app')

@section('title', 'Entidades')

@section('breadcrumb')
<nav class="text-xs sm:text-sm mb-3 sm:mb-4" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-1 sm:space-x-2 text-gray-600">
        <li>
            <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition-colors">
                <i class="fas fa-home"></i>
                <span class="hidden sm:inline ml-1">Inicio</span>
            </a>
        </li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li><span class="text-gray-500">Catálogos</span></li>
        <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
        <li class="text-gray-900 font-medium">
            <i class="fas fa-building"></i>
            <span class="ml-1">Entidades</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container mx-auto">
    <div class="flex justify-between items-center mb-4 sm:mb-6">
        <a href="{{ route('companies.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-plus mr-2"></i>Nueva Entidad
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entidad</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIT</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dirección de contacto</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Colores</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registros</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($companies as $company)
                    <tr>
                        <td class="px-4 py-3">
                            @if($company->logo_path)
                                <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo {{ $company->name }}" class="h-10 w-10 object-contain border border-gray-200 rounded bg-white">
                            @else
                                <span class="text-xs text-gray-400">N/A</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $company->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $company->nit ?: 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $company->address ?: 'N/A' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2 text-xs text-gray-600">
                                    <span class="inline-block w-4 h-4 rounded border border-gray-300" style="background-color: {{ $company->primary_color ?: '#ffffff' }}"></span>
                                    {{ $company->primary_color ?: 'N/A' }}
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-600">
                                    <span class="inline-block w-4 h-4 rounded border border-gray-300" style="background-color: {{ $company->alternate_color ?: '#ffffff' }}"></span>
                                    {{ $company->alternate_color ?: 'N/A' }}
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-600">
                                    <span class="inline-block w-4 h-4 rounded border border-gray-300" style="background-color: {{ $company->contrast_color ?: '#ffffff' }}"></span>
                                    {{ $company->contrast_color ?: 'N/A' }}
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-700">
                            <div>Contratos: {{ $company->contracts_count }}</div>
                            <div>Solicitudes: {{ $company->service_requests_count }}</div>
                            <div>Solicitantes: {{ $company->requesters_count }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                            <a href="{{ route('companies.show', $company) }}" class="text-blue-600 hover:text-blue-900 mr-3" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('companies.edit', $company) }}" class="text-green-600 hover:text-green-900 mr-3" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('companies.destroy', $company) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" title="Eliminar"
                                        onclick="return confirm('¿Está seguro de eliminar esta entidad?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                            No hay entidades registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $companies->links() }}
    </div>
</div>
@endsection
