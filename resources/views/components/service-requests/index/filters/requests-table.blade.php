<!-- Búsqueda Rápida -->
<div class="mt-4">
    <form method="GET" action="{{ route('service-requests.index') }}" class="flex gap-2">
        <div class="flex-1">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Buscar por ticket, título o descripción..."
                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <button type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200 text-sm font-medium">
            <i class="fas fa-search"></i>
        </button>
        @if(request()->hasAny(['search', 'status', 'criticality']))
        <a href="{{ route('service-requests.index') }}"
           class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition duration-200 text-sm font-medium">
            <i class="fas fa-times"></i>
        </a>
        @endif
    </form>
</div>
