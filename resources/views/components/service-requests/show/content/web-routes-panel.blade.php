@props(['serviceRequest'])

@php
    $webRoutes = [];
    $rawWebRoutes = $serviceRequest->web_routes;

    if (is_array($rawWebRoutes) && count($rawWebRoutes) > 0) {
        $webRoutes = $rawWebRoutes;
    } elseif (is_string($rawWebRoutes) && !empty(trim($rawWebRoutes))) {
        $decoded = json_decode($rawWebRoutes, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $webRoutes = $decoded;
        } else {
            $webRoutes = [['url' => trim($rawWebRoutes), 'name' => 'URL Principal']];
        }
    }
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
            <i class="fas fa-globe text-green-500" aria-hidden="true"></i>
            Rutas Web
            <span class="text-xs font-normal text-gray-400">({{ count($webRoutes) }})</span>
        </h3>
    </div>
    <div class="px-4 sm:px-5 py-3">
        @if (count($webRoutes) > 0)
            <div class="space-y-1.5" id="webRoutesContainer">
                @foreach ($webRoutes as $index => $route)
                    @php
                        $url = null;

                        if (is_string($route)) {
                            $url = $route;
                        } elseif (is_array($route)) {
                            $url = $route['url'] ?? ($route['route'] ?? ($route['path'] ?? null));
                        }

                        if ($url && !empty(trim($url))) {
                            $cleanUrl = trim($url);
                            $formattedUrl = preg_match('/^https?:\/\//', $cleanUrl) ? $cleanUrl : 'https://' . $cleanUrl;
                            $isValidUrl = filter_var($formattedUrl, FILTER_VALIDATE_URL);
                        } else {
                            $formattedUrl = null;
                            $isValidUrl = false;
                        }

                        $displayUrl = $url ?: 'URL no disponible';
                    @endphp

                    <div class="sr-route-item {{ $index >= 3 ? 'sr-web-route-extra hidden' : '' }}">
                        @if ($isValidUrl)
                            <a href="{{ $formattedUrl }}" target="_blank" rel="noopener noreferrer"
                               class="sr-route-link group"
                               title="{{ $formattedUrl }}">
                                <i class="fas fa-external-link-alt sr-route-icon" aria-hidden="true"></i>
                                <span class="sr-route-url">{{ $formattedUrl }}</span>
                            </a>
                        @else
                            <span class="sr-route-link sr-route-link--disabled">
                                <i class="fas fa-link sr-route-icon" aria-hidden="true"></i>
                                <span class="sr-route-url">{{ $displayUrl }}</span>
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>

            @if (count($webRoutes) > 3)
                <button type="button"
                        class="mt-2 text-xs text-blue-600 hover:text-blue-800 font-medium"
                        onclick="this.previousElementSibling.querySelectorAll('.sr-web-route-extra').forEach(function(el){el.classList.toggle('hidden')}); this.textContent = this.textContent.includes('más') ? 'Ver menos' : 'Ver {{ count($webRoutes) - 3 }} más';">
                    Ver {{ count($webRoutes) - 3 }} más
                </button>
            @endif
        @else
            <p class="text-xs text-gray-400 text-center py-2">No hay rutas web asociadas</p>
        @endif
    </div>
</div>

@once
@push('styles')
<style>
.sr-route-item {
    border-radius: 6px;
}

.sr-route-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    border-radius: 6px;
    text-decoration: none;
    transition: background 0.15s ease;
    min-width: 0;
}

.sr-route-link:hover {
    background: #f0f9ff;
}

.sr-route-link--disabled {
    opacity: 0.5;
    cursor: default;
}

.sr-route-link--disabled:hover {
    background: none;
}

.sr-route-icon {
    flex-shrink: 0;
    font-size: 0.65rem;
    color: #94a3b8;
    transition: color 0.15s ease;
}

.sr-route-link:hover .sr-route-icon {
    color: #3b82f6;
}

.sr-route-url {
    font-size: 0.8rem;
    color: #2563eb;
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}

.sr-route-link:hover .sr-route-url {
    text-decoration: underline;
    color: #1d4ed8;
}

.sr-route-link--disabled .sr-route-url {
    color: #64748b;
}

/* Responsive: wrap on small screens */
@media (max-width: 640px) {
    .sr-route-url {
        font-size: 0.72rem;
        white-space: normal;
        word-break: break-all;
        line-height: 1.4;
    }

    .sr-route-link {
        padding: 8px 10px;
        align-items: flex-start;
    }

    .sr-route-icon {
        margin-top: 3px;
    }
}
</style>
@endpush
@endonce
