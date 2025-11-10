@extends('layouts.app')

@section('title', "Solicitud $serviceRequest->ticket_number")

@section('breadcrumb')
<x-service-requests.layout.breadcrumb :serviceRequest="$serviceRequest" />
@endsection

@section('content')
<!-- Header Principal -->
<div class="bg-gradient-to-r from-blue-600 to-indigo-700 shadow-xl rounded-2xl overflow-hidden mb-8">
    <div class="px-8 py-6 text-white">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-4 mb-4 lg:mb-0">
                <div class="bg-white/20 p-3 rounded-2xl backdrop-blur-sm">
                    <i class="fas fa-ticket-alt text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">{{ $serviceRequest->title }}</h1>
                    <div class="flex flex-wrap items-center gap-4 mt-2 text-blue-100">
                        <span class="flex items-center">
                            <i class="fas fa-hashtag mr-2"></i>
                            {{ $serviceRequest->ticket_number }}
                        </span>
                        <span class="flex items-center">
                            <i class="fas fa-calendar mr-2"></i>
                            {{ $serviceRequest->created_at->format('d/m/Y H:i') }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 bg-white/20 rounded-full text-sm font-medium">
                            <i class="fas fa-circle mr-2 text-xs"></i>
                            {{ $serviceRequest->status }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                @if($serviceRequest->isOverdue())
                <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                    <i class="fas fa-exclamation-triangle mr-1"></i>VENCIDA
                </span>
                @endif
                <span class="bg-white/10 px-4 py-2 rounded-full text-sm font-medium">
                    <i class="fas fa-flag mr-2"></i>
                    {{ $serviceRequest->criticality_level }}
                </span>
            </div>
        </div>
    </div>
</div>

<div class="space-y-6">
    <!-- Fila 1: Información Esencial -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Tarjeta 1: Información del Servicio -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-blue-100">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-cogs text-blue-600 mr-3"></i>
                    Información del Servicio
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-layer-group text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Servicio</p>
                            <p class="font-semibold text-gray-800">
                                {{ $serviceRequest->subService->service->name ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-puzzle-piece text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Sub-servicio</p>
                            <p class="font-semibold text-gray-800">
                                {{ $serviceRequest->subService->name ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                    @if($serviceRequest->sla)
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shield-alt text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">SLA Aplicado</p>
                            <p class="font-semibold text-gray-800">{{ $serviceRequest->sla->name }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tarjeta 2: Asignación -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-purple-100">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-users text-purple-600 mr-3"></i>
                    Asignación
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <!-- Asignado -->
                    <div>
                        <p class="text-sm text-gray-500 mb-2">Técnico Asignado</p>
                        @if($serviceRequest->assignee)
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                {{ substr($serviceRequest->assignee->name, 0, 1) }}
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800">{{ $serviceRequest->assignee->name }}</p>
                                <p class="text-sm text-gray-600">{{ $serviceRequest->assignee->email }}</p>
                            </div>
                        </div>
                        @else
                        <div class="text-center p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <i class="fas fa-user-slash text-yellow-500 text-xl mb-2"></i>
                            <p class="text-yellow-700 font-medium">No asignado</p>
                        </div>
                        @endif
                    </div>

                    <!-- Solicitante -->
                    <div>
                        <p class="text-sm text-gray-500 mb-2">Solicitante</p>
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold">
                                {{ substr($serviceRequest->requester->name, 0, 1) }}
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800">{{ $serviceRequest->requester->name }}</p>
                                <p class="text-sm text-gray-600">{{ $serviceRequest->requester->email }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta 3: Tiempos y Fechas -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-green-100">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-clock text-green-600 mr-3"></i>
                    Tiempos y Fechas
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <!-- Tiempo transcurrido -->
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <div class="text-2xl font-bold text-gray-800">{{ $serviceRequest->created_at->diffForHumans() }}</div>
                        <div class="text-sm text-gray-500 mt-1">Tiempo transcurrido</div>
                    </div>

                    <!-- Fechas límite -->
                    <div class="space-y-3">
                        @if($serviceRequest->resolution_deadline)
                        <div class="flex justify-between items-center p-3 bg-white border rounded-lg {{ $serviceRequest->resolution_deadline->isPast() ? 'border-red-200 bg-red-50' : 'border-green-200 bg-green-50' }}">
                            <span class="text-sm {{ $serviceRequest->resolution_deadline->isPast() ? 'text-red-700' : 'text-green-700' }}">Resolución</span>
                            <span class="text-sm font-semibold {{ $serviceRequest->resolution_deadline->isPast() ? 'text-red-800' : 'text-green-800' }}">
                                {{ $serviceRequest->resolution_deadline->format('d/m/Y H:i') }}
                            </span>
                        </div>
                        @endif

                        @if($serviceRequest->response_deadline)
                        <div class="flex justify-between items-center p-3 bg-white border border-blue-200 bg-blue-50 rounded-lg">
                            <span class="text-sm text-blue-700">Respuesta</span>
                            <span class="text-sm font-semibold text-blue-800">
                                {{ $serviceRequest->response_deadline->format('d/m/Y H:i') }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fila 2: Descripción y Rutas Web -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Descripción -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-orange-50 to-amber-50 px-6 py-4 border-b border-orange-100">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-align-left text-orange-600 mr-3"></i>
                    Descripción
                </h3>
            </div>
            <div class="p-6">
                <div class="prose max-w-none">
                    <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $serviceRequest->description }}</p>
                </div>
            </div>
        </div>

        <!-- Rutas Web y Acciones -->
        <div class="space-y-6">
            <!-- Rutas Web -->
            @if($serviceRequest->web_routes && count($serviceRequest->web_routes) > 0)
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-50 to-blue-50 px-6 py-4 border-b border-indigo-100">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center">
                        <i class="fas fa-globe text-indigo-600 mr-3"></i>
                        Rutas Web
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-2">
                        @foreach($serviceRequest->web_routes as $route)
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <i class="fas fa-link text-indigo-500"></i>
                            <a href="{{ $route }}" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-800 truncate flex-1">
                                {{ $route }}
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Acciones Rápidas -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center">
                        <i class="fas fa-bolt text-gray-600 mr-3"></i>
                        Acciones
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <a href="{{ route('service-requests.index') }}"
                            class="w-full flex items-center justify-center space-x-2 bg-gray-100 text-gray-700 px-4 py-3 rounded-lg hover:bg-gray-200 transition duration-200 font-semibold">
                            <i class="fas fa-arrow-left"></i>
                            <span>Volver al listado</span>
                        </a>

                        @if($serviceRequest->status !== 'CERRADA')
                        <a href="{{ route('service-requests.edit', $serviceRequest) }}"
                            class="w-full flex items-center justify-center space-x-2 bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-semibold">
                            <i class="fas fa-edit"></i>
                            <span>Editar Solicitud</span>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fila 3: Evidencias -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-green-100">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <i class="fas fa-paperclip text-green-600 mr-3"></i>
                Evidencias ({{ $serviceRequest->evidences->count() }})
            </h3>
        </div>
        <div class="p-6">
            @if($serviceRequest->evidences && $serviceRequest->evidences->count() > 0)
            <!-- Mostrar evidencias con previsualización -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($serviceRequest->evidences as $evidence)
                @php
                $fileUrl = $evidence->getFileUrl();
                $hasFile = $evidence->hasFile();
                $isImage = $evidence->isImage();
                $isPdf = $evidence->isPdf();
                $isDocument = $evidence->isDocument();
                @endphp

                <div class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow duration-200">
                    <!-- Previsualización -->
                    <div class="bg-gray-50 h-48 flex items-center justify-center relative overflow-hidden">
                        @if($hasFile && $fileUrl && $isImage)
                        <!-- Contenedor de imagen simple y funcional -->
                        <div class="w-full h-full relative group">
                            <img
                                src="{{ $fileUrl }}"
                                alt="Evidencia {{ $loop->iteration }}"
                                class="w-full h-full object-cover cursor-pointer"
                                onclick="openPreview('{{ $fileUrl }}')">

                            <!-- Overlay corregido - versión funcional -->
                            <div class="absolute inset-0 bg-black opacity-0 group-hover:opacity-20 transition-opacity duration-200 flex items-center justify-center cursor-pointer"
                                onclick="openPreview('{{ $fileUrl }}')">
                                <i class="fas fa-search-plus text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200 text-2xl"></i>
                            </div>
                        </div>

                        @elseif($hasFile && $fileUrl && $isPdf)
                        <div class="text-center p-4">
                            <i class="fas fa-file-pdf text-red-600 text-5xl mb-3"></i>
                            <p class="text-sm text-gray-600">Documento PDF</p>
                            <button
                                onclick="openPdfPreview('{{ $fileUrl }}')"
                                class="mt-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition-colors">
                                Previsualizar
                            </button>
                        </div>

                        @elseif($hasFile && $fileUrl && $isDocument)
                        <div class="text-center p-4">
                            <i class="fas fa-file-word text-blue-600 text-5xl mb-3"></i>
                            <p class="text-sm text-gray-600">Documento</p>
                            <a href="{{ $fileUrl }}"
                                target="_blank"
                                class="mt-2 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors">
                                Descargar
                            </a>
                        </div>

                        @elseif($hasFile && $fileUrl)
                        <div class="text-center p-4">
                            <i class="fas fa-file text-gray-600 text-5xl mb-3"></i>
                            <p class="text-sm text-gray-600">Archivo</p>
                            <a href="{{ $fileUrl }}"
                                target="_blank"
                                class="mt-2 inline-block px-4 py-2 bg-gray-600 text-white rounded-lg text-sm hover:bg-gray-700 transition-colors">
                                Descargar
                            </a>
                        </div>

                        @else
                        <div class="text-center p-4">
                            <i class="fas fa-sticky-note text-gray-400 text-4xl mb-3"></i>
                            <p class="text-sm text-gray-500">Evidencia sin archivo</p>
                            @if($evidence->description)
                            <p class="text-xs text-gray-400 mt-2 line-clamp-2">
                                {{ \Illuminate\Support\Str::limit($evidence->description, 50) }}
                            </p>
                            @endif
                        </div>
                        @endif
                    </div>

                    <!-- Información de la evidencia -->
                    <div class="p-4">
                        <p class="text-sm font-medium text-gray-900 truncate" title="{{ $evidence->title ?? $evidence->file_original_name }}">
                            {{ $evidence->title ?? $evidence->file_original_name }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $evidence->created_at->format('d/m/Y H:i') }}
                        </p>
                        @if($evidence->description)
                        <p class="text-xs text-gray-600 mt-2 line-clamp-2">
                            {{ $evidence->description }}
                        </p>
                        @endif
                        @if($evidence->user)
                        <p class="text-xs text-gray-400 mt-1">
                            Subido por: {{ $evidence->user->name }}
                        </p>
                        @endif
                        @if($evidence->file_size)
                        <p class="text-xs text-gray-500 mt-1">
                            Tamaño: {{ $evidence->formatted_file_size }}
                        </p>
                        @endif
                        @if($evidence->evidence_type !== 'SISTEMA')
                        <span class="inline-block mt-2 px-2 py-1 text-xs rounded
                                @if($evidence->evidence_type === 'PASO_A_PASO') bg-blue-100 text-blue-800
                                @elseif($evidence->evidence_type === 'ARCHIVO') bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-800 @endif">
                            {{ $evidence->evidence_type }}
                        </span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Modal para previsualización de imágenes -->
            <div id="imagePreviewModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50 flex items-center justify-center p-4">
                <div class="relative max-w-4xl max-h-full">
                    <button onclick="closePreview()" class="absolute -top-12 right-0 text-white hover:text-gray-300 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                    <img id="previewImage" src="" alt="Previsualización" class="max-w-full max-h-full object-contain">
                </div>
            </div>

            <!-- Modal para previsualización de PDF -->
            <div id="pdfPreviewModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-lg w-full max-w-4xl h-5/6 flex flex-col">
                    <div class="flex justify-between items-center p-4 border-b">
                        <h3 class="text-lg font-semibold">Previsualización PDF</h3>
                        <button onclick="closePdfPreview()" class="text-gray-500 hover:text-gray-700 text-xl">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="flex-1">
                        <iframe id="pdfPreviewFrame" src="" class="w-full h-full" frameborder="0"></iframe>
                    </div>
                </div>
            </div>

            <script>
                function openPreview(imageUrl) {
                    console.log('Abriendo preview:', imageUrl);
                    document.getElementById('previewImage').src = imageUrl;
                    document.getElementById('imagePreviewModal').classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }

                function closePreview() {
                    document.getElementById('imagePreviewModal').classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }

                function openPdfPreview(pdfUrl) {
                    console.log('Abriendo PDF preview:', pdfUrl);
                    document.getElementById('pdfPreviewFrame').src = pdfUrl + '#view=FitH';
                    document.getElementById('pdfPreviewModal').classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }

                function closePdfPreview() {
                    document.getElementById('pdfPreviewFrame').src = '';
                    document.getElementById('pdfPreviewModal').classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }

                // Cerrar modales con ESC
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closePreview();
                        closePdfPreview();
                    }
                });

                // Cerrar al hacer click fuera
                document.getElementById('imagePreviewModal')?.addEventListener('click', function(e) {
                    if (e.target === this) closePreview();
                });
                document.getElementById('pdfPreviewModal')?.addEventListener('click', function(e) {
                    if (e.target === this) closePdfPreview();
                });
            </script>

            @else
            <div class="text-center py-8">
                <i class="fas fa-folder-open text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-500 text-lg">No hay evidencias disponibles</p>
                <p class="text-gray-400 text-sm mt-2">Las evidencias se mostrarán aquí cuando sean agregadas</p>
            </div>
            @endif
        </div>
    </div>
    <!-- Fila 4: Historial -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-purple-100">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <i class="fas fa-history text-purple-600 mr-3"></i>
                Historial y Línea de Tiempo
            </h3>
        </div>
        <div class="p-6">
            <x-service-requests.display.history-timeline :serviceRequest="$serviceRequest" />
        </div>
    </div>
</div>

<!-- INCLUIR TODOS LOS MODALES -->
<x-service-requests.modals.all :serviceRequest="$serviceRequest" />
@endsection

@section('scripts')
<x-service-requests.layout.scripts
    :serviceRequest="$serviceRequest"
    :webRoutes="true"
    :slaManagement="true"
    :formValidation="false" />

<style>
    .prose {
        max-width: none;
    }

    .prose p {
        margin-bottom: 0;
        line-height: 1.6;
    }

    .bg-gradient-to-r {
        background: linear-gradient(to right, var(--tw-gradient-from), var(--tw-gradient-to));
    }

    .transition {
        transition: all 0.2s ease-in-out;
    }
</style>
@endsection
