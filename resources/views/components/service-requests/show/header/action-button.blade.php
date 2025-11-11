{{-- resources/views/components/service-requests/show/header/action-button.blade.php --}}
@props([
    'route' => null,
    'color' => 'blue',
    'icon' => 'cog',
    'method' => 'POST',
    'confirm' => null,
    'compact' => false,
    'onclick' => null
])

@php
    $colorClasses = [
        'emerald' => 'bg-emerald-500 hover:bg-emerald-600 border-emerald-400 focus:ring-emerald-300',
        'red' => 'bg-red-500 hover:bg-red-600 border-red-400 focus:ring-red-300',
        'cyan' => 'bg-cyan-500 hover:bg-cyan-600 border-cyan-400 focus:ring-cyan-300',
        'blue' => 'bg-blue-500 hover:bg-blue-600 border-blue-400 focus:ring-blue-300',
        'yellow' => 'bg-yellow-500 hover:bg-yellow-600 border-yellow-400 focus:ring-yellow-300',
        'green' => 'bg-green-500 hover:bg-green-600 border-green-400 focus:ring-green-300',
        'orange' => 'bg-orange-500 hover:bg-orange-600 border-orange-400 focus:ring-orange-300',
        'purple' => 'bg-purple-500 hover:bg-purple-600 border-purple-400 focus:ring-purple-300',
    ][$color] ?? 'bg-blue-500 hover:bg-blue-600 border-blue-400 focus:ring-blue-300';

    $compactClass = $compact ? 'w-full justify-center' : '';
@endphp

@if($onclick)
    {{-- Botón con onclick personalizado --}}
    <button type="button"
            class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold text-white transition-all duration-200 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 shadow-sm border-2 {{ $colorClasses }} {{ $compactClass }} {{ $attributes->get('class') }}"
            onclick="{{ $onclick }}"
            {{ $attributes->except('class') }}>
        <i class="fas fa-{{ $icon }} mr-2"></i>
        {{ $slot }}
    </button>
@elseif($method === 'GET')
    {{-- Enlace GET --}}
    <a href="{{ $route }}"
       class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold text-white transition-all duration-200 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 shadow-sm border-2 {{ $colorClasses }} {{ $compactClass }} {{ $attributes->get('class') }}"
       @if($confirm) onclick="return confirm('{{ $confirm }}')" @endif
       {{ $attributes->except('class') }}>
        <i class="fas fa-{{ $icon }} mr-2"></i>
        {{ $slot }}
    </a>
@else
    {{-- Formulario POST/PUT/PATCH/DELETE --}}
    <form action="{{ $route }}" method="POST" class="inline">
        @csrf
        @if($method !== 'POST')
            @method($method)
        @endif
        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold text-white transition-all duration-200 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 shadow-sm border-2 {{ $colorClasses }} {{ $compactClass }} {{ $attributes->get('class') }}"
                @if($confirm) onclick="return confirm('{{ $confirm }}')" @endif
                {{ $attributes->except('class') }}>
            <i class="fas fa-{{ $icon }} mr-2"></i>
            {{ $slot }}
        </button>
    </form>
@endif
