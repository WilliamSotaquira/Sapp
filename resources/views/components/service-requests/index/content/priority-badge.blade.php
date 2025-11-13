@props(['priority', 'compact' => false])

@php
    $priorityConfig = [
        'BAJA' => [
            'class' => 'bg-green-100 text-green-800',
            'icon' => 'fa-flag'
        ],
        'MEDIA' => [
            'class' => 'bg-yellow-100 text-yellow-800',
            'icon' => 'fa-flag'
        ],
        'ALTA' => [
            'class' => 'bg-orange-100 text-orange-800',
            'icon' => 'fa-exclamation-triangle'
        ],
        'CRITICA' => [
            'class' => 'bg-red-100 text-red-800',
            'icon' => 'fa-skull-crossbones'
        ]
    ];

    $config = $priorityConfig[$priority] ?? $priorityConfig['BAJA'];
    $sizeClass = $compact ? 'px-2 py-1 text-xs' : 'px-3 py-1 text-sm';
@endphp

<span class="{{ $sizeClass }} font-medium rounded-full {{ $config['class'] }}">
    <i class="fas {{ $config['icon'] }} mr-1 text-xs"></i>
    {{ $priority }}
</span>
