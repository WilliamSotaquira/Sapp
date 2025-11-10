@props(['serviceRequest'])

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-orange-50 to-amber-50 px-6 py-4 border-b border-orange-100">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <i class="fas fa-hourglass-half text-orange-600 mr-3"></i>
            Información SLA
        </h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="text-sm font-medium text-gray-500">Tiempo Transcurrido</label>
                <div class="flex items-center space-x-2 mt-1">
                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                        @php
                        $progress = 0;
                        $slaHours = $serviceRequest->subService->sla_hours ?? 72;
                        $elapsedHours = $serviceRequest->created_at->diffInHours(now());
                        if($slaHours > 0) {
                        $progress = min(100, ($elapsedHours / $slaHours) * 100);
                        }
                        $progressColor = $progress >= 90 ? 'bg-red-500' : ($progress >= 75 ? 'bg-orange-500' : 'bg-green-500');
                        @endphp
                        <div class="h-2 rounded-full {{ $progressColor }}" style="width: {{ $progress }}%"></div>
                    </div>
                    <span class="text-sm font-semibold text-gray-700">{{ number_format($progress, 0) }}%</span>
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ $elapsedHours }}h / {{ $slaHours }}h</p>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-500">Estado SLA</label>
                @php
                $slaStatus = 'Dentro del SLA';
                $slaStatusColor = 'text-green-600 bg-green-100';

                if($progress >= 90) {
                $slaStatus = 'SLA Crítico';
                $slaStatusColor = 'text-red-600 bg-red-100';
                } elseif($progress >= 75) {
                $slaStatus = 'SLA en Riesgo';
                $slaStatusColor = 'text-orange-600 bg-orange-100';
                }
                @endphp
                <span class="inline-block px-3 py-1 text-sm font-semibold rounded-full {{ $slaStatusColor }}">
                    {{ $slaStatus }}
                </span>
                <p class="text-xs text-gray-500 mt-1">Tiempo límite: {{ $slaHours }} horas</p>
            </div>
        </div>
    </div>
</div>
