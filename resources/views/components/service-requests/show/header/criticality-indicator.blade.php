{{-- resources/views/components/service-requests/show/header/criticality-indicator.blade.php --}}
@props(['criticality'])

@php
    $criticalityConfig = [
        'BAJA' => [
            'color' => 'bg-green-100 text-green-800 border-green-300',
            'icon' => 'arrow-down',
            'text' => 'Baja'
        ],
        'MEDIA' => [
            'color' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
            'icon' => 'minus',
            'text' => 'Media'
        ],
        'ALTA' => [
            'color' => 'bg-orange-100 text-orange-800 border-orange-300',
            'icon' => 'exclamation-triangle',
            'text' => 'Alta'
        ],
        'CRITICA' => [
            'color' => 'bg-red-100 text-red-800 border-red-300',
            'icon' => 'skull-crossbones',
            'text' => 'Crítica'
        ]
    ];

    $config = $criticalityConfig[$criticality] ?? $criticalityConfig['MEDIA'];
@endphp

<span class="inline-flex max-h-7 items-center px-4 py-1 rounded-full text-xs font-semibold border-2 {{ $config['color'] }} leading-none">
    <i class="fas fa-{{ $config['icon'] }} mr-2"></i>
    {{ $config['text'] }}
</span>
