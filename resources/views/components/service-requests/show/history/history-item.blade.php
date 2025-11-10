@props(['historyItem'])

@php
$eventTypes = [
    'created' => ['color' => 'bg-green-500', 'icon' => 'fa-plus-circle'],
    'updated' => ['color' => 'bg-blue-500', 'icon' => 'fa-edit'],
    'status_changed' => ['color' => 'bg-purple-500', 'icon' => 'fa-sync-alt'],
    'assigned' => ['color' => 'bg-orange-500', 'icon' => 'fa-user-check'],
    'comment' => ['color' => 'bg-gray-500', 'icon' => 'fa-comment'],
    'evidence_added' => ['color' => 'bg-amber-500', 'icon' => 'fa-file-upload'],
    'sla_updated' => ['color' => 'bg-red-500', 'icon' => 'fa-clock'],
    'resolved' => ['color' => 'bg-emerald-500', 'icon' => 'fa-check-double']
];

$eventType = $eventTypes[$historyItem->event_type] ?? $eventTypes['updated'];
@endphp

<div class="timeline-item group relative pl-8">
    <!-- Punto de timeline -->
    <div class="absolute left-0 top-2 w-4 h-4 {{ $eventType['color'] }} rounded-full border-4 border-white shadow-lg timeline-dot transition duration-200"></div>

    <!-- Contenido del evento -->
    <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition duration-150">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center space-x-2 mb-2">
                    <i class="fas {{ $eventType['icon'] }} text-gray-500"></i>
                    <span class="font-semibold text-gray-900 capitalize">
                        {{ str_replace('_', ' ', $historyItem->event_type) }}
                    </span>
                    <span class="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded">
                        {{ $historyItem->created_at->diffForHumans() }}
                    </span>
                </div>

                <!-- Descripción del evento -->
                <p class="text-gray-700 text-sm mb-2">
                    {!! $historyItem->description ?? 'Sin descripción' !!}
                </p>

                <!-- Detalles adicionales -->
                @if($historyItem->details && count($historyItem->details) > 0)
                <div class="bg-white rounded border p-3 text-xs">
                    <table class="w-full">
                        <tbody>
                            @foreach($historyItem->details as $key => $value)
                            <tr class="border-b border-gray-100 last:border-b-0">
                                <td class="py-1 font-medium text-gray-600 capitalize">{{ str_replace('_', ' ', $key) }}:</td>
                                <td class="py-1 text-gray-800">
                                    @if(is_array($value))
                                        {{ json_encode($value) }}
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        <!-- Información del usuario -->
        <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-200">
            <div class="flex items-center space-x-2">
                @if($historyItem->user)
                <div class="w-6 h-6 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                    {{ substr($historyItem->user->name, 0, 1) }}
                </div>
                <span class="text-xs text-gray-600">{{ $historyItem->user->name }}</span>
                @else
                <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 text-xs">
                    <i class="fas fa-user"></i>
                </div>
                <span class="text-xs text-gray-600">Sistema</span>
                @endif
            </div>

            <span class="text-xs text-gray-500">
                {{ $historyItem->created_at->format('d/m/Y H:i') }}
            </span>
        </div>
    </div>
</div>
