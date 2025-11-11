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
            return redirect()->route('service-requests.show', $serviceRequest)->with('error', 'No se pueden agregar evidencias en el estado actual de la solicitud.');
        }

        // Obtener el siguiente número de paso
        $nextStep = $serviceRequest->stepByStepEvidences()->max('step_number') + 1;

        return view('service-request-evidences.create', compact('serviceRequest', 'nextStep'));
    }

    /**
     * Almacenar nueva evidencia - VERSIÓN ACTUALIZADA para nuestro formulario
     */
    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        \Log::info('🎯 === SERVICE REQUEST EVIDENCE CONTROLLER STORE CALLED ===');
        \Log::info('Service Request ID: ' . $serviceRequest->id);
        \Log::info('User ID: ' . auth()->id());
        \Log::info('Request data:', $request->all());
        \Log::info('Has files: ' . ($request->hasFile('files') ? 'YES' : 'NO'));

        try {
            // Nuestro formulario usa 'files[]' no 'file'
            $request->validate([
                'files.*' => 'required|file|max:10240',
            ]);

            \Log::info('✅ Validation passed');

            $uploadedFiles = [];

            if ($request->hasFile('files')) {
                \Log::info('Files count: ' . count($request->file('files')));

                foreach ($request->file('files') as $file) {
                    \Log::info('Processing file: ' . $file->getClientOriginalName());

                    // Crear carpeta específica para esta solicitud
                    $folderName = 'service-request-' . $serviceRequest->id;
                    $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();

                    // Guardar archivo en storage
                    $filePath = $file->storeAs("evidences/{$folderName}", $fileName, 'public');

                    \Log::info('📁 File stored at: ' . $filePath);

                    // Crear registro en la base de datos
                    $evidenceData = [
                        'service_request_id' => $serviceRequest->id,
                        'title' => $file->getClientOriginalName(),
                        'description' => 'Archivo subido: ' . $file->getClientOriginalName(),
                        'evidence_type' => 'ARCHIVO',
                        'file_path' => $filePath,
                        'file_original_name' => $file->getClientOriginalName(),
                        'file_mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'user_id' => auth()->id(),
                    ];

                    \Log::info('Creating evidence with data:', $evidenceData);

                    $evidence = ServiceRequestEvidence::create($evidenceData);
                    $evidence->load('user');

                    \Log::info('💾 Evidence created with ID: ' . $evidence->id);
                    $uploadedFiles[] = $evidence;
                }

                \Log::info('🎉 Upload completed: ' . count($uploadedFiles) . ' files');
                return redirect()
                    ->back()
                    ->with('success', count($uploadedFiles) . ' archivo(s) subido(s) correctamente.');
            }

            \Log::warning('⚠️ No files to process');
            return redirect()->back()->with('error', 'No se seleccionaron archivos.');
        } catch (\Exception $e) {
            \Log::error('❌ STORE EVIDENCE ERROR: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()
                ->back()
                ->with('error', 'Error al subir archivos: ' . $e->getMessage());
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

        return Storage::disk('public')->download($evidence->file_path, $evidence->file_original_name);
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
            return redirect()->back()->with('error', 'No se pueden eliminar evidencias en el estado actual de la solicitud.');
        }

        try {
            DB::transaction(function () use ($evidence) {
                // Eliminar archivo físico si existe
                if ($evidence->hasFile() && Storage::disk('public')->exists($evidence->file_path)) {
                    Storage::disk('public')->delete($evidence->file_path);
                }

                $evidence->delete();
            });

            return redirect()->route('service-requests.show', $serviceRequest)->with('success', 'Evidencia eliminada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar la evidencia: ' . $e->getMessage());
        }
    }
}
