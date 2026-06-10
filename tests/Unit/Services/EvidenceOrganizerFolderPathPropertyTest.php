<?php

namespace Tests\Unit\Services;

use App\Models\SystemSetting;
use App\Services\EvidenceOrganizerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property 4: Suggested folder path follows naming pattern
 *
 * For any cut with a given cut_id and start_date, the suggestFolderPath() method
 * SHALL return a string matching {basePath}/corte-{cut_id}-{YYYY-MM-DD} where
 * YYYY-MM-DD is the formatted start_date.
 *
 * **Validates: Requirements 2.1**
 *
 * @group pbt Feature: evidence-file-organization, Property 4: Suggested folder path follows naming pattern
 */
class EvidenceOrganizerFolderPathPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected EvidenceOrganizerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EvidenceOrganizerService();
    }

    /**
     * Property 4: For 100 random (cutId, startDate) combinations using the default base path,
     * the output matches {basePath}/corte-{cutId}-{YYYY-MM-DD}.
     */
    public function test_suggested_folder_path_follows_naming_pattern_with_default_base_path(): void
    {
        // Ensure no custom path is configured (use default)
        SystemSetting::where('key', 'evidence_base_path')->delete();

        $expectedBasePath = storage_path('app/public/evidences/cortes');

        for ($i = 0; $i < 100; $i++) {
            $cutId = random_int(1, 999999);
            $year = random_int(2000, 2099);
            $month = random_int(1, 12);
            $day = random_int(1, 28); // Use 28 to avoid invalid dates
            $startDate = Carbon::create($year, $month, $day);

            $result = $this->service->suggestFolderPath($cutId, $startDate);

            $expectedDateStr = $startDate->format('Y-m-d');
            $expectedPath = $expectedBasePath . DIRECTORY_SEPARATOR . "corte-{$cutId}-{$expectedDateStr}";

            $this->assertEquals(
                $expectedPath,
                $result,
                "Failed for cutId={$cutId}, date={$expectedDateStr}: got '{$result}'"
            );

            // Also verify via regex that the pattern matches
            $escapedBase = preg_quote($expectedBasePath, '/');
            $pattern = '/^' . $escapedBase . '[\/\\\\]corte-\d+-\d{4}-\d{2}-\d{2}$/';
            $this->assertMatchesRegularExpression(
                $pattern,
                $result,
                "Pattern mismatch for cutId={$cutId}, date={$expectedDateStr}"
            );
        }
    }

    /**
     * Property 4: For 100 random (cutId, startDate) combinations using a configured base path,
     * the output matches {configuredBasePath}/corte-{cutId}-{YYYY-MM-DD}.
     */
    public function test_suggested_folder_path_follows_naming_pattern_with_configured_base_path(): void
    {
        $customBasePath = '/var/data/evidences/custom';
        SystemSetting::set('evidence_base_path', $customBasePath);

        for ($i = 0; $i < 100; $i++) {
            $cutId = random_int(1, 999999);
            $year = random_int(2000, 2099);
            $month = random_int(1, 12);
            $day = random_int(1, 28);
            $startDate = Carbon::create($year, $month, $day);

            $result = $this->service->suggestFolderPath($cutId, $startDate);

            $expectedDateStr = $startDate->format('Y-m-d');
            $expectedPath = $customBasePath . DIRECTORY_SEPARATOR . "corte-{$cutId}-{$expectedDateStr}";

            $this->assertEquals(
                $expectedPath,
                $result,
                "Failed for cutId={$cutId}, date={$expectedDateStr} with custom base path"
            );

            // Verify the result contains the cut_id
            $this->assertStringContainsString(
                "corte-{$cutId}-",
                $result,
                "Result does not contain correct cut_id segment"
            );

            // Verify the result contains the formatted date
            $this->assertStringContainsString(
                $expectedDateStr,
                $result,
                "Result does not contain correct date segment"
            );
        }
    }

    /**
     * Property 4: The suggested path always starts with the resolved base path.
     */
    public function test_suggested_folder_path_always_starts_with_base_path(): void
    {
        SystemSetting::where('key', 'evidence_base_path')->delete();

        $basePath = $this->service->resolveBasePath();

        for ($i = 0; $i < 100; $i++) {
            $cutId = random_int(1, 999999);
            $startDate = Carbon::create(
                random_int(2000, 2099),
                random_int(1, 12),
                random_int(1, 28)
            );

            $result = $this->service->suggestFolderPath($cutId, $startDate);

            $this->assertStringStartsWith(
                $basePath . DIRECTORY_SEPARATOR,
                $result,
                "Path does not start with resolved base path for cutId={$cutId}"
            );
        }
    }

    /**
     * Property 4: The folder name segment (after base path) always matches corte-{id}-{date}.
     */
    public function test_folder_name_segment_matches_pattern(): void
    {
        SystemSetting::where('key', 'evidence_base_path')->delete();

        $basePath = $this->service->resolveBasePath();

        for ($i = 0; $i < 100; $i++) {
            $cutId = random_int(1, 999999);
            $startDate = Carbon::create(
                random_int(2000, 2099),
                random_int(1, 12),
                random_int(1, 28)
            );

            $result = $this->service->suggestFolderPath($cutId, $startDate);

            // Extract the folder name segment (everything after basePath + separator)
            $folderName = str_replace($basePath . DIRECTORY_SEPARATOR, '', $result);

            // Verify it matches the exact naming pattern
            $this->assertMatchesRegularExpression(
                '/^corte-\d+-\d{4}-\d{2}-\d{2}$/',
                $folderName,
                "Folder name '{$folderName}' does not match expected pattern for cutId={$cutId}"
            );

            // Verify the cut_id in the folder name is correct
            $expectedFolderName = "corte-{$cutId}-" . $startDate->format('Y-m-d');
            $this->assertEquals(
                $expectedFolderName,
                $folderName,
                "Folder name does not match expected value"
            );
        }
    }
}
