@extends('layouts.app')

@section('content')
<div class="py-6">
    <nav class="mb-6" aria-label="Breadcrumb">
        <ol class="flex space-x-2 text-sm text-gray-600">
            <li><a href="{{ route('dashboard') }}" class="hover:text-blue-600">Inicio</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="{{ route('reports.index') }}" class="hover:text-blue-600">Reportes</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="{{ route('reports.cuts.index') }}" class="hover:text-blue-600">Cortes</a></li>
            <li><span class="mx-2">/</span></li>
            <li class="text-gray-900 font-medium">Editar</li>
        </ol>
    </nav>

    <div class="bg-white rounded-xl shadow-md overflow-hidden max-w-3xl">
        <div class="px-6 py-4 border-b border-gray-200">
            <p class="text-xs uppercase tracking-wide text-gray-500">Corte #{{ $cut->id }}</p>
            <h2 class="text-xl font-bold text-gray-900">Editar corte</h2>
            <p class="text-sm text-gray-600">
                Contrato: {{ $cut->contract?->number ?? 'N/A' }}
            </p>
        </div>

        @if($errors->any())
            <div class="p-4 bg-red-50 text-red-700 border-b border-red-100">
                <ul class="list-disc ml-5 space-y-1 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('reports.cuts.update', $cut) }}" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nombre del corte</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $cut->name) }}"
                    required
                    maxlength="255"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Fecha y hora inicio</label>
                    <input
                        type="datetime-local"
                        id="start_date"
                        name="start_date"
                        value="{{ old('start_date', optional($cut->start_date)->format('Y-m-d\TH:i')) }}"
                        required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">Fecha y hora fin</label>
                    <input
                        type="datetime-local"
                        id="end_date"
                        name="end_date"
                        value="{{ old('end_date', optional($cut->end_date)->format('Y-m-d\TH:i')) }}"
                        required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                    <p class="mt-1 text-xs text-gray-500">
                        <button type="button" id="recalc-end-date" class="text-blue-600 hover:text-blue-800 font-medium">Recalcular 30 días desde inicio</button>
                    </p>
                </div>
            </div>

            <script>
            (function() {
                var startInput = document.getElementById('start_date');
                var endInput = document.getElementById('end_date');
                var recalcBtn = document.getElementById('recalc-end-date');

                function calcEndDate(startValue) {
                    if (!startValue) return null;
                    var start = new Date(startValue);
                    if (isNaN(start.getTime())) return null;
                    var end = new Date(start.getTime() + (30 * 24 * 60 * 60 * 1000) - 1000);
                    var y = end.getFullYear();
                    var m = String(end.getMonth() + 1).padStart(2, '0');
                    var d = String(end.getDate()).padStart(2, '0');
                    var h = String(end.getHours()).padStart(2, '0');
                    var min = String(end.getMinutes()).padStart(2, '0');
                    return y + '-' + m + '-' + d + 'T' + h + ':' + min;
                }

                recalcBtn.addEventListener('click', function() {
                    var val = calcEndDate(startInput.value);
                    if (val) endInput.value = val;
                });
            })();
            </script>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notas (opcional)</label>
                <textarea
                    id="notes"
                    name="notes"
                    rows="4"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                >{{ old('notes', $cut->notes) }}</textarea>
            </div>

            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                <label for="folder_path" class="block text-sm font-medium text-gray-700 mb-2">Carpeta de evidencias</label>
                <div class="flex gap-2">
                    <input
                        type="text"
                        id="folder_path"
                        name="folder_path"
                        value="{{ old('folder_path', $cut->folder_path) }}"
                        placeholder="Ej: E:\corte-3-2026-02-01"
                        maxlength="500"
                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                    <button type="button" id="browseFolder" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100" title="Seleccionar carpeta">
                        <i class="fa-solid fa-folder"></i>
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-500">Ruta absoluta de la carpeta donde se almacenarán las evidencias de este corte. Si la carpeta no existe, se creará automáticamente.</p>
                @error('folder_path')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror

                {{-- Hidden directory picker (webkitdirectory gives relative paths only, used as UX helper) --}}
                <input type="file" id="folderPicker" webkitdirectory directory class="hidden">
            </div>

            <script>
                document.getElementById('browseFolder').addEventListener('click', function() {
                    document.getElementById('folderPicker').click();
                });
                document.getElementById('folderPicker').addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        // webkitRelativePath gives something like "folder/subfolder/file.txt"
                        // Extract the top-level folder name from the first file
                        var relativePath = e.target.files[0].webkitRelativePath;
                        var folderName = relativePath.split('/')[0];
                        var input = document.getElementById('folder_path');
                        // If the input already has a base path, append; otherwise suggest
                        if (input.value && !input.value.endsWith('\\') && !input.value.endsWith('/')) {
                            input.value = input.value + '\\' + folderName;
                        } else if (input.value) {
                            input.value = input.value + folderName;
                        } else {
                            // Browsers don't provide absolute paths for security reasons
                            // Show the folder name and prompt user to complete the full path
                            input.value = folderName;
                            alert('El navegador no proporciona la ruta completa por seguridad. Se detectó la carpeta "' + folderName + '". Por favor completa la ruta absoluta (ej: E:\\' + folderName + ').');
                        }
                    }
                });
            </script>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('reports.cuts.show', $cut) }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

