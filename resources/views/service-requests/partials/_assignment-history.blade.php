{{-- resources/views/service-requests/partials/_assignment-history.blade.php --}}
{{-- Assignment history timeline --}}

<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center">
            <div class="flex items-center justify-center w-9 h-9 bg-orange-100 rounded-lg mr-3">
                <i class="fas fa-exchange-alt text-orange-600 text-sm"></i>
            </div>
            <div>
                <h3 class="sr-card-title text-gray-900">Historial de Asignaciones</h3>
                <p class="text-xs text-gray-500 mt-0.5">{{ $assignmentHistory->count() }} cambio(s) de asignación</p>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6">
        @if($assignmentHistory->isNotEmpty())
            <div class="relative">
                {{-- Timeline line --}}
                <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>

                <div class="space-y-4">
                    @foreach($assignmentHistory as $record)
                        <div class="relative flex gap-4 pl-10">
                            {{-- Timeline dot --}}
                            <div class="absolute left-2.5 top-1.5 w-3.5 h-3.5 rounded-full border-2 border-white shadow-sm
                                {{ $loop->first ? 'bg-orange-400' : 'bg-gray-300' }}"></div>

                            <div class="flex-1 min-w-0 pb-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs text-gray-500">
                                        {{ $record->created_at->format('d/m/Y H:i') }}
                                    </span>
                                    <span class="text-xs text-gray-400">•</span>
                                    <span class="text-xs text-gray-500">
                                        por {{ $record->changedBy->name ?? 'Sistema' }}
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center gap-2 text-sm">
                                    <span class="text-gray-600">
                                        {{ $record->previousAssignee->name ?? 'Sin asignar' }}
                                    </span>
                                    <i class="fas fa-arrow-right text-gray-400 text-xs"></i>
                                    <span class="font-medium text-gray-900">
                                        {{ $record->newAssignee->name ?? 'Sin asignar' }}
                                    </span>
                                </div>
                                @if($record->reason)
                                    <p class="mt-1 text-xs text-gray-500 italic">
                                        "{{ Str::limit($record->reason, 100) }}"
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="py-6 text-center text-gray-500">
                <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 mb-2">
                    <i class="fas fa-history text-gray-400"></i>
                </div>
                <p class="text-sm">No hay cambios de asignación registrados.</p>
            </div>
        @endif
    </div>
</div>
