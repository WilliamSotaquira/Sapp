<?php
// app/Http\Controllers/ServiceRequestEvidenceController.php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ServiceRequestEvidenceController extends Controller
{
    /**
     * Mostrar formulario para agregar evidencias
     */
    public function create(ServiceRequest $serviceRequest)
    {
        // Verificar que la solicitud está en estado adecuado para agregar evidencias
        if (!in_array($serviceRequest->status, ['ACEPTADA', 'EN_PROCESO'])) {
            return redirect()->route('service-requests.show', $serviceRequest)
                ->with('error', 'No se pueden agregar evidencias en el estado actual de la solicitud.');
        }

        // Obtener el siguiente número de paso
        $nextStep = $serviceRequest->stepByStepEvidences()->max('step_number') + 1;

        return view('service-request-evidences.create', compact('serviceRequest', 'nextStep'));
    }

    /**
     * Almacenar nueva evidencia
     */
    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'evidence_type' => 'required|in:PASO_A_PASO,ARCHIVO,COMENTARIO',
            'step_number' => 'nullable|integer|min:1',
            'evidence_data' => 'nullable|array',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip,rar|max:10240', // 10MB máximo
        ]);

        try {
            DB::transaction(function () use ($request, $serviceRequest) {
                $evidenceData = [
                    'service_request_id' => $serviceRequest->id,
                    'title' => $request->title,
                    'description' => $request->description,
                    'evidence_type' => $request->evidence_type,
                    'step_number' => $request->step_number,
                    'evidence_data' => $request->evidence_data ?? [],
                ];

                // Manejar archivo adjunto
                if ($request->hasFile('file')) {
                    $file = $request->file('file');

                    // Crear directorio si no existe
                    $directory = 'evidences/service-request-' . $serviceRequest->id;
                    if (!Storage::disk('public')->exists($directory)) {
                        Storage::disk('public')->makeDirectory($directory);
                    }

                    // Guardar archivo
                    $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs($directory, $filename, 'public');

                    $evidenceData['file_path'] = $path;
                    $evidenceData['file_original_name'] = $file->getClientOriginalName();
                    $evidenceData['file_mime_type'] = $file->getMimeType();
                    $evidenceData['file_size'] = $file->getSize();

                    // Agregar información del archivo a evidence_data
                    $evidenceData['evidence_data'] = array_merge(
                        $evidenceData['evidence_data'],
                        [
                            'file_extension' => $file->getClientOriginalExtension(),
                            'uploaded_at' => now()->toISOString(),
                            'uploaded_by' => auth()->id(),
                        ]
                    );
                }

                ServiceRequestEvidence::create($evidenceData);

                // Actualizar timestamp de la solicitud
                $serviceRequest->touch();
            });

            return redirect()->route('service-requests.show', $serviceRequest)
                ->with('success', 'Evidencia agregada correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al guardar la evidencia: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mostrar evidencia específica
     */
    public function show(ServiceRequest $serviceRequest, ServiceRequestEvidence $evidence)
    {
        if ($evidence->service_request_id !== $serviceRequest->id) {
            abort(404);
        }

        return view('service-request-evidences.show', compact('serviceRequest', 'evidence'));
    }

    /**
     * Descargar archivo adjunto
     */
    public function download(ServiceRequest $serviceRequest, ServiceRequestEvidence $evidence)
    {
        if ($evidence->service_request_id !== $serviceRequest->id || !$evidence->hasFile()) {
            abort(404);
        }

        if (!Storage::disk('public')->exists($evidence->file_path)) {
            abort(404, 'Archivo no encontrado.');
        }

        return Storage::disk('public')->download(
            $evidence->file_path,
            $evidence->file_original_name
        );
    }

    /**
     * Ver archivo (para imágenes y PDFs)
     */
    public function view(ServiceRequest $serviceRequest, ServiceRequestEvidence $evidence)
    {
        if ($evidence->service_request_id !== $serviceRequest->id || !$evidence->hasFile()) {
            abort(404);
        }

        if (!Storage::disk('public')->exists($evidence->file_path)) {
            abort(404, 'Archivo no encontrado.');
        }

        $file = Storage::disk('public')->get($evidence->file_path);
        $mimeType = Storage::disk('public')->mimeType($evidence->file_path);

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="' . $evidence->file_original_name . '"');
    }

    /**
     * Eliminar evidencia
     */
    public function destroy(ServiceRequest $serviceRequest, ServiceRequestEvidence $evidence)
    {
        if ($evidence->service_request_id !== $serviceRequest->id) {
            abort(404);
        }

        // Solo permitir eliminar en estados específicos
        if (!in_array($serviceRequest->status, ['ACEPTADA', 'EN_PROCESO'])) {
            return redirect()->back()
                ->with('error', 'No se pueden eliminar evidencias en el estado actual de la solicitud.');
        }

        try {
            DB::transaction(function () use ($evidence) {
                // Eliminar archivo físico si existe
                if ($evidence->hasFile() && Storage::disk('public')->exists($evidence->file_path)) {
                    Storage::disk('public')->delete($evidence->file_path);
                }

                $evidence->delete();
            });

            return redirect()->route('service-requests.show', $serviceRequest)
                ->with('success', 'Evidencia eliminada correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al eliminar la evidencia: ' . $e->getMessage());
        }
    }
}
