{{-- resources/views/service-requests/partials/_meeting-details.blade.php --}}
{{-- Meeting scheduling fields - conditionally displayed when type = "reunion" --}}
<div x-show="selectedTypeSlug === 'reunion'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2">
    <div class="mt-6 p-5 bg-indigo-50/50 border border-indigo-200 rounded-xl space-y-5">
        <div class="flex items-center gap-2 mb-1">
            <i class="fas fa-calendar-alt text-indigo-600"></i>
            <h3 class="text-sm font-semibold text-indigo-800 uppercase tracking-wide">Detalles de Reunión</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- Fecha Programada --}}
            <div>
                <label for="scheduled_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Fecha Programada <span class="text-red-500">*</span>
                </label>
                <input
                    type="date"
                    name="scheduled_date"
                    id="scheduled_date"
                    value="{{ old('scheduled_date') }}"
                    min="{{ date('Y-m-d') }}"
                    class="w-full px-4 py-3 border {{ $errors->has('scheduled_date') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                    @error('scheduled_date') aria-invalid="true" @enderror
                >
                @error('scheduled_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Hora de Inicio --}}
            <div>
                <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">
                    Hora de Inicio <span class="text-red-500">*</span>
                </label>
                <input
                    type="time"
                    name="start_time"
                    id="start_time"
                    value="{{ old('start_time') }}"
                    class="w-full px-4 py-3 border {{ $errors->has('start_time') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                    @error('start_time') aria-invalid="true" @enderror
                >
                @error('start_time')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Duración Esperada --}}
        <div>
            <label for="expected_duration_minutes" class="block text-sm font-medium text-gray-700 mb-2">
                Duración Esperada (minutos) <span class="text-red-500">*</span>
            </label>
            <input
                type="number"
                name="expected_duration_minutes"
                id="expected_duration_minutes"
                value="{{ old('expected_duration_minutes') }}"
                min="5"
                max="480"
                step="5"
                placeholder="Ej: 60"
                class="w-full px-4 py-3 border {{ $errors->has('expected_duration_minutes') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                @error('expected_duration_minutes') aria-invalid="true" @enderror
            >
            @error('expected_duration_minutes')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Mínimo 5 minutos, máximo 480 minutos (8 horas).</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- Ubicación (opcional) --}}
            <div>
                <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                    Ubicación <span class="text-gray-400 text-xs font-normal">(opcional)</span>
                </label>
                <input
                    type="text"
                    name="location"
                    id="location"
                    value="{{ old('location') }}"
                    maxlength="255"
                    placeholder="Ej: Sala de juntas 3, Piso 2"
                    class="w-full px-4 py-3 border {{ $errors->has('location') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                    @error('location') aria-invalid="true" @enderror
                >
                @error('location')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- URL Reunión Virtual (opcional) --}}
            <div>
                <label for="virtual_meeting_url" class="block text-sm font-medium text-gray-700 mb-2">
                    URL Reunión Virtual <span class="text-gray-400 text-xs font-normal">(opcional)</span>
                </label>
                <input
                    type="url"
                    name="virtual_meeting_url"
                    id="virtual_meeting_url"
                    value="{{ old('virtual_meeting_url') }}"
                    maxlength="2048"
                    placeholder="https://meet.google.com/..."
                    class="w-full px-4 py-3 border {{ $errors->has('virtual_meeting_url') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                    @error('virtual_meeting_url') aria-invalid="true" @enderror
                >
                @error('virtual_meeting_url')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>
