<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Cut;
use App\Models\EvidenceOrganizationLog;
use App\Models\Service;
use App\Models\ServiceFamily;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvidence;
use App\Models\ServiceSubservice;
use App\Models\SubService;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\EvidenceOrganizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Property-based tests for audit log creation during evidence organization.
 *
 * Tests Property 17 from the design document: for every file organization
 * operation (success, failed, or skipped), an audit log entry SHALL be created
 * with all required fields.
 *
 * **Validates: Requirements 7.6**
 */
class EvidenceOrganizerAuditLogPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected EvidenceOrganizerService $service;
    protected string $testBasePath;
    protected Contract $contract;
    protected User $user;
    protected int $subServiceId;
    protected int $slaId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EvidenceOrganizerService();
        $this->testBasePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'evidence_pbt_audit_' . uniqid();
        File::makeDirectory($this->testBasePath, 0755, true);
        SystemSetting::set('evidence_base_path', $this->testBasePath);

        // Create required FK references following the full dependency chain
        $this->user = User::factory()->create();
        $company = Company::create(['name' => 'Audit PBT Company', 'status' => 'active']);
        $this->user->companies()->syncWithoutDetaching([$company->id]);

        $this->contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-AUDIT-' . uniqid(),
            'name' => 'Audit PBT Contract',
            'is_active' => true,
        ]);
        $company->update(['active_contract_id' => $this->contract->id]);

        $family = ServiceFamily::create([
            'contract_id' => $this->contract->id,
            'name' => 'Audit PBT Family',
            'code' => 'APB',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $serviceModel = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Audit PBT Service',
            'code' => 'APS',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $serviceModel->id,
            'name' => 'Audit PBT SubService',
            'code' => 'APSS',
            'is_active' => true,
            'order' => 0,
        ]);
        $this->subServiceId = $subService->id;

        $serviceSubservice = ServiceSubservice::create([
            'service_family_id' => $family->id,
            'service_id' => $serviceModel->id,
            'sub_service_id' => $subService->id,
            'name' => 'Audit PBT SS',
            'is_active' => true,
        ]);

        $sla = ServiceLevelAgreement::create([
            'service_subservice_id' => $serviceSubservice->id,
            'service_family_id' => $family->id,
            'name' => 'SLA Audit PBT',
            'criticality_level' => 'MEDIA',
            'response_time_hours' => 4,
            'resolution_time_hours' => 24,
            'acceptance_time_minutes' => 30,
            'response_time_minutes' => 240,
            'resolution_time_minutes' => 1440,
            'availability_percentage' => 99.9,
            'is_active' => true,
        ]);
        $this->slaId = $sla->id;
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testBasePath)) {
            File::deleteDirectory($this->testBasePath);
        }
        parent::tearDown();
    }

    protected function createCut(array $overrides = []): Cut
    {
        return Cut::create(array_merge([
            'contract_id' => $this->contract->id,
            'name' => 'Audit PBT Cut ' . uniqid(),
            'start_date' => now()->subMonth(),
            'end_date' => now(),
            'created_by' => $this->user->id,
        ], $overrides));
    }

    protected function createServiceRequest(array $overrides = []): ServiceRequest
    {
        return ServiceRequest::withoutEvents(function () use ($overrides) {
            return ServiceRequest::forceCreate(array_merge([
                'ticket_number' => 'SR-AUDIT-' . uniqid(),
                'title' => 'Audit PBT Request',
                'description' => 'Audit PBT description',
                'status' => 'PENDIENTE',
                'criticality_level' => 'MEDIA',
                'complexity_level' => 'MEDIA',
                'priority_score' => 0,
                'priority_level' => 'P3',
                'distrust_factor' => 1,
                'thread_count' => 1,
                'company_id' => $this->contract->company_id,
                'sub_service_id' => $this->subServiceId,
                'sla_id' => $this->slaId,
                'requested_by' => $this->user->id,
                'entry_channel' => 'email_corporativo',
            ], $overrides));
        });
    }

    /**
     * Generate random file content.
     */
    protected function generateRandomContent(int $minBytes = 10, int $maxBytes = 1024): string
    {
        $size = random_int($minBytes, $maxBytes);
        return random_bytes($size);
    }

    // ===================================================================
    // Property 17: Audit log creation for every operation
    // For any file organization operation (whether successful, failed,
    // or skipped), an audit log entry SHALL be created containing the
    // source path, destination path, timestamp, user ID, and result status.
    // ===================================================================

    /**
     * @group pbt Feature: evidence-file-organization, Property 17: Audit log creation for every operation
     *
     * Validates: Requirements 7.6
     *
     * For each test iteration, creates a batch of evidence IDs with a mix of:
     * - Valid file evidences (result: success)
     * - ENLACE evidences (result: success)
     * - Evidences with missing source files (result: skipped)
     *
     * Then calls organizeEvidences() and verifies:
     * - EvidenceOrganizationLog has exactly one entry per evidence ID in the batch
     * - Each entry has all required fields: evidence_id, cut_id, user_id (nullable),
     *   source_path (non-empty), destination_path (non-empty), result (success/failed/skipped),
     *   error_message, created_at
     * - Result counts match: success + failed + skipped = total batch size
     */
    public function test_property17_audit_log_created_for_every_operation(): void
    {
        for ($i = 0; $i < 25; $i++) {
            // Clear logs from previous iterations
            EvidenceOrganizationLog::query()->delete();

            $cutFolder = $this->testBasePath . DIRECTORY_SEPARATOR . 'corte_p17_' . $i;
            File::makeDirectory($cutFolder, 0755, true);

            $cut = $this->createCut(['folder_path' => $cutFolder]);
            $serviceRequest = $this->createServiceRequest([
                'ticket_number' => 'SR-P17-' . str_pad($i, 4, '0', STR_PAD_LEFT),
            ]);
            $cut->serviceRequests()->attach($serviceRequest->id);

            // Build a random batch with a mix of valid files, ENLACE, and missing-file evidences
            $batchSize = random_int(3, 7);
            $evidenceIds = [];

            for ($j = 0; $j < $batchSize; $j++) {
                $type = random_int(0, 2); // 0=valid file, 1=ENLACE, 2=missing file on disk

                if ($type === 0) {
                    // Valid file evidence (will succeed)
                    $sourceDir = $this->testBasePath . DIRECTORY_SEPARATOR . "src_p17_{$i}_{$j}";
                    File::makeDirectory($sourceDir, 0755, true);
                    $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . "audit_file_{$j}.pdf";
                    file_put_contents($sourceFile, $this->generateRandomContent());

                    $evidence = ServiceRequestEvidence::create([
                        'service_request_id' => $serviceRequest->id,
                        'title' => "P17 Valid {$i}-{$j}",
                        'evidence_type' => 'ARCHIVO',
                        'file_path' => $sourceFile,
                    ]);
                    $evidenceIds[] = $evidence->id;
                } elseif ($type === 1) {
                    // ENLACE evidence (will succeed without filesystem move)
                    $evidence = ServiceRequestEvidence::create([
                        'service_request_id' => $serviceRequest->id,
                        'title' => "P17 Link {$i}-{$j}",
                        'evidence_type' => 'ENLACE',
                        'file_path' => 'https://example.com/doc-' . bin2hex(random_bytes(4)),
                    ]);
                    $evidenceIds[] = $evidence->id;
                } else {
                    // Evidence with missing source file (will be skipped)
                    $evidence = ServiceRequestEvidence::create([
                        'service_request_id' => $serviceRequest->id,
                        'title' => "P17 Missing {$i}-{$j}",
                        'evidence_type' => 'ARCHIVO',
                        'file_path' => '/nonexistent/path_' . $i . '_' . $j . '/missing.pdf',
                    ]);
                    $evidenceIds[] = $evidence->id;
                }
            }

            // Execute the organization
            $result = $this->service->organizeEvidences($cut, $evidenceIds);

            // Verify: exactly one log entry per evidence ID in the batch
            $logs = EvidenceOrganizationLog::where('cut_id', $cut->id)->get();
            $this->assertCount(
                $batchSize,
                $logs,
                "Iteration {$i}: Must have exactly one audit log per evidence ID in batch (expected {$batchSize}, got {$logs->count()})"
            );

            // Verify each log entry has all required fields
            foreach ($logs as $log) {
                // evidence_id is present and belongs to the batch
                $this->assertNotNull($log->evidence_id, "Iteration {$i}: evidence_id must not be null");
                $this->assertContains(
                    $log->evidence_id,
                    $evidenceIds,
                    "Iteration {$i}: log evidence_id must be from the batch"
                );

                // cut_id matches the target cut
                $this->assertEquals(
                    $cut->id,
                    $log->cut_id,
                    "Iteration {$i}: cut_id must match target cut"
                );

                // user_id field exists (nullable is valid)
                $this->assertTrue(
                    array_key_exists('user_id', $log->getAttributes()),
                    "Iteration {$i}: user_id field must exist"
                );

                // source_path is non-empty
                $this->assertNotEmpty(
                    $log->source_path,
                    "Iteration {$i}: source_path must be non-empty for evidence_id {$log->evidence_id}"
                );

                // destination_path is non-empty
                $this->assertNotEmpty(
                    $log->destination_path,
                    "Iteration {$i}: destination_path must be non-empty for evidence_id {$log->evidence_id}"
                );

                // result is one of allowed values
                $this->assertContains(
                    $log->result,
                    ['success', 'failed', 'skipped'],
                    "Iteration {$i}: result must be success/failed/skipped, got '{$log->result}'"
                );

                // error_message field exists (can be null for success)
                $this->assertTrue(
                    array_key_exists('error_message', $log->getAttributes()),
                    "Iteration {$i}: error_message field must exist"
                );

                // created_at must be set
                $this->assertNotNull(
                    $log->created_at,
                    "Iteration {$i}: created_at must not be null for evidence_id {$log->evidence_id}"
                );
            }

            // Verify result counts: success + failed + skipped = total batch size
            $successLogs = $logs->where('result', 'success')->count();
            $failedLogs = $logs->where('result', 'failed')->count();
            $skippedLogs = $logs->where('result', 'skipped')->count();

            $this->assertEquals(
                $batchSize,
                $successLogs + $failedLogs + $skippedLogs,
                "Iteration {$i}: success({$successLogs}) + failed({$failedLogs}) + skipped({$skippedLogs}) must equal batch size ({$batchSize})"
            );

            // Verify each evidence ID in the batch has exactly one log entry
            foreach ($evidenceIds as $evidenceId) {
                $logCount = $logs->where('evidence_id', $evidenceId)->count();
                $this->assertEquals(
                    1,
                    $logCount,
                    "Iteration {$i}: Evidence ID {$evidenceId} must have exactly 1 log entry, got {$logCount}"
                );
            }
        }
    }
}
