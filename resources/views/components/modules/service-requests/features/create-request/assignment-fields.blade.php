<!-- Nivel de Criticidad -->
<div>
    <label for="criticality_level" class="block text-sm font-medium text-gray-700">Nivel de Criticidad *</label>
    <select name="criticality_level" id="criticality_level" required
        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
        <option value="">Seleccione criticidad</option>
        @foreach($criticalityLevels as $level)
        <option value="{{ $level }}" {{ old('criticality_level') == $level ? 'selected' : '' }}>
            {{ $level }}
        </option>
        @endforeach
    </select>
    @error('criticality_level')
    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>

<!-- Asignado a -->
<div>
    <label for="assigned_to" class="block text-sm font-medium text-gray-700">Asignar a</label>
    <select name="assigned_to" id="assigned_to"
        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
        <option value="">Sin asignar</option>
        @foreach($users as $user)
        <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
            {{ $user->name }}
        </option>
        @endforeach
    </select>
    @error('assigned_to')
    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>
