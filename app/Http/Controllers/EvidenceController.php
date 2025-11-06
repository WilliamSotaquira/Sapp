<?php

namespace App\Http\Controllers;

use App\Models\Evidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EvidenceController extends Controller
{
    /**
     * Eliminar una evidencia y su archivo asociado
     */
    public function destroy(Evidence $evidence)
    {
        try {
            // Verificar permisos (ejemplo - ajusta según tu sistema de autorización)
            $this->authorize('delete', $evidence);

            // Eliminar archivo físico si existe
            if (Storage::exists($evidence->file_path)) {
                Storage::delete($evidence->file_path);
            }

            // Eliminar registro de la base de datos
            $evidence->delete();

            return back()->with('success', 'Evidencia eliminada exitosamente.');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning('Intento de eliminar evidencia no autorizado', [
                'user_id' => auth()->id(),
                'evidence_id' => $evidence->id
            ]);

            return back()->with('error', 'No tienes permisos para eliminar esta evidencia.');

        } catch (\Exception $e) {
            Log::error('Error al eliminar evidencia: ' . $e->getMessage(), [
                'evidence_id' => $evidence->id,
                'file_path' => $evidence->file_path
            ]);

            return back()->with('error', 'Error al eliminar la evidencia. Por favor, intente nuevamente.');
        }
    }

    /**
     * Descargar archivo de evidencia
     */
    public function download(Evidence $evidence)
    {
        try {
            // Verificar permisos (ejemplo - ajusta según tu sistema de autorización)
            $this->authorize('view', $evidence);

            if (!Storage::exists($evidence->file_path)) {
                Log::warning('Archivo de evidencia no encontrado', [
                    'evidence_id' => $evidence->id,
                    'file_path' => $evidence->file_path
                ]);

                return back()->with('error', 'El archivo no existe.');
            }

            // Incrementar contador de descargas si lo tienes en el modelo
            if (isset($evidence->download_count)) {
                $evidence->increment('download_count');
            }

            return Storage::download($evidence->file_path, $evidence->original_name);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning('Intento de descarga no autorizado', [
                'user_id' => auth()->id(),
                'evidence_id' => $evidence->id
            ]);

            return back()->with('error', 'No tienes permisos para descargar esta evidencia.');

        } catch (\Exception $e) {
            Log::error('Error al descargar evidencia: ' . $e->getMessage(), [
                'evidence_id' => $evidence->id,
                'file_path' => $evidence->file_path
            ]);

            return back()->with('error', 'Error al descargar el archivo. Por favor, intente nuevamente.');
        }
    }

    /**
     * Mostrar archivo en el navegador (si es una imagen o PDF)
     */
    public function show(Evidence $evidence)
    {
        try {
            $this->authorize('view', $evidence);

            if (!Storage::exists($evidence->file_path)) {
                return back()->with('error', 'El archivo no existe.');
            }

            $file = Storage::get($evidence->file_path);
            $mimeType = Storage::mimeType($evidence->file_path);

            return response($file, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="' . $evidence->original_name . '"');

        } catch (\Exception $e) {
            Log::error('Error al mostrar evidencia: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar el archivo.');
        }
    }
}
