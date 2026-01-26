<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Espacio de trabajo</h2>
        <p class="mt-1 text-sm text-gray-600">Selecciona el espacio donde deseas trabajar.</p>
    </header>

    @if(isset($userWorkspaces) && $userWorkspaces->count() > 0)
        <form method="POST" action="{{ route('workspaces.switch') }}" class="mt-4 space-y-4 max-w-xl">
            @csrf
            <div>
                <label for="profileWorkspace" class="block text-sm font-medium text-gray-700 mb-2">Espacio</label>
                <select id="profileWorkspace" name="company_id"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @foreach ($userWorkspaces as $workspace)
                        <option value="{{ $workspace->id }}" {{ optional($currentWorkspace)->id === $workspace->id ? 'selected' : '' }}>
                            {{ $workspace->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end">
                <x-primary-button>Actualizar espacio</x-primary-button>
            </div>
        </form>
    @else
        <div class="mt-4 p-3 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-800">
            No tienes espacios de trabajo asignados.
        </div>
    @endif
</section>
