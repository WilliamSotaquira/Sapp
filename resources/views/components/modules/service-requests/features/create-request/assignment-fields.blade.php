@props(['users' => []])

<div class="assignment-fields space-y-4 md:grid-flow-row-dense">
    <div class="border border-gray-200 rounded-lg p-4">

        <h3 class="text-lg font-semibold text-gray-900 pb-4">Asignación</h3>

        <!-- Asignado a -->
        <div class="mb-4 ">
            <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-1">
                Asignado a
            </label>
            <select name="assigned_to" id="assigned_to"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">Sin asignar</option>
                @foreach($users as $user)
                <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                    {{ $user->name }}
                </option>
                @endforeach
            </select>
            @error('assigned_to')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Observaciones -->
        <div>
            <label for="assignment_notes" class="block text-sm font-medium text-gray-700 mb-1">
                Observaciones de asignación
            </label>
            <textarea name="assignment_notes" id="assignment_notes" rows="3"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                placeholder="Notas adicionales sobre la asignación...">{{ old('assignment_notes') }}</textarea>
            @error('assignment_notes')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
