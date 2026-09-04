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
        $allowedStatuses = ['ACEPTADA', 'EN_PROCESO', 'PAUSADA', 'RESUELTA', 'CERRADA', 'NO_VIABLE'];

        // Permitir agregar evidencias mientras la solicitud está en gestión o cerrada
        if (!in_array($serviceRequest->status, $allowedStatuses, true)) {
            return redirect()
                ->route('service-requests.show', $serviceRequest)
                ->with('evidence_error', 'Solo se pueden agregar evidencias cuando la solicitud está en gestión (aceptada, en proceso o pausada) o cerrada.');
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
        $allowedStatuses = ['ACEPTADA', 'EN_PROCESO', 'PAUSADA', 'RESUELTA', 'CERRADA', 'NO_VIABLE'];

        // Permitir carga mientras la solicitud está en gestión o cerrada
        if (!in_array($serviceRequest->status, $allowedStatuses, true)) {
            return redirect()
                ->route('service-requests.show', $serviceRequest)
                ->with('evidence_error', 'Solo se pueden agregar evidencias cuando la solicitud está en gestión (aceptada, en proceso o pausada) o cerrada.');
        }

        try {
            $savedCount = 0;
            $messages = [];

            // Process link(s) if provided — supports multiple URLs separated by newlines, commas, or spaces
            if ($request->filled('link_url')) {
                $rawInput = $request->input('link_url');

                // Separar por líneas, comas o espacios (preservando URLs completas)
                $urls = preg_split('/[\r\n,]+/', $rawInput);
                $urls = array_values(array_filter(array_map(function ($url) {
                    $url = trim($url);
                    // Limpiar espacios internos que puedan separar una URL de basura
                    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                        // Tomar solo la parte URL (hasta el primer espacio)
                        $parts = preg_split('/\s+/', $url, 2);
                        return $parts[0] ?? '';
                    }
                    return '';
                }, $urls)));

                foreach ($urls as $url) {
                    if (filter_var($url, FILTER_VALIDATE_URL) && mb_strlen($url) <= 2048) {
                        ServiceRequestEvidence::create([
                            'service_request_id' => $serviceRequest->id,
                            'title' => 'Enlace - ' . now()->format('d/m/Y H:i'),
                            'description' => $url,
                            'evidence_type' => 'ENLACE',
                            'evidence_data' => ['url' => $url],
                            'user_id' => auth()->id(),
                        ]);
                        $savedCount++;
                    }
                }

                if ($savedCount > 0) {
                    $messages[] = $savedCount . ' enlace(s) agregado(s)';
                }
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
                return redirect()->to($this->backUrlWithAnchor($serviceRequest, 'sr-section-evidences'))->with('evidence_error', 'No se seleccionaron archivos ni enlaces.');
            }

            if ($savedCount > 0) {
                return redirect()->to($this->backUrlWithAnchor($serviceRequest, 'sr-section-evidences'))->with('evidence_success', implode(' · ', $messages));
            }

            return redirect()->to($this->backUrlWithAnchor($serviceRequest, 'sr-section-evidences'))->with('evidence_error', 'No se pudieron guardar las evidencias.');
        } catch (\Exception $e) {
            \Log::error('❌ STORE EVIDENCE ERROR: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()
                ->to($this->backUrlWithAnchor($serviceRequest, 'sr-section-evidences'))
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
     * Almacenar nota rápida de seguimiento (inline desde la vista show).
     * Permite agregar notas tipo COMENTARIO en cualquier estado activo de la solicitud.
     */
    public function storeQuickNote(Request $request, ServiceRequest $serviceRequest)
    {
        $deadStatuses = ['CERRADA', 'CANCELADA', 'RECHAZADA', 'NO_VIABLE'];

        if (in_array($serviceRequest->status, $deadStatuses, true)) {
            $message = 'No se pueden agregar notas de seguimiento a solicitudes cerradas, canceladas o rechazadas.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->back()->with('evidence_error', $message);
        }

        $request->validate([
            'note_content' => 'required|string|min:3|max:1000',
        ], [
            'note_content.required' => 'La nota de seguimiento no puede estar vacía.',
            'note_content.min' => 'La nota debe tener al menos 3 caracteres.',
            'note_content.max' => 'La nota no puede exceder 1000 caracteres.',
        ]);

        try {
            $note = ServiceRequestEvidence::create([
                'service_request_id' => $serviceRequest->id,
                'title' => 'Nota de seguimiento',
                'description' => $request->input('note_content'),
                'evidence_type' => 'COMENTARIO',
                'user_id' => auth()->id(),
                'evidence_data' => [
                    'type' => 'quick_note',
                    'author' => auth()->user()->name,
                    'created_via' => 'inline_form',
                ],
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nota de seguimiento registrada.',
                    'note' => [
                        'id' => $note->id,
                        'description' => $note->description,
                        'created_at' => $note->created_at->format('d/m H:i'),
                    ],
                ]);
            }

            return redirect()
                ->to($this->backUrlWithAnchor($serviceRequest, 'sr-section-system-notes'))
                ->with('evidence_success', 'Nota de seguimiento registrada correctamente.');
        } catch (\Exception $e) {
            \Log::error('Error al guardar nota de seguimiento: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al guardar la nota.'], 500);
            }

            return redirect()->back()->with('evidence_error', 'Error al guardar la nota de seguimiento.');
        }
    }

    /**
     * Eliminar evidencia
     */
    public function destroy(ServiceRequest $serviceRequest, ServiceRequestEvidence $evidence)
    {
        if ($evidence->service_request_id !== $serviceRequest->id) {
            abort(404);
        }

        $allowedStatuses = ['ACEPTADA', 'EN_PROCESO', 'PAUSADA', 'RESUELTA', 'CERRADA', 'NO_VIABLE'];

        // Permitir eliminar mientras la solicitud está en gestión o cerrada/no viable
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

    /**
     * Genera la URL de retorno con ancla para mantener la posición de scroll.
     */
    private function backUrlWithAnchor(ServiceRequest $serviceRequest, string $anchor): string
    {
        $baseUrl = route('service-requests.show', $serviceRequest);
        return $baseUrl . '#' . $anchor;
    }
}
