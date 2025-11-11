<!-- resources/views/components/service-requests/show/header/action-button.blade.php -->
@props([
    'route',
    'color' => 'blue',
    'icon',
    'method' => 'POST',
    'confirm' => null,
    'class' => ''
])

@php
    $colorClasses = [
        'emerald' => 'bg-emerald-500 hover:bg-emerald-600 border-emerald-400 focus:ring-emerald-300',
        'cyan' => 'bg-cyan-500 hover:bg-cyan-600 border-cyan-400 focus:ring-cyan-300',
        'teal' => 'bg-teal-500 hover:bg-teal-600 border-teal-400 focus:ring-teal-300',
        'blue' => 'bg-blue-500 hover:bg-blue-600 border-blue-400 focus:ring-blue-300'
    ][$color] ?? 'bg-blue-500 hover:bg-blue-600 border-blue-400 focus:ring-blue-300';
@endphp

<form action="{{ $route }}" method="POST" class="inline">
    @csrf
    @method($method)
    <button type="submit"
            class="{{ $colorClasses }} text-white px-4 py-2 rounded-full text-sm font-semibold transition-all duration-200 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 shadow-sm flex items-center border-2 {{ $class }}"
            @if($confirm) onclick="return confirm('{{ $confirm }}')" @endif>
        <i class="fas fa-{{ $icon }} mr-2"></i>
        {{ $slot }}
    </button>
</form>
