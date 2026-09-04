    <!-- Modal: Finalizar por No Viabilidad -->
    <div id="finalize-non-viable-modal-{{ $serviceRequest->id }}"
        class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center p-4 z-50"
        role="dialog"
        aria-modal="true"
        aria-hidden="true"
        aria-labelledby="finalize-non-viable-modal-title-{{ $serviceRequest->id }}"
        tabindex="-1">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 id="finalize-non-viable-modal-title-{{ $serviceRequest->id }}" class="text-lg font-medium text-gray-900">
                    Finalizar por No Viabilidad #{{ $serviceRequest->ticket_number }}
                </h3>
                <button type="button"
                    onclick="closeModal('finalize-non-viable-modal-{{ $serviceRequest->id }}')"
                    class="text-gray-400 hover:text-gray-500 text-xl"
                    aria-label="Cerrar diálogo">
                    ✕
                </button>
            </div>

            <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-md">
                <p class="text-sm text-amber-800">
                    Esta acción cierra la solicitud como <strong>No viable</strong>. Úsala cuando, tras la validación,
                    se identifica que la solicitud no cumple las características necesarias. La solicitud cuenta como
                    gestión realizada dentro de las métricas, en su propia clasificación.
                </p>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                    <ul class="text-sm text-red-600">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $hasSupportEvidence = $serviceRequest->evidences()
                    ->whereIn('evidence_type', ['ARCHIVO', 'PASO_A_PASO', 'ENLACE'])
                    ->exists();
            @endphp

            <form action="{{ route('service-requests.finalize-non-viable', $serviceRequest) }}" method="POST">
                @csrf
                @method('POST')

                <div class="space-y-4">
                    <div>
                        <label for="non_viable_reason-{{ $serviceRequest->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                            Concepto de no viabilidad *
                        </label>
                        <textarea name="non_viable_reason" id="non_viable_reason-{{ $serviceRequest->id }}" rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 bg-white focus:ring-amber-500 focus:border-amber-500"
                            placeholder="Describe la validación realizada y por qué la solicitud no es viable..." required minlength="10">{{ old('non_viable_reason') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Mínimo 10 caracteres. Registra el concepto técnico que
                            respalda la no viabilidad.</p>
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        @if($hasSupportEvidence)
                            <div class="flex items-start gap-2 p-3 bg-green-50 border border-green-200 rounded-md">
                                <i class="fas fa-check-circle text-green-600 mt-0.5"></i>
                                <p class="text-xs text-green-800">
                                    Esta solicitud ya tiene evidencias registradas que respaldan el concepto.
                                </p>
                            </div>
                        @else
                            <div class="flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-md">
                                <i class="fas fa-exclamation-triangle text-amber-600 mt-0.5"></i>
                                <p class="text-xs text-amber-800">
                                    Debes registrar al menos una evidencia como soporte antes de finalizar.
                                    Agrégala en la sección <strong>Evidencias</strong> de la solicitud.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button"
                        onclick="closeModal('finalize-non-viable-modal-{{ $serviceRequest->id }}')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        Cancelar
                    </button>
                    <button type="submit"
                        @unless($hasSupportEvidence) disabled title="Registra una evidencia de soporte antes de finalizar" @endunless
                        class="px-4 py-2 text-sm font-medium text-white bg-amber-600 border border-transparent rounded-md hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-amber-600">
                        Confirmar No Viable
                    </button>
                </div>
            </form>
        </div>
    </div>
