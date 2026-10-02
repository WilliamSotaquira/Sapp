<?php

namespace Tests\Feature\Auth;

use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §10.2 — Matriz de acceso al login (defensa capa 1).
 *
 * Prueba canAccessPanel() en el muro de LoginRequest::authenticate(): quién
 * puede iniciar sesión y quién no, de forma independiente del id del usuario.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function login(User $user, string $password = 'password'): \Illuminate\Testing\TestResponse
    {
        return $this->post('/login', [
            'login' => $user->email,
            'password' => $password,
        ]);
    }

    /**
     * Caso 1 — role=user → login rechazado (guest). Se crea un admin antes para
     * que el usuario plano NO obtenga id=1 (que isAdmin() trataría como dueño).
     */
    public function test_plain_user_is_rejected(): void
    {
        User::factory()->admin()->create();
        $user = User::factory()->plainUser()->create();

        $this->login($user);

        $this->assertGuest();
    }

    /**
     * Caso 2 — technician sin perfil Technician → login aceptado; al entrar a
     * my-agenda ve la vista no-technician-profile.
     *
     * my-agenda pasa por EnsureWorkspaceSelected, por lo que el técnico necesita
     * un contrato accesible (se auto-selecciona si hay uno solo). Sin perfil
     * Technician el controlador devuelve la vista no-technician-profile.
     */
    public function test_technician_without_profile_is_accepted_and_sees_no_profile_view(): void
    {
        $user = User::factory()->technicianRole()->create();

        // Un único contrato accesible → EnsureWorkspaceSelected lo auto-selecciona.
        $company = \App\Models\Company::create(['name' => 'Entidad Agenda', 'status' => 'active']);
        $contract = \App\Models\Contract::create([
            'company_id' => $company->id,
            'number' => 'C-AGENDA-001',
            'name' => 'Contrato agenda',
            'description' => 'Contrato de prueba',
            'is_active' => true,
        ]);
        $company->update(['active_contract_id' => $contract->id]);
        $user->companies()->syncWithoutDetaching([$company->id]);

        $this->login($user);
        $this->assertAuthenticatedAs($user);

        $response = $this->actingAs($user)->get(route('technician-schedule.my-agenda'));
        $response->assertOk();
        $response->assertViewIs('technician-schedule.no-technician-profile');
    }

    /**
     * Caso 3 — technician con perfil → login aceptado.
     */
    public function test_technician_with_profile_is_accepted(): void
    {
        $user = User::factory()->technicianRole()->create();
        Technician::factory()->create(['user_id' => $user->id]);

        $this->login($user);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Caso 4 — admin → login aceptado.
     */
    public function test_admin_is_accepted(): void
    {
        $user = User::factory()->admin()->create();

        $this->login($user);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Caso 5 — id===1 con role=user → login aceptado (dueño incondicional).
     * Es el ÚNICO caso que se apoya en id===1 a propósito.
     */
    public function test_owner_id_one_with_plain_role_is_accepted(): void
    {
        // Se fija id=1 explícitamente: isAdmin() trata al id===1 como dueño
        // incondicional aunque su columna role sea 'user'.
        $user = User::factory()->plainUser()->create(['id' => 1]);

        $this->assertSame(1, $user->id);
        $this->assertTrue($user->isAdmin());

        $this->login($user);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Caso 6 — cuenta sin password usable → login rechazado. Se crea un admin
     * primero para que esta cuenta no sea el dueño id=1. El password vacío
     * produce un hash inservible, por lo que Auth::attempt siempre falla.
     */
    public function test_account_without_password_is_rejected(): void
    {
        User::factory()->admin()->create();
        $user = User::factory()->admin()->create();
        // Password inservible (sin credencial válida), sin violar NOT NULL.
        $user->forceFill(['password' => ''])->saveQuietly();

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }
}
