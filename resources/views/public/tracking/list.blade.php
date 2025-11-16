<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Solicitudes - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="max-w-6xl mx-auto mb-8">
            <a href="{{ route('public.tracking.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold mb-4 px-4 py-2 bg-white rounded-lg shadow hover:shadow-md transition-all">
                <i class="fas fa-arrow-left mr-2"></i>
                Nueva búsqueda
            </a>
            <div class="bg-white rounded-lg shadow-lg p-6 sm:p-8">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0 mr-4">
                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-list text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                            Mis Solicitudes de Servicio
                        </h1>
                        <p class="text-gray-600 mt-1">
                            Resultados para: <span class="font-semibold text-blue-600">{{ $query }}</span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <div class="text-sm text-gray-600">
                        <i class="fas fa-info-circle text-blue-600 mr-1"></i>
                        Se encontraron <span class="font-bold text-gray-800">{{ $serviceRequests->total() }}</span> solicitud(es)
                    </div>
                    @if($serviceRequests->total() > 0)
                    <div class="text-sm text-gray-500">
                        Mostrando {{ $serviceRequests->firstItem() }} - {{ $serviceRequests->lastItem() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Lista de Solicitudes -->
        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-1 gap-4">
                @foreach($serviceRequests as $request)
                <div class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all overflow-hidden border-l-4 @if($request->status === 'CERRADA') border-gray-400 @elseif($request->status === 'RESUELTA') border-teal-500 @elseif($request->status === 'EN_PROGRESO') border-purple-500 @elseif($request->status === 'ACEPTADA') border-green-500 @else border-blue-500 @endif">
                    <div class="p-5 sm:p-6">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between mb-4">
                            <div class="flex-1 mb-3 sm:mb-0">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 mr-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center shadow-md">
                                            <i class="fas fa-ticket-alt text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-bold text-gray-800 mb-1 hover:text-blue-600 transition-colors">
                                            <a href="{{ route('public.tracking.show', $request->ticket_number) }}" class="flex items-center">
                                                {{ $request->ticket_number }}
                                                <i class="fas fa-external-link-alt text-xs ml-2 text-gray-400"></i>
                                            </a>
                                        </h3>
                                        <p class="text-gray-600 text-sm line-clamp-2">{{ $request->title }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                @php
                                    $statusConfig = [
                                        'NUEVA' => ['class' => 'bg-blue-100 text-blue-800 border-blue-200', 'icon' => 'fa-star'],
                                        'EN_REVISION' => ['class' => 'bg-yellow-100 text-yellow-800 border-yellow-200', 'icon' => 'fa-search'],
                                        'ACEPTADA' => ['class' => 'bg-green-100 text-green-800 border-green-200', 'icon' => 'fa-check'],
                                        'EN_PROGRESO' => ['class' => 'bg-purple-100 text-purple-800 border-purple-200', 'icon' => 'fa-cog'],
                                        'RESUELTA' => ['class' => 'bg-teal-100 text-teal-800 border-teal-200', 'icon' => 'fa-check-circle'],
                                        'CERRADA' => ['class' => 'bg-gray-100 text-gray-800 border-gray-200', 'icon' => 'fa-lock'],
                                        'RECHAZADA' => ['class' => 'bg-red-100 text-red-800 border-red-200', 'icon' => 'fa-times-circle'],
                                        'PAUSADA' => ['class' => 'bg-orange-100 text-orange-800 border-orange-200', 'icon' => 'fa-pause-circle'],
                                    ];
                                    $config = $statusConfig[$request->status] ?? ['class' => 'bg-gray-100 text-gray-800 border-gray-200', 'icon' => 'fa-question'];
                                @endphp
                                <span class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-bold border {{ $config['class'] }} shadow-sm">
                                    <i class="fas {{ $config['icon'] }} mr-1.5"></i>
                                    {{ str_replace('_', ' ', $request->status) }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm mb-4 bg-gray-50 p-3 rounded-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-2">
                                    <i class="fas fa-calendar text-blue-600 text-xs"></i>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Creada</div>
                                    <div class="font-semibold text-gray-800">{{ $request->created_at->format('d/m/Y') }}</div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-2">
                                    <i class="fas fa-layer-group text-purple-600 text-xs"></i>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Servicio</div>
                                    <div class="font-semibold text-gray-800 truncate">{{ $request->subService->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                @php
                                    $criticalityConfig = [
                                        'BAJA' => ['class' => 'text-green-600 bg-green-100', 'icon' => 'fa-arrow-down'],
                                        'MEDIA' => ['class' => 'text-yellow-600 bg-yellow-100', 'icon' => 'fa-minus'],
                                        'ALTA' => ['class' => 'text-orange-600 bg-orange-100', 'icon' => 'fa-arrow-up'],
                                        'CRITICA' => ['class' => 'text-red-600 bg-red-100', 'icon' => 'fa-exclamation-triangle'],
                                    ];
                                    $critConfig = $criticalityConfig[$request->criticality_level] ?? ['class' => 'text-gray-600 bg-gray-100', 'icon' => 'fa-question'];
                                @endphp
                                <div class="flex-shrink-0 w-8 h-8 {{ $critConfig['class'] }} rounded-full flex items-center justify-center mr-2">
                                    <i class="fas {{ $critConfig['icon'] }} text-xs"></i>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Criticidad</div>
                                    <div class="font-semibold {{ str_replace('bg-', 'text-', explode(' ', $critConfig['class'])[0]) }}">{{ $request->criticality_level }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-3 border-t border-gray-200">
                            <div class="text-xs text-gray-500">
                                <i class="fas fa-clock mr-1"></i>
                                Última actualización: {{ $request->updated_at->diffForHumans() }}
                            </div>
                            <a href="{{ route('public.tracking.show', $request->ticket_number) }}"
                               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium">
                            <a href="{{ route('public.tracking.show', $request->ticket_number) }}"
                               class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white text-sm font-semibold rounded-lg shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
                                <i class="fas fa-eye mr-2"></i>
                                Ver Detalles Completos
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Mensaje si no hay solicitudes -->
            @if($serviceRequests->isEmpty())
            <div class="bg-white rounded-lg shadow-lg p-8 sm:p-12 text-center">
                <div class="mb-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full">
                        <i class="fas fa-search text-gray-400 text-3xl"></i>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">No se encontraron solicitudes</h3>
                <p class="text-gray-600 mb-6">
                    No hay solicitudes registradas con el criterio de búsqueda: <span class="font-semibold">{{ $query }}</span>
                </p>
                <a href="{{ route('public.tracking.index') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Realizar otra búsqueda
                </a>
            </div>
            @endif

            <!-- Paginación -->
            @if($serviceRequests->hasPages())
            <div class="mt-8">
                <div class="bg-white rounded-lg shadow-lg p-4">
                    {{ $serviceRequests->links() }}
                </div>
            </div>
            @endif
        </div>
    </div>
</body>
</html>
