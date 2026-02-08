@extends('layouts.app')

@section('title', 'Crear Entidad')

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
                <span class="text-gray-500">Crear</span>
            </div>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="bg-blue-600 text-white px-6 py-4">
            <div class="flex items-center">
                <i class="fas fa-building text-2xl mr-3"></i>
                <div>
                    <h2 class="text-xl font-bold">Crear Entidad</h2>
                    <p class="text-blue-100 text-sm">Registra nombre, NIT, dirección de contacto y colores</p>
                </div>
            </div>
        </div>

        <div class="p-6">
            <form action="{{ route('companies.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('companies._form')

                <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end space-x-3">
                    <a href="{{ route('companies.index') }}"
                       class="bg-gray-300 text-gray-700 px-6 py-3 rounded-md hover:bg-gray-400 transition duration-150">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition duration-150">
                        Guardar Entidad
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
