{{-- resources/views/components/service-requests/show/header/action-button.blade.php --}}
@props([
    'route' => null,
    'color' => 'blue',
    'icon' => 'cog',
    'method' => 'POST',
    'confirm' => null,
    'compact' => false,
    'onclick' => null,
    'modal_id' => null  // 👈 NUEVA PROPIEDAD PARA MODALES
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

@if($method === 'MODAL')
    {{-- 👈 NUEVO: Botón que abre modal --}}
    <button type="button"
            class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold text-white transition-all duration-200 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 shadow-sm border-2 {{ $colorClasses }} {{ $compactClass }} {{ $attributes->get('class') }}"
            onclick="openModal('{{ $modal_id }}')"
            {{ $attributes->except('class') }}>
        <i class="fas fa-{{ $icon }} mr-2"></i>
        {{ $slot }}
    </button>
@elseif($onclick)
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
       @if($confirm) onclick="return handleConfirmation('{{ $confirm }}', event)" @endif
       {{ $attributes->except('class') }}>
        <i class="fas fa-{{ $icon }} mr-2"></i>
        {{ $slot }}
    </a>
@else
    {{-- Formulario POST/PUT/PATCH/DELETE --}}
    <form action="{{ $route }}" method="POST" class="inline"
          @if($confirm) onsubmit="return handleConfirmation('{{ $confirm }}', event)" @endif
          id="form-{{ \Illuminate\Support\Str::random(8) }}">
        @csrf
        @if($method !== 'POST')
            @method($method)
        @endif
        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold text-white transition-all duration-200 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 shadow-sm border-2 {{ $colorClasses }} {{ $compactClass }} {{ $attributes->get('class') }}"
                {{ $attributes->except('class') }}>
            <i class="fas fa-{{ $icon }} mr-2"></i>
            {{ $slot }}
        </button>
    </form>
@endif

{{-- Script global para manejar confirmaciones y modales --}}
<script>
function handleConfirmation(message, event) {
    console.log('🔍 Confirmación solicitada:', message);
    const result = confirm(message);
    console.log('✅ Usuario respondió:', result ? 'Aceptar' : 'Cancelar');

    if (!result) {
        event.preventDefault();
        event.stopPropagation();
        console.log('❌ Acción cancelada por el usuario');
    }

    return result;
}

// 👈 NUEVO: Función para abrir modales
function openModal(modalId) {
    console.log('🔍 Abriendo modal:', modalId);
    const modal = document.getElementById(modalId);

    if (modal) {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        console.log('✅ Modal abierto correctamente');
    } else {
        console.error('❌ Modal no encontrado:', modalId);
    }
}

// Debug para confirmaciones y modales
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Action Button Component - Confirmaciones y modales cargados');

    // Encontrar todos los forms con confirmación
    const formsWithConfirm = document.querySelectorAll('form[onsubmit*="handleConfirmation"]');
    console.log('📋 Forms con confirmación:', formsWithConfirm.length);

    // Encontrar todos los botones de modal
    const modalButtons = document.querySelectorAll('button[onclick*="openModal"]');
    console.log('📋 Botones de modal:', modalButtons.length);

    formsWithConfirm.forEach((form, index) => {
        form.addEventListener('submit', function(e) {
            console.log(`🟢 Form ${index} con confirmación - Enviado`);
        });
    });
});
</script>
