@props(['request'])

<tr class="hover:bg-gray-50 text-xs sm:text-sm transition-colors"
    data-status="{{ $request->status }}"
    data-criticality="{{ $request->criticality_level }}"
    tabindex="0">

    <!-- Ticket - Mejorado con enlace azul y más visible -->
    <td class="px-2 sm:px-3 py-1.5 sm:py-2 whitespace-nowrap">
        <a href="{{ route('service-requests.show', $request) }}"
           class="font-mono text-blue-600 hover:text-blue-800 hover:underline font-bold text-xs sm:text-sm transition-colors">
            {{ $request->ticket_number }}
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

    <!-- Solicitante con Avatar mejorado -->
    <td class="px-2 sm:px-3 py-1.5 sm:py-2 whitespace-nowrap hidden sm:table-cell">
        <div class="flex items-center space-x-1.5 sm:space-x-2">
            @php
                $name = $request->requester->name ?? 'N/A';
                $initials = collect(explode(' ', $name))->map(fn($word) => strtoupper(substr($word, 0, 1)))->take(2)->join('');
                $colors = ['from-purple-500 to-pink-500', 'from-blue-500 to-cyan-500', 'from-green-500 to-emerald-500', 'from-orange-500 to-red-500', 'from-indigo-500 to-purple-500'];
                $colorIndex = ord(substr($name, 0, 1)) % count($colors);
            @endphp
            <div class="w-6 h-6 bg-gradient-to-br {{ $colors[$colorIndex] }} rounded-full flex items-center justify-center text-white text-[10px] font-bold shadow-sm">
                {{ $initials }}
            </div>
            <div class="text-xs text-gray-900 truncate max-w-[80px]" title="{{ $name }}">
                {{ $name }}
            </div>
        </div>
    </td>

    <!-- Fecha con formato relativo -->
    <td class="px-2 sm:px-3 py-1.5 sm:py-2 whitespace-nowrap hidden xl:table-cell">
        <div class="text-xs text-gray-900">{{ $request->created_at->format('d/m/Y') }}</div>
        <div class="text-xs text-gray-500">{{ $request->created_at->diffForHumans() }}</div>
    </td>

    <!-- Acciones -->
    <td class="px-2 sm:px-3 py-1.5 sm:py-2 whitespace-nowrap">
        <x-service-requests.index.content.table-actions :request="$request" compact />
    </td>
</tr>
