{{-- resources/views/service-requests/partials/_derive-request.blade.php --}}
{{-- Derived requests list + create button --}}

<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-9 h-9 bg-violet-100 rounded-lg mr-3">
                    <i class="fas fa-code-branch text-violet-600 text-sm"></i>
                </div>
                <div>
                    <h3 class="sr-card-title text-gray-900">Solicitudes Derivadas</h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $childRequests->count() }} solicitud(es) derivada(s)</p>
                </div>
            </div>
            <a href="{{ route('service-requests.create', ['service_request_id' => $serviceRequest->id]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-violet-700 bg-violet-50 border border-violet-200 rounded-lg hover:bg-violet-100 transition">
                <i class="fas fa-plus text-[10px]"></i>
                Crear Solicitud Derivada
            </a>
        </div>
    </div>

    <div class="p-4 sm:p-6">
        @if($childRequests->isNotEmpty())
            <div class="space-y-2">
                @foreach($childRequests as $child)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100 hover:bg-gray-100 transition">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-ticket-alt text-violet-400 text-xs"></i>
                            <a href="{{ route('service-requests.show', $child) }}"
                               class="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                {{ $child->ticket_number }}
                            </a>
                        </div>
                        @php
                            $childStatusColors = [
                                'PENDIENTE' => 'bg-yellow-100 text-yellow-800',
                                'ACEPTADA' => 'bg-blue-100 text-blue-800',
                                'EN_PROCESO' => 'bg-indigo-100 text-indigo-800',
                                'RESUELTA' => 'bg-green-100 text-green-800',
                                'CERRADA' => 'bg-gray-100 text-gray-800',
                                'CANCELADA' => 'bg-red-100 text-red-800',
                                'RECHAZADA' => 'bg-red-100 text-red-800',
                                'PAUSADA' => 'bg-orange-100 text-orange-800',
                                'REABIERTO' => 'bg-purple-100 text-purple-800',
                            ];
                            $childStatusColor = $childStatusColors[$child->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $childStatusColor }}">
                            {{ $child->status }}
                        </span>
                    </div>
                @endforeach

                @if($childRequests->count() >= 50)
                    <p class="text-xs text-gray-400 text-center pt-2 italic">
                        Mostrando un máximo de 50 solicitudes derivadas.
                    </p>
                @endif
            </div>
        @else
            <div class="py-6 text-center text-gray-500">
                <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 mb-2">
                    <i class="fas fa-code-branch text-gray-400"></i>
                </div>
                <p class="text-sm">No hay solicitudes derivadas.</p>
                <p class="text-xs text-gray-400 mt-1">Puedes crear una solicitud derivada usando el botón "Crear Solicitud Derivada".</p>
            </div>
        @endif
    </div>
</div>
