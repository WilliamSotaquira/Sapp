@props([
    'title' => '',
    'icon' => '',
    'iconColor' => 'text-gray-500',
    'badge' => null,
    'headerBg' => null,
    'noPadding' => false,
    'id' => null,
    'dead' => false,
])

@php
    $resolvedHeaderBg = $dead
        ? 'bg-gray-100 border-gray-300'
        : ($headerBg ?? 'bg-gray-50 border-gray-200');
@endphp

<div @if($id) id="{{ $id }}" @endif
     {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden']) }}>

    {{-- Header --}}
    <div class="{{ $resolvedHeaderBg }} px-5 py-3 border-b">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-800 flex items-center">
                @if ($icon)
                    <i class="fas {{ $icon }} {{ $dead ? 'text-gray-400' : $iconColor }} mr-2.5" aria-hidden="true"></i>
                @endif
                {{ $title }}
            </h3>
            @if ($badge)
                <span class="text-xs text-gray-500">{{ $badge }}</span>
            @endif
        </div>
    </div>

    {{-- Content --}}
    <div class="{{ $noPadding ? '' : 'p-5' }}">
        {{ $slot }}
    </div>
</div>
