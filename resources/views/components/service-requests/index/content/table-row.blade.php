@props(['request'])

@php
    $isClosed = in_array(strtoupper((string) $request->status), ['CERRADA', 'RECHAZADA'], true);
    $openStatuses = ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO', 'PAUSADA', 'REABIERTO'];
    $isOpenRequest = in_array(strtoupper((string) $request->status), $openStatuses, true);

    $fallbackResponseMinutes = (int) ($request->sla->response_time_minutes ?? 0);
    $responseStartAt = $request->accepted_at;
    $responseDeadline = ($responseStartAt && $fallbackResponseMinutes > 0)
        ? $responseStartAt->copy()->addMinutes($fallbackResponseMinutes)
        : null;
    $respondedAt = $request->responded_at;
    $remainingMinutes = $responseDeadline ? now()->diffInMinutes($responseDeadline, false) : null;

    $responseToneClasses = 'text-gray-700 bg-gray-100';
    $responseLabel = 'Sin objetivo';
    $responseDetail = 'Sin plazo';
    $responseProgress = 0;

    $formatWindow = function (int $minutes): string {
        $minutes = max(0, $minutes);
        $hours = intdiv($minutes, 60);
        $days = intdiv($hours, 24);
        $remainingHours = $hours % 24;

        if ($days > 0) {
            return $days . 'd ' . $remainingHours . 'h';
        }

        return $hours . 'h';
    };

    if ($respondedAt) {
        $responseToneClasses = 'text-emerald-700 bg-emerald-100';
        $responseLabel = 'Respondida';
        $responseDetail = $respondedAt->format('d/m H:i');
        $responseProgress = 100;
    } elseif ($isOpenRequest && !$responseStartAt) {
        $responseToneClasses = 'text-slate-700 bg-slate-100';
        $responseLabel = 'Pendiente de aceptación';
        $responseDetail = 'Aún no inicia';
        $responseProgress = 0;
    } elseif ($isOpenRequest && $responseDeadline && $responseStartAt) {
        $totalWindowMinutes = max(1, (int) $responseStartAt->diffInMinutes($responseDeadline));
        $elapsedMinutes = max(0, (int) $responseStartAt->diffInMinutes(now()));
        $remainingWindowMinutes = max(0, $totalWindowMinutes - $elapsedMinutes);
        $responseProgress = min(100, (int) round(($elapsedMinutes / $totalWindowMinutes) * 100));

        if ($responseProgress >= 90) {
            $responseToneClasses = 'text-red-700 bg-red-100';
            $responseLabel = 'Tiempo Crítico';
        } elseif ($responseProgress >= 75) {
            $responseToneClasses = 'text-amber-700 bg-amber-100';
            $responseLabel = 'Tiempo en Riesgo';
        } else {
            $responseToneClasses = 'text-emerald-700 bg-emerald-100';
            $responseLabel = 'En Tiempo';
        }

        $responseDetail = $formatWindow($remainingWindowMinutes);
    }
@endphp

<tr class="{{ $isClosed ? 'bg-gray-50 text-gray-500 grayscale-[85%] opacity-80' : 'hover:bg-gray-50' }} text-xs sm:text-sm transition-colors"
    data-status="{{ $request->status }}"
    data-criticality="{{ $request->criticality_level }}"
    tabindex="0">

    <!-- Ticket - Mejorado con enlace azul y más visible -->
    <td class="px-1.5 sm:px-2.5 py-1 sm:py-1.5 whitespace-nowrap">
        <a href="{{ route('service-requests.show', $request) }}"
           class="font-mono {{ $isClosed ? 'text-gray-600 hover:text-gray-700' : 'text-blue-600 hover:text-blue-800' }} hover:underline font-bold text-xs sm:text-sm transition-colors">
            {{ $request->ticket_number }}
        </a>
        @if(!$isClosed)
            <div class="mt-1 w-36">
                <div class="w-full h-1.5 rounded-full bg-gray-200 overflow-hidden" aria-label="Progreso del tiempo de respuesta">
                    <div class="h-full {{ str_contains($responseToneClasses, 'red') ? 'bg-red-500' : (str_contains($responseToneClasses, 'amber') ? 'bg-amber-500' : 'bg-emerald-500') }}"
                         style="width: {{ $responseProgress }}%"></div>
                </div>
                <div class="text-[11px] mt-1 space-y-1">
                    <span class="inline-flex max-w-full items-center px-1.5 py-0.5 rounded {{ $responseToneClasses }} break-words leading-tight">{{ $responseLabel }}</span>
                    <span class="block text-gray-500 leading-tight">{{ $responseDetail }}</span>
                </div>
            </div>
        @endif
        <div class="mt-1 text-[11px] {{ $isClosed ? 'text-gray-500' : 'text-gray-600' }} sm:hidden">
            <div class="font-medium {{ $isClosed ? 'text-gray-600' : 'text-gray-800' }}">{{ Str::limit($request->title, 45) }}</div>
            <div class="text-gray-500">{{ $request->subService->name ?? 'Sin servicio' }}</div>
        </div>
    </td>

    <!-- Título y Descripción -->
    <td class="px-1.5 sm:px-2.5 py-1 sm:py-1.5 hidden md:table-cell">
        <div class="font-medium {{ $isClosed ? 'text-gray-600' : 'text-gray-900' }} text-xs">{{ Str::limit($request->title, 65) }}</div>
        <div class="text-xs text-gray-500 mt-0.5">{{ Str::limit($request->description, 60) }}</div>
    </td>

    <!-- Servicio -->
    <td class="px-1.5 sm:px-2.5 py-1 sm:py-1.5 hidden lg:table-cell">
        <div class="font-medium text-xs {{ $isClosed ? 'text-gray-600' : 'text-gray-900' }}">{{ $request->subService->name ?? 'N/A' }}</div>
        @php
            $family = $request->subService?->service?->family;
            $familyName = $family?->name ?? '';
            $contractNumber = $family?->contract?->number;
            $familyLabel = $contractNumber ? ($contractNumber . ' - ' . $familyName) : $familyName;
        @endphp
        <div class="text-xs text-gray-500">{{ $familyLabel }}</div>
    </td>

    <!-- Prioridad -->
    <td class="px-1.5 sm:px-2.5 py-1 sm:py-1.5 whitespace-nowrap">
        <x-service-requests.index.content.priority-badge :priority="$request->criticality_level" compact />
    </td>

    <!-- Estado -->
    <td class="px-1.5 sm:px-2.5 py-1 sm:py-1.5 whitespace-nowrap">
        <x-service-requests.index.content.status-badge :status="$request->status" compact />
    </td>

    <!-- Solicitante con Avatar mejorado -->
    <td class="px-1.5 sm:px-2.5 py-1 sm:py-1.5 whitespace-nowrap hidden sm:table-cell">
        <div class="flex items-center space-x-1.5 sm:space-x-2">
            @php
                $name = $request->requester->name ?? 'N/A';
                $initials = collect(explode(' ', $name))->map(fn($word) => strtoupper(substr($word, 0, 1)))->take(2)->join('');
                $colors = ['from-purple-500 to-pink-500', 'from-blue-500 to-cyan-500', 'from-green-500 to-emerald-500', 'from-orange-500 to-red-500', 'from-indigo-500 to-purple-500'];
                $colorIndex = ord(substr($name, 0, 1)) % count($colors);
            @endphp
            <div class="w-6 h-6 {{ $isClosed ? 'bg-gray-400' : 'bg-gradient-to-br ' . $colors[$colorIndex] }} rounded-full flex items-center justify-center text-white text-[10px] font-bold shadow-sm">
                {{ $initials }}
            </div>
            <div class="text-xs {{ $isClosed ? 'text-gray-600' : 'text-gray-900' }} truncate max-w-[80px]" title="{{ $name }}">
                {{ $name }}
            </div>
        </div>
    </td>

    <!-- Fecha con formato relativo -->
    <td class="px-1.5 sm:px-2.5 py-1 sm:py-1.5 whitespace-nowrap hidden xl:table-cell">
        <div class="text-xs text-gray-900">{{ $request->created_at->format('d/m/Y') }}</div>
        <div class="text-xs text-gray-500">{{ $request->created_at->locale('es')->diffForHumans() }}</div>
    </td>

    <!-- Acciones -->
    <td class="px-1.5 sm:px-2.5 py-1 sm:py-1.5 whitespace-nowrap">
        <x-service-requests.index.content.table-actions :request="$request" compact />
    </td>
</tr>
