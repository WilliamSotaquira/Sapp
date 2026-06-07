{{-- resources/views/service-requests/partials/_meeting-participants.blade.php --}}
{{-- Participant list + add form for meeting requests --}}
@php
    $participants = $meetingDetail->participants ?? collect();
    $canAddParticipants = in_array($serviceRequest->status, ['PENDIENTE', 'ACEPTADA', 'EN_PROCESO']);
    $canMarkAttendance = in_array($serviceRequest->status, ['RESUELTA', 'CERRADA']);
@endphp

<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-9 h-9 bg-teal-100 rounded-lg mr-3">
                    <i class="fas fa-users text-teal-600 text-sm"></i>
                </div>
                <div>
                    <h3 class="sr-card-title text-gray-900">Participantes</h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $participants->count() }} participante(s) registrado(s)</p>
                </div>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6">
        {{-- Participant list --}}
        @if($participants->isNotEmpty())
            <div class="space-y-2 mb-4">
                @foreach($participants as $participant)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center">
                                <i class="fas fa-user text-teal-600 text-xs"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $participant->name }}</p>
                                <p class="text-xs text-gray-500">{{ $participant->email }}</p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                @if($participant->role === 'organizador') bg-purple-100 text-purple-800
                                @elseif($participant->role === 'participante') bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($participant->role) }}
                            </span>
                            @if($participant->attended === true)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check mr-1"></i> Asistió
                                </span>
                            @elseif($participant->attended === false)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-times mr-1"></i> No asistió
                                </span>
                            @endif
                        </div>
                        @if($canAddParticipants)
                            <form action="{{ route('service-requests.meeting.participants.destroy', [$serviceRequest, $participant]) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-600 transition p-1" title="Eliminar participante">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-6 text-center text-gray-500 mb-4">
                <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 mb-2">
                    <i class="fas fa-user-slash text-gray-400"></i>
                </div>
                <p class="text-sm">No hay participantes registrados.</p>
            </div>
        @endif

        {{-- Attendance form --}}
        @if($canMarkAttendance && $participants->isNotEmpty())
            <div class="border-t border-gray-200 pt-4 mt-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Registrar Asistencia</h4>
                <form action="{{ route('service-requests.meeting.attendance', $serviceRequest) }}" method="POST">
                    @csrf
                    <div class="space-y-2 mb-3">
                        @foreach($participants as $participant)
                            <label class="flex items-center gap-3 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="attendance[{{ $participant->id }}]" value="1"
                                       {{ $participant->attended ? 'checked' : '' }}
                                       class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                                <span class="text-sm text-gray-700">{{ $participant->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white text-sm font-medium rounded-lg hover:bg-teal-700 focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition">
                        <i class="fas fa-check-double text-xs"></i> Guardar Asistencia
                    </button>
                </form>
            </div>
        @endif

        {{-- Add participant form --}}
        @if($canAddParticipants)
            <div class="border-t border-gray-200 pt-4 mt-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Agregar Participante</h4>
                <form action="{{ route('service-requests.meeting.participants.store', $serviceRequest) }}" method="POST" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <input type="text" name="name" placeholder="Nombre completo" required maxlength="255"
                                   value="{{ old('name') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm"
                                   aria-label="Nombre del participante">
                        </div>
                        <div>
                            <input type="email" name="email" placeholder="correo@ejemplo.com" required
                                   value="{{ old('email') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm"
                                   aria-label="Correo electrónico del participante">
                        </div>
                        <div class="flex gap-2">
                            <select name="role" required
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm"
                                    aria-label="Rol del participante">
                                <option value="participante">Participante</option>
                                <option value="organizador">Organizador</option>
                                <option value="invitado">Invitado</option>
                            </select>
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-teal-600 text-white text-sm font-medium rounded-lg hover:bg-teal-700 focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition whitespace-nowrap">
                                <i class="fas fa-plus text-xs"></i> Agregar
                            </button>
                        </div>
                    </div>
                    @if($errors->has('email'))
                        <p class="text-sm text-red-600">{{ $errors->first('email') }}</p>
                    @endif
                    <p class="text-xs text-gray-500">Máximo 50 participantes por reunión.</p>
                </form>
            </div>
        @endif
    </div>
</div>
