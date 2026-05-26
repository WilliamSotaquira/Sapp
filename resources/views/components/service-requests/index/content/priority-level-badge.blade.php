@props(['level', 'score' => null, 'compact' => false])

@php
    $levelConfig = [
        'P0' => [
            'class' => 'bg-red-600 text-white',
            'icon' => 'fa-fire',
            'label' => 'Atender hoy',
        ],
        'P1' => [
            'class' => 'bg-orange-500 text-white',
            'icon' => 'fa-bolt',
            'label' => 'Atender 24-48h',
        ],
        'P2' => [
            'class' => 'bg-yellow-500 text-white',
            'icon' => 'fa-calendar-week',
            'label' => 'Programar esta semana',
        ],
        'P3' => [
            'class' => 'bg-blue-500 text-white',
            'icon' => 'fa-list',
            'label' => 'Cola operativa',
        ],
        'P4' => [
            'class' => 'bg-gray-400 text-white',
            'icon' => 'fa-archive',
            'label' => 'Archivar o validar',
        ],
    ];

    $config = $levelConfig[$level] ?? $levelConfig['P3'];
    $sizeClass = $compact ? 'px-2 py-0.5 text-[10px]' : 'px-2.5 py-1 text-xs';
@endphp

<span class="{{ $sizeClass }} inline-flex items-center gap-1 font-bold rounded-full {{ $config['class'] }}"
      title="{{ $config['label'] }}{{ $score !== null ? ' (Score: ' . $score . ')' : '' }}">
    <i class="fas {{ $config['icon'] }}"></i>
    {{ $level }}
</span>
