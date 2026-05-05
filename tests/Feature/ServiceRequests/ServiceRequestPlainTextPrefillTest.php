<?php

namespace Tests\Feature\ServiceRequests;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Requester;
use App\Models\Service;
use App\Models\ServiceFamily;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceSubservice;
use App\Models\SubService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceRequestPlainTextPrefillTest extends TestCase
{
    use RefreshDatabase;

    private function seedContext(): array
    {
        $user = User::factory()->create();

        $company = Company::create([
            'name' => 'Ministerio de Cultura',
            'status' => 'active',
        ]);

        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'MINC-2026',
            'name' => 'Contrato portal principal',
            'description' => 'Contrato activo',
            'is_active' => true,
        ]);

        $company->update(['active_contract_id' => $contract->id]);
        $user->companies()->attach($company->id);

        $family = ServiceFamily::create([
            'contract_id' => $contract->id,
            'name' => 'Gestión Integral de la Estrategia Digital',
            'code' => 'GIED',
            'description' => 'Familia digital',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Gestión de Contenidos y Actualizaciones Web',
            'code' => 'GCAW',
            'description' => 'Servicio web',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $service->id,
            'name' => 'Actualización de Contenidos en Portal Principal',
            'code' => 'ACT_PORTAL',
            'description' => 'Actualización de contenidos',
            'is_active' => true,
            'order' => 0,
        ]);

        $serviceSubservice = ServiceSubservice::create([
            'service_family_id' => $family->id,
            'service_id' => $service->id,
            'sub_service_id' => $subService->id,
            'name' => 'Actualización de contenidos',
            'description' => 'Relación activa',
            'is_active' => true,
        ]);

        $sla = ServiceLevelAgreement::create([
            'service_subservice_id' => $serviceSubservice->id,
            'service_family_id' => $family->id,
            'name' => 'SLA MEDIA',
            'criticality_level' => 'MEDIA',
            'response_time_hours' => 1,
            'resolution_time_hours' => 8,
            'availability_percentage' => 99.90,
            'acceptance_time_minutes' => 30,
            'response_time_minutes' => 60,
            'resolution_time_minutes' => 480,
            'conditions' => null,
            'is_active' => true,
        ]);

        if (Schema::hasColumn('service_level_agreements', 'sub_service_id')) {
            $sla->forceFill(['sub_service_id' => $subService->id])->save();
        }

        return compact('user', 'company', 'contract', 'family', 'service', 'subService', 'sla');
    }

    private function samplePlainText(): string
    {
        return <<<'TEXT'
RE: Uso de imagen página ley general de cultura

Se solicita retirar y reemplazar la imagen ubicada en la landing de la Ley General de Cultura debido a que no se cuenta con autorización para su uso. Se ha gestionado la creación de una nueva pieza con apoyo del equipo de diseño, la cual ya fue entregada para su implementación en el sitio web, con el fin de evitar riesgos legales o la generación de PQRS por parte de la ciudadanía.

20 de abril de 2026 11:40 a.m.

Laura Camila Ceron Bonell

Actualización de Contenidos en Portal Principal

[https://www.mincultura.gov.co/despacho/ley-general-de-cultura/Paginas/index.aspx](https://www.mincultura.gov.co/despacho/ley-general-de-cultura/Paginas/index.aspx)

Reemplazo de imagen en landing Ley General de Cultura (3 subtareas)

* Validar la nueva imagen recibida y su cumplimiento de lineamientos técnicos y de contenido (15 min)
* Reemplazar la imagen actual en la landing por la nueva pieza aprobada (20 min)
* Confirmar actualización con el solicitante y equipo involucrado (10 min)
TEXT;
    }

    private function samplePlainTextWithoutDateAndBullets(): string
    {
        return <<<'TEXT'
Actualización micrositio DEDE | Inclusión sección o categoría 'Transparencia' en Escuelas Taller

Se solicita incorporar una nueva categoría visible denominada “Transparencia” en el módulo de “Accesos directos” del micrositio del Programa Nacional de Escuelas Taller, con el fin de publicar contenidos asociados al cumplimiento de la Ley de Transparencia y Acceso a la Información Pública. La sección incluirá archivos como informes de gestión y un normograma suministrado. Adicionalmente, se propone reemplazar el ítem actual “Programa Nacional de Escuelas Taller” debido a que no aporta valor de navegación y redirige fuera del sitio.

Jazmin Rodriguez Cespedes

Actualización de Secciones del Portal Principal

https://www.mincultura.gov.co/direcciones/estrategia-desarrollo-y-emprendimiento/Paginas/grupo-escuelas-taller-de-colombia/grupo-escuelas-taller-de-colombia.aspx

Inclusión de sección de Transparencia en micrositio Escuelas Taller (4 subtareas)
Validar estructura actual del módulo “Accesos directos” y viabilidad de inclusión de nueva categoría (15 min)
Configurar nueva categoría “Transparencia” en el módulo correspondiente (20 min)
Cargar y vincular los contenidos suministrados dentro de la nueva sección (20 min)
Confirmar implementación con el solicitante y validar correcta visualización (10 min)
TEXT;
    }

    private function sampleStructuredPlainTextWithoutLinks(): string
    {
        return <<<'TEXT'
Actualización micrositio DEDE | Inclusión sección o categoría 'Transparencia' en Escuelas Taller

Se solicita incorporar una nueva categoría visible denominada “Transparencia” en el módulo de “Accesos directos” del micrositio del Programa Nacional de Escuelas Taller.

Jazmin Rodriguez Cespedes

Actualización de Secciones del Portal Principal

Inclusión de sección de Transparencia en micrositio Escuelas Taller (2 subtareas)

* Validar estructura actual del módulo “Accesos directos” y viabilidad de inclusión de nueva categoría (15 min)
* Configurar nueva categoría “Transparencia” en el módulo correspondiente (20 min)
TEXT;
    }

    private function sampleExactStructuredPlainText(): string
    {
        return <<<'TEXT'
RE: Uso de imagen página ley general de cultura
Se solicita retirar y reemplazar la imagen ubicada en la landing de la Ley General de Cultura debido a que no se cuenta con autorización para su uso.
Mié 22/04/2026, 11:00 AM
10 de mayo de 2026
Laura Camila Ceron Bonell
Reunión
Actualización de Contenidos en Portal Principal
https://www.mincultura.gov.co/despacho/ley-general-de-cultura/Paginas/index.aspx, https://www.mincultura.gov.co/
Media
Reemplazo de imagen en landing Ley General de Cultura (3 subtareas)
- Validar la nueva imagen recibida y su cumplimiento de lineamientos técnicos y de contenido (15 min)
- Reemplazar la imagen actual en la landing por la nueva pieza aprobada (20 min)
- Confirmar con el solicitante el registro y cierre (10 min)
TEXT;
    }

    private function sampleExactStructuredPlainTextWithUnavailableOptionalFields(): string
    {
        return <<<'TEXT'
RE: Uso de imagen página ley general de cultura

Se solicita retirar y reemplazar la imagen ubicada en la landing de la Ley General de Cultura debido a que no se cuenta con autorización para su uso.

4 de mayo de 2026 10:30 a. m.

No disponible

Laura Camila Ceron Bonell

Reunión

Actualización de Contenidos en Portal Principal

No disponible

Media

Reemplazo de imagen en landing Ley General de Cultura (3 subtareas)
- Validar la nueva imagen recibida y su cumplimiento de lineamientos técnicos y de contenido (15 min)
- Reemplazar la imagen actual en la landing por la nueva pieza aprobada (20 min)
- Confirmar actualización con el solicitante y equipo involucrado (10 min)
TEXT;
    }

    private function sampleExactStructuredPlainTextWithoutSubservice(): string
    {
        return <<<'TEXT'
Presentación Mapa Familias Lingüísticas

Se requiere coordinación y participación en reunión convocada para la presentación del “Mapa de Familias Lingüísticas”, con el fin de conocer avances, lineamientos y posibles requerimientos técnicos o de contenido asociados.

Mié 22/04/2026, 11:00 AM

No disponible

Edwin Armando Zúñiga Abril

Reunión

No disponible

Media

Participación y levantamiento de requerimientos en reunión de presentación (3 subtareas)

- Revisar el contexto de la presentación y validar los requerimientos asociados (20 min)
- Coordinar la participación y registrar acuerdos con las áreas involucradas (25 min)
- Responder al solicitante confirmando la gestión y los próximos pasos (10 min)
TEXT;
    }

    public function test_plain_text_prefill_redirects_with_form_data_ready_for_review(): void
    {
        $data = $this->seedContext();

        $requester = Requester::factory()->create([
            'company_id' => $data['company']->id,
            'name' => 'Laura Camila Ceron Bonell',
            'email' => 'laura@example.com',
        ]);

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->post(route('service-requests.prefill-from-text'), [
                'plain_text' => $this->samplePlainText(),
            ]);

        $response->assertRedirect(route('service-requests.create'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('_old_input.title', 'Uso de imagen página ley general de cultura');
        $response->assertSessionHas('_old_input.requester_id', $requester->id);
        $response->assertSessionHas('_old_input.sub_service_id', $data['subService']->id);
        $response->assertSessionHas('_old_input.entry_channel', 'email_corporativo');
        $response->assertSessionHas('_old_input.criticality_level', 'MEDIA');
        $response->assertSessionHas('_old_input.created_at', '2026-04-20T11:40');
        $response->assertSessionHas('_old_input.tasks.0.title', 'Reemplazo de imagen en landing Ley General de Cultura');
        $response->assertSessionHas('_old_input.tasks.0.subtasks.0.title', 'Validar la nueva imagen recibida y su cumplimiento de lineamientos técnicos y de contenido');
        $response->assertSessionHas('_old_input.tasks.0.subtasks.1.estimated_minutes', 20);
        $response->assertSessionHas('_old_input.tasks.0.subtasks.2.estimated_minutes', 10);

        $webRoutes = json_decode((string) session('_old_input.web_routes'), true);
        $this->assertContains(
            'https://www.mincultura.gov.co/despacho/ley-general-de-cultura/Paginas/index.aspx',
            is_array($webRoutes) ? $webRoutes : []
        );
        $this->assertStringContainsString(
            'Se solicita retirar y reemplazar la imagen ubicada en la landing de la Ley General de Cultura',
            (string) session('_old_input.description')
        );
    }

    public function test_plain_text_prefill_creates_requester_when_missing_in_workspace(): void
    {
        $data = $this->seedContext();

        $response = $this->actingAs($data['user'])
            ->withSession(['current_company_id' => $data['company']->id])
            ->post(route('service-requests.prefill-from-text'), [
                'plain_text' => $this->samplePlainText(),
            ]);

        $response->assertRedirect(route('service-requests.create'));

        $createdRequester = Requester::withoutGlobalScopes()
            ->where('company_id', $data['company']->id)
            ->where('name', 'Laura Camila Ceron Bonell')
            ->first();

        $this->assertNotNull($createdRequester);
        $response->assertSessionHas('_old_input.requester_id', $createdRequester->id);
    }

    public function test_plain_text_prefill_handles_text_without_date_and_without_bullets(): void
    {
        Carbon::setTestNow('2026-05-03 09:15:00');

        try {
            $data = $this->seedContext();

            $requester = Requester::factory()->create([
                'company_id' => $data['company']->id,
                'name' => 'Jazmin Rodriguez Cespedes',
                'email' => 'jazmin@example.com',
            ]);

            $subService = SubService::create([
                'service_id' => $data['service']->id,
                'name' => 'Actualización de Secciones del Portal Principal',
                'code' => 'ACT_SECCIONES',
                'description' => 'Actualización de secciones',
                'is_active' => true,
                'order' => 1,
            ]);

            ServiceSubservice::create([
                'service_family_id' => $data['family']->id,
                'service_id' => $data['service']->id,
                'sub_service_id' => $subService->id,
                'name' => 'Actualización de secciones',
                'description' => 'Relación activa',
                'is_active' => true,
            ]);

            $response = $this->actingAs($data['user'])
                ->withSession(['current_company_id' => $data['company']->id])
                ->post(route('service-requests.prefill-from-text'), [
                    'plain_text' => $this->samplePlainTextWithoutDateAndBullets(),
                ]);

            $response->assertRedirect(route('service-requests.create'));
            $response->assertSessionHas('success');
            $response->assertSessionHas('_old_input.title', "Actualización micrositio DEDE | Inclusión sección o categoría 'Transparencia' en Escuelas Taller");
            $response->assertSessionHas('_old_input.requester_id', $requester->id);
            $response->assertSessionHas('_old_input.sub_service_id', $subService->id);
            $response->assertSessionHas('_old_input.created_at', '2026-05-03T09:15');
            $response->assertSessionHas('_old_input.tasks.0.title', 'Inclusión de sección de Transparencia en micrositio Escuelas Taller');
            $response->assertSessionHas('_old_input.tasks.0.subtasks.0.estimated_minutes', 15);
            $response->assertSessionHas('_old_input.tasks.0.subtasks.1.estimated_minutes', 20);
            $response->assertSessionHas('_old_input.tasks.0.subtasks.2.estimated_minutes', 20);
            $response->assertSessionHas('_old_input.tasks.0.subtasks.3.estimated_minutes', 10);

            $webRoutes = json_decode((string) session('_old_input.web_routes'), true);
            $this->assertContains(
                'https://www.mincultura.gov.co/direcciones/estrategia-desarrollo-y-emprendimiento/Paginas/grupo-escuelas-taller-de-colombia/grupo-escuelas-taller-de-colombia.aspx',
                is_array($webRoutes) ? $webRoutes : []
            );
            $this->assertStringContainsString(
                'Se solicita incorporar una nueva categoría visible denominada “Transparencia”',
                (string) session('_old_input.description')
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_plain_text_prefill_uses_expected_block_structure_and_supports_optional_links(): void
    {
        Carbon::setTestNow('2026-05-04 10:30:00');

        try {
            $data = $this->seedContext();

            $requester = Requester::factory()->create([
                'company_id' => $data['company']->id,
                'name' => 'Jazmin Rodriguez Cespedes',
            ]);

            $subService = SubService::create([
                'service_id' => $data['service']->id,
                'name' => 'Actualización de Secciones del Portal Principal',
                'code' => 'ACT_SECCIONES',
                'description' => 'Actualización de secciones',
                'is_active' => true,
                'order' => 1,
            ]);

            ServiceSubservice::create([
                'service_family_id' => $data['family']->id,
                'service_id' => $data['service']->id,
                'sub_service_id' => $subService->id,
                'name' => 'Actualización de secciones',
                'description' => 'Relación activa',
                'is_active' => true,
            ]);

            $response = $this->actingAs($data['user'])
                ->withSession(['current_company_id' => $data['company']->id])
                ->post(route('service-requests.prefill-from-text'), [
                    'plain_text' => $this->sampleStructuredPlainTextWithoutLinks(),
                ]);

            $response->assertRedirect(route('service-requests.create'));
            $response->assertSessionHas('success');
            $response->assertSessionHas('_old_input.title', "Actualización micrositio DEDE | Inclusión sección o categoría 'Transparencia' en Escuelas Taller");
            $response->assertSessionHas('_old_input.requester_id', $requester->id);
            $response->assertSessionHas('_old_input.sub_service_id', $subService->id);
            $response->assertSessionHas('_old_input.created_at', '2026-05-04T10:30');
            $response->assertSessionHas('_old_input.tasks.0.title', 'Inclusión de sección de Transparencia en micrositio Escuelas Taller');
            $response->assertSessionHas('_old_input.tasks.0.subtasks.0.estimated_minutes', 15);
            $response->assertSessionHas('_old_input.tasks.0.subtasks.1.estimated_minutes', 20);

            $webRoutes = json_decode((string) session('_old_input.web_routes'), true);
            $this->assertSame([], is_array($webRoutes) ? $webRoutes : []);
            $this->assertStringContainsString(
                'Se solicita incorporar una nueva categoría visible denominada “Transparencia”',
                (string) session('_old_input.description')
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_plain_text_prefill_parses_exact_nineteen_line_format(): void
    {
        Carbon::setTestNow('2026-05-04 10:30:00');

        try {
            $data = $this->seedContext();

            $requester = Requester::factory()->create([
                'company_id' => $data['company']->id,
                'name' => 'Laura Camila Ceron Bonell',
                'email' => 'laura@example.com',
            ]);

            $response = $this->actingAs($data['user'])
                ->withSession(['current_company_id' => $data['company']->id])
                ->post(route('service-requests.prefill-from-text'), [
                    'plain_text' => $this->sampleExactStructuredPlainText(),
                ]);

            $response->assertRedirect(route('service-requests.create'));
            $response->assertSessionHas('success');
            $response->assertSessionHas('_old_input.title', 'Uso de imagen página ley general de cultura');
            $response->assertSessionHas('_old_input.requester_id', $requester->id);
            $response->assertSessionHas('_old_input.entry_channel', 'reunion');
            $response->assertSessionHas('_old_input.sub_service_id', $data['subService']->id);
            $response->assertSessionHas('_old_input.criticality_level', 'MEDIA');
            $response->assertSessionHas('_old_input.created_at', '2026-04-22T11:00');
            $response->assertSessionHas('_old_input.due_date', '2026-05-10');
            $response->assertSessionHas('_old_input.tasks.0.title', 'Reemplazo de imagen en landing Ley General de Cultura');
            $response->assertSessionHas('_old_input.tasks.0.subtasks.0.title', 'Validar la nueva imagen recibida y su cumplimiento de lineamientos técnicos y de contenido');
            $response->assertSessionHas('_old_input.tasks.0.subtasks.1.estimated_minutes', 20);
            $response->assertSessionHas('_old_input.tasks.0.subtasks.2.estimated_minutes', 10);

            $webRoutes = json_decode((string) session('_old_input.web_routes'), true);
            $this->assertIsArray($webRoutes);
            $this->assertContains('https://www.mincultura.gov.co/despacho/ley-general-de-cultura/Paginas/index.aspx', $webRoutes);
            $this->assertContains('https://www.mincultura.gov.co/', $webRoutes);
            $this->assertStringContainsString(
                'Se solicita retirar y reemplazar la imagen ubicada en la landing de la Ley General de Cultura',
                (string) session('_old_input.description')
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_plain_text_prefill_accepts_no_disponible_in_optional_slots(): void
    {
        Carbon::setTestNow('2026-05-04 10:30:00');

        try {
            $data = $this->seedContext();

            $requester = Requester::factory()->create([
                'company_id' => $data['company']->id,
                'name' => 'Laura Camila Ceron Bonell',
                'email' => 'laura@example.com',
            ]);

            $response = $this->actingAs($data['user'])
                ->withSession(['current_company_id' => $data['company']->id])
                ->post(route('service-requests.prefill-from-text'), [
                    'plain_text' => $this->sampleExactStructuredPlainTextWithUnavailableOptionalFields(),
                ]);

            $response->assertRedirect(route('service-requests.create'));
            $response->assertSessionHas('success');
            $response->assertSessionHas('_old_input.title', 'Uso de imagen página ley general de cultura');
            $response->assertSessionHas('_old_input.requester_id', $requester->id);
            $response->assertSessionHas('_old_input.entry_channel', 'reunion');
            $response->assertSessionHas('_old_input.sub_service_id', $data['subService']->id);
            $response->assertSessionHas('_old_input.criticality_level', 'MEDIA');
            $response->assertSessionHas('_old_input.created_at', '2026-05-04T10:30');
            $this->assertNull(session('_old_input.due_date'));

            $webRoutes = json_decode((string) session('_old_input.web_routes'), true);
            $this->assertSame([], is_array($webRoutes) ? $webRoutes : []);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_plain_text_prefill_infers_generic_subservice_when_subservice_is_unavailable(): void
    {
        Carbon::setTestNow('2026-04-22 11:00:00');

        try {
            $data = $this->seedContext();

            $generalFamily = ServiceFamily::create([
                'contract_id' => $data['contract']->id,
                'name' => 'Servicios Generales',
                'code' => 'SERV_GEN',
                'description' => 'Servicios generales del contrato',
                'is_active' => true,
                'sort_order' => 99,
            ]);

            $generalService = Service::create([
                'service_family_id' => $generalFamily->id,
                'name' => 'Servicios Generales',
                'code' => 'GEN',
                'description' => 'Gestión general',
                'is_active' => true,
                'order' => 99,
            ]);

            $generalSubService = SubService::create([
                'service_id' => $generalService->id,
                'name' => 'Solicitud de Apoyo General',
                'code' => 'APOYO_GENERAL',
                'description' => 'Solicitud de apoyo general no categorizado en otros subservicios',
                'is_active' => true,
                'order' => 1,
            ]);

            $generalServiceSubservice = ServiceSubservice::create([
                'service_family_id' => $generalFamily->id,
                'service_id' => $generalService->id,
                'sub_service_id' => $generalSubService->id,
                'name' => 'Solicitud de Apoyo General',
                'description' => 'Relación activa',
                'is_active' => true,
            ]);

            ServiceLevelAgreement::create([
                'service_subservice_id' => $generalServiceSubservice->id,
                'service_family_id' => $generalFamily->id,
                'name' => 'SLA MEDIA APOYO',
                'criticality_level' => 'MEDIA',
                'response_time_hours' => 1,
                'resolution_time_hours' => 8,
                'availability_percentage' => 99.90,
                'acceptance_time_minutes' => 30,
                'response_time_minutes' => 60,
                'resolution_time_minutes' => 480,
                'conditions' => null,
                'is_active' => true,
            ]);

            $requester = Requester::factory()->create([
                'company_id' => $data['company']->id,
                'name' => 'Edwin Armando Zúñiga Abril',
            ]);

            $response = $this->actingAs($data['user'])
                ->withSession(['current_company_id' => $data['company']->id])
                ->post(route('service-requests.prefill-from-text'), [
                    'plain_text' => $this->sampleExactStructuredPlainTextWithoutSubservice(),
                ]);

            $response->assertRedirect(route('service-requests.create'));
            $response->assertSessionHas('success');
            $response->assertSessionHas('_old_input.title', 'Presentación Mapa Familias Lingüísticas');
            $response->assertSessionHas('_old_input.entry_channel', 'reunion');
            $response->assertSessionHas('_old_input.sub_service_id', $generalSubService->id);
            $response->assertSessionHas('_old_input.criticality_level', 'MEDIA');
            $response->assertSessionHas('_old_input.created_at', '2026-04-22T11:00');
            $this->assertNotEmpty(session('_old_input.requester_id'));
            $this->assertNull(session('_old_input.due_date'));

            $webRoutes = json_decode((string) session('_old_input.web_routes'), true);
            $this->assertSame([], is_array($webRoutes) ? $webRoutes : []);
        } finally {
            Carbon::setTestNow();
        }
    }
}
