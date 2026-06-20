@props(['serviceRequest'])

@php
    $isDead = in_array($serviceRequest->status, ['CERRADA', 'CANCELADA', 'RECHAZADA']);
@endphp

<div id="evidences-section" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="{{ $isDead ? 'bg-gray-100 border-gray-300' : 'bg-amber-50/50 border-amber-100' }} px-5 py-3 border-b">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-images {{ $isDead ? 'text-gray-500' : 'text-amber-500' }} mr-2.5"></i>
                <h3 class="text-base font-semibold text-gray-800">Evidencias</h3>
            </div>
            <div class="text-xs {{ $isDead ? 'text-gray-600' : 'text-amber-700' }}">
                {{ $serviceRequest->evidences->where('evidence_type', 'ARCHIVO')->count() }} archivo{{ $serviceRequest->evidences->where('evidence_type', 'ARCHIVO')->count() !== 1 ? 's' : '' }}
                ·
                {{ $serviceRequest->evidences->where('evidence_type', 'ENLACE')->count() }} enlace{{ $serviceRequest->evidences->where('evidence_type', 'ENLACE')->count() !== 1 ? 's' : '' }}
            </div>
        </div>
    </div>

    <div class="p-5">
        @php
            $fileEvidences = $serviceRequest->evidences->where('evidence_type', 'ARCHIVO');
            $linkEvidences = $serviceRequest->evidences->where('evidence_type', 'ENLACE');
            $galleryEvidencesCount = $fileEvidences->count() + $linkEvidences->count();
            $canUpload = in_array($serviceRequest->status, ['EN_PROCESO', 'RESUELTA', 'CERRADA'], true);
        @endphp

        @if($galleryEvidencesCount > 0)
            <div class="mb-4">
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
        @endif

        {{-- Upload section --}}
        @if($canUpload)
            <div class="{{ $galleryEvidencesCount > 0 ? 'pt-4 border-t border-gray-100' : '' }}">
                <x-service-requests.show.evidences.evidence-uploader :serviceRequest="$serviceRequest" />
            </div>
        @elseif($galleryEvidencesCount === 0)
            <div class="text-center py-6 text-gray-400">
                <i class="fas fa-images text-2xl mb-2"></i>
                <p class="text-sm">Sin evidencias. Se pueden agregar cuando esté en proceso.</p>
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
