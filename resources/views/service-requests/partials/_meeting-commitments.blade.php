{{-- resources/views/service-requests/partials/_meeting-commitments.blade.php --}}
{{-- Commitment management section for Meeting Requests --}}
{{-- Only shown when status is EN_PROCESO, RESUELTA, or REABIERTO --}}
@php
    $allowedStatuses = ['EN_PROCESO', 'RESUELTA', 'REABIERTO'];
    $canShowCommitments = in_array($serviceRequest->status, $allowedStatuses);
    $allCompleted = $commitments->isNotEmpty() && $commitments->every(fn($c) => strtolower($c->status) === 'completed');
@endphp

@if($canShowCommitments)
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-9 h-9 bg-amber-100 rounded-lg mr-3">
                    <i class="fas fa-handshake text-amber-600 text-sm"></i>
                </div>
                <div>
                    <h3 class="sr-card-title text-gray-900">Compromisos</h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $commitments->count() }} compromiso(s)</p>
                </div>
            </div>
            @if($allCompleted)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg">
                    <i class="fas fa-check-circle"></i> Todos cumplidos
                </span>
            @endif
        </div>
    </div>

    <div class="p-4 sm:p-6">
        {{-- Commitment list --}}
        @if($commitments->isNotEmpty())
            <div class="space-y-2 mb-4">
                @foreach($commitments as $commitment)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $commitment->title }}</p>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    @if(strtolower($commitment->status) === 'completed') bg-green-100 text-green-800
                                    @elseif(strtolower($commitment->status) === 'in_progress') bg-blue-100 text-blue-800
                                    @elseif(strtolower($commitment->status) === 'blocked') bg-red-100 text-red-800
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ ucfirst(str_replace('_', ' ', $commitment->status)) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
                                <span>
                                    <i class="fas fa-user mr-1"></i>
                                    {{ $commitment->technician->user->name ?? 'Sin asignar' }}
                                </span>
                                @if($commitment->due_date)
                                    <span>
                                        <i class="fas fa-calendar mr-1"></i>
                                        {{ \Carbon\Carbon::parse($commitment->due_date)->format('d/m/Y') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-6 text-center text-gray-500 mb-4">
                <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 mb-2">
                    <i class="fas fa-clipboard-list text-gray-400"></i>
                </div>
                <p class="text-sm">No hay compromisos registrados.</p>
            </div>
        @endif

        {{-- Add commitment form --}}
        @if($canShowCommitments)
            <div class="border-t border-gray-200 pt-4 mt-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Nuevo Compromiso</h4>
                <form action="{{ route('service-requests.meeting.commitments.store', $serviceRequest) }}" method="POST" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label for="commitment_title" class="block text-xs font-medium text-gray-700 mb-1">Título <span class="text-red-500">*</span></label>
                            <input type="text" name="title" id="commitment_title" required maxlength="255"
                                   value="{{ old('title') }}" placeholder="Título del compromiso"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
                            @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="commitment_description" class="block text-xs font-medium text-gray-700 mb-1">Descripción <span class="text-red-500">*</span></label>
                            <textarea name="description" id="commitment_description" required rows="2" maxlength="2000"
                                      placeholder="Descripción del compromiso"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">{{ old('description') }}</textarea>
                            @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="commitment_technician" class="block text-xs font-medium text-gray-700 mb-1">Responsable <span class="text-red-500">*</span></label>
                            <select name="technician_id" id="commitment_technician" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
                                <option value="">Seleccionar...</option>
                                @foreach($technicians as $technician)
                                    <option value="{{ $technician->id }}" {{ old('technician_id') == $technician->id ? 'selected' : '' }}>
                                        {{ $technician->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('technician_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="commitment_due_date" class="block text-xs font-medium text-gray-700 mb-1">Fecha Límite <span class="text-red-500">*</span></label>
                            <input type="date" name="due_date" id="commitment_due_date" required
                                   value="{{ old('due_date') }}" min="{{ date('Y-m-d') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
                            @error('due_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="pt-2">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition">
                            <i class="fas fa-plus text-xs"></i> Crear Compromiso
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
@endif
