@props(['criticality'])

@php
$criticalityColors = [
    'BAJA' => 'bg-green-500 text-white',
    'MEDIA' => 'bg-yellow-500 text-white',
    'ALTA' => 'bg-orange-500 text-white',
    'CRITICA' => 'bg-red-500 text-white'
];

$criticalityIcons = [
    'BAJA' => 'fa-flag',
    'MEDIA' => 'fa-flag',
    'ALTA' => 'fa-exclamation-triangle',
    'CRITICA' => 'fa-skull-crossbones'
];
@endphp

<span class="px-4 py-2 rounded-full text-sm font-semibold {{ $criticalityColors[$criticality] ?? 'bg-gray-500 text-white' }}">
    <i class="fas {{ $criticalityIcons[$criticality] ?? 'fa-flag' }} mr-2"></i>
    {{ $criticality }}
</span>
