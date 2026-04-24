<?php

namespace Tests\Feature\Reports;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Cut;
use App\Models\Requester;
use App\Models\Service;
use App\Models\ServiceFamily;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceRequest;
use App\Models\SubService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CutAnalyticsReportTest extends TestCase
{
    use RefreshDatabase;

    private function seedContext(): array
    {
        $user = User::factory()->create();

        $company = Company::create([
            'name' => 'Movilidad Test',
            'status' => 'active',
        ]);

        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-ANA-001',
            'name' => 'Contrato analitico',
            'description' => 'Contrato de prueba',
            'is_active' => true,
        ]);

        $company->update(['active_contract_id' => $contract->id]);
        $user->companies()->attach($company->id);

        $cut = Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Corte abril',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'created_by' => $user->id,
        ]);

        $family = ServiceFamily::create([
            'contract_id' => $contract->id,
            'name' => 'Gestion editorial',
            'code' => 'GED',
            'description' => 'Familia para informe analitico',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Registro de publicaciones',
            'code' => 'REGPUB',
            'description' => 'Servicio de prueba',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $service->id,
            'name' => 'Registro diario',
            'code' => 'REGDIARIO',
            'description' => 'Subservicio de prueba',
            'is_active' => true,
            'order' => 0,
        ]);

        $sla = ServiceLevelAgreement::create([
            'service_family_id' => $family->id,
            'name' => 'SLA MEDIA',
            'criticality_level' => 'MEDIA',
            'response_time_hours' => 2,
            'resolution_time_hours' => 6,
            'availability_percentage' => 99.90,
            'acceptance_time_minutes' => 60,
            'response_time_minutes' => 120,
            'resolution_time_minutes' => 360,
            'conditions' => 'Test',
            'is_active' => true,
        ]);

        $requester = Requester::factory()->create([
            'company_id' => $company->id,
            'department' => 'Comunicaciones',
        ]);

        $request = ServiceRequest::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'requester_id' => $requester->id,
            'title' => 'Publicacion de boletin interno',
            'description' => 'Detalle de prueba',
            'sub_service_id' => $subService->id,
            'sla_id' => $sla->id,
            'requested_by' => $user->id,
            'assigned_to' => $user->id,
            'entry_channel' => 'email_corporativo',
            'main_web_route' => '/intranet/boletines/abril',
            'criticality_level' => 'MEDIA',
            'status' => 'PENDIENTE',
        ]);

        $request->forceFill([
            'status' => 'CERRADA',
            'created_at' => '2026-04-10 08:00:00',
            'updated_at' => '2026-04-10 12:00:00',
            'resolved_at' => '2026-04-10 12:00:00',
            'closed_at' => '2026-04-10 12:00:00',
        ])->saveQuietly();

        $cut->serviceRequests()->attach($request->id);

        return compact('user', 'company', 'cut', 'family', 'request');
    }

    public function test_cut_analytics_view_renders_with_summary_data(): void
    {
        $data = $this->seedContext();

        $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.cuts.analytics', $data['cut']))
            ->assertOk()
            ->assertSee('Informe analitico de gestion y registro')
            ->assertSee('Publicacion de boletin interno')
            ->assertSee('Comunicaciones')
            ->assertSee('Email corporativo');
    }

    public function test_cut_analytics_csv_export_downloads_detail_rows(): void
    {
        $data = $this->seedContext();

        $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->get(route('reports.cuts.analytics.export.csv', [
                'cut' => $data['cut'],
                'families' => [$data['family']->id],
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertSee('Publicacion de boletin interno')
            ->assertSee('/intranet/boletines/abril');
    }
}
