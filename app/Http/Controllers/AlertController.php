<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index()
    {
        $alerts = Alert::orderBy('alert_date', 'desc')->paginate(10);
        return view('alerts.index', compact('alerts'));
    }

    public function create()
    {
        return view('alerts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,warning,danger,success',
            'alert_date' => 'required|date',
            'expiration_date' => 'nullable|date|after:alert_date',
            'target_audience' => 'required|in:all,specific_department',
            'is_active' => 'boolean'
        ]);

        Alert::create($validated);

        return redirect()->route('alerts.index')
            ->with('success', 'Alerta creada exitosamente.');
    }

    public function show(Alert $alert)
    {
        return view('alerts.show', compact('alert'));
    }

    public function edit(Alert $alert)
    {
        return view('alerts.edit', compact('alert'));
    }

    public function update(Request $request, Alert $alert)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,warning,danger,success',
            'alert_date' => 'required|date',
            'expiration_date' => 'nullable|date|after:alert_date',
            'target_audience' => 'required|in:all,specific_department',
            'is_active' => 'boolean'
        ]);

        $alert->update($validated);

        return redirect()->route('alerts.index')
            ->with('success', 'Alerta actualizada exitosamente.');
    }

    public function destroy(Alert $alert)
    {
        $alert->delete();

        return redirect()->route('alerts.index')
            ->with('success', 'Alerta eliminada exitosamente.');
    }

    public function toggle(Alert $alert)
    {
        $alert->update([
            'is_active' => !$alert->is_active
        ]);

        $status = $alert->is_active ? 'activada' : 'desactivada';

        return back()->with('success', "Alerta {$status} exitosamente.");
    }
}
