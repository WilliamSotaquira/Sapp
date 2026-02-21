@props(['serviceRequest'])

@php
    $isDead = in_array($serviceRequest->status, ['CERRADA', 'CANCELADA', 'RECHAZADA']);
@endphp

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="{{ $isDead ? 'bg-gray-100 border-gray-300' : 'bg-gradient-to-r from-orange-50 to-amber-50 border-orange-100' }} px-6 py-4 border-b">
        <h3 class="sr-card-title text-gray-800 flex items-center">
            <i class="fas fa-clock {{ $isDead ? 'text-gray-500' : 'text-orange-600' }} mr-3"></i>
            Tiempo de Respuesta
        </h3>
    </div>
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            @php
                $fallbackResponseMinutes = (int) ($serviceRequest->sla->response_time_minutes ?? 0);
                $responseStartAt = $serviceRequest->accepted_at;
                $responseDeadline = ($responseStartAt && $fallbackResponseMinutes > 0)
                    ? $responseStartAt->copy()->addMinutes($fallbackResponseMinutes)
                    : null;

                $totalWindowMinutes = $responseDeadline
                    ? max(1, (int) $responseStartAt->diffInMinutes($responseDeadline))
                    : 0;
                $elapsedMinutes = $responseDeadline
                    ? max(0, (int) $responseStartAt->diffInMinutes(now()))
                    : 0;
                $remainingMinutes = $responseDeadline
                    ? max(0, $totalWindowMinutes - $elapsedMinutes)
                    : 0;

                $elapsedHours = (int) floor($elapsedMinutes / 60);
                $remainingHours = (int) floor($remainingMinutes / 60);
                $slaHours = (int) floor($totalWindowMinutes / 60);

                $elapsedDays = floor($elapsedHours / 24);
                $elapsedRemainingHours = $elapsedHours % 24;

                $remainingDays = floor($remainingHours / 24);
                $remainingRemainingHours = $remainingHours % 24;

                $slaDays = floor($slaHours / 24);
                $slaRemainingHours = $slaHours % 24;

                $progress = $totalWindowMinutes > 0
                    ? min(100, ($elapsedMinutes / $totalWindowMinutes) * 100)
                    : 0;

                // Determinar estado y colores
                if (!$responseStartAt) {
                    $status = 'Pendiente de aceptación';
                    $statusColor = 'text-slate-600';
                    $bgColor = 'bg-slate-50';
                    $borderColor = 'border-slate-200';
                    $progressColor = 'bg-slate-400';
                    $icon = 'fa-hourglass-start';
                } elseif($progress >= 90) {
                    $status = 'Tiempo Crítico';
                    $statusColor = 'text-red-600';
                    $bgColor = 'bg-red-50';
                    $borderColor = 'border-red-200';
                    $progressColor = 'bg-red-500';
                    $icon = 'fa-exclamation-triangle';
                } elseif($progress >= 75) {
                    $status = 'Tiempo en Riesgo';
                    $statusColor = 'text-orange-600';
                    $bgColor = 'bg-orange-50';
                    $borderColor = 'border-orange-200';
                    $progressColor = 'bg-orange-500';
                    $icon = 'fa-clock';
                } else {
                    $status = 'En Tiempo';
                    $statusColor = 'text-green-600';
                    $bgColor = 'bg-green-50';
                    $borderColor = 'border-green-200';
                    $progressColor = 'bg-green-500';
                    $icon = 'fa-check-circle';
                }
            @endphp
            <div class="inline-flex items-center px-3 py-1.5 rounded-full {{ $bgColor }} {{ $borderColor }} border text-sm">
                <i class="fas {{ $icon }} {{ $statusColor }} mr-2"></i>
                <span class="font-semibold {{ $statusColor }}">{{ $status }}</span>
            </div>
            <div class="text-sm font-medium text-gray-700">{{ number_format($progress, 0) }}%</div>
        </div>

        <div class="mb-4">
            <div class="bg-gray-200 rounded-full h-2 overflow-hidden">
                <div class="h-2 rounded-full {{ $progressColor }}" style="width: {{ $progress }}%"></div>
            </div>
            <div class="mt-2 flex justify-between text-xs text-gray-600">
                <span>Transcurrido: {{ $elapsedDays > 0 ? ($elapsedDays . 'd ' . round($elapsedRemainingHours) . 'h') : (round($elapsedRemainingHours) . 'h') }}</span>
                <span>Restante: {{ $remainingDays > 0 ? ($remainingDays . 'd ' . round($remainingRemainingHours) . 'h') : (round($remainingRemainingHours) . 'h') }}</span>
                <span>Total: {{ $slaDays > 0 ? ($slaDays . 'd ' . $slaRemainingHours . 'h') : ($slaHours . 'h') }}</span>
            </div>
        </div>

        <div class="text-xs text-gray-500">
            Inicio: {{ $responseStartAt ? $responseStartAt->format('d/m/Y H:i') : 'Pendiente de aceptación' }} ·
            Límite: {{ $responseDeadline ? $responseDeadline->format('d/m/Y H:i') : 'Sin definir' }}
        </div>
    </div>
</div>
