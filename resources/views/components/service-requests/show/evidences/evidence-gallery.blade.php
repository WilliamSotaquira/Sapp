@props(['serviceRequest'])

@php
    $isDead = in_array($serviceRequest->status, ['CERRADA', 'CANCELADA', 'RECHAZADA']);
@endphp

<div id="evidences-section" class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="{{ $isDead ? 'bg-gray-100 border-gray-300' : 'bg-gradient-to-r from-amber-50 to-orange-50 border-amber-100' }} px-6 py-4 border-b">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-images {{ $isDead ? 'text-gray-500' : 'text-amber-600' }} mr-3 text-xl"></i>
                <div>
                    <h3 class="sr-card-title text-gray-800">Evidencias</h3>
                </div>
            </div>
            <div class="text-sm {{ $isDead ? 'text-gray-600' : 'text-amber-800' }}">
                {{ $serviceRequest->evidences->where('evidence_type', 'ARCHIVO')->count() }} archivo{{ $serviceRequest->evidences->where('evidence_type', 'ARCHIVO')->count() !== 1 ? 's' : '' }}
                ·
                {{ $serviceRequest->evidences->where('evidence_type', 'ENLACE')->count() }} enlace{{ $serviceRequest->evidences->where('evidence_type', 'ENLACE')->count() !== 1 ? 's' : '' }}
            </div>
        </div>
    </div>

    <div class="p-6">
        @php
            // Filtrar solo evidencias de tipo ARCHIVO
            $fileEvidences = $serviceRequest->evidences->where('evidence_type', 'ARCHIVO');
            $linkEvidences = $serviceRequest->evidences->where('evidence_type', 'ENLACE');
            $galleryEvidencesCount = $fileEvidences->count() + $linkEvidences->count();
        @endphp

        @if($galleryEvidencesCount > 0)
            <div class="mb-6">
                @php
                    $totalSize = $fileEvidences->sum('file_size');
                @endphp
                <p class="text-xs text-gray-500 mb-3">
                    {{ $galleryEvidencesCount }} evidencia(s) · {{ number_format($totalSize / 1024 / 1024, 1) }}MB
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($fileEvidences as $evidence)
                    <x-service-requests.show.evidences.evidence-card :evidence="$evidence" :serviceRequest="$serviceRequest" />
                    @endforeach
                    @foreach($linkEvidences as $evidence)
                    <x-service-requests.show.evidences.evidence-card :evidence="$evidence" :serviceRequest="$serviceRequest" />
                    @endforeach
                </div>
            </div>
        @else
            <div class="text-center py-10 text-gray-500">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-amber-100 rounded-full mb-3">
                    <i class="fas fa-images text-xl text-amber-500"></i>
                </div>
                <p class="text-sm font-medium">No hay evidencias adjuntas.</p>
            </div>
        @endif

        <!-- Sección de subida de archivos -->
        @if(in_array($serviceRequest->status, ['EN_PROCESO', 'CERRADA'], true))
        <div class="{{ $fileEvidences->count() > 0 ? 'mt-8 pt-6 border-t border-gray-200' : '' }}">
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-cloud-upload-alt text-gray-600 mr-2"></i>
                        <h4 class="text-md font-semibold text-gray-700">
                            {{ $fileEvidences->count() > 0 ? 'Agregar más evidencias' : 'Subir primera evidencia' }}
                        </h4>
                    </div>
                    <span class="text-xs text-gray-500 bg-white px-2 py-1 rounded border">
                        Máx. 10MB por archivo
                    </span>
                </div>
                <x-service-requests.show.evidences.evidence-uploader :serviceRequest="$serviceRequest" />
            </div>
        </div>
        @else
        <!-- Mensaje cuando la solicitud no permite evidencias -->
        <div class="mt-8 pt-6 border-t border-gray-200">
            <div class="bg-gray-100 rounded-xl p-4 border border-gray-300 text-center text-sm text-gray-600">
                No se pueden agregar evidencias en el estado actual: <strong>{{ $serviceRequest->status }}</strong>.
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrf = csrfMeta ? csrfMeta.getAttribute('content') : null;

    document.querySelectorAll('.delete-evidence-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const url = this.dataset.deleteUrl;
            const card = this.closest('.bg-white');
            const self = this;

            if (!confirm('¿Eliminar esta evidencia?')) {
                return;
            }

            self.disabled = true;
            self.classList.add('opacity-50');

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                let data = null;
                try {
                    data = await response.json();
                } catch (_) {
                    // ignore parse errors
                }

                if (response.ok && data && data.success && card) {
                    card.remove();
                } else {
                    const message = (data && data.message) ? data.message : 'No se pudo eliminar la evidencia';
                    alert(message);
                    self.disabled = false;
                    self.classList.remove('opacity-50');
                }
            } catch (error) {
                console.error(error);
                alert('Error al eliminar la evidencia');
                self.disabled = false;
                self.classList.remove('opacity-50');
            }
        });
    });
});
</script>
@endpush
