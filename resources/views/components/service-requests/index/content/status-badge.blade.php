@props(['status', 'compact' => false])

@php
    $statusConfig = [
        'PENDIENTE' => [
            'class' => 'bg-yellow-100 text-yellow-800',
            'icon' => 'fa-clock'
        ],
        'ACEPTADA' => [
            'class' => 'bg-blue-100 text-blue-800',
            'icon' => 'fa-check-circle'
        ],
        'EN_PROCESO' => [
            'class' => 'bg-purple-100 text-purple-800',
            'icon' => 'fa-cog'
        ],
        'PAUSADA' => [
            'class' => 'bg-orange-100 text-orange-800',
            'icon' => 'fa-pause-circle'
        ],
        'RESUELTA' => [
            'class' => 'bg-green-100 text-green-800',
            'icon' => 'fa-check-double'
        ],
        'CERRADA' => [
            'class' => 'bg-gray-100 text-gray-800',
            'icon' => 'fa-lock'
        ],
        'CANCELADA' => [
            'class' => 'bg-red-100 text-red-800',
            'icon' => 'fa-times-circle'
        ]
    ];

    $config = $statusConfig[$status] ?? $statusConfig['PENDIENTE'];
    $sizeClass = $compact ? 'px-2 py-1 text-xs' : 'px-3 py-1 text-sm';
@endphp

<span class="{{ $sizeClass }} font-medium rounded-full {{ $config['class'] }}">
    <i class="fas {{ $config['icon'] }} mr-1 text-xs"></i>
    {{ $status }}
</span>
