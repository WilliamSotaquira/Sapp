@props(['serviceRequest'])

<div>
    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-exchange-alt text-purple-600 mr-2"></i>
        Cambios de Estado
    </h4>

    <div class="space-y-3">
        <!-- ✅ CORREGIDO: Verificar si statusChanges existe -->
        @if($serviceRequest->statusChanges && $serviceRequest->statusChanges->count() > 0)
            @foreach($serviceRequest->statusChanges->sortByDesc('created_at') as $statusChange)
            <div class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg hover:border-purple-300 transition duration-150">
                <div class="flex items-center space-x-3">
                    <!-- Estado anterior -->
                    <div class="text-center">
                        <span class="text-xs text-gray-500 block mb-1">Desde</span>
                        @php
                        $oldStatusColors = [
                            'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                            'ACEPTADA' => 'bg-blue-100 text-blue-800',
                            'EN_PROCESO' => 'bg-purple-100 text-purple-800',
                            'PAUSADA' => 'bg-orange-100 text-orange-800',
                            'RESUELTA' => 'bg-green-100 text-green-800',
                            'CERRADA' => 'bg-gray-100 text-gray-800',
                            'CANCELADA' => 'bg-red-100 text-red-800'
                        ];
                        @endphp
                        <span class="px-2 py-1 text-xs font-semibold rounded {{ $oldStatusColors[$statusChange->old_status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $statusChange->old_status }}
                        </span>
                    </div>

                    <!-- Flecha de transición -->
                    <div class="text-gray-400">
                        <i class="fas fa-arrow-right"></i>
                    </div>

                    <!-- Nuevo estado -->
                    <div class="text-center">
                        <span class="text-xs text-gray-500 block mb-1">Hacia</span>
                        @php
                        $newStatusColors = [
                            'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                            'ACEPTADA' => 'bg-blue-100 text-blue-800',
                            'EN_PROCESO' => 'bg-purple-100 text-purple-800',
                            'PAUSADA' => 'bg-orange-100 text-orange-800',
                            'RESUELTA' => 'bg-green-100 text-green-800',
                            'CERRADA' => 'bg-gray-100 text-gray-800',
                            'CANCELADA' => 'bg-red-100 text-red-800'
                        ];
                        @endphp
                        <span class="px-2 py-1 text-xs font-semibold rounded {{ $newStatusColors[$statusChange->new_status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $statusChange->new_status }}
                        </span>
                    </div>
                </div>

                <div class="text-right">
                    <div class="text-xs text-gray-500">
                        {{ $statusChange->created_at->diffForHumans() }}
                    </div>
                    <div class="text-xs text-gray-400">
                        {{ $statusChange->created_at->format('d/m/Y H:i') }}
                    </div>
                    @if($statusChange->user)
                    <div class="text-xs text-gray-600 mt-1">
                        Por: {{ $statusChange->user->name }}
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        @else
            <div class="text-center py-4 text-gray-500">
                <i class="fas fa-info-circle mr-2"></i>
                No hay cambios de estado registrados
            </div>
        @endif

        <!-- Estado inicial -->
        <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-lg">
            <div class="flex items-center space-x-3">
                <div class="text-center">
                    <span class="text-xs text-gray-500 block mb-1">Estado Inicial</span>
                    @php
                    $initialStatusColors = [
                        'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                        'ACEPTADA' => 'bg-blue-100 text-blue-800',
                        'EN_PROCESO' => 'bg-purple-100 text-purple-800',
                        'PAUSADA' => 'bg-orange-100 text-orange-800',
                        'RESUELTA' => 'bg-green-100 text-green-800',
                        'CERRADA' => 'bg-gray-100 text-gray-800',
                        'CANCELADA' => 'bg-red-100 text-red-800'
                    ];
                    @endphp
                    <span class="px-2 py-1 text-xs font-semibold rounded {{ $initialStatusColors[$serviceRequest->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ $serviceRequest->status }}
                    </span>
                </div>

                <div class="text-gray-400 text-sm">
                    Estado actual
                </div>
            </div>

            <div class="text-right">
                <div class="text-xs text-gray-500">
                    {{ $serviceRequest->created_at->diffForHumans() }}
                </div>
                <div class="text-xs text-gray-400">
                    {{ $serviceRequest->created_at->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>
    </div>
</div>
