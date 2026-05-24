<?php

namespace Tests\Feature\Reports;

use App\Models\Company;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsIndexPageTest extends TestCase
{
    use RefreshDatabase;

    private function seedContext(): array
    {
        $user = User::factory()->create();

        $company = Company::create([
            'name' => 'Test Company',
            'status' => 'active',
        ]);

        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-IDX-001',
            'name' => 'Contrato index test',
            'description' => 'Contrato de prueba para index',
            'is_active' => true,
        ]);

        $company->update(['active_contract_id' => $contract->id]);
        $user->companies()->attach($company->id);

        return compact('user', 'company');
    }

    public function test_index_page_renders_exactly_7_cards_in_correct_order(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.index'));

        $response->assertOk();

        // Verify all 7 card titles are present
        $response->assertSee('Cortes');
        $response->assertSee('Informe Analítico por Corte');
        $response->assertSee('Línea de Tiempo');
        $response->assertSee('Reporte por Rango de Tiempo');
        $response->assertSee('Servicios y SLA');
        $response->assertSee('Panorama Operativo');
        $response->assertSee('Búsqueda y Análisis');

        // Verify correct order by checking positions in the HTML
        $content = $response->getContent();
        $posCortes = strpos($content, '>Cortes</h3>');
        $posAnalitico = strpos($content, '>Informe Analítico por Corte</h3>');
        $posTimeline = strpos($content, '>Línea de Tiempo</h3>');
        $posTimeRange = strpos($content, '>Reporte por Rango de Tiempo</h3>');
        $posSla = strpos($content, '>Servicios y SLA</h3>');
        $posPanorama = strpos($content, '>Panorama Operativo</h3>');
        $posBusqueda = strpos($content, '>Búsqueda y Análisis</h3>');

        $this->assertNotFalse($posCortes);
        $this->assertNotFalse($posAnalitico);
        $this->assertNotFalse($posTimeline);
        $this->assertNotFalse($posTimeRange);
        $this->assertNotFalse($posSla);
        $this->assertNotFalse($posPanorama);
        $this->assertNotFalse($posBusqueda);

        // Verify order
        $this->assertLessThan($posAnalitico, $posCortes);
        $this->assertLessThan($posTimeline, $posAnalitico);
        $this->assertLessThan($posTimeRange, $posTimeline);
        $this->assertLessThan($posSla, $posTimeRange);
        $this->assertLessThan($posPanorama, $posSla);
        $this->assertLessThan($posBusqueda, $posPanorama);
    }

    public function test_index_page_has_correct_routes_for_each_card(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.index'));

        $response->assertOk();

        // Verify each card links to the correct route
        $response->assertSee(route('reports.cuts.index'));
        $response->assertSee(route('reports.timeline.index'));
        $response->assertSee(route('reports.time-range.index'));
        $response->assertSee(route('reports.services-sla.index'));
        $response->assertSee(route('reports.operational-overview.index'));
        $response->assertSee(route('reports.search-analysis.index'));
    }

    public function test_index_page_has_responsive_grid_classes(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.index'));

        $response->assertOk();

        $content = $response->getContent();

        // Verify responsive grid: 1 col default, 2 cols at sm (640px), 3 cols at lg (1024px)
        $this->assertStringContainsString('grid-cols-1', $content);
        $this->assertStringContainsString('sm:grid-cols-2', $content);
        $this->assertStringContainsString('lg:grid-cols-3', $content);
    }

    public function test_index_page_cards_have_unique_border_colors(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.index'));

        $response->assertOk();

        $content = $response->getContent();

        // Each card should have a unique border color
        $expectedColors = [
            'border-blue-500',
            'border-purple-500',
            'border-green-500',
            'border-orange-500',
            'border-teal-500',
            'border-red-500',
            'border-indigo-500',
        ];

        foreach ($expectedColors as $color) {
            $this->assertStringContainsString($color, $content);
        }

        // Verify all colors are unique (each appears exactly once as a border-l-4 class)
        foreach ($expectedColors as $color) {
            $count = substr_count($content, 'border-l-4 ' . $color);
            $this->assertEquals(1, $count, "Border color {$color} should appear exactly once, found {$count} times");
        }
    }

    public function test_index_page_does_not_contain_removed_cards(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.index'));

        $response->assertOk();

        $content = $response->getContent();

        // Verify removed cards are not present as card titles in the grid
        // (they may still appear in the navigation layout, so we check h3 tags)
        $removedCards = [
            'Estadísticas Rápidas',
            'Timeline por Ticket',
            'Cumplimiento de SLA',
            'Solicitudes por Estado',
            'Niveles de Criticidad',
            'Rendimiento de Servicios',
            'Tendencias Mensuales',
        ];

        foreach ($removedCards as $card) {
            $this->assertStringNotContainsString(
                ">{$card}</h3>",
                $content,
                "Removed card '{$card}' should not appear as a card title in the index page"
            );
        }
    }
}
