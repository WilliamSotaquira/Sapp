<?php
// app/Http\Controllers/ServiceRequestEvidenceController.php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvidence;
use App\Services\EvidenceService;
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
        $allowedStatuses = ['EN_PROCESO', 'RESUELTA', 'CERRADA'];

        // Permitir agregar evidencias en proceso y también después de cerrada
        if (!in_array($serviceRequest->status, $allowedStatuses, true)) {
            return redirect()
                ->route('service-requests.show', $serviceRequest)
                ->with('evidence_error', 'Solo se pueden agregar evidencias cuando la solicitud está en proceso o cerrada.');
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
        $allowedStatuses = ['EN_PROCESO', 'RESUELTA', 'CERRADA'];

        // Permitir carga en proceso y cerrada
        if (!in_array($serviceRequest->status, $allowedStatuses, true)) {
            return redirect()
                ->route('service-requests.show', $serviceRequest)
                ->with('evidence_error', 'Solo se pueden agregar evidencias cuando la solicitud está en proceso o cerrada.');
        }

        try {
            $savedCount = 0;
            $messages = [];

            // Process link if provided
            if ($request->filled('link_url')) {
                $request->validate([
                    'link_url' => 'url|max:2048',
                ]);

                ServiceRequestEvidence::create([
                    'service_request_id' => $serviceRequest->id,
                    'title' => 'Enlace - ' . now()->format('d/m/Y H:i'),
                    'description' => $request->input('link_url'),
                    'evidence_type' => 'ENLACE',
                    'evidence_data' => ['url' => $request->input('link_url')],
                    'user_id' => auth()->id(),
                ]);

                $savedCount++;
                $messages[] = 'Enlace agregado';
            }

            // Process files if provided
            if ($request->hasFile('files')) {
                $evidenceType = $request->input('evidence_type', 'ARCHIVO');

                $fileRules = 'file|max:10240';
                if ($evidenceType === 'ACTA') {
                    $fileRules .= '|mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/jpg,image/png';
                } else {
                    $fileRules .= '|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip,rar,csv,svg';
                }

                $request->validate([
                    'evidence_type' => 'nullable|string|in:PASO_A_PASO,ARCHIVO,COMENTARIO,ENLACE,ACTA',
                    'files' => 'array|min:1|max:10',
                    'files.*' => $fileRules,
                ], [
                    'files.*.mimetypes' => 'El tipo de archivo para actas debe ser PDF, DOCX, JPG, JPEG o PNG.',
                    'evidence_type.in' => 'El tipo de evidencia seleccionado no es válido.',
                ]);

                $evidenceService = app(EvidenceService::class);
                $result = $evidenceService->uploadEvidences($serviceRequest, $request->file('files'), $evidenceType);

                $savedCount += $result['success_count'];
                if ($result['success_count'] > 0) {
                    $messages[] = $result['success_count'] . ' archivo(s) subido(s)';
                }
                if ($result['error_count'] > 0) {
                    $messages[] = $result['error_count'] . ' archivo(s) con errores';
                }
            }

            // If nothing was submitted
            if ($savedCount === 0 && !$request->hasFile('files') && !$request->filled('link_url')) {
                return redirect()->back()->with('evidence_error', 'No se seleccionaron archivos ni enlaces.');
            }

            if ($savedCount > 0) {
                return redirect()->back()->with('evidence_success', implode(' · ', $messages));
            }

            return redirect()->back()->with('evidence_error', 'No se pudieron guardar las evidencias.');
        } catch (\Exception $e) {
            \Log::error('❌ STORE EVIDENCE ERROR: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()
                ->back()
                ->with('evidence_error', 'Error al subir archivos: ' . $e->getMessage());
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

        $allowedStatuses = ['EN_PROCESO', 'RESUELTA', 'CERRADA'];

        // Permitir eliminar solo en proceso y cerrada
        if (!in_array($serviceRequest->status, $allowedStatuses, true)) {
            $message = 'No se pueden eliminar evidencias en el estado actual de la solicitud.';

            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        try {
            DB::transaction(function () use ($evidence) {
                // Eliminar archivo físico si existe
                if ($evidence->hasFile() && Storage::disk('public')->exists($evidence->file_path)) {
                    Storage::disk('public')->delete($evidence->file_path);
                }

                $evidence->delete();
            });

            if (request()->expectsJson()) {
                return response()->json(['success' => true]);
            }

            return redirect()->route('service-requests.show', $serviceRequest)->with('success', 'Evidencia eliminada correctamente.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Error al eliminar la evidencia: ' . $e->getMessage());
        }
    }
}
