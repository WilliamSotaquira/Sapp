{{-- resources/views/service-requests/partials/_meeting-details-show.blade.php --}}
{{-- Meeting details display (read-only) + edit form when status is PENDIENTE --}}
@php
    $canEdit = $serviceRequest->status === 'PENDIENTE';
@endphp

<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-9 h-9 bg-indigo-100 rounded-lg mr-3">
                    <i class="fas fa-calendar-alt text-indigo-600 text-sm"></i>
                </div>
                <div>
                    <h3 class="sr-card-title text-gray-900">Detalles de Reunión</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Programación y ubicación</p>
                </div>
            </div>
            @if($canEdit)
                <button type="button"
                    x-data
                    @click="$dispatch('toggle-meeting-edit')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition">
                    <i class="fas fa-pencil-alt text-[10px]"></i>
                    Editar
                </button>
            @endif
        </div>
    </div>

    <div class="p-4 sm:p-6" x-data="{ editing: false }" @toggle-meeting-edit.window="editing = !editing">
        {{-- Read-only display --}}
        <div x-show="!editing" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Fecha Programada</p>
                <p class="mt-1 text-sm text-gray-900 font-medium">
                    {{ $meetingDetail->scheduled_date->format('d/m/Y') }}
                </p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Hora de Inicio</p>
                <p class="mt-1 text-sm text-gray-900 font-medium">
                    {{ $meetingDetail->start_time }}
                </p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Duración Esperada</p>
                <p class="mt-1 text-sm text-gray-900 font-medium">
                    {{ $meetingDetail->expected_duration_minutes }} minutos
                </p>
            </div>
            @if($meetingDetail->location)
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Ubicación</p>
                    <p class="mt-1 text-sm text-gray-900">{{ $meetingDetail->location }}</p>
                </div>
            @endif
            @if($meetingDetail->virtual_meeting_url)
                <div class="sm:col-span-2">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">URL Reunión Virtual</p>
                    <a href="{{ $meetingDetail->virtual_meeting_url }}" target="_blank" rel="noopener noreferrer"
                       class="mt-1 text-sm text-blue-600 hover:text-blue-800 hover:underline inline-flex items-center gap-1 break-all">
                        {{ Str::limit($meetingDetail->virtual_meeting_url, 60) }}
                        <i class="fas fa-external-link-alt text-[10px]"></i>
                    </a>
                </div>
            @endif
        </div>

        {{-- Edit form (only if PENDIENTE) --}}
        @if($canEdit)
            <div x-show="editing" x-cloak>
                <form action="{{ route('service-requests.meeting.update-details', $serviceRequest) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label for="edit_scheduled_date" class="block text-sm font-medium text-gray-700 mb-1">Fecha Programada <span class="text-red-500">*</span></label>
                            <input type="date" name="scheduled_date" id="edit_scheduled_date"
                                   value="{{ $meetingDetail->scheduled_date->format('Y-m-d') }}"
                                   min="{{ date('Y-m-d') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>
                        <div>
                            <label for="edit_start_time" class="block text-sm font-medium text-gray-700 mb-1">Hora de Inicio <span class="text-red-500">*</span></label>
                            <input type="time" name="start_time" id="edit_start_time"
                                   value="{{ $meetingDetail->start_time }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>
                        <div>
                            <label for="edit_expected_duration" class="block text-sm font-medium text-gray-700 mb-1">Duración (min) <span class="text-red-500">*</span></label>
                            <input type="number" name="expected_duration_minutes" id="edit_expected_duration"
                                   value="{{ $meetingDetail->expected_duration_minutes }}"
                                   min="5" max="480" step="5"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>
                        <div>
                            <label for="edit_location" class="block text-sm font-medium text-gray-700 mb-1">Ubicación</label>
                            <input type="text" name="location" id="edit_location"
                                   value="{{ $meetingDetail->location }}"
                                   maxlength="255"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="edit_virtual_url" class="block text-sm font-medium text-gray-700 mb-1">URL Reunión Virtual</label>
                            <input type="url" name="virtual_meeting_url" id="edit_virtual_url"
                                   value="{{ $meetingDetail->virtual_meeting_url }}"
                                   maxlength="2048"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        </div>
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                            <i class="fas fa-save text-xs"></i> Guardar Cambios
                        </button>
                        <button type="button" @click="editing = false"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
