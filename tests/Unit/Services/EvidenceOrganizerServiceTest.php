<?php

namespace Tests\Unit\Services;

use App\DTOs\ValidationResult;
use App\Models\SystemSetting;
use App\Services\EvidenceOrganizerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EvidenceOrganizerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EvidenceOrganizerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EvidenceOrganizerService();
    }

    // ---------------------------------------------------------------
    // resolveBasePath() tests
    // ---------------------------------------------------------------

    public function test_resolve_base_path_returns_default_when_setting_is_null(): void
    {
        // Ensure no setting exists (default state)
        SystemSetting::where('key', 'evidence_base_path')->delete();

        $result = $this->service->resolveBasePath();

        $this->assertEquals(storage_path('app/public/evidences/cortes'), $result);
    }

    public function test_resolve_base_path_returns_default_when_setting_is_empty_string(): void
    {
        SystemSetting::set('evidence_base_path', '');

        $result = $this->service->resolveBasePath();

        $this->assertEquals(storage_path('app/public/evidences/cortes'), $result);
    }

    public function test_resolve_base_path_returns_default_when_setting_is_whitespace(): void
    {
        SystemSetting::set('evidence_base_path', '   ');

        $result = $this->service->resolveBasePath();

        $this->assertEquals(storage_path('app/public/evidences/cortes'), $result);
    }

    public function test_resolve_base_path_returns_configured_path_when_set(): void
    {
        $customPath = '/var/data/evidences';
        SystemSetting::set('evidence_base_path', $customPath);

        $result = $this->service->resolveBasePath();

        $this->assertEquals($customPath, $result);
    }

    // ---------------------------------------------------------------
    // validateFolderName() tests
    // ---------------------------------------------------------------

    public function test_validate_folder_name_passes_for_valid_name(): void
    {
        $basePath = sys_get_temp_dir();

        $result = $this->service->validateFolderName('corte-1-2024-01-15', $basePath);

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->errors);
    }

    public function test_validate_folder_name_passes_for_alphanumeric_only(): void
    {
        $basePath = sys_get_temp_dir();

        $result = $this->service->validateFolderName('MyCutFolder123', $basePath);

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->errors);
    }

    public function test_validate_folder_name_passes_for_underscores_and_hyphens(): void
    {
        $basePath = sys_get_temp_dir();

        $result = $this->service->validateFolderName('cut_2024-01_test', $basePath);

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->errors);
    }

    public function test_validate_folder_name_fails_for_empty_string(): void
    {
        $basePath = sys_get_temp_dir();

        $result = $this->service->validateFolderName('', $basePath);

        $this->assertFalse($result->passed);
        $this->assertNotEmpty($result->errors);
    }

    public function test_validate_folder_name_fails_for_name_exceeding_128_chars(): void
    {
        $basePath = sys_get_temp_dir();
        $longName = str_repeat('a', 129);

        $result = $this->service->validateFolderName($longName, $basePath);

        $this->assertFalse($result->passed);
        $this->assertNotEmpty($result->errors);
    }

    public function test_validate_folder_name_passes_for_exactly_128_chars(): void
    {
        $basePath = sys_get_temp_dir();
        $name = str_repeat('a', 128);

        $result = $this->service->validateFolderName($name, $basePath);

        $this->assertTrue($result->passed);
    }

    public function test_validate_folder_name_fails_for_spaces(): void
    {
        $basePath = sys_get_temp_dir();

        $result = $this->service->validateFolderName('my folder', $basePath);

        $this->assertFalse($result->passed);
        $this->assertNotEmpty($result->errors);
    }

    public function test_validate_folder_name_fails_for_special_characters(): void
    {
        $basePath = sys_get_temp_dir();

        $result = $this->service->validateFolderName('folder@name!', $basePath);

        $this->assertFalse($result->passed);
        $this->assertNotEmpty($result->errors);
    }

    public function test_validate_folder_name_fails_for_slashes(): void
    {
        $basePath = sys_get_temp_dir();

        $result = $this->service->validateFolderName('folder/name', $basePath);

        $this->assertFalse($result->passed);
        $this->assertNotEmpty($result->errors);
    }

    public function test_validate_folder_name_fails_for_dots(): void
    {
        $basePath = sys_get_temp_dir();

        $result = $this->service->validateFolderName('folder.name', $basePath);

        $this->assertFalse($result->passed);
        $this->assertNotEmpty($result->errors);
    }

    public function test_validate_folder_name_fails_when_directory_already_exists(): void
    {
        // Create a temp directory to simulate an existing folder
        $basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'evidence_test_' . uniqid();
        $folderName = 'existing-folder';
        $existingDir = $basePath . DIRECTORY_SEPARATOR . $folderName;

        File::makeDirectory($existingDir, 0755, true);

        try {
            $result = $this->service->validateFolderName($folderName, $basePath);

            $this->assertFalse($result->passed);
            $this->assertNotEmpty($result->errors);
        } finally {
            // Cleanup
            File::deleteDirectory($basePath);
        }
    }

    public function test_validate_folder_name_passes_when_directory_does_not_exist(): void
    {
        $basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'evidence_test_' . uniqid();
        File::makeDirectory($basePath, 0755, true);

        try {
            $result = $this->service->validateFolderName('new-folder', $basePath);

            $this->assertTrue($result->passed);
        } finally {
            File::deleteDirectory($basePath);
        }
    }

    // ---------------------------------------------------------------
    // suggestFolderPath() tests
    // ---------------------------------------------------------------

    public function test_suggest_folder_path_uses_default_base_path_and_naming_pattern(): void
    {
        // No custom path configured
        SystemSetting::where('key', 'evidence_base_path')->delete();

        $cutId = 42;
        $startDate = Carbon::create(2024, 3, 15);

        $result = $this->service->suggestFolderPath($cutId, $startDate);

        $expected = storage_path('app/public/evidences/cortes') . DIRECTORY_SEPARATOR . 'corte-42-2024-03-15';
        $this->assertEquals($expected, $result);
    }

    public function test_suggest_folder_path_uses_configured_base_path(): void
    {
        $customPath = '/var/data/evidences';
        SystemSetting::set('evidence_base_path', $customPath);

        $cutId = 7;
        $startDate = Carbon::create(2025, 1, 1);

        $result = $this->service->suggestFolderPath($cutId, $startDate);

        $expected = '/var/data/evidences' . DIRECTORY_SEPARATOR . 'corte-7-2025-01-01';
        $this->assertEquals($expected, $result);
    }

    public function test_suggest_folder_path_formats_date_as_yyyy_mm_dd(): void
    {
        SystemSetting::where('key', 'evidence_base_path')->delete();

        $cutId = 1;
        $startDate = Carbon::create(2023, 12, 5, 14, 30, 0);

        $result = $this->service->suggestFolderPath($cutId, $startDate);

        // Date should be formatted without time component
        $this->assertStringContainsString('corte-1-2023-12-05', $result);
        $this->assertStringNotContainsString('14', $result);
    }

    public function test_suggest_folder_path_handles_single_digit_month_and_day_with_zero_padding(): void
    {
        SystemSetting::where('key', 'evidence_base_path')->delete();

        $cutId = 100;
        $startDate = Carbon::create(2024, 1, 9);

        $result = $this->service->suggestFolderPath($cutId, $startDate);

        $this->assertStringContainsString('corte-100-2024-01-09', $result);
    }

    public function test_suggest_folder_path_trims_trailing_separator_from_base_path(): void
    {
        SystemSetting::set('evidence_base_path', '/var/data/evidences/');

        $cutId = 5;
        $startDate = Carbon::create(2024, 6, 20);

        $result = $this->service->suggestFolderPath($cutId, $startDate);

        // Should not have double separators
        $this->assertStringNotContainsString('//', $result);
        $this->assertStringContainsString('corte-5-2024-06-20', $result);
    }

    // ---------------------------------------------------------------
    // createCutFolder() tests
    // ---------------------------------------------------------------

    public function test_create_cut_folder_creates_directory_recursively(): void
    {
        $basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'evidence_test_' . uniqid();
        $folderPath = $basePath . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'corte-1-2024-01-01';

        try {
            $result = $this->service->createCutFolder($folderPath);

            $this->assertTrue($result);
            $this->assertDirectoryExists($folderPath);
        } finally {
            File::deleteDirectory($basePath);
        }
    }

    public function test_create_cut_folder_returns_true_when_directory_already_exists(): void
    {
        $folderPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'evidence_test_' . uniqid();
        File::makeDirectory($folderPath, 0755, true);

        try {
            $result = $this->service->createCutFolder($folderPath);

            $this->assertTrue($result);
            $this->assertDirectoryExists($folderPath);
        } finally {
            File::deleteDirectory($folderPath);
        }
    }

    public function test_create_cut_folder_does_not_overwrite_existing_contents(): void
    {
        $folderPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'evidence_test_' . uniqid();
        File::makeDirectory($folderPath, 0755, true);

        // Create a file in the existing directory
        $testFile = $folderPath . DIRECTORY_SEPARATOR . 'existing-file.txt';
        file_put_contents($testFile, 'existing content');

        try {
            $result = $this->service->createCutFolder($folderPath);

            $this->assertTrue($result);
            $this->assertFileExists($testFile);
            $this->assertEquals('existing content', file_get_contents($testFile));
        } finally {
            File::deleteDirectory($folderPath);
        }
    }

    public function test_create_cut_folder_returns_false_on_permission_error(): void
    {
        // Use an invalid/impossible path to trigger failure
        if (PHP_OS_FAMILY === 'Windows') {
            // On Windows, use a path that should fail
            $folderPath = 'Z:\\nonexistent_drive\\impossible_path\\' . uniqid();
        } else {
            // On Linux/Mac, use a read-only system path
            $folderPath = '/proc/impossible_path/' . uniqid();
        }

        $result = $this->service->createCutFolder($folderPath);

        $this->assertFalse($result);
    }
}
