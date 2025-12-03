{{-- resources/views/components/service-requests/show/header/status-badge.blade.php --}}
@props(['status'])

@php
    $statusConfig = [
        'PENDIENTE' => [
            'color' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
            'icon' => 'clock',
            'text' => 'Pendiente'
        ],
        'ACEPTADA' => [
            'color' => 'bg-blue-100 text-blue-800 border-blue-300',
            'icon' => 'user-check',
            'text' => 'Aceptada'
        ],
        'EN_PROCESO' => [
            'color' => 'bg-purple-100 text-purple-800 border-purple-300',
            'icon' => 'cog',
            'text' => 'En Proceso'
        ],
        'PAUSADA' => [
            'color' => 'bg-orange-100 text-orange-800 border-orange-300',
            'icon' => 'pause',
            'text' => 'Pausada'
        ],
        'RESUELTA' => [
            'color' => 'bg-green-100 text-green-800 border-green-300',
            'icon' => 'check-double',
            'text' => 'Resuelta'
        ],
        'CERRADA' => [
            'color' => 'bg-gray-100 text-gray-800 border-gray-300',
            'icon' => 'lock',
            'text' => 'Cerrada'
        ],
        'RECHAZADA' => [
            'color' => 'bg-gray-100 text-gray-800 border-gray-300',
            'icon' => 'lock',
            'text' => 'Rechazada'
        ]
    ];

    $config = $statusConfig[$status] ?? $statusConfig['PENDIENTE'];
@endphp

<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold border-2 {{ $config['color'] }}">
    <i class="fas fa-{{ $config['icon'] }} mr-2"></i>
    {{ $config['text'] }}
</span>
