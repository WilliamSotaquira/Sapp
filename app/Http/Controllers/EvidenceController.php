<?php

namespace App\Http\Controllers;

use App\Models\Evidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenceController extends Controller
{
    public function destroy(Evidence $evidence)
    {
        // Eliminar archivo físico
        Storage::delete($evidence->file_path);

        $evidence->delete();

        return back()->with('success', 'Evidencia eliminada exitosamente.');
    }

    public function download(Evidence $evidence)
    {
        if (!Storage::exists($evidence->file_path)) {
            return back()->with('error', 'El archivo no existe.');
        }

        return Storage::download($evidence->file_path, $evidence->original_name);
    }
}
