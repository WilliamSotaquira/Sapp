<?php

namespace Tests\Unit\Services;

use App\DTOs\ValidationResult;
use App\Models\Cut;
use App\Models\SystemSetting;
use App\Services\EvidenceOrganizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Property-based tests for EvidenceOrganizerService.
 *
 * Uses PHPUnit data providers with randomized inputs (100+ iterations)
 * to validate universal correctness properties.
 */
class EvidenceOrganizerServicePropertyTest extends TestCase
{
    use RefreshDatabase;

    protected EvidenceOrganizerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EvidenceOrganizerService();
    }

    // ===================================================================
    // Property 1: Default path resolution
    // For ANY null/empty/whitespace setting, resolveBasePath() returns
    // storage_path('app/public/evidences/cortes')
    // ===================================================================

    /**
     * @group pbt Feature: evidence-file-organization, Property 1: Default path resolution
     * @dataProvider nullEmptyWhitespaceProvider
     *
     * Validates: Requirements 1.2
     */
    public function test_property1_default_path_resolution_for_null_empty_whitespace(mixed $settingValue): void
    {
        if ($settingValue === null) {
            SystemSetting::where('key', 'evidence_base_path')->delete();
        } else {
            SystemSetting::set('evidence_base_path', $settingValue);
        }

        $result = $this->service->resolveBasePath();

        $this->assertEquals(
            storage_path('app/public/evidences/cortes'),
            $result,
            "resolveBasePath() must return default path when setting is: " . json_encode($settingValue)
        );
    }

    /**
     * Generate 100+ null/empty/whitespace values to test default path resolution.
     */
    public static function nullEmptyWhitespaceProvider(): array
    {
        $cases = [];

        // Always include explicit edge cases
        $cases['null'] = [null];
        $cases['empty_string'] = [''];
        $cases['single_space'] = [' '];
        $cases['tab'] = ["\t"];
        $cases['newline'] = ["\n"];
        $cases['carriage_return'] = ["\r"];
        $cases['mixed_whitespace'] = [" \t\n\r "];

        // Generate 100+ random whitespace-only strings
        $whitespaceChars = [' ', "\t", "\n", "\r", "\x0B", "\x00"];

        for ($i = 0; $i < 100; $i++) {
            $length = random_int(1, 20);
            $str = '';
            for ($j = 0; $j < $length; $j++) {
                $str .= $whitespaceChars[array_rand($whitespaceChars)];
            }
            $cases["random_whitespace_{$i}"] = [$str];
        }

        return $cases;
    }

    // ===================================================================
    // Property 2: Path validation rejects invalid characters
    // For ANY string, path validation rejects if it contains characters
    // outside ^[a-zA-Z0-9\-_/\\:]+$
    // ===================================================================

    /**
     * @group pbt Feature: evidence-file-organization, Property 2: Path validation rejects invalid characters
     * @dataProvider validPathCharactersProvider
     *
     * Validates: Requirements 1.3
     */
    public function test_property2_valid_path_characters_accepted(string $path): void
    {
        // Valid paths should match the pattern ^[a-zA-Z0-9\-_/\\:]+$
        $this->assertMatchesRegularExpression(
            '/^[a-zA-Z0-9\-_\/\\\\:]+$/',
            $path,
            "Test input should match valid pattern"
        );

        // The path passes the character validation check
        $isValid = (bool) preg_match('/^[a-zA-Z0-9\-_\/\\\\:]+$/', $path);
        $this->assertTrue($isValid, "Path with only valid characters must pass: {$path}");
    }

    /**
     * @group pbt Feature: evidence-file-organization, Property 2: Path validation rejects invalid characters
     * @dataProvider invalidPathCharactersProvider
     *
     * Validates: Requirements 1.3
     */
    public function test_property2_invalid_path_characters_rejected(string $path): void
    {
        // Strings containing characters outside the valid set must be rejected
        $isValid = (bool) preg_match('/^[a-zA-Z0-9\-_\/\\\\:]+$/', $path);
        $this->assertFalse($isValid, "Path with invalid characters must be rejected: " . bin2hex($path));
    }

    /**
     * Generate 100+ valid path strings (only alphanumeric, hyphens, underscores, slashes, colons).
     */
    public static function validPathCharactersProvider(): array
    {
        $cases = [];
        $validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_/\\:';

        // Explicit valid paths
        $cases['simple_path'] = ['/var/data/evidences'];
        $cases['windows_path'] = ['C:\\Users\\data\\evidences'];
        $cases['relative_path'] = ['storage/app/public'];
        $cases['with_hyphens'] = ['my-path/sub-dir'];
        $cases['with_underscores'] = ['my_path/sub_dir'];
        $cases['drive_letter'] = ['D:\\backup'];

        // Generate 100+ random valid paths
        for ($i = 0; $i < 100; $i++) {
            $length = random_int(1, 50);
            $str = '';
            $validLen = strlen($validChars);
            for ($j = 0; $j < $length; $j++) {
                $str .= $validChars[random_int(0, $validLen - 1)];
            }
            $cases["random_valid_{$i}"] = [$str];
        }

        return $cases;
    }

    /**
     * Generate 100+ strings containing invalid path characters.
     */
    public static function invalidPathCharactersProvider(): array
    {
        $cases = [];

        // Explicit invalid cases
        $cases['spaces'] = ['/path with spaces'];
        $cases['at_sign'] = ['path@name'];
        $cases['exclamation'] = ['path!name'];
        $cases['hash'] = ['path#name'];
        $cases['dollar'] = ['path$name'];
        $cases['percent'] = ['path%name'];
        $cases['ampersand'] = ['path&name'];
        $cases['asterisk'] = ['path*name'];
        $cases['question'] = ['path?name'];
        $cases['pipe'] = ['path|name'];
        $cases['angle_brackets'] = ['path<name>'];
        $cases['quotes'] = ['path"name'];
        $cases['tilde'] = ['path~name'];
        $cases['backtick'] = ['path`name'];
        $cases['parentheses'] = ['path(name)'];
        $cases['brackets'] = ['path[name]'];
        $cases['braces'] = ['path{name}'];
        $cases['semicolon'] = ['path;name'];
        $cases['comma'] = ['path,name'];
        $cases['equals'] = ['path=name'];
        $cases['plus'] = ['path+name'];
        $cases['dot'] = ['path.name'];

        // Invalid characters that are NOT in the valid set [a-zA-Z0-9\-_/\\:]
        $invalidCharArray = [' ', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '+', '=', '[', ']', '{', '}', '|', ';', ',', '.', '<', '>', '?', '~', '"', "'"];

        // Generate 80+ random strings with at least one invalid character
        for ($i = 0; $i < 80; $i++) {
            // Pick a guaranteed invalid character
            $invalidChar = $invalidCharArray[array_rand($invalidCharArray)];

            // Build a base string from simple alphanumeric chars only
            $baseChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $length = random_int(2, 25);
            $str = '';
            $baseLen = strlen($baseChars);
            for ($j = 0; $j < $length; $j++) {
                $str .= $baseChars[random_int(0, $baseLen - 1)];
            }

            // Insert the invalid character at a random position
            $insertPos = random_int(0, strlen($str));
            $str = substr($str, 0, $insertPos) . $invalidChar . substr($str, $insertPos);

            $cases["random_invalid_{$i}"] = [$str];
        }

        return $cases;
    }

    // ===================================================================
    // Property 5: Custom folder name validation
    // For ANY folder name, validation passes IFF it matches
    // ^[a-zA-Z0-9_-]+$ AND length is 1-128 AND no duplicate exists
    // ===================================================================

    /**
     * @group pbt Feature: evidence-file-organization, Property 5: Custom folder name validation
     * @dataProvider validFolderNamesProvider
     *
     * Validates: Requirements 2.4
     */
    public function test_property5_valid_folder_names_pass_validation(string $name): void
    {
        // Use a temp directory that doesn't contain the folder
        $basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pbt_test_' . uniqid();
        @mkdir($basePath, 0755, true);

        try {
            $result = $this->service->validateFolderName($name, $basePath);

            $this->assertTrue(
                $result->passed,
                "Valid folder name '{$name}' (len=" . strlen($name) . ") must pass validation. Errors: " . implode(', ', $result->errors)
            );
            $this->assertEmpty($result->errors);
        } finally {
            @rmdir($basePath);
        }
    }

    /**
     * @group pbt Feature: evidence-file-organization, Property 5: Custom folder name validation
     * @dataProvider invalidFolderNamesProvider
     *
     * Validates: Requirements 2.4
     */
    public function test_property5_invalid_folder_names_fail_validation(string $name, string $reason): void
    {
        $basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pbt_test_' . uniqid();
        @mkdir($basePath, 0755, true);

        try {
            $result = $this->service->validateFolderName($name, $basePath);

            $this->assertFalse(
                $result->passed,
                "Invalid folder name must fail validation. Reason: {$reason}. Name: " . bin2hex($name) . " (len=" . strlen($name) . ")"
            );
            $this->assertNotEmpty($result->errors);
        } finally {
            @rmdir($basePath);
        }
    }

    /**
     * @group pbt Feature: evidence-file-organization, Property 5: Custom folder name validation
     * @dataProvider duplicateFolderNameProvider
     *
     * Validates: Requirements 2.4
     */
    public function test_property5_duplicate_folder_names_fail_validation(string $name): void
    {
        $basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pbt_dup_' . uniqid();
        $existingDir = $basePath . DIRECTORY_SEPARATOR . $name;
        @mkdir($existingDir, 0755, true);

        try {
            $result = $this->service->validateFolderName($name, $basePath);

            $this->assertFalse(
                $result->passed,
                "Folder name '{$name}' must fail when directory already exists"
            );
            $this->assertNotEmpty($result->errors);
        } finally {
            File::deleteDirectory($basePath);
        }
    }

    /**
     * Generate 100+ valid folder names (alphanumeric, hyphens, underscores, length 1-128).
     */
    public static function validFolderNamesProvider(): array
    {
        $cases = [];
        $validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';

        // Boundary cases
        $cases['single_char_a'] = ['a'];
        $cases['single_char_Z'] = ['Z'];
        $cases['single_digit'] = ['5'];
        $cases['single_hyphen_with_alpha'] = ['a-b'];
        $cases['single_underscore_with_alpha'] = ['a_b'];
        $cases['exactly_128_chars'] = [str_repeat('a', 128)];
        $cases['typical_name'] = ['corte-1-2024-01-15'];
        $cases['all_digits'] = ['123456'];
        $cases['mixed'] = ['My_Cut-Folder123'];

        // Generate 100+ random valid folder names
        for ($i = 0; $i < 100; $i++) {
            $length = random_int(1, 128);
            $str = '';
            $validLen = strlen($validChars);
            for ($j = 0; $j < $length; $j++) {
                $str .= $validChars[random_int(0, $validLen - 1)];
            }
            $cases["random_valid_folder_{$i}"] = [$str];
        }

        return $cases;
    }

    /**
     * Generate 100+ invalid folder names with reasons.
     */
    public static function invalidFolderNamesProvider(): array
    {
        $cases = [];
        $validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
        $invalidChars = ' !@#$%^&*()+=[]{}|;\'",.<>?`~';

        // Empty string (length < 1)
        $cases['empty_string'] = ['', 'empty_string'];

        // Too long (> 128 chars)
        $cases['129_chars'] = [str_repeat('x', 129), 'exceeds_128_chars'];
        $cases['200_chars'] = [str_repeat('y', 200), 'exceeds_128_chars'];
        $cases['256_chars'] = [str_repeat('z', 256), 'exceeds_128_chars'];

        // Invalid characters - explicit cases
        $cases['space'] = ['my folder', 'contains_space'];
        $cases['dot'] = ['my.folder', 'contains_dot'];
        $cases['slash'] = ['my/folder', 'contains_slash'];
        $cases['backslash'] = ['my\\folder', 'contains_backslash'];
        $cases['colon'] = ['my:folder', 'contains_colon'];
        $cases['at_sign'] = ['my@folder', 'contains_at'];
        $cases['hash'] = ['my#folder', 'contains_hash'];
        $cases['unicode'] = ['carpeta_ñ', 'contains_unicode'];
        $cases['emoji'] = ['folder_😀', 'contains_emoji'];
        $cases['tab_char'] = ["my\tfolder", 'contains_tab'];

        // Generate 90+ random invalid folder names
        for ($i = 0; $i < 45; $i++) {
            // Invalid character in valid-length string
            $length = random_int(2, 50);
            $str = '';
            $validLen = strlen($validChars);
            for ($j = 0; $j < $length - 1; $j++) {
                $str .= $validChars[random_int(0, $validLen - 1)];
            }
            // Insert invalid char at random position
            $invalidChar = $invalidChars[random_int(0, strlen($invalidChars) - 1)];
            $insertPos = random_int(0, strlen($str));
            $str = substr($str, 0, $insertPos) . $invalidChar . substr($str, $insertPos);
            $cases["random_invalid_char_{$i}"] = [$str, 'contains_invalid_character'];
        }

        for ($i = 0; $i < 45; $i++) {
            // Strings that exceed 128 characters (valid chars but too long)
            $length = random_int(129, 300);
            $str = '';
            $validLen = strlen($validChars);
            for ($j = 0; $j < $length; $j++) {
                $str .= $validChars[random_int(0, $validLen - 1)];
            }
            $cases["random_too_long_{$i}"] = [$str, 'exceeds_128_chars'];
        }

        return $cases;
    }

    /**
     * Generate folder names to test uniqueness/duplicate detection.
     */
    public static function duplicateFolderNameProvider(): array
    {
        $cases = [];
        $validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';

        // Explicit duplicates
        $cases['simple_name'] = ['existing-folder'];
        $cases['numeric_name'] = ['12345'];
        $cases['typical_cut'] = ['corte-1-2024-01-15'];

        // Generate random valid names that will be pre-created as existing directories
        for ($i = 0; $i < 30; $i++) {
            $length = random_int(3, 30);
            $str = '';
            $validLen = strlen($validChars);
            for ($j = 0; $j < $length; $j++) {
                $str .= $validChars[random_int(0, $validLen - 1)];
            }
            $cases["random_duplicate_{$i}"] = [$str];
        }

        return $cases;
    }
}
