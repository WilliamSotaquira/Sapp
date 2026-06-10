<?php

namespace Tests\Unit\Services;

use App\Models\SystemSetting;
use App\Services\EvidenceOrganizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EvidenceOrganizerCleanBackupsTest extends TestCase
{
    use RefreshDatabase;

    protected EvidenceOrganizerService $service;
    protected string $testBasePath;
    protected string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EvidenceOrganizerService();

        // Set up a temporary base path for testing
        $this->testBasePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'evidence_backup_test_' . uniqid();
        $this->backupDir = $this->testBasePath . DIRECTORY_SEPARATOR . '_backups';

        SystemSetting::set('evidence_base_path', $this->testBasePath);
    }

    protected function tearDown(): void
    {
        // Clean up temp directories
        if (is_dir($this->testBasePath)) {
            File::deleteDirectory($this->testBasePath);
        }

        parent::tearDown();
    }

    public function test_returns_zero_when_backup_directory_does_not_exist(): void
    {
        // Don't create the _backups directory
        $result = $this->service->cleanOrphanedBackups();

        $this->assertEquals(0, $result);
    }

    public function test_returns_zero_when_backup_directory_is_empty(): void
    {
        File::makeDirectory($this->backupDir, 0755, true);

        $result = $this->service->cleanOrphanedBackups();

        $this->assertEquals(0, $result);
    }

    public function test_does_not_delete_files_newer_than_24_hours(): void
    {
        File::makeDirectory($this->backupDir, 0755, true);

        // Create a recent file (current time)
        $recentFile = $this->backupDir . DIRECTORY_SEPARATOR . 'recent_backup.pdf';
        file_put_contents($recentFile, 'recent backup content');

        $result = $this->service->cleanOrphanedBackups();

        $this->assertEquals(0, $result);
        $this->assertFileExists($recentFile);
    }

    public function test_deletes_files_older_than_24_hours(): void
    {
        File::makeDirectory($this->backupDir, 0755, true);

        // Create a file and set its modification time to 25 hours ago
        $oldFile = $this->backupDir . DIRECTORY_SEPARATOR . '1_1700000000_old_backup.pdf';
        file_put_contents($oldFile, 'old backup content');
        touch($oldFile, time() - (25 * 60 * 60));

        $result = $this->service->cleanOrphanedBackups();

        $this->assertEquals(1, $result);
        $this->assertFileDoesNotExist($oldFile);
    }

    public function test_deletes_multiple_old_files_and_preserves_recent_ones(): void
    {
        File::makeDirectory($this->backupDir, 0755, true);

        // Create old files (older than 24 hours)
        $oldFile1 = $this->backupDir . DIRECTORY_SEPARATOR . '1_1700000000_backup1.pdf';
        $oldFile2 = $this->backupDir . DIRECTORY_SEPARATOR . '2_1700000000_backup2.pdf';
        file_put_contents($oldFile1, 'old content 1');
        file_put_contents($oldFile2, 'old content 2');
        touch($oldFile1, time() - (48 * 60 * 60)); // 48 hours old
        touch($oldFile2, time() - (30 * 60 * 60)); // 30 hours old

        // Create a recent file
        $recentFile = $this->backupDir . DIRECTORY_SEPARATOR . '3_1700000000_backup3.pdf';
        file_put_contents($recentFile, 'recent content');

        $result = $this->service->cleanOrphanedBackups();

        $this->assertEquals(2, $result);
        $this->assertFileDoesNotExist($oldFile1);
        $this->assertFileDoesNotExist($oldFile2);
        $this->assertFileExists($recentFile);
    }

    public function test_skips_subdirectories_in_backup_folder(): void
    {
        File::makeDirectory($this->backupDir, 0755, true);

        // Create a subdirectory (should be skipped, not deleted)
        $subDir = $this->backupDir . DIRECTORY_SEPARATOR . 'some_directory';
        File::makeDirectory($subDir, 0755, true);

        $result = $this->service->cleanOrphanedBackups();

        $this->assertEquals(0, $result);
        $this->assertDirectoryExists($subDir);
    }

    public function test_returns_count_of_successfully_deleted_files(): void
    {
        File::makeDirectory($this->backupDir, 0755, true);

        // Create 3 old files
        for ($i = 1; $i <= 3; $i++) {
            $file = $this->backupDir . DIRECTORY_SEPARATOR . "{$i}_backup_file_{$i}.pdf";
            file_put_contents($file, "content {$i}");
            touch($file, time() - (26 * 60 * 60));
        }

        $result = $this->service->cleanOrphanedBackups();

        $this->assertEquals(3, $result);
    }

    public function test_file_exactly_24_hours_old_is_not_deleted(): void
    {
        File::makeDirectory($this->backupDir, 0755, true);

        // Create a file exactly 24 hours old (on the boundary)
        $boundaryFile = $this->backupDir . DIRECTORY_SEPARATOR . 'boundary_backup.pdf';
        file_put_contents($boundaryFile, 'boundary content');
        touch($boundaryFile, time() - (24 * 60 * 60));

        $result = $this->service->cleanOrphanedBackups();

        // File at exactly 24 hours should NOT be deleted (threshold is strictly older than 24h)
        $this->assertEquals(0, $result);
        $this->assertFileExists($boundaryFile);
    }
}
