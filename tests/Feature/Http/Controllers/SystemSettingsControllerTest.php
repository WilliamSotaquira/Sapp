<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit and property-based tests for SystemSettingsController.
 *
 * Validates: Requirements 1.1, 1.3, 1.4, 1.5, 1.6, 1.7
 */
class SystemSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    // ===================================================================
    // Helper methods
    // ===================================================================

    private function createAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function createNonAdmin(): User
    {
        // Ensure a non-admin user has role != 'admin' and ID != 1
        // (User::isAdmin() also returns true for user ID 1)
        $admin = $this->createAdmin(); // Takes ID 1
        return User::factory()->create(['role' => 'user']);
    }

    /**
     * Create a temporary directory with a path that only contains valid characters
     * for the controller's regex: ^[a-zA-Z0-9\-_/\\:]+$
     */
    private function createValidTempDir(): string
    {
        $path = storage_path('app' . DIRECTORY_SEPARATOR . 'test_evidence_' . bin2hex(random_bytes(4)));
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    // ===================================================================
    // Unit Tests: Settings form renders for admin (Req 1.1)
    // ===================================================================

    public function test_settings_form_renders_for_admin(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('settings.edit'));

        $response->assertOk();
        $response->assertViewIs('settings.edit');
        $response->assertViewHas('basePath');
    }

    public function test_settings_form_displays_current_base_path_value(): void
    {
        $admin = $this->createAdmin();
        SystemSetting::set('evidence_base_path', 'C:\\evidences\\cortes');

        $response = $this->actingAs($admin)->get(route('settings.edit'));

        $response->assertOk();
        $response->assertViewHas('basePath', 'C:\\evidences\\cortes');
    }

    public function test_settings_form_displays_null_when_no_path_configured(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('settings.edit'));

        $response->assertOk();
        $response->assertViewHas('basePath', null);
    }

    // ===================================================================
    // Unit Tests: Non-admin gets 403 (Req 1.6)
    // ===================================================================

    public function test_non_admin_gets_403_on_settings_edit(): void
    {
        // Create admin first to take ID 1, then non-admin user
        $this->createAdmin();
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('settings.edit'));

        $response->assertForbidden();
    }

    public function test_non_admin_gets_403_on_settings_update(): void
    {
        // Create admin first to take ID 1, then non-admin user
        $this->createAdmin();
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->put(route('settings.update'), [
            'base_path' => '/some/path',
        ]);

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_redirected_to_login_on_edit(): void
    {
        $response = $this->get(route('settings.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_redirected_to_login_on_update(): void
    {
        $response = $this->put(route('settings.update'), [
            'base_path' => '/some/path',
        ]);

        $response->assertRedirect(route('login'));
    }

    // ===================================================================
    // Unit Tests: Invalid path rejected with error (Req 1.3, 1.4)
    // ===================================================================

    public function test_empty_path_rejected_with_validation_error(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put(route('settings.update'), [
            'base_path' => '',
        ]);

        $response->assertSessionHasErrors('base_path');
    }

    public function test_path_with_invalid_characters_rejected(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put(route('settings.update'), [
            'base_path' => '/path with spaces/here',
        ]);

        $response->assertSessionHasErrors('base_path');
    }

    public function test_path_exceeding_max_length_rejected(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put(route('settings.update'), [
            'base_path' => str_repeat('a', 256),
        ]);

        $response->assertSessionHasErrors('base_path');
    }

    public function test_path_with_special_characters_rejected(): void
    {
        $admin = $this->createAdmin();

        $invalidPaths = [
            '/path@invalid',
            '/path#invalid',
            '/path$invalid',
            'path!name',
            'path&name',
        ];

        foreach ($invalidPaths as $path) {
            $response = $this->actingAs($admin)
                ->from(route('settings.edit'))
                ->put(route('settings.update'), ['base_path' => $path]);

            $response->assertSessionHasErrors('base_path');
        }
    }

    public function test_nonexistent_directory_rejected_with_error(): void
    {
        $admin = $this->createAdmin();
        // Use a path with valid regex characters that doesn't exist
        $nonexistentPath = storage_path('app/nonexistent_' . bin2hex(random_bytes(4)));

        $response = $this->actingAs($admin)
            ->from(route('settings.edit'))
            ->put(route('settings.update'), ['base_path' => $nonexistentPath]);

        $response->assertRedirect(route('settings.edit'));
        $response->assertSessionHasErrors('base_path');
    }

    // ===================================================================
    // Unit Tests: Valid path persisted correctly (Req 1.5)
    // ===================================================================

    public function test_valid_path_persisted_correctly(): void
    {
        $admin = $this->createAdmin();

        // Create a temporary directory with valid-regex characters
        $tempPath = $this->createValidTempDir();

        try {
            $response = $this->actingAs($admin)
                ->from(route('settings.edit'))
                ->put(route('settings.update'), ['base_path' => $tempPath]);

            $response->assertRedirect(route('settings.edit'));
            $response->assertSessionHasNoErrors();
            $response->assertSessionHas('success');

            // Verify persisted in DB
            $this->assertEquals($tempPath, SystemSetting::get('evidence_base_path'));
        } finally {
            @rmdir($tempPath);
        }
    }

    public function test_valid_path_updates_existing_value(): void
    {
        $admin = $this->createAdmin();
        SystemSetting::set('evidence_base_path', '/old/path');

        $tempPath = $this->createValidTempDir();

        try {
            $response = $this->actingAs($admin)
                ->from(route('settings.edit'))
                ->put(route('settings.update'), ['base_path' => $tempPath]);

            $response->assertRedirect(route('settings.edit'));
            $response->assertSessionHasNoErrors();

            $this->assertEquals($tempPath, SystemSetting::get('evidence_base_path'));
        } finally {
            @rmdir($tempPath);
        }
    }

    // ===================================================================
    // Unit Tests: Persistence failure retains previous value (Req 1.7)
    // ===================================================================

    public function test_persistence_failure_retains_previous_value(): void
    {
        $admin = $this->createAdmin();
        $originalPath = '/original/configured/path';
        SystemSetting::set('evidence_base_path', $originalPath);

        // Create a valid temp dir so it passes existence/writable checks
        $tempPath = $this->createValidTempDir();

        try {
            // Simulate persistence failure by dropping the system_settings table
            // temporarily to cause a DB exception during SystemSetting::set()
            \Illuminate\Support\Facades\Schema::rename('system_settings', 'system_settings_backup');

            $response = $this->actingAs($admin)
                ->from(route('settings.edit'))
                ->put(route('settings.update'), ['base_path' => $tempPath]);

            // Restore table before assertions
            \Illuminate\Support\Facades\Schema::rename('system_settings_backup', 'system_settings');

            $response->assertRedirect(route('settings.edit'));
            $response->assertSessionHasErrors('base_path');

            // The original value should still be in the DB (since the write failed)
            $this->assertEquals($originalPath, SystemSetting::where('key', 'evidence_base_path')->first()->value);
        } finally {
            // Ensure table is restored even if test fails
            if (\Illuminate\Support\Facades\Schema::hasTable('system_settings_backup')) {
                \Illuminate\Support\Facades\Schema::rename('system_settings_backup', 'system_settings');
            }
            @rmdir($tempPath);
        }
    }

    // ===================================================================
    // Property 3: Settings persistence round-trip
    // For ANY valid path string that passes validation, storing it via
    // SystemSetting::set() and retrieving via SystemSetting::get()
    // returns the identical string.
    //
    // @group pbt Feature: evidence-file-organization, Property 3: Settings persistence round-trip
    // Validates: Requirements 1.5
    // ===================================================================

    /**
     * @group pbt Feature: evidence-file-organization, Property 3: Settings persistence round-trip
     * @dataProvider validPathStringsProvider
     *
     * Validates: Requirements 1.5
     */
    public function test_property3_settings_persistence_round_trip(string $path): void
    {
        SystemSetting::set('evidence_base_path', $path);

        $retrieved = SystemSetting::get('evidence_base_path');

        $this->assertSame(
            $path,
            $retrieved,
            "Round-trip failed: stored '{$path}' but retrieved '{$retrieved}'"
        );
    }

    /**
     * Generate 100+ valid path strings that conform to the validation pattern.
     * Pattern: ^[a-zA-Z0-9\-_/\\:]+$ with max length 255.
     */
    public static function validPathStringsProvider(): array
    {
        $cases = [];
        $validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_/\\:';

        // Explicit edge cases
        $cases['single_char'] = ['a'];
        $cases['single_slash'] = ['/'];
        $cases['single_backslash'] = ['\\'];
        $cases['single_colon'] = [':'];
        $cases['single_hyphen'] = ['-'];
        $cases['single_underscore'] = ['_'];
        $cases['windows_path'] = ['C:\\Users\\admin\\evidences'];
        $cases['unix_path'] = ['/var/www/evidences/cortes'];
        $cases['relative_path'] = ['storage/app/public/evidences/cortes'];
        $cases['path_with_hyphens'] = ['/my-app/evidence-files/corte-1'];
        $cases['path_with_underscores'] = ['/my_app/evidence_files/corte_1'];
        $cases['max_length_255'] = [str_repeat('a', 255)];
        $cases['mixed_separators'] = ['C:\\data/evidence\\cortes/backup'];
        $cases['numeric_only'] = ['12345'];
        $cases['drive_with_path'] = ['D:\\backup\\2024'];
        $cases['deeply_nested'] = ['/a/b/c/d/e/f/g/h/i/j/k/l/m'];

        // Generate 100+ random valid path strings
        for ($i = 0; $i < 100; $i++) {
            $length = random_int(1, 255);
            $str = '';
            $validLen = strlen($validChars);
            for ($j = 0; $j < $length; $j++) {
                $str .= $validChars[random_int(0, $validLen - 1)];
            }
            $cases["random_valid_path_{$i}"] = [$str];
        }

        return $cases;
    }
}
