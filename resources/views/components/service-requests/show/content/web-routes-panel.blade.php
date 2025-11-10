@props(['serviceRequest'])

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-green-100">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <i class="fas fa-globe text-green-600 mr-3"></i>
            Rutas Web y URLs
        </h3>
    </div>
    <div class="p-6">
        <div class="space-y-4">
            @if($serviceRequest->web_routes && count($serviceRequest->web_routes) > 0)
                @foreach($serviceRequest->web_routes as $index => $route)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-link text-blue-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ $route['name'] ?? 'Ruta ' . ($index + 1) }}</p>
                            <p class="text-sm text-gray-500">{{ $route['url'] ?? 'URL no disponible' }}</p>
                        </div>
                    </div>
                    <a href="{{ $route['url'] ?? '#' }}"
                       target="_blank"
                       class="text-blue-600 hover:text-blue-800 transition duration-150"
                       title="Abrir en nueva pestaña">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
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
    </div>
</div>
