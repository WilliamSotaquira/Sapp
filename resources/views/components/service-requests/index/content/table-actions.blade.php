@props(['request', 'compact' => false])

<div class="flex items-center gap-1" data-request-id="{{ $request->id }}">
    <!-- Ver Detalles -->
    <a href="{{ route('service-requests.show', $request) }}"
       class="text-blue-600 hover:text-blue-800 p-1 rounded transition-colors duration-150"
       title="Ver detalles"
       aria-label="Ver detalles de la solicitud {{ $request->ticket_number }}">
        <i class="fas fa-eye {{ $compact ? 'text-xs' : 'text-sm' }}"></i>
    </a>

    <!-- Aceptar -->
    @if($request->status === 'PENDIENTE')
        <form method="POST" action="{{ route('service-requests.accept', $request) }}" class="sr-action-form inline" data-action="accept">
            @csrf
            @method('PATCH')
            <button type="submit" class="text-green-600 hover:text-green-800 p-1 rounded transition-colors duration-150"
                    title="Aceptar solicitud"
                    aria-label="Aceptar solicitud {{ $request->ticket_number }}">
                <i class="fas fa-check {{ $compact ? 'text-xs' : 'text-sm' }}"></i>
            </button>
        </form>
    @endif

    <!-- Iniciar / En Progreso -->
    @if(in_array($request->status, ['ACEPTADA','EN_PROCESO']))
        <form method="POST" action="{{ route('service-requests.start', $request) }}" class="sr-action-form inline" data-action="start">
            @csrf
            @method('PATCH')
            <input type="hidden" name="use_standard_tasks" value="0" />
            <button type="submit" class="text-purple-600 hover:text-purple-800 p-1 rounded transition-colors duration-150"
                    title="Marcar en proceso"
                    aria-label="Marcar en proceso solicitud {{ $request->ticket_number }}">
                <i class="fas fa-cog {{ $compact ? 'text-xs' : 'text-sm' }}"></i>
            </button>
        </form>
    @endif

    <!-- Menú Más Opciones (placeholder) -->
    <div class="relative">
        <button type="button" class="text-gray-500 hover:text-gray-700 p-1 rounded transition-colors duration-150 sr-more-btn"
                title="Más opciones"
                aria-haspopup="true"
                aria-expanded="false"
                aria-label="Más opciones para solicitud {{ $request->ticket_number }}">
            <i class="fas fa-ellipsis-h {{ $compact ? 'text-xs' : 'text-sm' }}"></i>
        </button>
        <div class="sr-more-menu absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow text-xs hidden z-10">
            <a href="{{ route('service-requests.timeline', $request) }}" class="block px-3 py-2 hover:bg-gray-50" aria-label="Ver línea de tiempo">Línea de tiempo</a>
             <a href="{{ route('service-requests.reassign', $request) }}" class="block px-3 py-2 hover:bg-gray-50" aria-label="Reasignar">Reasignar</a>
             @if(in_array($request->status,['EN_PROCESO']))
                <form method="POST" action="{{ route('service-requests.pause', $request) }}" class="sr-action-form" data-action="pause">
                    @csrf
                    <input type="hidden" name="pause_reason" value="" />
                    <button type="submit" class="w-full text-left px-3 py-2 hover:bg-gray-50" aria-label="Pausar solicitud">Pausar</button>
                </form>
             @endif
            @if(in_array($request->status,['PAUSADA']))
                <form method="POST" action="{{ route('service-requests.resume', $request) }}" class="sr-action-form">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 hover:bg-gray-50" aria-label="Reanudar solicitud">Reanudar</button>
                </form>
            @endif
        </div>
    </div>
</div>
