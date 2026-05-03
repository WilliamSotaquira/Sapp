@props(['serviceRequest'])

@php
    $entryChannelOptions = \App\Models\ServiceRequest::getEntryChannelOptions();
    $selectedEntryChannel = $serviceRequest->entry_channel;
    $isDead = in_array($serviceRequest->status, ['CERRADA', 'CANCELADA', 'RECHAZADA']);
    $hasDueDate = $serviceRequest->hasRequestDueDate();
    $isFinalForDueDate = in_array(strtoupper((string) $serviceRequest->status), ['RESUELTA', 'CERRADA', 'CANCELADA', 'RECHAZADA'], true);
    $dueDays = $serviceRequest->daysUntilRequestDue();
    $dueTone = 'bg-slate-50 text-slate-700 border-slate-200';
    $dueLabel = 'Sin vencimiento';

    if ($hasDueDate) {
        if ($isFinalForDueDate) {
            $dueTone = 'bg-slate-50 text-slate-600 border-slate-200';
            $dueLabel = 'Registrado';
        } elseif ($serviceRequest->isRequestDueOverdue()) {
            $dueTone = 'bg-red-50 text-red-700 border-red-200';
            $dueLabel = 'Vencida';
        } elseif ($serviceRequest->isRequestDueSoon()) {
            $dueTone = 'bg-amber-50 text-amber-700 border-amber-200';
            $dueLabel = $dueDays === 0 ? 'Vence hoy' : 'Por vencer';
        } else {
            $dueTone = 'bg-emerald-50 text-emerald-700 border-emerald-200';
            $dueLabel = 'En plazo';
        }
    }
@endphp

<div class="h-full bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="{{ $isDead ? 'bg-gray-100 border-gray-300' : 'bg-slate-50 border-gray-200' }} px-5 py-3 border-b">
        <h3 class="sr-card-title text-gray-800 flex items-center">
            <i class="fas fa-cogs {{ $isDead ? 'text-gray-500' : 'text-blue-600' }} mr-2"></i>
            Información del Servicio
        </h3>
    </div>
    <div class="p-5">
        @php
            $familyName = $serviceRequest->subService?->service?->family?->name;
            $serviceName = $serviceRequest->subService?->service?->name;
            $subServiceName = $serviceRequest->subService?->name;
            $serviceLabel = trim(collect([$familyName, $serviceName, $subServiceName])->filter()->join(' · '));
            $contract = $serviceRequest->subService?->service?->family?->contract;
            $contractLabel = $contract ? ($contract->name ?: $contract->number) : null;
            $selectedOption = $selectedEntryChannel && isset($entryChannelOptions[$selectedEntryChannel])
                ? $entryChannelOptions[$selectedEntryChannel]
                : null;
            $cuts = $serviceRequest->cuts ?? collect();
        @endphp

        <dl class="divide-y divide-gray-100">
            <div class="pb-3">
                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Servicio</dt>
                <dd class="mt-1 text-sm text-gray-950 font-semibold leading-snug break-words">
                    {{ $serviceLabel ?: 'N/A' }}
                </dd>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 py-3">
                <div>
                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Espacio</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $serviceRequest->company->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Contrato</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $contractLabel ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $serviceRequest->status }} · {{ $serviceRequest->criticality_level }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Canal</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $selectedOption['label'] ?? 'No registrado' }}</dd>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 pt-3">
                <div>
                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Vencimiento</dt>
                    <dd class="mt-1">
                        @if ($hasDueDate)
                            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md border {{ $dueTone }} text-xs font-semibold">
                                <i class="fas fa-calendar-check"></i>
                                <span>{{ ucfirst($serviceRequest->due_date->locale('es')->translatedFormat('j M Y')) }}</span>
                                <span>{{ $dueLabel }}</span>
                            </div>
                            @if (!$isDead && $dueDays !== null)
                                <p class="mt-1 text-xs text-gray-500">
                                    @if ($dueDays < 0)
                                        {{ abs($dueDays) }} día(s) vencida.
                                    @elseif ($dueDays === 0)
                                        Requiere seguimiento hoy.
                                    @else
                                        Faltan {{ $dueDays }} día(s).
                                    @endif
                                </p>
                            @endif
                        @else
                            <span class="text-sm text-gray-500">Sin fecha de vencimiento</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Corte</dt>
                    <dd id="cutAssociationContainer" class="mt-1">
                        @if ($cuts->isEmpty())
                            <p class="text-sm text-gray-500">Sin corte asociado</p>
                            <p class="mt-1 text-xs text-gray-500">Se calcula con la fecha de la solicitud.</p>
                        @else
                            @foreach ($cuts as $cut)
                                <a href="{{ route('reports.cuts.show', $cut) }}" class="flex max-w-full flex-col items-start gap-1 rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-2 text-indigo-700 transition hover:bg-indigo-100">
                                    <span class="inline-flex items-center gap-2 text-xs font-semibold leading-tight">
                                        <i class="fas fa-cut shrink-0"></i>
                                        <span class="break-words">{{ $cut->name }}</span>
                                    </span>
                                    <span class="pl-5 text-[11px] font-medium leading-tight text-indigo-500">
                                        {{ ucfirst($cut->start_date->locale('es')->translatedFormat('j M Y')) }}
                                        <span class="text-indigo-300">al</span>
                                        {{ ucfirst($cut->end_date->locale('es')->translatedFormat('j M Y')) }}
                                    </span>
                                </a>
                            @endforeach
                            <p class="mt-1 text-xs text-gray-500">Automático por fecha de la solicitud.</p>
                        @endif
                    </dd>
                </div>
            </div>
        </dl>
    </div>
</div>
