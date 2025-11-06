<?php
// app/Http\Controllers/ServiceRequestEvidenceController.php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'evidence_type' => 'required|in:PASO_A_PASO,ARCHIVO,COMENTARIO',
            'description' => 'nullable|string',
            'step_number' => 'nullable|integer|min:1',
            'file' => 'nullable|file|max:10240',
            'evidence_data' => 'nullable|array',
        ]);

        try {
            DB::transaction(function () use ($validated, $serviceRequest, $request) {
                // SOLO usar columnas que existen en la tabla
                $evidenceData = [
                    'service_request_id' => $serviceRequest->id,
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'evidence_type' => $validated['evidence_type'],
                    'step_number' => $validated['step_number'] ?? null,
                    'user_id' => auth()->id(),
                ];

                // Manejar archivo si se subió
                if ($request->hasFile('file')) {
                    $file = $request->file('file');
                    $fileName = time() . '_' . Str::random(10) . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs("evidences/service-request-{$serviceRequest->id}", $fileName);

                    // SOLO usar columnas que existen en la tabla
                    $evidenceData = array_merge($evidenceData, [
                        'file_original_name' => $file->getClientOriginalName(), // ← Esta SÍ existe
                        'file_path' => $filePath, // ← Esta SÍ existe
                        'file_mime_type' => $file->getMimeType(), // ← Esta SÍ existe
                        'file_size' => $file->getSize(), // ← Esta SÍ existe
                        // NO incluir 'file_name' - esa columna NO existe
                        // NO incluir 'mime_type' - esa columna NO existe
                    ]);

                    // Guardar metadata en evidence_data
                    $evidenceData['evidence_data'] = [
                        'file_extension' => $file->getClientOriginalExtension(),
                        'uploaded_at' => now()->toISOString(),
                        'uploaded_by' => auth()->id(),
                        'technician' => $request->input('evidence_data.technician'),
                        'duration' => $request->input('evidence_data.duration'),
                        'observations' => $request->input('evidence_data.observations'),
                    ];
                } else {
                    // Para evidencias sin archivo
                    $evidenceData['evidence_data'] = [
                        'technician' => $request->input('evidence_data.technician'),
                        'duration' => $request->input('evidence_data.duration'),
                        'observations' => $request->input('evidence_data.observations'),
                    ];
                }

                $evidence = ServiceRequestEvidence::create($evidenceData);

                \Log::info('Evidencia creada exitosamente', [
                    'evidence_id' => $evidence->id,
                    'service_request_id' => $serviceRequest->id
                ]);
            });

            return redirect()->route('service-requests.show', $serviceRequest)
                ->with('success', 'Evidencia agregada exitosamente.');
        } catch (\Exception $e) {
            \Log::error('Error al crear evidencia: ' . $e->getMessage());
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
