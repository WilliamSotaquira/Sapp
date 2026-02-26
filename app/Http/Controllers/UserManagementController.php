<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->orderBy('name')
            ->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identification_number' => 'required|string|max:30|unique:users,identification_number',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:user,technician,admin',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'identification_number' => $validated['identification_number'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identification_number' => ['required', 'string', 'max:30', Rule::unique('users', 'identification_number')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:user,technician,admin',
        ]);

        $payload = [
            'name' => $validated['name'],
            'identification_number' => $validated['identification_number'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);
        return redirect()->route('users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy(User $user)
    {
        if ((int) auth()->id() === (int) $user->id) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        if ($user->technician()->exists()) {
            return back()->with('error', 'No se puede eliminar: el usuario tiene perfil técnico asociado.');
        }

        $assignedRequestsCount = ServiceRequest::withTrashed()
            ->where('assigned_to', $user->id)
            ->count();

        if ($assignedRequestsCount > 0) {
            return back()->with(
                'error',
                "No se puede eliminar: el usuario está asignado como técnico en {$assignedRequestsCount} solicitud(es). Reasigna esas solicitudes primero."
            );
        }

        $requestedRequestsCount = ServiceRequest::withTrashed()
            ->where('requested_by', $user->id)
            ->count();

        if ($requestedRequestsCount > 0) {
            return back()->with(
                'error',
                "No se puede eliminar: el usuario figura como creador en {$requestedRequestsCount} solicitud(es)."
            );
        }

        try {
            $user->companies()->detach();
            $user->delete();
        } catch (QueryException $e) {
            return back()->with(
                'error',
                'No se puede eliminar el usuario porque tiene información relacionada en el sistema. Elimina o reasigna esas relaciones primero.'
            );
        }

        return redirect()->route('users.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }

}
