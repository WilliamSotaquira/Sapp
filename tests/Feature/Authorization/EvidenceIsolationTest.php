<?php

namespace Tests\Feature\Authorization;

use App\Models\Classification;
use App\Models\Evidence;
use App\Models\Reporter;
use App\Models\Requirement;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvidence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §10.6 — Evidencias en DOS modelos.
 *
 * Bloque A: ServiceRequestEvidence (flujo real del técnico), aislado por
 * ServiceRequestEvidencePolicy (serviceRequest.assigned_to + user_id).
 * Bloque B: Evidence (requirement-based), fail-closed para no-admin vía
 * EvidencePolicy; admin OK (regresión del bug del Gate ausente, finding #1).
 */
class EvidenceIsolationTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithIsolationFixtures;

    protected ServiceRequest $srA;
    protected ServiceRequest $srB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedIsolationWorld();

        $this->srA = $this->makeServiceRequest($this->t1User->id, ['ticket_number' => 'SR-A-EV']);
        $this->srB = $this->makeServiceRequest($this->t2User->id, ['ticket_number' => 'SR-B-EV']);
    }

    private function asT1()
    {
        return $this->actingAs($this->t1User)->withSession($this->workspaceSession());
    }

    private function asAdmin()
    {
        return $this->actingAs($this->admin)->withSession($this->workspaceSession());
    }

    private function makeSrEvidence(ServiceRequest $sr, int $userId, array $overrides = []): ServiceRequestEvidence
    {
        return ServiceRequestEvidence::create(array_merge([
            'service_request_id' => $sr->id,
            'title' => 'Evidencia de prueba',
            'description' => 'Nota de aislamiento',
            'evidence_type' => 'COMENTARIO',
            'user_id' => $userId,
        ], $overrides));
    }

    // ============ BLOQUE A — ServiceRequestEvidence ============

    // 35 — T1 sube evidencia a SR-A (propia) -> OK (no 403).
    public function test_technician_uploads_to_own_request(): void
    {
        $response = $this->asT1()->post(route('service-requests.evidences.store', $this->srA), [
            'link_url' => 'https://example.com/evidencia-propia',
        ]);
        $this->assertNotSame(403, $response->getStatusCode());
        $this->assertDatabaseHas('service_request_evidences', [
            'service_request_id' => $this->srA->id,
            'description' => 'https://example.com/evidencia-propia',
        ]);
    }

    // 36 — T1 sube evidencia a SR-B (ajena) -> 403 (binding de SR-B frena a T1).
    public function test_technician_upload_to_foreign_request_is_forbidden(): void
    {
        $this->asT1()->post(route('service-requests.evidences.store', $this->srB), [
            'link_url' => 'https://example.com/evidencia-ajena',
        ])->assertForbidden();
    }

    // 37 — T1 download/view/show sobre evidencia de SR-B -> 403.
    public function test_technician_access_to_foreign_evidence_is_forbidden(): void
    {
        $evidenceB = $this->makeSrEvidence($this->srB, $this->t2User->id);

        $this->asT1()->get(route('service-requests.evidences.show', [$this->srB, $evidenceB]))->assertForbidden();
        $this->asT1()->get(route('service-requests.evidences.download', [$this->srB, $evidenceB]))->assertForbidden();
    }

    // 38 — T1 borra su propia evidencia (user_id=T1) en SR-A -> OK;
    //       evidencia de SR-A subida por OTRO (user_id=admin) -> 403.
    public function test_technician_delete_own_evidence_but_not_others(): void
    {
        $ownEvidence = $this->makeSrEvidence($this->srA, $this->t1User->id);
        $resp = $this->asT1()->delete(route('service-requests.evidences.destroy', [$this->srA, $ownEvidence]));
        $this->assertNotSame(403, $resp->getStatusCode());

        $othersEvidence = $this->makeSrEvidence($this->srA, $this->admin->id);
        $this->asT1()
            ->delete(route('service-requests.evidences.destroy', [$this->srA, $othersEvidence]))
            ->assertForbidden();
    }

    // 39 — Admin: todas las anteriores OK (pasa por before()).
    public function test_admin_can_access_and_delete_any_evidence(): void
    {
        $evidenceB = $this->makeSrEvidence($this->srB, $this->t2User->id);

        // La autorización deja pasar al admin (no 403). Se evita assertOk() sobre
        // la vista show por un bug ajeno a seguridad en la plantilla.
        $show = $this->asAdmin()->get(route('service-requests.evidences.show', [$this->srB, $evidenceB]));
        $this->assertNotSame(403, $show->getStatusCode());

        $resp = $this->asAdmin()->delete(route('service-requests.evidences.destroy', [$this->srB, $evidenceB]));
        $this->assertNotSame(403, $resp->getStatusCode());
    }

    // ============ BLOQUE B — Evidence (requirement-based) ============

    private function makeRequirementEvidence(): Evidence
    {
        $reporter = Reporter::create([
            'name' => 'Reportante ISO',
            'email' => 'reportante-iso@example.com',
            'department' => 'IT',
        ]);
        $classification = Classification::create([
            'name' => 'Clasificación ISO',
            'color' => '#333333',
            'order' => 0,
            'is_active' => true,
        ]);
        $requirement = Requirement::create([
            'title' => 'Requerimiento ISO',
            'description' => 'Requerimiento de prueba',
            'code' => 'REQ-ISO-' . strtoupper(bin2hex(random_bytes(3))),
            'reporter_id' => $reporter->id,
            'classification_id' => $classification->id,
            'priority' => 'medium',
            'status' => 'pending',
        ]);

        return Evidence::create([
            'requirement_id' => $requirement->id,
            'file_path' => 'evidences/req-iso.txt',
            'file_name' => 'req-iso.txt',
            'file_type' => 'text/plain',
            'original_name' => 'req-iso.txt',
        ]);
    }

    // 40 — técnico download/destroy -> 403 (fail-closed; EvidencePolicy false).
    public function test_technician_requirement_evidence_is_forbidden(): void
    {
        $evidence = $this->makeRequirementEvidence();

        $this->asT1()->get(route('evidences.download', $evidence))->assertForbidden();
        $this->asT1()->delete(route('evidences.destroy', $evidence))->assertForbidden();
    }

    // 40b — admin download/destroy -> OK (pasa por before(); el Gate::policy()
    //        registrado corrige el bug latente de clase ausente).
    public function test_admin_requirement_evidence_passes_policy(): void
    {
        $evidence = $this->makeRequirementEvidence();

        // No 403: la autorización (can:view/delete,evidence) deja pasar al admin.
        // El Storage puede faltar (archivo inexistente) -> el controlador redirige
        // con error, pero NUNCA 403. Afirmamos que NO es prohibido.
        $download = $this->asAdmin()->get(route('evidences.download', $evidence));
        $this->assertNotSame(403, $download->getStatusCode());

        $destroy = $this->asAdmin()->delete(route('evidences.destroy', $evidence));
        $this->assertNotSame(403, $destroy->getStatusCode());
    }
}
