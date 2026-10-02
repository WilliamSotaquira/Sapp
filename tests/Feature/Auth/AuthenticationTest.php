<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    /**
     * §10.1 — El administrador (líder del proceso) puede autenticar y es
     * redirigido al dashboard. El rol se fija explícitamente para NO depender
     * de que el primer usuario del run obtenga id=1.
     */
    public function test_admin_can_authenticate(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    /**
     * §10.1 — Un técnico autorizado (role=technician, correo verificado)
     * puede autenticar.
     */
    public function test_technician_with_access_can_authenticate(): void
    {
        $user = User::factory()->technicianRole()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    /**
     * §10.1 — Un usuario plano (role=user) NO tiene acceso: queda invitado y
     * recibe el mensaje de cuenta sin acceso. Se crea un admin primero para que
     * el usuario plano NO obtenga id=1 (que isAdmin() trataría como dueño).
     */
    public function test_plain_user_cannot_authenticate(): void
    {
        User::factory()->admin()->create();
        $user = User::factory()->plainUser()->create();

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
        $this->assertEquals(
            'Esta cuenta no tiene acceso a la aplicación.',
            session('errors')->get('login')[0]
        );
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->admin()->create();

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
