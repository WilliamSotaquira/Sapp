@props(['request'])

@php
    $viewService = app(\App\Services\ServiceRequestViewService::class);
    $routes = $viewService->getWebRoutesData($request);
@endphp

<!-- Rutas Web -->
<div class="p-6 border-b">
    <h3 class="text-lg font-semibold mb-4">Rutas Web</h3>
    @if(!empty($routes))
    <div class="space-y-3">
        @foreach($routes as $route)
        <div class="flex items-center justify-between p-3 {{ $route['type'] === 'main' ? 'bg-blue-50' : 'bg-gray-50' }} rounded-lg">
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
    <div class="text-center py-4 text-gray-500">
        <i class="fas fa-link text-2xl mb-2"></i>
        <p>No hay rutas web configuradas</p>
    </div>
    @endif
</div>
