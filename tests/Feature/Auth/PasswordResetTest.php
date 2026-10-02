<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El restablecimiento público de contraseña fue eliminado del diseño (§4/§8):
 * el acceso lo administra el líder técnico, no hay flujo público de reset.
 * Estas pruebas confirman que las rutas correspondientes YA NO existen (404),
 * en línea con §10.1 (aserción de "ruta ausente").
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_route_is_absent(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', ['email' => 'x@example.com'])->assertNotFound();
    }

    public function test_reset_password_route_is_absent(): void
    {
        $this->get('/reset-password/some-token')->assertNotFound();
        $this->post('/reset-password', [
            'token' => 'some-token',
            'email' => 'x@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }

    public function test_password_reset_route_name_is_not_registered(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('password.request'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('password.reset'));
    }
}
