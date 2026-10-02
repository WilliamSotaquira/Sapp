<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El registro público fue eliminado del diseño (§4/§8): el líder técnico crea
 * y habilita a los usuarios desde el panel; no hay auto-registro. Estas pruebas
 * confirman que las rutas de registro YA NO existen (404), en línea con §10.1
 * (aserción de "ruta ausente").
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_route_is_absent(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }

    public function test_register_route_name_is_not_registered(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('register'));
    }
}
