@props([
    /**
     * auto: por defecto usa wordmark (barra superior),
     * icon: usa el ícono cuadrado,
     * wordmark: usa el logo horizontal.
     */
    'variant' => 'auto',
])

@php
    $resolvedVariant = $variant === 'auto' ? 'wordmark' : $variant;
    $src = $resolvedVariant === 'icon'
    ? asset('logo_sapp_xs.png')
    : asset('sapp_logo_lg.png');
@endphp

<img
    src="{{ $src }}"
    alt="{{ config('app.name', 'SAPP') }}"
    {{ $attributes->merge(['class' => 'object-contain rounded-md']) }}
/>
