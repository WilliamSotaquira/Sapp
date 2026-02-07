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
        $allowedStatuses = ['EN_PROCESO', 'CERRADA'];

        // Permitir agregar evidencias en proceso y también después de cerrada
        if (!in_array($serviceRequest->status, $allowedStatuses, true)) {
            return redirect()
                ->route('service-requests.show', $serviceRequest)
                ->with('error', 'Solo se pueden agregar evidencias cuando la solicitud está en proceso o cerrada.');
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

        $allowedStatuses = ['EN_PROCESO', 'CERRADA'];

        // Permitir carga en proceso y cerrada
        if (!in_array($serviceRequest->status, $allowedStatuses, true)) {
            return redirect()
                ->route('service-requests.show', $serviceRequest)
                ->with('error', 'Solo se pueden agregar evidencias cuando la solicitud está en proceso o cerrada.');
        }

        try {
            // Permite guardar enlace desde el mismo endpoint
            if ($request->filled('link_url')) {
                $validatedLink = $request->validate([
                    'link_url' => 'required|url|max:2048',
                ]);

                ServiceRequestEvidence::create([
                    'service_request_id' => $serviceRequest->id,
                    'title' => 'Enlace - ' . now()->format('d/m/Y H:i'),
                    'description' => $validatedLink['link_url'],
                    'evidence_type' => 'ENLACE',
                    'evidence_data' => ['url' => $validatedLink['link_url']],
                    'user_id' => auth()->id(),
                ]);

                return redirect()->back()->with('success', 'Enlace agregado correctamente.');
            }

            // Nuestro formulario usa 'files[]' no 'file'
            $request->validate([
                'files' => 'required|array|min:1|max:5',
                'files.*' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip,rar,csv,svg',
            ]);

            \Log::info('✅ Validation passed');

            if (!$request->hasFile('files')) {
                \Log::warning('⚠️ No files to process');
                return redirect()->back()->with('error', 'No se seleccionaron archivos.');
            }

            $evidenceService = app(EvidenceService::class);
            $result = $evidenceService->uploadEvidences($serviceRequest, $request->file('files'));

            if ($result['success_count'] > 0) {
                $message = $result['success_count'] . ' archivo(s) subido(s) correctamente.';
                if ($result['error_count'] > 0) {
                    $message .= ' ' . $result['error_count'] . ' archivo(s) con errores.';
                }
                return redirect()->back()->with('success', $message);
            }

            return redirect()->back()->with('error', 'No se pudieron subir los archivos.');
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

        // Solo permitir eliminar si no está cerrada/cancelada
        if (in_array($serviceRequest->status, ['CERRADA', 'CANCELADA'])) {
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
