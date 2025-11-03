{{-- resources/views/service-request-evidences/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Agregar Evidencia')

@section('content')
<div class="py-6">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <!-- Encabezado -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Agregar Evidencia</h2>
                <p class="text-gray-600 text-sm">Solicitud #{{ $serviceRequest->ticket_number }}</p>
            </div>
            <a href="{{ route('service-requests.show', $serviceRequest) }}"
               class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                ← Volver a la solicitud
            </a>
        </div>

        <!-- Tarjeta del Formulario -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form action="{{ route('service-requests.evidences.store', $serviceRequest) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="space-y-6">
                        <!-- Tipo de Evidencia -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tipo de evidencia *
                            </label>
                            <select name="evidence_type" required id="evidenceType"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors">
                                <option value="">Seleccione el tipo de evidencia</option>
                                <option value="PASO_A_PASO">Paso a Paso</option>
                                <option value="ARCHIVO">Archivo Adjunto</option>
                                <option value="COMENTARIO">Comentario</option>
                            </select>
                        </div>

                        <!-- Campos condicionales -->
                        <div id="conditionalFields" class="space-y-4">
                            <!-- Número de Paso -->
                            <div id="stepField" style="display: none;">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Número de paso
                                </label>
                                <input type="number" name="step_number" value="1" min="1"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                       placeholder="Ejemplo: 1, 2, 3...">
                            </div>

                            <!-- Título -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Título de la evidencia *
                                </label>
                                <input type="text" name="title" required
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                       placeholder="Ejemplo: Diagnóstico inicial, Instalación completada...">
                            </div>

                            <!-- Descripción -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Descripción detallada
                                </label>
                                <textarea name="description" rows="3"
                                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                          placeholder="Describa en detalle las acciones realizadas, resultados obtenidos y cualquier observación relevante..."></textarea>
                            </div>

                            <!-- Archivo -->
                            <div id="fileField" style="display: none;">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Archivo adjunto
                                </label>
                                <input type="file" name="file" id="fileInput"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="text-xs text-gray-500 mt-2">
                                    <strong>Formatos admitidos:</strong> JPG, PNG, PDF, DOC, DOCX, XLS, XLSX, ZIP, RAR
                                    <br><strong>Tamaño máximo:</strong> 10MB
                                </p>

                                <!-- Vista Previa -->
                                <div id="filePreview" class="mt-3 p-4 border border-gray-200 rounded-lg bg-gray-50" style="display: none;">
                                    <div id="previewContent" class="text-center"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Datos Adicionales -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-4">Datos Adicionales (Opcional)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">
                                        Técnico responsable
                                    </label>
                                    <input type="text" name="evidence_data[technician]"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                           placeholder="Nombre del técnico">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">
                                        Duración (minutos)
                                    </label>
                                    <input type="number" name="evidence_data[duration]" min="1"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                           placeholder="Tiempo empleado">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Observaciones adicionales
                                </label>
                                <textarea name="evidence_data[observations]" rows="2"
                                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                          placeholder="Información complementaria, recomendaciones o notas importantes..."></textarea>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                            <a href="{{ route('service-requests.show', $serviceRequest) }}"
                               class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 transition-colors">
                                Cancelar
                            </a>
                            <button type="submit"
                                    class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200 transition-colors">
                                Guardar Evidencia
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const evidenceType = document.getElementById('evidenceType');
    const stepField = document.getElementById('stepField');
    const fileField = document.getElementById('fileField');
    const fileInput = document.getElementById('fileInput');
    const filePreview = document.getElementById('filePreview');
    const previewContent = document.getElementById('previewContent');

    // Manejar cambio de tipo de evidencia
    evidenceType.addEventListener('change', function() {
        const tipo = this.value;

        stepField.style.display = tipo === 'PASO_A_PASO' ? 'block' : 'none';
        fileField.style.display = (tipo === 'PASO_A_PASO' || tipo === 'ARCHIVO') ? 'block' : 'none';

        if (tipo === 'ARCHIVO') {
            fileInput.required = true;
        } else {
            fileInput.required = false;
        }
    });

    // Vista previa de archivo
    fileInput.addEventListener('change', function() {
        if (!this.files[0]) {
            filePreview.style.display = 'none';
            return;
        }

        const archivo = this.files[0];

        // Validar tamaño del archivo
        if (archivo.size > 10 * 1024 * 1024) {
            alert('El archivo es demasiado grande. El tamaño máximo permitido es 10MB.');
            this.value = '';
            return;
        }

        let html = '';
        if (archivo.type.startsWith('image/')) {
            const lector = new FileReader();
            lector.onload = (e) => {
                html = `
                    <div class="mb-3">
                        <img src="${e.target.result}" class="mx-auto max-h-40 rounded-lg shadow-sm" alt="Vista previa">
                    </div>
                    <div class="text-sm text-gray-600">
                        <div class="font-medium text-gray-800">${archivo.name}</div>
                        <div>${(archivo.size / 1024 / 1024).toFixed(1)} MB • ${archivo.type}</div>
                    </div>
                `;
                previewContent.innerHTML = html;
                filePreview.style.display = 'block';
            };
            lector.readAsDataURL(archivo);
        } else {
            const icono = archivo.type.includes('pdf') ? '📄' :
                         archivo.type.includes('word') ? '📝' :
                         archivo.type.includes('excel') ? '📊' :
                         archivo.type.includes('zip') ? '📦' : '📎';

            html = `
                <div class="text-4xl mb-3">${icono}</div>
                <div class="text-sm text-gray-600">
                    <div class="font-medium text-gray-800">${archivo.name}</div>
                    <div>${(archivo.size / 1024 / 1024).toFixed(1)} MB • ${archivo.type || 'Archivo'}</div>
                </div>
            `;
            previewContent.innerHTML = html;
            filePreview.style.display = 'block';
        }
    });

    // Validación del formulario
    const formulario = document.querySelector('form');
    formulario.addEventListener('submit', function(e) {
        const tipoEvidencia = evidenceType.value;
        const tieneArchivo = fileInput.files.length > 0;

        if (tipoEvidencia === 'ARCHIVO' && !tieneArchivo) {
            e.preventDefault();
            alert('Para evidencias de tipo "Archivo Adjunto" debe seleccionar un archivo.');
            fileInput.focus();
            return;
        }
    });
});
</script>

<style>
select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
</style>
@endsection
