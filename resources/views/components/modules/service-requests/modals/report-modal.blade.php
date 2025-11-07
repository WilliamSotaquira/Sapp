@props(['serviceRequest'])

<!-- Modal de Reporte -->
<div id="reportModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-teal-100">
                <i class="fas fa-chart-line text-teal-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Generar Reporte</h3>
            <p class="text-sm text-gray-500 mt-2">Seleccione el formato del reporte</p>

            <div class="mt-4 space-y-3">
                <a href="{{ route('reports.timeline.export', [$serviceRequest->id, 'pdf']) }}"
                    target="_blank"
                    class="w-full flex items-center justify-center px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors">
                    <i class="fas fa-file-pdf mr-2"></i>Descargar PDF
                </a>
                <a href="{{ route('reports.timeline.export', [$serviceRequest->id, 'excel']) }}"
                    target="_blank"
                    class="w-full flex items-center justify-center px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors">
                    <i class="fas fa-file-excel mr-2"></i>Descargar Excel
                </a>
                <a href="{{ route('reports.timeline.detail', $serviceRequest->id) }}"
                    target="_blank"
                    class="w-full flex items-center justify-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">
                    <i class="fas fa-eye mr-2"></i>Ver Timeline Web
                </a>
            </div>

            <div class="flex justify-end mt-5">
                <button type="button" onclick="serviceRequestModals.close('report')"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
