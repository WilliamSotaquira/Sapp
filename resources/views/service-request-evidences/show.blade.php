{{-- resources/views/service-request-evidences/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Evidencia: ' . $evidence->title)

@section('content')
<div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <div class="p-6">
        <!-- Header de la Evidencia -->
        <div class="flex justify-between items-start mb-6 pb-4 border-b border-gray-200">
            <div class="flex-1">
                <h2 class="text-xl font-semibold text-gray-800">{{ $evidence->title }}</h2>
                <p class="text-gray-600 text-sm mt-1">
                    Solicitud #{{ $serviceRequest->ticket_number }} -
                    Creado el {{ $evidence->created_at->format('d/m/Y \\a \\l\\a\\s H:i') }}
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                    @if($evidence->evidence_type == 'PASO_A_PASO') bg-blue-100 text-blue-800
                    @elseif($evidence->evidence_type == 'ARCHIVO') bg-green-100 text-green-800
                    @elseif($evidence->evidence_type == 'COMENTARIO') bg-purple-100 text-purple-800
                    @else bg-gray-100 text-gray-800 @endif">
                    {{ $evidence->evidence_type }}
                </span>
                @if($evidence->step_number)
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    Paso {{ $evidence->step_number }}
                </span>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <!-- Información General y Archivo -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Información General -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Información General</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de evidencia</label>
                            <p class="text-gray-900">{{ $evidence->evidence_type }}</p>
                        </div>
                        @if($evidence->step_number)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número de paso</label>
                            <p class="text-gray-900">{{ $evidence->step_number }}</p>
                        </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de creación</label>
                            <p class="text-gray-900">{{ $evidence->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Creado por</label>
                            <p class="text-gray-900">{{ auth()->user()->name }}</p>
                        </div>
                    </div>
                </div>

                <!-- Archivo Adjunto -->
                @if($evidence->hasFile())
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Archivo Adjunto</h3>
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="flex-shrink-0">
                                <i class="fas fa-file text-blue-600 text-2xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ $evidence->file_original_name }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $evidence->file_mime_type }} • {{ $evidence->getFormattedFileSize() }}
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('service-requests.evidences.download', [$serviceRequest, $evidence]) }}"
                           class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-download mr-2"></i>
                            Descargar archivo
                        </a>
                    </div>
                </div>
                @endif
            </div>

            <!-- Descripción -->
            @if($evidence->description)
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Descripción</h3>
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <p class="text-gray-700 whitespace-pre-line">{{ $evidence->description }}</p>
                </div>
            </div>
            @endif

            <!-- Datos Adicionales -->
            @if(!empty($evidence->evidence_data) && count(array_filter($evidence->evidence_data)))
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Datos Adicionales</h3>
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <tbody class="divide-y divide-gray-200">
                                @foreach($evidence->evidence_data as $key => $value)
                                    @if(!empty($value))
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 capitalize whitespace-nowrap">
                                            {{ str_replace('_', ' ', $key) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $value }}
                                        </td>
                                    </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Acciones -->
            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                <a href="{{ route('service-requests.show', $serviceRequest) }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver a la solicitud
                </a>

                @if($evidence->canBeDeleted())
                <form action="{{ route('service-requests.evidences.destroy', [$serviceRequest, $evidence]) }}"
                      method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            onclick="return confirm('¿Está seguro de que desea eliminar esta evidencia? Esta acción no se puede deshacer.')"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <i class="fas fa-trash mr-2"></i>
                        Eliminar evidencia
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
