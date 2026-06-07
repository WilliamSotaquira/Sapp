{{-- resources/views/service-requests/partials/_traceability-chain.blade.php --}}
{{-- Traceability chain tree view --}}
{{-- $traceabilityChain is an array with 'root' and 'children' nodes --}}

<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center">
            <div class="flex items-center justify-center w-9 h-9 bg-cyan-100 rounded-lg mr-3">
                <i class="fas fa-project-diagram text-cyan-600 text-sm"></i>
            </div>
            <div>
                <h3 class="sr-card-title text-gray-900">Cadena de Trazabilidad</h3>
                <p class="text-xs text-gray-500 mt-0.5">Relación entre solicitudes padre-hijo</p>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6">
        @if(isset($traceabilityChain['root']))
            <div class="space-y-1">
                {{-- Root node --}}
                @include('service-requests.partials._traceability-node', [
                    'node' => $traceabilityChain['root'],
                    'depth' => 0,
                    'isCurrentRequest' => ($traceabilityChain['root']['id'] ?? null) === $serviceRequest->id
                ])

                {{-- Children (recursive) --}}
                @if(!empty($traceabilityChain['root']['children']))
                    <div class="ml-6 border-l-2 border-cyan-200 pl-4 space-y-1">
                        @foreach($traceabilityChain['root']['children'] as $child)
                            @include('service-requests.partials._traceability-node', [
                                'node' => $child,
                                'depth' => 1,
                                'isCurrentRequest' => ($child['id'] ?? null) === $serviceRequest->id
                            ])

                            @if(!empty($child['children']))
                                <div class="ml-6 border-l-2 border-cyan-100 pl-4 space-y-1">
                                    @foreach($child['children'] as $grandchild)
                                        @include('service-requests.partials._traceability-node', [
                                            'node' => $grandchild,
                                            'depth' => 2,
                                            'isCurrentRequest' => ($grandchild['id'] ?? null) === $serviceRequest->id
                                        ])

                                        @if(!empty($grandchild['children']))
                                            <div class="ml-6 border-l-2 border-gray-100 pl-4 space-y-1">
                                                @foreach($grandchild['children'] as $deepChild)
                                                    @include('service-requests.partials._traceability-node', [
                                                        'node' => $deepChild,
                                                        'depth' => 3,
                                                        'isCurrentRequest' => ($deepChild['id'] ?? null) === $serviceRequest->id
                                                    ])

                                                    {{-- Truncation indicator at max depth --}}
                                                    @if(!empty($deepChild['child_requests_count']) && $deepChild['child_requests_count'] > 0)
                                                        <div class="ml-6 pl-4 py-1">
                                                            <span class="text-xs text-gray-400 italic">
                                                                <i class="fas fa-ellipsis-h mr-1"></i>
                                                                {{ $deepChild['child_requests_count'] }} solicitud(es) adicional(es) no mostrada(s)
                                                            </span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @elseif(!empty($grandchild['child_requests_count']) && $grandchild['child_requests_count'] > 0)
                                            <div class="ml-6 pl-4 py-1">
                                                <span class="text-xs text-gray-400 italic">
                                                    <i class="fas fa-ellipsis-h mr-1"></i>
                                                    {{ $grandchild['child_requests_count'] }} solicitud(es) adicional(es) no mostrada(s)
                                                </span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- Commitments as nodes --}}
                @if(!empty($traceabilityChain['commitments']))
                    <div class="ml-6 border-l-2 border-amber-200 pl-4 space-y-1 mt-2">
                        <p class="text-xs font-medium text-amber-700 uppercase tracking-wide mb-1">
                            <i class="fas fa-handshake mr-1"></i> Compromisos
                        </p>
                        @foreach($traceabilityChain['commitments'] as $commitment)
                            <div class="flex items-center gap-2 py-1.5 px-2 rounded hover:bg-amber-50 transition">
                                <i class="fas fa-clipboard-check text-amber-500 text-xs"></i>
                                <span class="text-sm text-gray-900">{{ $commitment['title'] }}</span>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium
                                    @if(strtolower($commitment['status'] ?? '') === 'completed') bg-green-100 text-green-700
                                    @elseif(strtolower($commitment['status'] ?? '') === 'in_progress') bg-blue-100 text-blue-700
                                    @else bg-yellow-100 text-yellow-700 @endif">
                                    {{ ucfirst(str_replace('_', ' ', $commitment['status'] ?? 'pending')) }}
                                </span>
                                @if(!empty($commitment['assigned_to']))
                                    <span class="text-xs text-gray-500">— {{ $commitment['assigned_to'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <p class="text-sm text-gray-500 text-center py-4">No se pudo construir la cadena de trazabilidad.</p>
        @endif
    </div>
</div>
