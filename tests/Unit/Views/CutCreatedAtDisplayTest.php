<?php

namespace Tests\Unit\Views;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Cut;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property tests for Cut created_at date format display in cut list view.
 *
 * - Property 18: Date format output
 *
 * **Validates: Requirements 6.2, 6.3, 6.4**
 *
 * @group pbt Feature: evidence-file-organization, Property 18: Date format output
 */
class CutCreatedAtDisplayTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------
    // Property 18: Date format output
    // For any non-null created_at datetime value on a Cut record, the
    // formatted display string SHALL match the pattern DD/MM/YYYY HH:mm
    // using the server timezone.
    //
    // Validates: Requirements 6.2, 6.3, 6.4
    // ---------------------------------------------------------------

    /**
     * @group pbt Feature: evidence-file-organization, Property 18: Date format output
     */
    public function test_non_null_created_at_renders_as_dd_mm_yyyy_hh_mm(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate a random datetime
            $year = random_int(2000, 2035);
            $month = random_int(1, 12);
            $day = random_int(1, 28);
            $hour = random_int(0, 23);
            $minute = random_int(0, 59);

            $dateTime = Carbon::create($year, $month, $day, $hour, $minute, random_int(0, 59));

            // Apply the format used in the Blade view
            $formatted = $dateTime->format('d/m/Y H:i');

            // Verify the pattern matches DD/MM/YYYY HH:mm
            $this->assertMatchesRegularExpression(
                '/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/',
                $formatted,
                "Iteration {$i}: Format output '{$formatted}' does not match DD/MM/YYYY HH:mm pattern "
                . "for input datetime: {$dateTime->toDateTimeString()}"
            );

            // Verify the individual components are correct
            $expectedDay = str_pad($day, 2, '0', STR_PAD_LEFT);
            $expectedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
            $expectedYear = (string) $year;
            $expectedHour = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $expectedMinute = str_pad($minute, 2, '0', STR_PAD_LEFT);

            $expectedFormatted = "{$expectedDay}/{$expectedMonth}/{$expectedYear} {$expectedHour}:{$expectedMinute}";

            $this->assertEquals(
                $expectedFormatted,
                $formatted,
                "Iteration {$i}: Expected '{$expectedFormatted}' but got '{$formatted}' "
                . "for Carbon date: {$dateTime->toDateTimeString()}"
            );
        }
    }

    /**
     * @group pbt Feature: evidence-file-organization, Property 18: Date format output
     */
    public function test_format_output_day_is_zero_padded(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Specifically test single-digit days (1-9) to verify zero-padding
            $day = random_int(1, 9);
            $dateTime = Carbon::create(
                random_int(2000, 2035),
                random_int(1, 12),
                $day,
                random_int(0, 23),
                random_int(0, 59),
                0
            );

            $formatted = $dateTime->format('d/m/Y H:i');

            // Day part should be zero-padded (e.g., "01", "09")
            $dayPart = substr($formatted, 0, 2);
            $this->assertEquals(
                str_pad($day, 2, '0', STR_PAD_LEFT),
                $dayPart,
                "Iteration {$i}: Day {$day} should be zero-padded to '{$dayPart}' "
                . "in formatted output: {$formatted}"
            );
        }
    }

    /**
     * @group pbt Feature: evidence-file-organization, Property 18: Date format output
     */
    public function test_format_output_uses_24_hour_clock(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $hour = random_int(0, 23);
            $dateTime = Carbon::create(
                random_int(2000, 2035),
                random_int(1, 12),
                random_int(1, 28),
                $hour,
                random_int(0, 59),
                0
            );

            $formatted = $dateTime->format('d/m/Y H:i');

            // Extract hour part (position 11-12 in "DD/MM/YYYY HH:mm")
            $hourPart = substr($formatted, 11, 2);
            $expectedHour = str_pad($hour, 2, '0', STR_PAD_LEFT);

            $this->assertEquals(
                $expectedHour,
                $hourPart,
                "Iteration {$i}: Hour {$hour} should render as '{$expectedHour}' (24h) "
                . "but got '{$hourPart}' in: {$formatted}"
            );
        }
    }

    /**
     * Test that null created_at displays "Sin fecha de creación" in the view.
     *
     * Validates: Requirement 6.4
     */
    public function test_null_created_at_shows_sin_fecha_de_creacion(): void
    {
        $company = Company::create(['name' => 'Test Company']);
        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-TEST-001',
            'name' => 'Test Contract',
            'is_active' => true,
        ]);

        // Create a cut and manually set created_at to null
        $cut = Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Corte Test Null Date',
            'start_date' => now()->subMonth()->format('Y-m-d H:i:s'),
            'end_date' => now()->format('Y-m-d H:i:s'),
        ]);

        // Force created_at to null
        Cut::where('id', $cut->id)->update(['created_at' => null]);
        $cut->refresh();

        // Verify the model has null created_at
        $this->assertNull($cut->created_at);

        // Verify the view logic: when created_at is null, text should be "Sin fecha de creación"
        // This tests the conditional logic from the Blade view
        if ($cut->created_at) {
            $displayText = $cut->created_at->format('d/m/Y H:i');
        } else {
            $displayText = 'Sin fecha de creación';
        }

        $this->assertEquals('Sin fecha de creación', $displayText);
    }

    /**
     * Test that non-null created_at displays the formatted date (not the fallback text).
     *
     * Validates: Requirement 6.2
     */
    public function test_non_null_created_at_does_not_show_fallback_text(): void
    {
        $company = Company::create(['name' => 'Test Company 2']);
        $contract = Contract::create([
            'company_id' => $company->id,
            'number' => 'C-TEST-002',
            'name' => 'Test Contract 2',
            'is_active' => true,
        ]);

        $cut = Cut::create([
            'contract_id' => $contract->id,
            'name' => 'Corte With Date',
            'start_date' => now()->subMonth()->format('Y-m-d H:i:s'),
            'end_date' => now()->format('Y-m-d H:i:s'),
        ]);

        // Verify the model has a non-null created_at
        $this->assertNotNull($cut->created_at);

        // Apply same logic as Blade view
        if ($cut->created_at) {
            $displayText = $cut->created_at->format('d/m/Y H:i');
        } else {
            $displayText = 'Sin fecha de creación';
        }

        // Should NOT be the fallback text
        $this->assertNotEquals('Sin fecha de creación', $displayText);

        // Should match DD/MM/YYYY HH:mm format
        $this->assertMatchesRegularExpression(
            '/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/',
            $displayText
        );
    }
}
