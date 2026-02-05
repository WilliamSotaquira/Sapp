@extends('layouts.app')

@section('title', 'Seleccionar espacio de trabajo')

@section('content')
<div class="max-w-2xl mx-auto py-10">
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-blue-100">
            <h2 class="text-xl font-bold text-gray-800">Selecciona un espacio de trabajo</h2>
            <p class="text-sm text-gray-600 mt-1">Todas las acciones se realizarán dentro de este espacio.</p>
        </div>

        <div class="p-6">
            @if (session('error'))
                <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($companies->isEmpty())
                <div class="p-4 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-800">
                    No tienes espacios de trabajo asignados. Contacta al administrador.
                </div>
            @else
                <form method="POST" action="{{ route('workspaces.switch') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="company_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Espacio de trabajo
                        </label>
                        <select id="company_id" name="company_id"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seleccione...</option>
                            @foreach ($companies as $company)
                                @php
                                    $companyDisplayName = $company->name;
                                    if (Str::contains(Str::lower($companyDisplayName), 'cultura')) {
                                        $companyDisplayName = 'Min Culturas';
                                    }
                                @endphp
                                <option value="{{ $company->id }}" {{ (string) $currentCompanyId === (string) $company->id ? 'selected' : '' }}>
                                    {{ $companyDisplayName }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-5 py-2.5 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700">
                            Continuar
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
