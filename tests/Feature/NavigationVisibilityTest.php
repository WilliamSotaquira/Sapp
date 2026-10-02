<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §10.7 — Navegación (capa 4, cosmética), casos 41-43.
 *
 * El técnico NO ve enlaces administrativos (usuarios, entidades, reportes,
 * configuración); el admin sí. El filtrado es cosmético: la protección real
 * (403 ante URL forzada) la cubre AdminRouteGuardTest (caso 43, referenciado).
 */
class NavigationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Contract $contract;

    private function workspace(User $user): array
    {
        // Entidad + contrato único accesible -> EnsureWorkspaceSelected auto-selecciona.
        $this->company = Company::create(['name' => 'Entidad Nav', 'status' => 'active']);
        $this->contract = Contract::create([
            'company_id' => $this->company->id,
            'number' => 'C-NAV-001',
            'name' => 'Contrato nav',
            'description' => 'Contrato de prueba',
            'is_active' => true,
        ]);
        $this->company->update(['active_contract_id' => $this->contract->id]);
        $user->companies()->syncWithoutDetaching([$this->company->id]);

        return [
            'current_company_id' => $this->company->id,
            'current_contract_id' => $this->contract->id,
        ];
    }

    /**
     * Enlaces administrativos que la navegación renderiza como <a href>.
     * (settings.edit NO está en la barra de navegación — es administración
     * global sin enlace en el menú; su blindaje lo cubre AdminRouteGuardTest.)
     */
    private function adminLinkUrls(): array
    {
        return [
            route('users.index'),
            route('companies.index'),
            route('reports.index'),
        ];
    }

    // 41 — render como técnico: NO contiene enlaces administrativos.
    public function test_technician_navigation_hides_admin_links(): void
    {
        // Un admin ocupa id=1 primero: así el técnico NO es el dueño incondicional
        // (isAdmin() trata id===1 como admin y mostraría toda la navegación).
        User::factory()->admin()->create();

        $user = User::factory()->technicianRole()->create();
        Technician::factory()->create(['user_id' => $user->id]);
        $session = $this->workspace($user);

        $response = $this->actingAs($user)->withSession($session)->get(route('my-space.index'));
        $response->assertOk();

        foreach ($this->adminLinkUrls() as $url) {
            $this->assertStringNotContainsString($url, $response->getContent(), "Técnico NO debe ver: {$url}");
        }
    }

    // 42 — render como admin: SÍ contiene los enlaces administrativos.
    public function test_admin_navigation_shows_admin_links(): void
    {
        $user = User::factory()->admin()->create();
        $session = $this->workspace($user);

        $response = $this->actingAs($user)->withSession($session)->get(route('my-space.index'));
        $response->assertOk();

        foreach ($this->adminLinkUrls() as $url) {
            $response->assertSee($url, false);
        }
    }

    // 43 — defensa en profundidad: la URL forzada igualmente da 403.
    // Cubierto por AdminRouteGuardTest; se referencia aquí para trazabilidad.
    public function test_forced_admin_url_still_forbidden_for_technician(): void
    {
        // Admin en id=1 para que el técnico no sea el dueño incondicional.
        User::factory()->admin()->create();

        $user = User::factory()->technicianRole()->create();
        Technician::factory()->create(['user_id' => $user->id]);
        $session = $this->workspace($user);

        $this->actingAs($user)->withSession($session)->get(route('users.index'))->assertForbidden();
    }
}
