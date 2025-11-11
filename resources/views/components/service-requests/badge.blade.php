<!-- resources/views/components/service-requests/badge.blade.php -->
@props([
    'type' => 'status',
    'value',
    'class' => '',
])

@php
    // Configuración de estados
    $statusConfig = [
        'PENDIENTE' => [
            'color' => 'bg-yellow-500 text-white',
            'icon' => 'fa-clock',
            'label' => 'Pendiente',
        ],
        'ACEPTADA' => [
            'color' => 'bg-blue-500 text-white',
            'icon' => 'fa-check',
            'label' => 'Aceptada',
        ],
        'EN_PROCESO' => [
            'color' => 'bg-purple-500 text-white',
            'icon' => 'fa-cog',
            'label' => 'En Proceso',
        ],
        'PAUSADA' => [
            'color' => 'bg-orange-500 text-white',
            'icon' => 'fa-pause',
            'label' => 'Pausada',
        ],
        'RESUELTA' => [
            'color' => 'bg-green-500 text-white',
            'icon' => 'fa-check-double',
            'label' => 'Resuelta',
        ],
        'CERRADA' => [
            'color' => 'bg-gray-500 text-white',
            'icon' => 'fa-lock',
            'label' => 'Cerrada',
        ],
        'CANCELADA' => [
            'color' => 'bg-red-500 text-white',
            'icon' => 'fa-times',
            'label' => 'Cancelada',
        ],
    ];

    // Configuración de criticidad
    $criticalityConfig = [
        'BAJA' => [
            'color' => 'bg-green-500 text-white',
            'icon' => 'fa-flag',
            'label' => 'Baja',
        ],
        'MEDIA' => [
            'color' => 'bg-yellow-500 text-white',
            'icon' => 'fa-flag',
            'label' => 'Media',
        ],
        'ALTA' => [
            'color' => 'bg-orange-500 text-white',
            'icon' => 'fa-exclamation-triangle',
            'label' => 'Alta',
        ],
        'CRITICA' => [
            'color' => 'bg-red-500 text-white',
            'icon' => 'fa-skull-crossbones',
            'label' => 'Crítica',
        ],
    ];

    // Seleccionar configuración según el tipo
    $config = $type === 'status' ? $statusConfig : $criticalityConfig;

    // Obtener item o valores por defecto
    $item = $config[$value] ?? [
        'color' => 'bg-gray-500 text-white',
        'icon' => 'fa-circle',
        'label' => $value,
    ];
@endphp

<span class="px-4 py-2 rounded-full text-sm font-semibold {{ $item['color'] }} {{ $class }}">
    <i class="fas {{ $item['icon'] }} mr-2"></i>
    {{ $item['label'] }}
</span>
