<?php

namespace Tests\Unit\Services;

use App\DTOs\OrganizationResult;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Cut;
use App\Models\EvidenceOrganizationLog;
use App\Models\Service;
use App\Models\ServiceFamily;
use App\Models\ServiceLevelAgreement;
use App\Models\ServiceRequestEvidence;
use App\Models\ServiceSubservice;
use App\Models\SubService;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\EvidenceOrganizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EvidenceOrganizerOrganizeTest extends TestCase
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
        $this->testBasePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'evidence_organize_test_' . uniqid();
        File::makeDirectory($this->testBasePath, 0755, true);
        SystemSetting::set('evidence_base_path', $this->testBasePath);

        // Create required FK references following the full dependency chain
        $this->user = User::factory()->create();
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $this->user->companies()->syncWithoutDetaching([$company->id]);

        $this->contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-TEST-' . uniqid(),
            'name' => 'Test Contract',
            'is_active' => true,
        ]);
        $company->update(['active_contract_id' => $this->contract->id]);

        $family = ServiceFamily::create([
            'contract_id' => $this->contract->id,
            'name' => 'Test Family',
            'code' => 'TST',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $serviceModel = Service::create([
            'service_family_id' => $family->id,
            'name' => 'Test Service',
            'code' => 'TS',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $serviceModel->id,
            'name' => 'Test SubService',
            'code' => 'TSS',
            'is_active' => true,
            'order' => 0,
        ]);
        $this->subServiceId = $subService->id;

        $serviceSubservice = ServiceSubservice::create([
            'service_family_id' => $family->id,
            'service_id' => $serviceModel->id,
            'sub_service_id' => $subService->id,
            'name' => 'Test SS',
            'is_active' => true,
        ]);

        $sla = ServiceLevelAgreement::create([
            'service_subservice_id' => $serviceSubservice->id,
            'service_family_id' => $family->id,
            'name' => 'SLA Test',
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
            'name' => 'Test Cut ' . uniqid(),
            'start_date' => now()->subMonth(),
            'end_date' => now(),
            'created_by' => $this->user->id,
        ], $overrides));
    }

    protected function createServiceRequest(array $overrides = []): \App\Models\ServiceRequest
    {
        // Use withoutEvents to bypass the ticket_number generating boot logic
        // which requires SubService with related models
        return \App\Models\ServiceRequest::withoutEvents(function () use ($overrides) {
            return \App\Models\ServiceRequest::forceCreate(array_merge([
                'ticket_number' => 'SR-TEST-' . uniqid(),
                'title' => 'Test Request',
                'description' => 'Test description',
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

    public function test_organize_enlace_type_evidence_skips_filesystem_and_succeeds(): void
    {
        $cut = $this->createCut([
            'folder_path' => $this->testBasePath . DIRECTORY_SEPARATOR . 'corte-test',
        ]);

        $serviceRequest = $this->createServiceRequest(['ticket_number' => 'SR-TEST-001']);
        $cut->serviceRequests()->attach($serviceRequest->id);

        $evidence = ServiceRequestEvidence::create([
            'service_request_id' => $serviceRequest->id,
            'title' => 'Link evidence',
            'evidence_type' => 'ENLACE',
            'file_path' => 'https://example.com/document.pdf',
        ]);

        $result = $this->service->organizeEvidences($cut, [$evidence->id]);

        $this->assertInstanceOf(OrganizationResult::class, $result);
        $this->assertCount(1, $result->succeeded);
        $this->assertCount(0, $result->failed);
        $this->assertContains($evidence->id, $result->succeeded);

        // Verify audit log was created
        $log = EvidenceOrganizationLog::where('evidence_id', $evidence->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('success', $log->result);
    }

    public function test_organize_nonexistent_evidence_reports_as_skipped(): void
    {
        $cut = $this->createCut([
            'folder_path' => $this->testBasePath . DIRECTORY_SEPARATOR . 'corte-test',
        ]);

        $result = $this->service->organizeEvidences($cut, [99999]);

        $this->assertCount(0, $result->succeeded);
        $this->assertCount(1, $result->failed);
        $this->assertEquals(99999, $result->failed[0]['evidence_id']);
    }

    public function test_organize_evidence_with_missing_source_file_reports_skipped(): void
    {
        $cut = $this->createCut([
            'folder_path' => $this->testBasePath . DIRECTORY_SEPARATOR . 'corte-test',
        ]);

        $serviceRequest = $this->createServiceRequest(['ticket_number' => 'SR-TEST-002']);
        $cut->serviceRequests()->attach($serviceRequest->id);

        $evidence = ServiceRequestEvidence::create([
            'service_request_id' => $serviceRequest->id,
            'title' => 'File evidence',
            'evidence_type' => 'ARCHIVO',
            'file_path' => 'nonexistent/path/file.pdf',
        ]);

        $result = $this->service->organizeEvidences($cut, [$evidence->id]);

        $this->assertCount(0, $result->succeeded);
        $this->assertCount(1, $result->failed);
        $this->assertEquals($evidence->id, $result->failed[0]['evidence_id']);

        // Verify DB record was NOT modified
        $evidence->refresh();
        $this->assertEquals('nonexistent/path/file.pdf', $evidence->file_path);
    }

    public function test_organize_file_evidence_moves_to_ticket_subdirectory(): void
    {
        // Create source file
        $sourceDir = $this->testBasePath . DIRECTORY_SEPARATOR . 'source';
        File::makeDirectory($sourceDir, 0755, true);
        $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . 'test-document.pdf';
        file_put_contents($sourceFile, 'test content for evidence file');

        // Create cut with folder_path
        $cutFolder = $this->testBasePath . DIRECTORY_SEPARATOR . 'corte-1';
        File::makeDirectory($cutFolder, 0755, true);

        $cut = $this->createCut(['folder_path' => $cutFolder]);

        $serviceRequest = $this->createServiceRequest(['ticket_number' => 'SR-TEST-003']);
        $cut->serviceRequests()->attach($serviceRequest->id);

        $evidence = ServiceRequestEvidence::create([
            'service_request_id' => $serviceRequest->id,
            'title' => 'Document',
            'evidence_type' => 'ARCHIVO',
            'file_path' => $sourceFile,
        ]);

        $result = $this->service->organizeEvidences($cut, [$evidence->id]);

        $this->assertCount(1, $result->succeeded);
        $this->assertContains($evidence->id, $result->succeeded);

        // Verify file exists at destination in ticket subdirectory
        $expectedDir = $cutFolder . DIRECTORY_SEPARATOR . 'SR-TEST-003';
        $this->assertDirectoryExists($expectedDir);
        $this->assertFileExists($expectedDir . DIRECTORY_SEPARATOR . 'test-document.pdf');

        // Verify source was removed
        $this->assertFileDoesNotExist($sourceFile);

        // Verify DB was updated
        $evidence->refresh();
        $this->assertStringContainsString('test-document.pdf', $evidence->file_path);
    }

    public function test_organize_handles_duplicate_filenames_with_numeric_suffix(): void
    {
        // Create source files
        $sourceDir = $this->testBasePath . DIRECTORY_SEPARATOR . 'source2';
        File::makeDirectory($sourceDir, 0755, true);
        $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . 'report.pdf';
        file_put_contents($sourceFile, 'content of duplicate file');

        // Create cut folder with existing file at destination
        $cutFolder = $this->testBasePath . DIRECTORY_SEPARATOR . 'corte-dup';
        $ticketDir = $cutFolder . DIRECTORY_SEPARATOR . 'SR-DUP-001';
        File::makeDirectory($ticketDir, 0755, true);
        file_put_contents($ticketDir . DIRECTORY_SEPARATOR . 'report.pdf', 'existing file');

        $cut = $this->createCut(['folder_path' => $cutFolder]);

        $serviceRequest = $this->createServiceRequest(['ticket_number' => 'SR-DUP-001']);
        $cut->serviceRequests()->attach($serviceRequest->id);

        $evidence = ServiceRequestEvidence::create([
            'service_request_id' => $serviceRequest->id,
            'title' => 'Duplicate file',
            'evidence_type' => 'ARCHIVO',
            'file_path' => $sourceFile,
        ]);

        $result = $this->service->organizeEvidences($cut, [$evidence->id]);

        $this->assertCount(1, $result->succeeded);

        // Original file should remain untouched
        $this->assertFileExists($ticketDir . DIRECTORY_SEPARATOR . 'report.pdf');
        $this->assertEquals('existing file', file_get_contents($ticketDir . DIRECTORY_SEPARATOR . 'report.pdf'));

        // New file should have numeric suffix
        $this->assertFileExists($ticketDir . DIRECTORY_SEPARATOR . 'report_1.pdf');
        $this->assertEquals('content of duplicate file', file_get_contents($ticketDir . DIRECTORY_SEPARATOR . 'report_1.pdf'));
    }

    public function test_organize_independent_processing_failure_does_not_block_next(): void
    {
        // Create a valid source file for second evidence
        $sourceDir = $this->testBasePath . DIRECTORY_SEPARATOR . 'source3';
        File::makeDirectory($sourceDir, 0755, true);
        $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . 'valid-file.pdf';
        file_put_contents($sourceFile, 'valid content');

        $cutFolder = $this->testBasePath . DIRECTORY_SEPARATOR . 'corte-batch';
        File::makeDirectory($cutFolder, 0755, true);

        $cut = $this->createCut(['folder_path' => $cutFolder]);

        $serviceRequest = $this->createServiceRequest(['ticket_number' => 'SR-BATCH-001']);
        $cut->serviceRequests()->attach($serviceRequest->id);

        // First evidence: missing file (will fail)
        $evidence1 = ServiceRequestEvidence::create([
            'service_request_id' => $serviceRequest->id,
            'title' => 'Missing file',
            'evidence_type' => 'ARCHIVO',
            'file_path' => '/nonexistent/missing.pdf',
        ]);

        // Second evidence: valid file (should succeed despite first failure)
        $evidence2 = ServiceRequestEvidence::create([
            'service_request_id' => $serviceRequest->id,
            'title' => 'Valid file',
            'evidence_type' => 'ARCHIVO',
            'file_path' => $sourceFile,
        ]);

        $result = $this->service->organizeEvidences($cut, [$evidence1->id, $evidence2->id]);

        // First fails, second succeeds
        $this->assertEquals(1, $result->successCount);
        $this->assertEquals(1, $result->failureCount);
        $this->assertContains($evidence2->id, $result->succeeded);
        $this->assertEquals($evidence1->id, $result->failed[0]['evidence_id']);
    }

    public function test_organize_creates_audit_log_for_each_operation(): void
    {
        $cutFolder = $this->testBasePath . DIRECTORY_SEPARATOR . 'corte-audit';
        File::makeDirectory($cutFolder, 0755, true);

        $cut = $this->createCut(['folder_path' => $cutFolder]);

        $serviceRequest = $this->createServiceRequest(['ticket_number' => 'SR-AUDIT-001']);
        $cut->serviceRequests()->attach($serviceRequest->id);

        // ENLACE (success) + missing file (skipped)
        $evidence1 = ServiceRequestEvidence::create([
            'service_request_id' => $serviceRequest->id,
            'title' => 'Link',
            'evidence_type' => 'ENLACE',
            'file_path' => 'https://example.com',
        ]);

        $evidence2 = ServiceRequestEvidence::create([
            'service_request_id' => $serviceRequest->id,
            'title' => 'Missing',
            'evidence_type' => 'ARCHIVO',
            'file_path' => '/nonexistent.pdf',
        ]);

        $this->service->organizeEvidences($cut, [$evidence1->id, $evidence2->id]);

        // Both should have audit log entries
        $logs = EvidenceOrganizationLog::where('cut_id', $cut->id)->get();
        $this->assertCount(2, $logs);

        $log1 = $logs->firstWhere('evidence_id', $evidence1->id);
        $this->assertEquals('success', $log1->result);

        $log2 = $logs->firstWhere('evidence_id', $evidence2->id);
        $this->assertEquals('skipped', $log2->result);
    }

    public function test_organize_returns_organization_result_with_correct_counts(): void
    {
        $cutFolder = $this->testBasePath . DIRECTORY_SEPARATOR . 'corte-counts';
        File::makeDirectory($cutFolder, 0755, true);

        $cut = $this->createCut(['folder_path' => $cutFolder]);

        $serviceRequest = $this->createServiceRequest(['ticket_number' => 'SR-COUNT-001']);
        $cut->serviceRequests()->attach($serviceRequest->id);

        // 2 ENLACE (success) + 1 missing (fail) = 3 total
        $e1 = ServiceRequestEvidence::create([
            'service_request_id' => $serviceRequest->id,
            'title' => 'Link 1',
            'evidence_type' => 'ENLACE',
            'file_path' => 'https://example.com/1',
        ]);
        $e2 = ServiceRequestEvidence::create([
            'service_request_id' => $serviceRequest->id,
            'title' => 'Link 2',
            'evidence_type' => 'ENLACE',
            'file_path' => 'https://example.com/2',
        ]);
        $e3 = ServiceRequestEvidence::create([
            'service_request_id' => $serviceRequest->id,
            'title' => 'Missing',
            'evidence_type' => 'ARCHIVO',
            'file_path' => '/nonexistent.pdf',
        ]);

        $result = $this->service->organizeEvidences($cut, [$e1->id, $e2->id, $e3->id]);

        $this->assertEquals(2, $result->successCount);
        $this->assertEquals(1, $result->failureCount);
        $this->assertCount(2, $result->succeeded);
        $this->assertCount(1, $result->failed);
        // Total = succeeded + failed
        $this->assertEquals(3, $result->successCount + $result->failureCount);
    }

    public function test_organize_soft_deleted_evidence_is_skipped(): void
    {
        $cutFolder = $this->testBasePath . DIRECTORY_SEPARATOR . 'corte-soft-del';
        File::makeDirectory($cutFolder, 0755, true);

        $cut = $this->createCut(['folder_path' => $cutFolder]);

        $serviceRequest = $this->createServiceRequest(['ticket_number' => 'SR-DEL-001']);
        $cut->serviceRequests()->attach($serviceRequest->id);

        $evidence = ServiceRequestEvidence::create([
            'service_request_id' => $serviceRequest->id,
            'title' => 'Soft-deleted file',
            'evidence_type' => 'ARCHIVO',
            'file_path' => 'some/file.pdf',
        ]);

        // Manually soft-delete (since model doesn't use SoftDeletes trait)
        ServiceRequestEvidence::where('id', $evidence->id)->update(['deleted_at' => now()]);

        $result = $this->service->organizeEvidences($cut, [$evidence->id]);

        $this->assertCount(0, $result->succeeded);
        $this->assertCount(1, $result->failed);
    }
}
