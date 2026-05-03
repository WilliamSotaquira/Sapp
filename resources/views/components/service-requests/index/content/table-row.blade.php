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
    $hasDueDate = $request->hasRequestDueDate();
    $isFinalForDueDate = in_array(strtoupper((string) $request->status), ['RESUELTA', 'CERRADA', 'CANCELADA', 'RECHAZADA'], true);
    $dueClasses = 'bg-slate-100 text-slate-700';

    if ($hasDueDate) {
        if ($isFinalForDueDate) {
            $dueClasses = 'bg-slate-100 text-slate-600';
        } elseif ($request->isRequestDueOverdue()) {
            $dueClasses = 'bg-red-100 text-red-700';
        } elseif ($request->isRequestDueSoon()) {
            $dueClasses = 'bg-amber-100 text-amber-700';
        } else {
            $dueClasses = 'bg-emerald-100 text-emerald-700';
        }
    }

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

<tr class="{{ $isClosed ? 'bg-slate-50 text-gray-500 grayscale-[85%] opacity-80' : 'bg-white hover:bg-slate-50' }} text-sm transition-colors"
    data-status="{{ $request->status }}"
    data-criticality="{{ $request->criticality_level }}"
    tabindex="0">

    <!-- Ticket y SLA -->
    <td class="px-3 py-2.5 align-top">
        <a href="{{ route('service-requests.show', $request) }}"
           class="font-mono {{ $isClosed ? 'text-gray-600 hover:text-gray-700' : 'text-blue-700 hover:text-blue-900' }} hover:underline font-bold text-xs transition-colors break-all"
           title="Ver solicitud {{ $request->ticket_number }}">
            {{ $request->ticket_number }}
        </a>
        @if(!$isClosed)
            <div class="mt-1.5 max-w-[9rem]" title="{{ $responseLabel }} - {{ $responseDetail }}">
                <div class="w-full h-1 rounded-full bg-gray-200 overflow-hidden" aria-label="Progreso del tiempo de respuesta">
                    <div class="h-full {{ str_contains($responseToneClasses, 'red') ? 'bg-red-500' : (str_contains($responseToneClasses, 'amber') ? 'bg-amber-500' : 'bg-emerald-500') }}"
                         style="width: {{ $responseProgress }}%"></div>
                </div>
                <div class="mt-1 text-[11px] leading-tight text-slate-500 truncate">
                    <span class="font-medium {{ str_contains($responseToneClasses, 'red') ? 'text-red-700' : (str_contains($responseToneClasses, 'amber') ? 'text-amber-700' : 'text-slate-600') }}">{{ $responseLabel }}</span>
                    <span class="text-slate-400">·</span>
                    <span>{{ $responseDetail }}</span>
                </div>
            </div>
        @endif
    </td>

    <!-- Solicitud -->
    <td class="px-3 py-2.5 align-top">
        @php
            $family = $request->subService?->service?->family;
            $familyName = $family?->name ?? '';
            $contractNumber = $family?->contract?->number;
            $familyLabel = $contractNumber ? ($contractNumber . ' - ' . $familyName) : $familyName;
        @endphp
        <div class="font-semibold {{ $isClosed ? 'text-gray-600' : 'text-slate-900' }} leading-snug text-sm"
             title="{{ $request->title }}">
            {{ Str::limit($request->title, 72) }}
        </div>
        @if($request->description)
            <div class="mt-0.5 truncate text-xs leading-snug text-slate-600"
                 title="{{ $request->description }}">
                {{ $request->description }}
            </div>
        @endif
        <div class="mt-1 flex min-w-0 items-center gap-2 overflow-hidden whitespace-nowrap text-[11px] text-slate-500 leading-tight">
            <span class="min-w-0 max-w-[58%] truncate" title="{{ $request->subService->name ?? 'Sin servicio' }}">
                {{ $request->subService->name ?? 'Sin servicio' }}
            </span>
            @if($familyLabel)
                <span class="shrink-0 text-slate-300">|</span>
                <span class="min-w-0 flex-1 truncate" title="{{ $familyLabel }}">{{ $familyLabel }}</span>
            @endif
        </div>
    </td>

    <!-- Prioridad -->
    <td class="px-3 py-2.5 align-top whitespace-nowrap">
        <x-service-requests.index.content.priority-badge :priority="$request->criticality_level" compact />
    </td>

    <!-- Estado -->
    <td class="px-3 py-2.5 align-top">
        <x-service-requests.index.content.status-badge :status="$request->status" compact />
        <div class="mt-1.5 text-[11px] leading-tight">
            @if($hasDueDate)
                <span class="inline-flex items-center gap-1 rounded-full {{ $dueClasses }} px-2 py-1 text-xs font-semibold"
                      title="Fecha de vencimiento: {{ $request->due_date->format('d/m/Y') }}">
                    <i class="fas fa-calendar-check text-[10px]"></i>
                    <span>{{ $request->due_date->format('d/m/Y') }}</span>
                </span>
            @else
                <span class="text-gray-400" title="Esta solicitud no tiene fecha de vencimiento">Sin vencimiento</span>
            @endif
        </div>
    </td>

    <!-- Solicitante -->
    <td class="px-3 py-2.5 align-top">
        <div class="flex items-center gap-2">
            @php
                $name = $request->requester->name ?? 'N/A';
                $initials = collect(explode(' ', $name))->map(fn($word) => strtoupper(substr($word, 0, 1)))->take(2)->join('');
                $colors = ['from-purple-500 to-pink-500', 'from-blue-500 to-cyan-500', 'from-green-500 to-emerald-500', 'from-orange-500 to-red-500', 'from-indigo-500 to-purple-500'];
                $colorIndex = ord(substr($name, 0, 1)) % count($colors);
            @endphp
            <div class="w-7 h-7 {{ $isClosed ? 'bg-gray-400' : 'bg-gradient-to-br ' . $colors[$colorIndex] }} rounded-full flex items-center justify-center text-white text-[10px] font-bold shadow-sm flex-shrink-0"
                 title="{{ $name }}">
                {{ $initials }}
            </div>
            <div class="min-w-0">
                <div class="text-xs font-semibold {{ $isClosed ? 'text-gray-600' : 'text-slate-900' }} truncate max-w-[9rem]" title="{{ $name }}">
                    {{ $name }}
                </div>
                <div class="text-[11px] text-slate-500 truncate max-w-[9rem]" title="{{ $request->requester->email ?? 'Sin correo' }}">
                    {{ $request->requester->email ?? '' }}
                </div>
            </div>
        </div>
    </td>

    <!-- Fecha de la solicitud -->
    <td class="px-3 py-2.5 align-top whitespace-nowrap overflow-hidden">
        <div class="text-xs font-semibold text-gray-900 truncate" title="Fecha solicitud: {{ $request->created_at->format('d/m/Y H:i') }}">{{ $request->created_at->format('d/m/Y') }}</div>
        <div class="text-[11px] text-gray-500 truncate" title="Fecha solicitud: {{ $request->created_at->format('d/m/Y H:i') }}">{{ $request->created_at->locale('es')->diffForHumans() }}</div>
    </td>

    <!-- Acciones -->
    <td class="px-3 py-2.5 align-top whitespace-nowrap text-right overflow-visible">
        <x-service-requests.index.content.table-actions :request="$request" compact />
    </td>
</tr>
