@props(['serviceRequest'])

@php
    $viewService = app(\App\Services\ServiceRequestViewService::class);
    $routes = $viewService->getWebRoutesData($serviceRequest);
@endphp

<!-- Rutas Web -->
<div class="space-y-4">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Rutas Web</h2>

    @if(!empty($routes))
    <div class="space-y-3">
        @foreach($routes as $route)
        <div class="flex items-center justify-between p-3 {{ $route['type'] === 'main' ? 'bg-blue-50' : 'bg-gray-50' }} rounded-lg border">
            <div class="flex items-center">
                <i class="{{ $route['icon'] }} mr-3"></i>
                <div>
                    <span class="font-medium text-sm text-gray-700">
                        {{ $route['description'] }}
                    </span>
                    @if($route['is_valid'])
                    <a href="{{ $route['url'] }}" target="_blank"
                        class="text-blue-600 hover:text-blue-800 block text-sm">
                        {{ $route['url'] }}
                    </a>
                    @else
                    <span class="text-gray-500 text-sm">{{ $route['url'] }}</span>
                    @endif
                </div>
            </div>
            @if($route['is_valid'])
            <a href="{{ $route['url'] }}" target="_blank"
                class="text-blue-600 hover:text-blue-800 ml-4">
                <i class="fas fa-external-link-alt"></i>
            </a>
            @endif
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-8">
        <i class="fas fa-link text-gray-300 text-4xl mb-3"></i>
        <p class="text-gray-500">No hay rutas web configuradas</p>
    </div>
    @endif
</div>
