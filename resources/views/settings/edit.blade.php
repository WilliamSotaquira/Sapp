@extends('layouts.app')

@section('title', 'Configuración del Sistema')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                @if (session('success'))
                    <div class="mb-4 text-green-600">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('settings.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="base_path" class="block text-sm font-medium text-gray-700">
                            Ruta base de almacenamiento
                        </label>
                        <input type="text" name="base_path" id="base_path"
                            value="{{ old('base_path', $basePath) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            maxlength="255">
                        @error('base_path')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                        Guardar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
