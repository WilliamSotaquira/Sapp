@props(['serviceRequest'])

@if(in_array($serviceRequest->status, ['ACEPTADA', 'EN_PROCESO', 'RESUELTA', 'PAUSADA']))
    @php
        $statusInfo = [
            'ACEPTADA' => [
                'icon' => 'user-check',
                'text' => $serviceRequest->accepted_at ? 'Aceptada el ' . $serviceRequest->accepted_at->format('d/m/Y H:i') : 'Aceptada'
            ],
            'EN_PROCESO' => [
                'icon' => 'cog',
                'text' => $serviceRequest->started_at ? 'En proceso desde ' . $serviceRequest->started_at->format('d/m/Y H:i') : 'En proceso'
            ],
            'RESUELTA' => [
                'icon' => 'check-double',
                'text' => $serviceRequest->resolved_at ? 'Resuelta el ' . $serviceRequest->resolved_at->format('d/m/Y H:i') : 'Resuelta'
            ],
            'PAUSADA' => [
                'icon' => 'pause',
                'text' => $serviceRequest->paused_at ? 'Pausada desde ' . $serviceRequest->paused_at->format('d/m/Y H:i') : 'Pausada'
            ]
        ][$serviceRequest->status];
    @endphp

    <div class="bg-white/20 backdrop-blur-sm px-4 py-2 border-2 rounded-full text-sm font-semibold flex items-center border border-white/30 leading-tight">
        <i class="fas fa-{{ $statusInfo['icon'] }} mr-2"></i>
        <span>{{ $statusInfo['text'] }}</span>
    </div>
@endif
