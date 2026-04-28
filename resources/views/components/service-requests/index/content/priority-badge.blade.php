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

<span class="{{ $sizeClass }} inline-flex items-center gap-1 font-semibold rounded-full {{ $config['class'] }}"
      title="Prioridad: {{ ucfirst(strtolower($priority)) }}">
    <i class="fas {{ $config['icon'] }} text-[10px]"></i>
    {{ $priority }}
</span>
