@props(['status'])

@php
$statusColors = [
    'PENDIENTE' => 'bg-yellow-500 text-white',
    'ACEPTADA' => 'bg-blue-500 text-white',
    'EN_PROCESO' => 'bg-purple-500 text-white',
    'PAUSADA' => 'bg-orange-500 text-white',
    'RESUELTA' => 'bg-green-500 text-white',
    'CERRADA' => 'bg-gray-500 text-white',
    'CANCELADA' => 'bg-red-500 text-white'
];

$statusIcons = [
    'PENDIENTE' => 'fa-clock',
    'ACEPTADA' => 'fa-check',
    'EN_PROCESO' => 'fa-cog',
    'PAUSADA' => 'fa-pause',
    'RESUELTA' => 'fa-check-double',
    'CERRADA' => 'fa-lock',
    'CANCELADA' => 'fa-times'
];
@endphp

<span class="px-4 py-2 rounded-full text-sm font-semibold {{ $statusColors[$status] ?? 'bg-gray-500 text-white' }}">
    <i class="fas {{ $statusIcons[$status] ?? 'fa-circle' }} mr-2"></i>
    {{ $status }}
</span>
