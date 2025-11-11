@props(['serviceRequest'])

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-orange-50 to-amber-50 px-6 py-4 border-b border-orange-100">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <i class="fas fa-clock text-orange-600 mr-3"></i>
            Tiempo de Respuesta
        </h3>
    </div>
    <div class="p-6">
        <!-- Estado principal del SLA -->
        <div class="text-center mb-6">
            @php
                $slaHours = $serviceRequest->subService->sla_hours ?? 72;
                $elapsedHours = $serviceRequest->created_at->diffInHours(now());
                $remainingHours = max(0, $slaHours - $elapsedHours);

                // Calcular días y horas para mostrar
                $elapsedDays = floor($elapsedHours / 24);
                $elapsedRemainingHours = $elapsedHours % 24;

                $remainingDays = floor($remainingHours / 24);
                $remainingRemainingHours = $remainingHours % 24;

                $slaDays = floor($slaHours / 24);
                $slaRemainingHours = $slaHours % 24;

                if($slaHours > 0) {
                    $progress = min(100, ($elapsedHours / $slaHours) * 100);
                } else {
                    $progress = 0;
                }

                // Determinar estado y colores
                if($progress >= 90) {
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

            <div class="inline-flex items-center px-4 py-2 rounded-full {{ $bgColor }} {{ $borderColor }} border">
                <i class="fas {{ $icon }} {{ $statusColor }} mr-2"></i>
                <span class="font-semibold {{ $statusColor }}">{{ $status }}</span>
            </div>
        </div>

        <!-- Barra de progreso mejorada -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700">Progreso del tiempo</span>
                <span class="text-sm font-bold text-gray-900">{{ number_format($progress, 0) }}%</span>
            </div>

            <div class="bg-gray-200 rounded-full h-3 overflow-hidden">
                <div
                    class="h-3 rounded-full {{ $progressColor }} transition-all duration-500 ease-out"
                    style="width: {{ $progress }}%"
                ></div>
            </div>

            <div class="flex justify-between items-center mt-2">
                <div class="text-left">
                    <p class="text-xs text-gray-500">Transcurrido</p>
                    <p class="text-sm font-semibold text-gray-900">
                        @if($elapsedDays > 0)
                            {{ $elapsedDays }}d {{ round($elapsedRemainingHours) }}h
                        @else
                            {{ round($elapsedRemainingHours) }}h
                        @endif
                    </p>
                </div>

                <div class="text-center">
                    <p class="text-xs text-gray-500">Restante</p>
                    <p class="text-sm font-semibold {{ $progress >= 90 ? 'text-red-600' : 'text-gray-900' }}">
                        @if($remainingDays > 0)
                            {{ $remainingDays }}d {{ round($remainingRemainingHours) }}h
                        @else
                            {{ round($remainingRemainingHours) }}h
                        @endif
                    </p>
                </div>

                <div class="text-right">
                    <p class="text-xs text-gray-500">Límite total</p>
                    <p class="text-sm font-semibold text-gray-900">
                        @if($slaDays > 0)
                            {{ $slaDays }}d {{ $slaRemainingHours }}h
                        @else
                            {{ $slaHours }}h
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Información resumida -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-gray-200">
            <div class="text-center p-3 bg-blue-50 rounded-lg">
                <div class="text-xl font-bold text-blue-600 mb-1">
                    {{ $elapsedDays }}d {{ round($elapsedRemainingHours) }}h
                </div>
                <p class="text-sm font-medium text-gray-700">Tiempo transcurrido</p>
                <p class="text-xs text-gray-500">Desde la creación</p>
            </div>

            <div class="text-center p-3 {{ $progress >= 90 ? 'bg-red-50' : 'bg-green-50' }} rounded-lg">
                <div class="text-xl font-bold {{ $progress >= 90 ? 'text-red-600' : 'text-green-600' }} mb-1">
                    @if($remainingDays > 0)
                        {{ $remainingDays }}d {{ round($remainingRemainingHours) }}h
                    @else
                        {{ round($remainingRemainingHours) }}h
                    @endif
                </div>
                <p class="text-sm font-medium text-gray-700">Tiempo restante</p>
                <p class="text-xs text-gray-500">Para cumplir el SLA</p>
            </div>

            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <div class="text-xl font-bold text-gray-700 mb-1">
                    @if($slaDays > 0)
                        {{ $slaDays }}d {{ $slaRemainingHours }}h
                    @else
                        {{ $slaHours }}h
                    @endif
                </div>
                <p class="text-sm font-medium text-gray-700">Plazo establecido</p>
                <p class="text-xs text-gray-500">Tiempo límite total</p>
            </div>
        </div>

        <!-- Alerta crítica -->
        @if($progress >= 90)
        <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                <span class="text-sm font-medium text-red-700">
                    @if($remainingHours <= 0)
                    ⚠️ El tiempo de respuesta ha sido excedido
                    @else
                    ⚠️ Tiempo de respuesta crítico - Actúe pronto
                    @endif
                </span>
            </div>
        </div>
        @elseif($progress >= 75)
        <div class="mt-4 p-3 bg-orange-50 border border-orange-200 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-clock text-orange-500 mr-2"></i>
                <span class="text-sm font-medium text-orange-700">
                    ⏳ Tiempo de respuesta en riesgo - Monitoree de cerca
                </span>
            </div>
        </div>
        @endif

        <!-- Fechas importantes -->
        <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
            <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                <i class="fas fa-calendar-alt text-gray-500 mr-2"></i>
                Fechas clave
            </h4>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-600">Fecha de inicio:</span>
                    <span class="text-xs font-medium text-gray-900">{{ $serviceRequest->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-600">Fecha límite:</span>
                    <span class="text-xs font-medium {{ $progress >= 90 ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $serviceRequest->created_at->addHours($slaHours)->format('d/m/Y H:i') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
