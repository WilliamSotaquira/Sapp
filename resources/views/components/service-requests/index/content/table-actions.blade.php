@props(['request'])

<div class="flex items-center space-x-2">
    <a href="{{ route('service-requests.show', $request) }}"
        class="text-blue-600 hover:text-blue-900 transition duration-150 p-2 rounded-lg bg-blue-50 hover:bg-blue-100"
        title="Ver detalles">
        <i class="fas fa-eye"></i>
    </a>

    @if(in_array($request->status, ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA']))
    <a href="{{ route('service-requests.edit', $request) }}"
        class="text-green-600 hover:text-green-900 transition duration-150 p-2 rounded-lg bg-green-50 hover:bg-green-100"
        title="Editar">
        <i class="fas fa-edit"></i>
    </a>
    @endif

    @if(in_array($request->status, ['PENDIENTE', 'CANCELADA']))
    <form action="{{ route('service-requests.destroy', $request) }}" method="POST" class="inline">
        @csrf
        @method('DELETE')
        <button type="submit"
            class="text-red-600 hover:text-red-900 transition duration-150 p-2 rounded-lg bg-red-50 hover:bg-red-100"
            title="Eliminar"
            onclick="return confirm('¿Está seguro de que desea eliminar esta solicitud?')">
            <i class="fas fa-trash"></i>
        </button>
    </form>
    @endif
</div>
