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
 * Property-based tests for EvidenceOrganizerService::organizeEvidences() method.
 *
 * Tests Properties 6-13 from the design document using randomized inputs
 * to validate universal correctness properties of the organize operation.
 *
 * **Validates: Requirements 2.8, 3.3, 3.4, 3.5, 3.7, 4.2, 4.5, 7.2, 7.3, 8.2, 8.3**
 */
class EvidenceOrganizerOrganizePropertyTest extends TestCase
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
        $this->testBasePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'evidence_pbt_organize_' . uniqid();
        File::makeDirectory($this->testBasePath, 0755, true);
        SystemSetting::set('evidence_base_path', $this->testBasePath);

        // Create required FK references
        $this->user = User::factory()->create();
        $company = Company::create(['name' => 'PBT Company', 'status' => 'active']);
        $this->user->companies()->syncWithoutDetaching([$company->id]);

        $this->contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-PBT-' . uniqid(),
            'name' => 'PBT Contract',
            'is_active' => true,
        ]);
        $company->update(['active_contract_id' => $this->contract->id]);

        $family = ServiceFamily::create([
            'contract_id' => $this->contract->id,
            'name' => 'PBT Family',
            'code' => 'PBT',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $serviceModel = Service::create([
            'service_family_id' => $family->id,
            'name' => 'PBT Service',
            'code' => 'PS',
            'is_active' => true,
            'order' => 0,
        ]);

        $subService = SubService::create([
            'service_id' => $serviceModel->id,
            'name' => 'PBT SubService',
            'code' => 'PSS',
            'is_active' => true,
            'order' => 0,
        ]);
        $this->subServiceId = $subService->id;

        $serviceSubservice = ServiceSubservice::create([
            'service_family_id' => $family->id,
            'service_id' => $serviceModel->id,
            'sub_service_id' => $subService->id,
            'name' => 'PBT SS',
            'is_active' => true,
        ]);

        $sla = ServiceLevelAgreement::create([
            'service_subservice_id' => $serviceSubservice->id,
            'service_family_id' => $family->id,
            'name' => 'SLA PBT',
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
            'name' => 'PBT Cut ' . uniqid(),
            'start_date' => now()->subMonth(),
            'end_date' => now(),
            'created_by' => $this->user->id,
        ], $overrides));
    }

    protected function createServiceRequest(array $overrides = []): ServiceRequest
    {
        return ServiceRequest::withoutEvents(function () use ($overrides) {
            return ServiceRequest::forceCreate(array_merge([
                'ticket_number' => 'SR-PBT-' . uniqid(),
                'title' => 'PBT Request',
                'description' => 'PBT description',
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
     * Generate a random alphanumeric ticket number for testing.
     */
    protected function generateRandomTicketNumber(): string
    {
        $prefixes = ['SR', 'INC', 'REQ', 'TKT', 'CASO'];
        $prefix = $prefixes[array_rand($prefixes)];
        $number = random_int(1, 99999);
        return $prefix . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate random file content of a given size.
     */
    protected function generateRandomContent(int $minBytes = 10, int $maxBytes = 1024): string
    {
        $size = random_int($minBytes, $maxBytes);
        return random_bytes($size);
    }

    // ===================================================================
    // Property 6: Evidence placed in ticket-number subdirectory
    // For any evidence file being organized, the destination path SHALL
    // contain a subdirectory segment matching the service request's
    // ticket number (sanitized for filesystem characters).
    // ===================================================================

    /**
     * @group pbt Feature: evidence-file-organization, Property 6: Evidence placed in ticket-number subdirectory
     *
     * Validates: Requirements 2.8, 8.2
     */
    public function test_property6_evidence_placed_in_ticket_number_subdirectory(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $ticketNumber = $this->generateRandomTicketNumber();

            // Create source file
            $sourceDir = $this->testBasePath . DIRECTORY_SEPARATOR . 'src_p6_' . $i;
            File::makeDirectory($sourceDir, 0755, true);
            $filename = 'evidence_' . $i . '.pdf';
            $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . $filename;
            file_put_contents($sourceFile, $this->generateRandomContent());

            // Create cut folder
            $cutFolder = $this->testBasePath . DIRECTORY_SEPARATOR . 'corte_p6_' . $i;
            File::makeDirectory($cutFolder, 0755, true);

            $cut = $this->createCut(['folder_path' => $cutFolder]);
            $serviceRequest = $this->createServiceRequest(['ticket_number' => $ticketNumber]);
            $cut->serviceRequests()->attach($serviceRequest->id);

            $evidence = ServiceRequestEvidence::create([
                'service_request_id' => $serviceRequest->id,
                'title' => 'P6 Evidence ' . $i,
                'evidence_type' => 'ARCHIVO',
                'file_path' => $sourceFile,
            ]);

            $result = $this->service->organizeEvidences($cut, [$evidence->id]);

            $this->assertCount(1, $result->succeeded, "Iteration {$i}: Evidence should succeed");

            // Verify that destination directory contains the sanitized ticket number
            $sanitizedTicket = preg_replace('/[<>:"\/\\\\|?*\x00-\x1F]/', '_', $ticketNumber);
            $sanitizedTicket = trim($sanitizedTicket, '. ');
            $expectedTicketDir = $cutFolder . DIRECTORY_SEPARATOR . $sanitizedTicket;

            $this->assertDirectoryExists(
                $expectedTicketDir,
                "Iteration {$i}: Ticket directory '{$sanitizedTicket}' must exist within cut folder"
            );

            // Verify file is inside the ticket subdirectory
            $this->assertFileExists(
                $expectedTicketDir . DIRECTORY_SEPARATOR . $filename,
                "Iteration {$i}: File must be inside ticket subdirectory"
            );
        }
    }

    // ===================================================================
    // Property 7: Filename preservation during move
    // For any evidence file being moved, the filename (base name plus
    // extension) at the destination SHALL equal the original filename
    // when no naming conflict exists.
    // ===================================================================

    /**
     * @group pbt Feature: evidence-file-organization, Property 7: Filename preservation during move
     *
     * Validates: Requirements 3.3
     */
    public function test_property7_filename_preserved_when_no_conflict(): void
    {
        $extensions = ['pdf', 'png', 'jpg', 'docx', 'xlsx', 'txt', 'zip', 'csv'];

        for ($i = 0; $i < 30; $i++) {
            $ext = $extensions[array_rand($extensions)];
            $baseName = 'file_' . bin2hex(random_bytes(4));
            $filename = $baseName . '.' . $ext;

            // Create source file
            $sourceDir = $this->testBasePath . DIRECTORY_SEPARATOR . 'src_p7_' . $i;
            File::makeDirectory($sourceDir, 0755, true);
            $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . $filename;
            file_put_contents($sourceFile, $this->generateRandomContent());

            // Create cut folder (fresh, no conflicts)
            $cutFolder = $this->testBasePath . DIRECTORY_SEPARATOR . 'corte_p7_' . $i;
            File::makeDirectory($cutFolder, 0755, true);

            $cut = $this->createCut(['folder_path' => $cutFolder]);
            $ticketNumber = 'SR-P7-' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $serviceRequest = $this->createServiceRequest(['ticket_number' => $ticketNumber]);
            $cut->serviceRequests()->attach($serviceRequest->id);

            $evidence = ServiceRequestEvidence::create([
                'service_request_id' => $serviceRequest->id,
                'title' => 'P7 Evidence ' . $i,
                'evidence_type' => 'ARCHIVO',
                'file_path' => $sourceFile,
            ]);

            $result = $this->service->organizeEvidences($cut, [$evidence->id]);

            $this->assertCount(1, $result->succeeded, "Iteration {$i}: Evidence should succeed");

            // Verify the filename at destination matches original
            $expectedDestFile = $cutFolder . DIRECTORY_SEPARATOR . $ticketNumber . DIRECTORY_SEPARATOR . $filename;
            $this->assertFileExists(
                $expectedDestFile,
                "Iteration {$i}: Original filename '{$filename}' must be preserved at destination"
            );

            // Verify basename matches
            $this->assertEquals(
                $filename,
                basename($expectedDestFile),
                "Iteration {$i}: Basename must equal original filename"
            );
        }
    }

    // ===================================================================
    // Property 8: ENLACE-type evidence skips filesystem operations
    // For any evidence record where evidence_type equals 'ENLACE',
    // the organize operation SHALL not perform any filesystem move.
    // ===================================================================

    /**
     * @group pbt Feature: evidence-file-organization, Property 8: ENLACE-type evidence skips filesystem operations
     *
     * Validates: Requirements 3.4
     */
    public function test_property8_enlace_type_skips_filesystem_operations(): void
    {
        $urlPatterns = [
            'https://example.com/doc/%s.pdf',
            'https://drive.google.com/file/d/%s/view',
            'https://sharepoint.com/sites/%s',
            'http://internal-server.local/files/%s',
            'https://storage.blob.core.windows.net/container/%s',
        ];

        for ($i = 0; $i < 50; $i++) {
            $urlPattern = $urlPatterns[array_rand($urlPatterns)];
            $url = sprintf($urlPattern, bin2hex(random_bytes(8)));

            // Create cut folder
            $cutFolder = $this->testBasePath . DIRECTORY_SEPARATOR . 'corte_p8_' . $i;
            File::makeDirectory($cutFolder, 0755, true);

            $cut = $this->createCut(['folder_path' => $cutFolder]);
            $serviceRequest = $this->createServiceRequest(['ticket_number' => 'SR-P8-' . $i]);
            $cut->serviceRequests()->attach($serviceRequest->id);

            $evidence = ServiceRequestEvidence::create([
                'service_request_id' => $serviceRequest->id,
                'title' => 'Link Evidence ' . $i,
                'evidence_type' => 'ENLACE',
                'file_path' => $url,
            ]);

            // Count files in cut folder before
            $filesBefore = $this->countFilesRecursive($cutFolder);

            $result = $this->service->organizeEvidences($cut, [$evidence->id]);

            // ENLACE should succeed without any filesystem operations
            $this->assertContains(
                $evidence->id,
                $result->succeeded,
                "Iteration {$i}: ENLACE evidence must succeed"
            );
            $this->assertCount(0, $result->failed, "Iteration {$i}: ENLACE evidence must not fail");

            // No new files should exist in the cut folder
            $filesAfter = $this->countFilesRecursive($cutFolder);
            $this->assertEquals(
                $filesBefore,
                $filesAfter,
                "Iteration {$i}: No files should be created in cut folder for ENLACE type"
            );
        }
    }

    // ===================================================================
    // Property 9: Independent batch processing
    // For any batch, succeeded + failed = total, and failure in one
    // does not prevent processing of the next.
    // ===================================================================

    /**
     * @group pbt Feature: evidence-file-organization, Property 9: Independent batch processing
     *
     * Validates: Requirements 3.5
     */
    public function test_property9_independent_batch_processing(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $cutFolder = $this->testBasePath . DIRECTORY_SEPARATOR . 'corte_p9_' . $i;
            File::makeDirectory($cutFolder, 0755, true);

            $cut = $this->createCut(['folder_path' => $cutFolder]);
            $serviceRequest = $this->createServiceRequest(['ticket_number' => 'SR-P9-' . $i]);
            $cut->serviceRequests()->attach($serviceRequest->id);

            // Generate a random mix of valid and invalid evidence IDs
            $batchSize = random_int(2, 8);
            $evidenceIds = [];
            $validCount = 0;

            for ($j = 0; $j < $batchSize; $j++) {
                $isValid = (bool) random_int(0, 1);

                if ($isValid) {
                    // Create a valid file evidence
                    $sourceDir = $this->testBasePath . DIRECTORY_SEPARATOR . "src_p9_{$i}_{$j}";
                    File::makeDirectory($sourceDir, 0755, true);
                    $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . "file_{$j}.pdf";
                    file_put_contents($sourceFile, $this->generateRandomContent());

                    $evidence = ServiceRequestEvidence::create([
                        'service_request_id' => $serviceRequest->id,
                        'title' => "P9 Valid {$j}",
                        'evidence_type' => 'ARCHIVO',
                        'file_path' => $sourceFile,
                    ]);
                    $evidenceIds[] = $evidence->id;
                    $validCount++;
                } else {
                    // Use a nonexistent ID to cause failure
                    $evidenceIds[] = random_int(900000, 999999);
                }
            }

            $result = $this->service->organizeEvidences($cut, $evidenceIds);

            // Property: succeeded + failed = total
            $total = count($evidenceIds);
            $this->assertEquals(
                $total,
                $result->successCount + $result->failureCount,
                "Iteration {$i}: succeeded ({$result->successCount}) + failed ({$result->failureCount}) must equal total ({$total})"
            );

            // Every ID must appear in either succeeded or failed
            foreach ($evidenceIds as $id) {
                $inSucceeded = in_array($id, $result->succeeded);
                $inFailed = collect($result->failed)->pluck('evidence_id')->contains($id);
                $this->assertTrue(
                    $inSucceeded || $inFailed,
                    "Iteration {$i}: Evidence ID {$id} must be in either succeeded or failed"
                );
            }
        }
    }

    // ===================================================================
    // Property 10: Duplicate filename gets numeric suffix
    // If original filename exists at destination, a numeric suffix is
    // appended and the original existing file remains unmodified.
    // ===================================================================

    /**
     * @group pbt Feature: evidence-file-organization, Property 10: Duplicate filename gets numeric suffix
     *
     * Validates: Requirements 3.7, 8.3
     */
    public function test_property10_duplicate_filename_gets_numeric_suffix(): void
    {
        $extensions = ['pdf', 'png', 'docx', 'txt', 'xlsx'];

        for ($i = 0; $i < 20; $i++) {
            $ext = $extensions[array_rand($extensions)];
            $baseName = 'duplicate_' . $i;
            $filename = $baseName . '.' . $ext;

            $cutFolder = $this->testBasePath . DIRECTORY_SEPARATOR . 'corte_p10_' . $i;
            $ticketNumber = 'SR-P10-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $ticketDir = $cutFolder . DIRECTORY_SEPARATOR . $ticketNumber;
            File::makeDirectory($ticketDir, 0755, true);

            // Create the existing file at destination (the one that causes conflict)
            $existingContent = 'existing_content_' . $i;
            file_put_contents($ticketDir . DIRECTORY_SEPARATOR . $filename, $existingContent);

            // Create the source file with different content
            $sourceDir = $this->testBasePath . DIRECTORY_SEPARATOR . 'src_p10_' . $i;
            File::makeDirectory($sourceDir, 0755, true);
            $newContent = $this->generateRandomContent(50, 200);
            $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . $filename;
            file_put_contents($sourceFile, $newContent);

            $cut = $this->createCut(['folder_path' => $cutFolder]);
            $serviceRequest = $this->createServiceRequest(['ticket_number' => $ticketNumber]);
            $cut->serviceRequests()->attach($serviceRequest->id);

            $evidence = ServiceRequestEvidence::create([
                'service_request_id' => $serviceRequest->id,
                'title' => 'P10 Duplicate ' . $i,
                'evidence_type' => 'ARCHIVO',
                'file_path' => $sourceFile,
            ]);

            $result = $this->service->organizeEvidences($cut, [$evidence->id]);

            $this->assertCount(1, $result->succeeded, "Iteration {$i}: Should succeed with suffix");

            // The original file at destination must remain unchanged
            $this->assertFileExists($ticketDir . DIRECTORY_SEPARATOR . $filename);
            $this->assertEquals(
                $existingContent,
                file_get_contents($ticketDir . DIRECTORY_SEPARATOR . $filename),
                "Iteration {$i}: Original file must remain unmodified"
            );

            // The new file should have a numeric suffix (e.g., file_1.ext)
            $suffixedFilename = $baseName . '_1.' . $ext;
            $this->assertFileExists(
                $ticketDir . DIRECTORY_SEPARATOR . $suffixedFilename,
                "Iteration {$i}: Suffixed file '{$suffixedFilename}' must exist"
            );
            $this->assertEquals(
                $newContent,
                file_get_contents($ticketDir . DIRECTORY_SEPARATOR . $suffixedFilename),
                "Iteration {$i}: Suffixed file content must match source"
            );
        }
    }

    // ===================================================================
    // Property 11: File integrity verification (size match)
    // For any evidence file copied to the destination, the file size
    // at the destination SHALL equal the file size at the source.
    // ===================================================================

    /**
     * @group pbt Feature: evidence-file-organization, Property 11: File integrity verification (size match)
     *
     * Validates: Requirements 4.2
     */
    public function test_property11_file_integrity_verification_size_match(): void
    {
        for ($i = 0; $i < 30; $i++) {
            // Generate a random file size between 1 byte and 10KB
            $content = $this->generateRandomContent(1, 10240);
            $expectedSize = strlen($content);

            $sourceDir = $this->testBasePath . DIRECTORY_SEPARATOR . 'src_p11_' . $i;
            File::makeDirectory($sourceDir, 0755, true);
            $filename = 'integrity_' . $i . '.bin';
            $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . $filename;
            file_put_contents($sourceFile, $content);

            $cutFolder = $this->testBasePath . DIRECTORY_SEPARATOR . 'corte_p11_' . $i;
            File::makeDirectory($cutFolder, 0755, true);

            $cut = $this->createCut(['folder_path' => $cutFolder]);
            $ticketNumber = 'SR-P11-' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $serviceRequest = $this->createServiceRequest(['ticket_number' => $ticketNumber]);
            $cut->serviceRequests()->attach($serviceRequest->id);

            $evidence = ServiceRequestEvidence::create([
                'service_request_id' => $serviceRequest->id,
                'title' => 'P11 Integrity ' . $i,
                'evidence_type' => 'ARCHIVO',
                'file_path' => $sourceFile,
            ]);

            $result = $this->service->organizeEvidences($cut, [$evidence->id]);

            $this->assertCount(1, $result->succeeded, "Iteration {$i}: Should succeed");

            // Verify destination file size matches source
            $destFile = $cutFolder . DIRECTORY_SEPARATOR . $ticketNumber . DIRECTORY_SEPARATOR . $filename;
            $this->assertFileExists($destFile, "Iteration {$i}: Destination file must exist");

            $destSize = filesize($destFile);
            $this->assertEquals(
                $expectedSize,
                $destSize,
                "Iteration {$i}: Destination size ({$destSize}) must equal source size ({$expectedSize})"
            );
        }
    }

    // ===================================================================
    // Property 12: Transactional atomicity on failure
    // If operation fails, file_path in DB remains unchanged and any
    // partially moved file is restored to source location.
    // ===================================================================

    /**
     * @group pbt Feature: evidence-file-organization, Property 12: Transactional atomicity on failure
     *
     * Validates: Requirements 4.5, 7.2
     */
    public function test_property12_transactional_atomicity_on_failure(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $cutFolder = $this->testBasePath . DIRECTORY_SEPARATOR . 'corte_p12_' . $i;
            File::makeDirectory($cutFolder, 0755, true);

            $cut = $this->createCut(['folder_path' => $cutFolder]);
            $ticketNumber = 'SR-P12-' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $serviceRequest = $this->createServiceRequest(['ticket_number' => $ticketNumber]);
            $cut->serviceRequests()->attach($serviceRequest->id);

            // Create evidence with a non-existent file path (will fail at source validation)
            $invalidPath = '/nonexistent/path_' . $i . '/missing_file_' . bin2hex(random_bytes(4)) . '.pdf';

            $evidence = ServiceRequestEvidence::create([
                'service_request_id' => $serviceRequest->id,
                'title' => 'P12 Atomicity ' . $i,
                'evidence_type' => 'ARCHIVO',
                'file_path' => $invalidPath,
            ]);

            $originalFilePath = $evidence->file_path;

            $result = $this->service->organizeEvidences($cut, [$evidence->id]);

            // Should fail
            $this->assertCount(0, $result->succeeded, "Iteration {$i}: Should not succeed");
            $this->assertCount(1, $result->failed, "Iteration {$i}: Should fail");

            // Verify DB record was NOT modified
            $evidence->refresh();
            $this->assertEquals(
                $originalFilePath,
                $evidence->file_path,
                "Iteration {$i}: file_path must remain unchanged after failure"
            );
        }
    }

    // ===================================================================
    // Property 13: Pre-move validation rejects invalid records
    // Soft-deleted or non-existent IDs are skipped without error,
    // and no filesystem state is modified.
    // ===================================================================

    /**
     * @group pbt Feature: evidence-file-organization, Property 13: Pre-move validation rejects invalid records
     *
     * Validates: Requirements 7.3
     */
    public function test_property13_premove_validation_rejects_soft_deleted_records(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $cutFolder = $this->testBasePath . DIRECTORY_SEPARATOR . 'corte_p13sd_' . $i;
            File::makeDirectory($cutFolder, 0755, true);

            $cut = $this->createCut(['folder_path' => $cutFolder]);
            $serviceRequest = $this->createServiceRequest(['ticket_number' => 'SR-P13SD-' . $i]);
            $cut->serviceRequests()->attach($serviceRequest->id);

            $evidence = ServiceRequestEvidence::create([
                'service_request_id' => $serviceRequest->id,
                'title' => 'P13 Soft-deleted ' . $i,
                'evidence_type' => 'ARCHIVO',
                'file_path' => 'some/path/file_' . $i . '.pdf',
            ]);

            // Soft-delete the record
            ServiceRequestEvidence::where('id', $evidence->id)->update(['deleted_at' => now()]);

            $filesBefore = $this->countFilesRecursive($cutFolder);

            $result = $this->service->organizeEvidences($cut, [$evidence->id]);

            // Should be in failed (skipped)
            $this->assertCount(0, $result->succeeded, "Iteration {$i}: Soft-deleted must not succeed");
            $this->assertCount(1, $result->failed, "Iteration {$i}: Soft-deleted must be in failed");

            // No filesystem changes
            $filesAfter = $this->countFilesRecursive($cutFolder);
            $this->assertEquals(
                $filesBefore,
                $filesAfter,
                "Iteration {$i}: No files should be created for soft-deleted records"
            );
        }
    }

    /**
     * @group pbt Feature: evidence-file-organization, Property 13: Pre-move validation rejects invalid records
     *
     * Validates: Requirements 7.3
     */
    public function test_property13_premove_validation_rejects_nonexistent_ids(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $cutFolder = $this->testBasePath . DIRECTORY_SEPARATOR . 'corte_p13ne_' . $i;
            File::makeDirectory($cutFolder, 0755, true);

            $cut = $this->createCut(['folder_path' => $cutFolder]);

            // Use IDs that definitely don't exist
            $nonexistentId = random_int(900000, 999999);
            $filesBefore = $this->countFilesRecursive($cutFolder);

            $result = $this->service->organizeEvidences($cut, [$nonexistentId]);

            // Should be in failed
            $this->assertCount(0, $result->succeeded, "Iteration {$i}: Nonexistent ID must not succeed");
            $this->assertCount(1, $result->failed, "Iteration {$i}: Nonexistent ID must be in failed");
            $this->assertEquals(
                $nonexistentId,
                $result->failed[0]['evidence_id'],
                "Iteration {$i}: Failed evidence_id must match the nonexistent ID"
            );

            // No filesystem changes
            $filesAfter = $this->countFilesRecursive($cutFolder);
            $this->assertEquals(
                $filesBefore,
                $filesAfter,
                "Iteration {$i}: No files should be created for nonexistent IDs"
            );
        }
    }

    /**
     * @group pbt Feature: evidence-file-organization, Property 13: Pre-move validation rejects invalid records
     *
     * Validates: Requirements 7.3
     */
    public function test_property13_premove_validation_rejects_missing_source_file(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $cutFolder = $this->testBasePath . DIRECTORY_SEPARATOR . 'corte_p13mf_' . $i;
            File::makeDirectory($cutFolder, 0755, true);

            $cut = $this->createCut(['folder_path' => $cutFolder]);
            $serviceRequest = $this->createServiceRequest(['ticket_number' => 'SR-P13MF-' . $i]);
            $cut->serviceRequests()->attach($serviceRequest->id);

            // Create evidence pointing to a non-existent file on disk
            $missingPath = '/definitely/not/here/' . bin2hex(random_bytes(6)) . '.pdf';
            $evidence = ServiceRequestEvidence::create([
                'service_request_id' => $serviceRequest->id,
                'title' => 'P13 Missing File ' . $i,
                'evidence_type' => 'ARCHIVO',
                'file_path' => $missingPath,
            ]);

            $originalFilePath = $evidence->file_path;
            $filesBefore = $this->countFilesRecursive($cutFolder);

            $result = $this->service->organizeEvidences($cut, [$evidence->id]);

            // Should fail
            $this->assertCount(0, $result->succeeded, "Iteration {$i}: Missing file must not succeed");
            $this->assertCount(1, $result->failed, "Iteration {$i}: Missing file must be in failed");

            // DB record unchanged
            $evidence->refresh();
            $this->assertEquals(
                $originalFilePath,
                $evidence->file_path,
                "Iteration {$i}: file_path must remain unchanged for missing source file"
            );

            // No filesystem changes
            $filesAfter = $this->countFilesRecursive($cutFolder);
            $this->assertEquals(
                $filesBefore,
                $filesAfter,
                "Iteration {$i}: No files should be created for missing source file"
            );
        }
    }

    // ===================================================================
    // Helper methods
    // ===================================================================

    /**
     * Count all files recursively in a directory.
     */
    protected function countFilesRecursive(string $directory): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }

        return $count;
    }
}
