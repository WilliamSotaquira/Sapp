@props(['serviceRequest'])

@php
    // Manejar diferentes formatos de web_routes
    $webRoutes = [];
    $rawWebRoutes = $serviceRequest->web_routes;

    if (is_array($rawWebRoutes) && count($rawWebRoutes) > 0) {
        $webRoutes = $rawWebRoutes;
    } elseif (is_string($rawWebRoutes) && !empty(trim($rawWebRoutes))) {
        // Intentar decodificar como JSON primero
        $decoded = json_decode($rawWebRoutes, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $webRoutes = $decoded;
        } else {
            // Si no es JSON válido, tratar como string simple
            $webRoutes = [['url' => trim($rawWebRoutes), 'name' => 'URL Principal']];
        }
    }
@endphp

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-green-100">
        <h3 class="sr-card-title text-gray-800 flex items-center">
            <i class="fas fa-globe text-green-600 mr-3"></i>
            Rutas Web y URLs
        </h3>
    </div>
    <div class="p-6">
        <div class="space-y-4">
            @if (count($webRoutes) > 0)
                @foreach ($webRoutes as $index => $route)
                    @php
                        // Manejar diferentes formatos de ruta
                        $url = null;
                        $name = 'Ruta ' . ($index + 1);

                        if (is_string($route)) {
                            $url = $route;
                        } elseif (is_array($route)) {
                            $url = $route['url'] ?? ($route['route'] ?? ($route['path'] ?? null));
                            $name = $route['name'] ?? ($route['title'] ?? ($route['label'] ?? $name));
                        }

                        // Formatear URL si es necesario
                        if ($url && !empty(trim($url))) {
                            $cleanUrl = trim($url);
                            if (!preg_match('/^https?:\/\//', $cleanUrl)) {
                                $formattedUrl = 'https://' . $cleanUrl;
                            } else {
                                $formattedUrl = $cleanUrl;
                            }
                            $isValidUrl = filter_var($formattedUrl, FILTER_VALIDATE_URL);
                        } else {
                            $formattedUrl = null;
                            $isValidUrl = false;
                        }

                        $displayUrl = $url ?: 'URL no disponible';
                    @endphp

                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3 flex-1 min-w-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-link text-blue-600 text-sm"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-gray-900 truncate">{{ $name }}</p>
                                <p class="text-sm text-gray-500 truncate">{{ $displayUrl }}</p>
                            </div>
                        </div>

                        @if ($isValidUrl)
                            <a href="{{ $formattedUrl }}" target="_blank" rel="noopener noreferrer"
                                class="flex-shrink-0 text-blue-600 hover:text-blue-800 transition duration-150 px-3 py-2 rounded-lg hover:bg-blue-50 ml-3"
                                title="Abrir en nueva pestaña">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        @elseif($url)
                            <span class="flex-shrink-0 text-gray-400 px-3 py-2 cursor-not-allowed ml-3"
                                title="URL no válida">
                                <i class="fas fa-external-link-alt"></i>
                            </span>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="text-center py-8">
                    <div class="text-gray-400 mb-3">
                        <i class="fas fa-link text-4xl"></i>
                    </div>
                    <p class="text-gray-500">No hay rutas web asociadas a esta solicitud</p>
                </div>
            @endif
        </div>

        {{-- Debug opcional --}}
        @if (false)
            <div class="mt-4 p-3 bg-yellow-50 rounded-lg">
                <p class="text-sm text-yellow-800">
                    <strong>Debug - hasWebRoutes:</strong> {{ $serviceRequest->hasWebRoutes() ? 'TRUE' : 'FALSE' }}<br>
                    <strong>Tipo:</strong> {{ gettype($serviceRequest->web_routes) }}<br>
                    <strong>Valor:</strong> {{ $serviceRequest->web_routes }}<br>
                    <strong>Count:</strong>
                    {{ is_array($serviceRequest->web_routes) ? count($serviceRequest->web_routes) : 'N/A' }}<br>
                    <strong>WebRoutes procesadas:</strong> {{ json_encode($webRoutes) }}
                </p>
            </div>
        @endif
    </div>
</div>
