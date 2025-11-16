@props(['request'])

<tr class="hover:bg-gray-50 text-xs sm:text-sm"
    data-status="{{ $request->status }}"
    data-criticality="{{ $request->criticality_level }}"
    tabindex="0">

    <!-- Ticket -->
    <td class="px-2 sm:px-3 py-1.5 sm:py-2 whitespace-nowrap">
        <a href="{{ route('service-requests.show', $request) }}"
           class="font-mono text-gray-600 hover:text-gray-800 font-semibold text-[10px] sm:text-xs">
            #{{ $request->ticket_number }}
        </a>
    </td>

    <!-- Título y Descripción -->
    <td class="px-2 sm:px-3 py-1.5 sm:py-2 hidden md:table-cell">
        <div class="font-medium text-gray-900 text-xs">{{ Str::limit($request->title, 65) }}</div>
        <div class="text-xs text-gray-500 mt-0.5">{{ Str::limit($request->description, 60) }}</div>
    </td>

    <!-- Servicio -->
    <td class="px-2 sm:px-3 py-1.5 sm:py-2 hidden lg:table-cell">
        <div class="font-medium text-xs text-gray-900">{{ $request->subService->name ?? 'N/A' }}</div>
        <div class="text-xs text-gray-500">{{ $request->subService->service->family->name ?? '' }}</div>
    </td>

    <!-- Prioridad -->
    <td class="px-2 sm:px-3 py-1.5 sm:py-2 whitespace-nowrap">
        <x-service-requests.index.content.priority-badge :priority="$request->criticality_level" compact />
    </td>

    <!-- Estado -->
    <td class="px-2 sm:px-3 py-1.5 sm:py-2 whitespace-nowrap">
        <x-service-requests.index.content.status-badge :status="$request->status" compact />
    </td>

    <!-- Solicitante -->
    <td class="px-2 sm:px-3 py-1.5 sm:py-2 whitespace-nowrap hidden sm:table-cell">
        <div class="flex items-center space-x-1.5 sm:space-x-2">
            <div class="w-4 h-4 sm:w-5 sm:h-5 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white text-[10px] sm:text-xs font-bold whitespace-nowrap">
                {{ substr($request->requester->name ?? 'N', 0, 1) }}
            </div>
            <div class="text-xs text-gray-900 truncate max-w-[60px] sm:max-w-[80px]">
                {{ $request->requester->name ?? 'N/A' }}
            </div>
        </div>
    </td>

    <!-- Fecha -->
    <td class="px-2 sm:px-3 py-1.5 sm:py-2 whitespace-nowrap hidden xl:table-cell">
        <div class="text-xs text-gray-900">{{ $request->created_at->format('d/m/Y') }}</div>
        <div class="text-xs text-gray-500">{{ $request->created_at->format('H:i') }}</div>
    </td>

    <!-- Acciones -->
    <td class="px-2 sm:px-3 py-1.5 sm:py-2 whitespace-nowrap">
        <x-service-requests.index.content.table-actions :request="$request" compact />
    </td>
</tr>
