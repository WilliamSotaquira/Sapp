@props(['serviceRequest'])

@php
    $isDead = in_array($serviceRequest->status, ['CERRADA', 'CANCELADA', 'RECHAZADA', 'NO_VIABLE']);
@endphp

<x-service-requests.show.sr-card
    title="Descripción"
    icon="fa-align-left"
    iconColor="text-blue-500"
    :headerBg="$isDead ? null : 'bg-blue-50/50 border-blue-100'"
    :dead="$isDead">

    <div class="space-y-4">
        <div>
            <label class="text-sm font-medium text-gray-500 block mb-2">Descripción</label>
            <div class="prose max-w-none">
                <p class="text-gray-700 whitespace-pre-line">{{ $serviceRequest->description }}</p>
            </div>
        </div>

        @if($serviceRequest->additional_notes)
        <div>
            <label class="text-sm font-medium text-gray-500 block mb-2">Notas Adicionales</label>
            <div class="prose max-w-none">
                <p class="text-gray-700 whitespace-pre-line">{{ $serviceRequest->additional_notes }}</p>
            </div>
        </div>
        @endif

        @if($serviceRequest->solution_details)
        <div>
            <label class="text-sm font-medium text-gray-500 block mb-2">Detalles de la Solución</label>
            <div class="prose max-w-none">
                <p class="text-gray-700 whitespace-pre-line">{{ $serviceRequest->solution_details }}</p>
            </div>
        </div>
        @endif

        @if($serviceRequest->resolution_notes)
        @php
            $rawNotes = $serviceRequest->resolution_notes;
            $rawNotes = preg_replace('/\s*===\s*CIERRE(?:\s+POR\s+VENCIMIENTO|\s+NORMAL)\s*===\s*\n?(?:Fecha\/Hora:.*\n?)?(?:Usuario:.*\n?)?/', '', $rawNotes);
            $rawNotes = trim($rawNotes);

            $sections = [];
            if (str_contains($rawNotes, "Acciones realizadas:\n")) {
                $parts = explode("\n\nNotas adicionales:\n", str_replace("Acciones realizadas:\n", '', $rawNotes), 2);
                $sections['actions'] = trim($parts[0] ?? '');
                $actionParts = explode("\n\nObservaciones de cierre:\n", $sections['actions'], 2);
                $sections['actions'] = trim($actionParts[0] ?? '');
                $sections['closure'] = trim($actionParts[1] ?? '');
                $sections['extra'] = trim($parts[1] ?? '');
            } else {
                $sections['actions'] = trim($rawNotes);
                $sections['extra'] = '';
                $sections['closure'] = '';
            }

            if (str_starts_with($sections['actions'], 'Motivo: ')) {
                $sections['closure'] = $sections['closure'] ?: substr($sections['actions'], 8);
                $sections['actions'] = '';
            }
        @endphp
        @if($sections['actions'] || $sections['extra'] || $sections['closure'])
        <div class="border-t border-gray-200 pt-5 mt-5">
            <div class="flex items-center gap-2 mb-4">
                <div class="flex items-center justify-center w-7 h-7 bg-green-100 rounded-full">
                    <i class="fas fa-check-circle text-green-600 text-xs"></i>
                </div>
                <h4 class="text-sm font-semibold text-gray-800">Resolución</h4>
                @if($serviceRequest->resolved_at)
                    <span class="text-xs text-gray-400 ml-auto">
                        {{ \Carbon\Carbon::parse($serviceRequest->resolved_at)->format('d/m/Y H:i') }}
                    </span>
                @endif
            </div>

            @if($sections['actions'])
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 mb-3">
                <p class="text-xs font-medium text-green-700 uppercase tracking-wide mb-2">Acciones realizadas</p>
                <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-line">{{ $sections['actions'] }}</p>
            </div>
            @endif

            @if($sections['extra'])
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-3">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Notas adicionales</p>
                <p class="text-gray-600 text-sm leading-relaxed whitespace-pre-line">{{ $sections['extra'] }}</p>
            </div>
            @endif

            @if($sections['closure'])
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <p class="text-xs font-medium text-amber-700 uppercase tracking-wide mb-2">Observaciones de cierre</p>
                <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-line">{{ $sections['closure'] }}</p>
            </div>
            @endif
        </div>
        @endif
        @endif
    </div>

</x-service-requests.show.sr-card>
