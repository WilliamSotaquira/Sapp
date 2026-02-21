{{-- resources/views/service-requests/edit.blade.php --}}
@extends('layouts.app')

@section('title', "Editar Solicitud {$serviceRequest->ticket_number}")

@section('content')
<form action="{{ route('service-requests.update', $serviceRequest) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-blue-100">
                <h2 class="text-xl font-bold text-gray-800">
                    Editar Solicitud: {{ $serviceRequest->ticket_number }}
                </h2>
            </div>
            <div class="p-6">
                @include('components.service-requests.forms.basic-fields', [
                    'serviceRequest' => $serviceRequest,
                    'subServices' => $subServices,
                    'selectedSubService' => $selectedSubService ?? null,
                    'requesters' => $requesters,
                    'companies' => $companies ?? [],
                    'cuts' => $cuts ?? [],
                    'errors' => $errors,
                    'mode' => 'edit'
                ])
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row justify-end gap-3">
                <a href="{{ route('service-requests.show', $serviceRequest) }}"
                   class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-xl text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 hover:text-gray-900 transition-all duration-200 font-medium shadow-sm hover:shadow-md">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center px-8 py-3 border border-transparent rounded-xl text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 transition-all duration-200 font-semibold shadow-md hover:shadow-lg">
                    <i class="fas fa-save mr-2"></i>
                    Actualizar Solicitud
                </button>
            </div>
            <p id="editFormInlineError" class="hidden mt-2 text-sm text-red-600 font-medium"></p>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const formEl = document.querySelector('form[action="{{ route('service-requests.update', $serviceRequest) }}"]');
    const inlineErrorEl = document.getElementById('editFormInlineError');
    let updateConfirmed = false;

    if (!formEl) return;

    function setFieldValidity(el, ok) {
        if (!el) return;
        el.classList.remove('border-red-500');
        if (!ok) el.classList.add('border-red-500');
    }

    function validateMainFields() {
        const title = document.getElementById('title');
        const description = document.getElementById('description');
        const requester = document.getElementById('requester_id');
        const subService = document.getElementById('sub_service_id');
        const entryChannel = document.getElementById('entry_channel');

        const checks = [
            { el: title, ok: !!title?.value?.trim(), label: 'Título' },
            { el: description, ok: !!description?.value?.trim(), label: 'Descripción' },
            { el: requester, ok: !!requester?.value, label: 'Solicitante' },
            { el: subService, ok: !!subService?.value, label: 'Subservicio' },
            { el: entryChannel, ok: !!entryChannel?.value, label: 'Canal de ingreso' },
        ];

        checks.forEach(({ el, ok }) => setFieldValidity(el, ok));
        const missing = checks.filter(c => !c.ok).map(c => c.label);
        return { valid: missing.length === 0, missing };
    }

    function buildSummaryText() {
        const title = document.getElementById('title')?.value?.trim() || '(sin título)';
        const requester = document.getElementById('requester_id');
        const subService = document.getElementById('sub_service_id');
        const channel = document.getElementById('entry_channel');
        const cut = document.getElementById('cut_id');

        const requesterText = requester?.selectedOptions?.[0]?.textContent?.trim() || 'Sin solicitante';
        const subServiceText = subService?.selectedOptions?.[0]?.textContent?.trim() || 'Sin subservicio';
        const channelText = channel?.selectedOptions?.[0]?.textContent?.trim() || 'Sin canal';
        const cutText = cut?.selectedOptions?.[0]?.textContent?.trim() || 'Sin corte';

        return [
            'Resumen de cambios:',
            `- Título: ${title}`,
            `- Solicitante: ${requesterText}`,
            `- Subservicio: ${subServiceText}`,
            `- Canal: ${channelText}`,
            `- Corte: ${cutText}`,
            '',
            '¿Deseas actualizar la solicitud?'
        ].join('\n');
    }

    ['title', 'description', 'requester_id', 'sub_service_id', 'entry_channel'].forEach((id) => {
        const field = document.getElementById(id);
        if (!field) return;
        field.addEventListener('input', validateMainFields);
        field.addEventListener('change', validateMainFields);
    });

    formEl.addEventListener('submit', function (e) {
        if (updateConfirmed) return;

        const validation = validateMainFields();
        if (!validation.valid) {
            e.preventDefault();
            if (inlineErrorEl) {
                inlineErrorEl.textContent = `Completa los campos obligatorios: ${validation.missing.join(', ')}.`;
                inlineErrorEl.classList.remove('hidden');
            }
            const firstInvalid = formEl.querySelector('.border-red-500');
            firstInvalid?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid?.focus();
            return;
        }

        if (inlineErrorEl) {
            inlineErrorEl.classList.add('hidden');
            inlineErrorEl.textContent = '';
        }

        e.preventDefault();
        const confirmed = window.confirm(buildSummaryText());
        if (!confirmed) return;

        updateConfirmed = true;
        formEl.submit();
    });
});
</script>
@endsection
