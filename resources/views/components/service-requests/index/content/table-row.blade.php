@props(['request'])

<tr class="hover:bg-gray-50 transition duration-150"
    data-status="{{ $request->status }}"
    data-criticality="{{ $request->criticality_level }}">

    <!-- Ticket -->
    <td class="px-6 py-4 whitespace-nowrap">
        <a href="{{ route('service-requests.show', $request) }}"
           class="font-mono text-blue-600 hover:text-blue-800 font-semibold text-sm">
            #{{ $request->ticket_number }}
        </a>
    </td>

    <!-- Título y Descripción -->
    <td class="px-6 py-4">
        <div class="font-medium text-gray-900 text-sm">{{ Str::limit($request->title, 50) }}</div>
        <div class="text-xs text-gray-500 mt-1">{{ Str::limit($request->description, 70) }}</div>
    </td>

    <!-- Servicio -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900">{{ $request->subService->name ?? 'N/A' }}</div>
        <div class="text-xs text-gray-500">{{ $request->subService->service->family->name ?? 'N/A' }}</div>
    </td>

    <!-- Prioridad -->
    <td class="px-6 py-4 whitespace-nowrap">
        @php
        $criticalityColors = [
            'BAJA' => 'bg-green-100 text-green-800 border-green-200',
            'MEDIA' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            'ALTA' => 'bg-orange-100 text-orange-800 border-orange-200',
            'CRITICA' => 'bg-red-100 text-red-800 border-red-200'
        ];
        @endphp
        <span class="px-3 py-1 text-xs font-semibold rounded-full border {{ $criticalityColors[$request->criticality_level] ?? 'bg-gray-100 text-gray-800 border-gray-200' }}">
            <i class="fas fa-flag mr-1"></i>{{ $request->criticality_level }}
        </span>
    </td>

    <!-- Estado -->
    <td class="px-6 py-4 whitespace-nowrap">
        @php
        $statusColors = [
            'PENDIENTE' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            'ACEPTADA' => 'bg-blue-100 text-blue-800 border-blue-200',
            'EN_PROCESO' => 'bg-purple-100 text-purple-800 border-purple-200',
            'PAUSADA' => 'bg-orange-100 text-orange-800 border-orange-200',
            'RESUELTA' => 'bg-green-100 text-green-800 border-green-200',
            'CERRADA' => 'bg-gray-100 text-gray-800 border-gray-200',
            'CANCELADA' => 'bg-red-100 text-red-800 border-red-200'
        ];
        $statusIcons = [
            'PENDIENTE' => 'fa-clock',
            'ACEPTADA' => 'fa-check',
            'EN_PROCESO' => 'fa-cog',
            'PAUSADA' => 'fa-pause',
            'RESUELTA' => 'fa-check-double',
            'CERRADA' => 'fa-lock',
            'CANCELADA' => 'fa-times'
        ];
        @endphp
        <span class="px-3 py-1 text-xs font-semibold rounded-full border {{ $statusColors[$request->status] ?? 'bg-gray-100 text-gray-800 border-gray-200' }}">
            <i class="fas {{ $statusIcons[$request->status] ?? 'fa-circle' }} mr-1"></i>
            {{ $request->status }}
        </span>
    </td>

    <!-- Solicitante -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="flex items-center space-x-2">
            <div class="w-6 h-6 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                {{ substr($request->requester->name ?? 'N', 0, 1) }}
            </div>
            <div>
                <div class="text-sm text-gray-900">{{ $request->requester->name ?? 'N/A' }}</div>
            </div>
        </div>
    </td>

    <!-- Fecha -->
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900">{{ $request->created_at->format('d/m/Y') }}</div>
        <div class="text-xs text-gray-500">{{ $request->created_at->format('H:i') }}</div>
    </td>

    <!-- Acciones -->
    <td class="px-6 py-4 whitespace-nowrap">
        <x-service-requests.index.content.table-actions :request="$request" />
    </td>
</tr>
