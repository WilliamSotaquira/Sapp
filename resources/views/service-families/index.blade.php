{{-- resources/views/service-families/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Familias de Servicio')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Familias de Servicio</h1>
        <a href="{{ route('service-families.create') }}"
           class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-plus mr-2"></i>Nueva Familia
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nombre
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Código
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Servicios
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Estado
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Orden
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($serviceFamilies as $family)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $family->name }}</div>
                        @if($family->description)
                        <div class="text-sm text-gray-500">{{ Str::limit($family->description, 50) }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $family->code }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $family->services_count }} servicios
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $family->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $family->is_active ? 'Activa' : 'Inactiva' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $family->sort_order }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('service-families.show', $family) }}"
                           class="text-blue-600 hover:text-blue-900 mr-3">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('service-families.edit', $family) }}"
                           class="text-green-600 hover:text-green-900 mr-3">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('service-families.destroy', $family) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-red-600 hover:text-red-900"
                                    onclick="return confirm('¿Está seguro de eliminar esta familia?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                        No hay familias de servicio registradas.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
