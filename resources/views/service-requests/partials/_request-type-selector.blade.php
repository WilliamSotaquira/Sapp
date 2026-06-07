{{-- resources/views/service-requests/partials/_request-type-selector.blade.php --}}
@props([
    'requestTypes' => collect(),
    'errors' => null,
])

@php
    $selectedRequestTypeId = old('request_type_id', '');
    $requestTypeBorderClass = $errors && $errors->has('request_type_id') ? 'border-red-500' : 'border-gray-300';

    // Determine the slug of the currently selected type for Alpine.js initialization
    $selectedSlug = '';
    if ($selectedRequestTypeId) {
        $selectedType = $requestTypes->firstWhere('id', (int) $selectedRequestTypeId);
        $selectedSlug = $selectedType ? $selectedType->slug : '';
    }
@endphp

<div x-data="{ selectedTypeSlug: '{{ $selectedSlug }}' }">
    <!-- Tipo de Solicitud (opcional) -->
    <div>
        <label for="request_type_id" class="block text-sm font-medium text-gray-700 mb-2">
            Tipo de Solicitud
        </label>
        <select
            name="request_type_id"
            id="request_type_id"
            class="w-full px-4 py-3 border {{ $requestTypeBorderClass }} rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
            x-on:change="selectedTypeSlug = $event.target.selectedOptions[0]?.dataset?.slug || ''"
        >
            <option value="" data-slug="">Sin tipo específico</option>
            @foreach ($requestTypes as $type)
                <option
                    value="{{ $type->id }}"
                    data-slug="{{ $type->slug }}"
                    {{ $selectedRequestTypeId == $type->id ? 'selected' : '' }}
                >
                    {{ $type->name }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">
            Opcional. Define el comportamiento y campos adicionales de la solicitud.
        </p>
        @if($errors && $errors->has('request_type_id'))
            <p class="mt-1 text-sm text-red-600">{{ $errors->first('request_type_id') }}</p>
        @endif
    </div>

    <!-- Meeting-specific fields (shown/hidden by the partial itself based on selectedTypeSlug) -->
    @include('service-requests.partials._meeting-details', ['errors' => $errors])
</div>
