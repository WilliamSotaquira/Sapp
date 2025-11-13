@props(['request', 'compact' => false])

<div class="flex items-center gap-1">
    <a href="{{ route('service-requests.show', $request) }}"
       class="text-blue-600 hover:text-blue-800 p-1 rounded transition-colors duration-150"
       title="Ver detalles"
       aria-label="Ver detalles de la solicitud {{ $request->ticket_number }}">
        <i class="fas fa-eye {{ $compact ? 'text-xs' : 'text-sm' }}"></i>
    </a>

    @if($request->status === 'PENDIENTE')
        <button class="text-green-600 hover:text-green-800 p-1 rounded transition-colors duration-150"
                title="Aceptar solicitud"
                aria-label="Aceptar solicitud {{ $request->ticket_number }}">
            <i class="fas fa-check {{ $compact ? 'text-xs' : 'text-sm' }}"></i>
        </button>
    @endif

    @if(in_array($request->status, ['ACEPTADA', 'EN_PROCESO']))
        <button class="text-purple-600 hover:text-purple-800 p-1 rounded transition-colors duration-150"
                title="Marcar en progreso"
                aria-label="Marcar en progreso solicitud {{ $request->ticket_number }}">
            <i class="fas fa-cog {{ $compact ? 'text-xs' : 'text-sm' }}"></i>
        </button>
    @endif

    <button class="text-gray-500 hover:text-gray-700 p-1 rounded transition-colors duration-150"
            title="Más opciones"
            aria-label="Más opciones para solicitud {{ $request->ticket_number }}">
        <i class="fas fa-ellipsis-h {{ $compact ? 'text-xs' : 'text-sm' }}"></i>
    </button>
</div>
