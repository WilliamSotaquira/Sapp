<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemSettingsController extends Controller
{
    /**
     * Show the settings form with the current base path value.
     */
    public function edit(): View|RedirectResponse
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $basePath = SystemSetting::get('evidence_base_path');

        return view('settings.edit', [
            'basePath' => $basePath,
        ]);
    }

    /**
     * Validate and persist the base path setting.
     */
    public function update(Request $request): RedirectResponse
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'base_path' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\-_\/\\\\:]+$/'],
        ], [
            'base_path.regex' => 'La ruta contiene caracteres no válidos. Solo se permiten letras, números, guiones, guiones bajos, barras y dos puntos.',
            'base_path.max' => 'La ruta no puede exceder 255 caracteres.',
        ]);

        $path = $request->input('base_path');

        // Check the directory exists
        if (!is_dir($path)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['base_path' => 'El directorio especificado no existe.']);
        }

        // Check the directory is writable
        if (!is_writable($path)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['base_path' => 'El directorio especificado no tiene permisos de escritura.']);
        }

        // Persist the setting
        try {
            SystemSetting::set('evidence_base_path', $path);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['base_path' => 'No se pudo guardar la configuración. Intente nuevamente.']);
        }

        return redirect()->back()
            ->with('success', 'Ruta base de almacenamiento actualizada correctamente.');
    }
}
