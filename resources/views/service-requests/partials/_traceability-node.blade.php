{{-- resources/views/service-requests/partials/_traceability-node.blade.php --}}
{{-- Single node in the traceability chain tree --}}
@php
    $typeLabel = $node['type_label'] ?? 'general';
    $typeIcons = [
        'reunion' => 'fa-calendar-alt text-indigo-500',
        'compromiso' => 'fa-handshake text-amber-500',
        'seguimiento' => 'fa-sync text-green-500',
        'solicitud_documental' => 'fa-file-alt text-orange-500',
        'general' => 'fa-ticket-alt text-gray-500',
    ];
    $icon = $typeIcons[$typeLabel] ?? $typeIcons['general'];
    $isCurrent = $isCurrentRequest ?? false;
@endphp

<div class="flex items-center gap-2 py-1.5 px-2 rounded transition {{ $isCurrent ? 'bg-cyan-50 border border-cyan-200' : 'hover:bg-gray-50' }}">
    <i class="fas {{ $icon }} text-xs flex-shrink-0"></i>
    <div class="flex items-center gap-2 flex-wrap min-w-0">
        @if(!$isCurrent && !empty($node['id']))
            <a href="{{ route('service-requests.show', $node['id']) }}"
               class="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline">
                {{ $node['ticket_number'] ?? 'N/A' }}
            </a>
        @else
            <span class="text-sm font-medium {{ $isCurrent ? 'text-cyan-800' : 'text-gray-900' }}">
                {{ $node['ticket_number'] ?? 'N/A' }}
                @if($isCurrent) <span class="text-[10px] text-cyan-600">(actual)</span> @endif
            </span>
        @endif

        <span class="text-sm text-gray-600 truncate max-w-[200px]" title="{{ $node['title'] ?? '' }}">
            {{ Str::limit($node['title'] ?? '', 35) }}
        </span>

        {{-- Status badge --}}
        @if(!empty($node['status']))
            @php
                $statusColors = [
                    'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                    'ACEPTADA' => 'bg-blue-100 text-blue-800',
                    'EN_PROCESO' => 'bg-indigo-100 text-indigo-800',
                    'RESUELTA' => 'bg-green-100 text-green-800',
                    'CERRADA' => 'bg-gray-100 text-gray-800',
                    'CANCELADA' => 'bg-red-100 text-red-800',
                    'RECHAZADA' => 'bg-red-100 text-red-800',
                    'PAUSADA' => 'bg-orange-100 text-orange-800',
                    'REABIERTO' => 'bg-purple-100 text-purple-800',
                ];
                $statusColor = $statusColors[$node['status']] ?? 'bg-gray-100 text-gray-800';
            @endphp
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ $statusColor }}">
                {{ $node['status'] }}
            </span>
        @endif

        {{-- Type label --}}
        <span class="text-[10px] text-gray-400">{{ $typeLabel }}</span>

        {{-- Assigned technician --}}
        @if(!empty($node['assigned_to']))
            <span class="text-xs text-gray-500">
                <i class="fas fa-user text-[9px] mr-0.5"></i>{{ $node['assigned_to'] }}
            </span>
        @else
            <span class="text-xs text-gray-400 italic">Sin asignar</span>
        @endif
    </div>
</div>
