<?php

declare(strict_types=1);

namespace Tests\Unit\Services\SmartParser;

use App\Models\Requester;
use App\Services\SmartParser\Resolvers\RequesterResolver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequesterResolverTest extends TestCase
{
    use RefreshDatabase;

    private RequesterResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new RequesterResolver();
    }

    // --- Email exact match (case-insensitive) ---

    public function test_resolves_by_exact_email_match(): void
    {
        $requester = Requester::factory()->create([
            'company_id' => 1,
            'name' => 'Juan Pérez',
            'email' => 'juan.perez@empresa.com',
            'is_active' => true,
        ]);

        $result = $this->resolver->resolve(1, 'Juan Pérez', 'juan.perez@empresa.com');

        $this->assertEquals($requester->id, $result['id']);
        $this->assertEquals('Juan Pérez', $result['name']);
        $this->assertFalse($result['pending']);
        $this->assertEquals('juan.perez@empresa.com', $result['email']);
    }

    public function test_resolves_by_email_case_insensitive(): void
    {
        $requester = Requester::factory()->create([
            'company_id' => 1,
            'name' => 'María López',
            'email' => 'Maria.Lopez@Empresa.COM',
            'is_active' => true,
        ]);

        $result = $this->resolver->resolve(1, 'María López', 'maria.lopez@empresa.com');

        $this->assertEquals($requester->id, $result['id']);
        $this->assertFalse($result['pending']);
    }

    public function test_email_match_takes_priority_over_name_match(): void
    {
        $requesterByName = Requester::factory()->create([
            'company_id' => 1,
            'name' => 'Carlos García',
            'email' => 'carlos.other@empresa.com',
            'is_active' => true,
        ]);

        $requesterByEmail = Requester::factory()->create([
            'company_id' => 1,
            'name' => 'Carlos Diferente',
            'email' => 'carlos.garcia@empresa.com',
            'is_active' => true,
        ]);

        $result = $this->resolver->resolve(1, 'Carlos García', 'carlos.garcia@empresa.com');

        // Should match by email first, not by name
        $this->assertEquals($requesterByEmail->id, $result['id']);
        $this->assertEquals('Carlos Diferente', $result['name']);
        $this->assertFalse($result['pending']);
    }

    // --- Normalized name match ---

    public function test_resolves_by_normalized_name_without_accents(): void
    {
        $requester = Requester::factory()->create([
            'company_id' => 1,
            'name' => 'José María García',
            'email' => 'jose@empresa.com',
            'is_active' => true,
        ]);

        // Input without accents should match
        $result = $this->resolver->resolve(1, 'Jose Maria Garcia', null);

        $this->assertEquals($requester->id, $result['id']);
        $this->assertEquals('José María García', $result['name']);
        $this->assertFalse($result['pending']);
    }

    public function test_resolves_by_normalized_name_case_insensitive(): void
    {
        $requester = Requester::factory()->create([
            'company_id' => 1,
            'name' => 'Ana Belén Rodríguez',
            'email' => 'ana@empresa.com',
            'is_active' => true,
        ]);

        $result = $this->resolver->resolve(1, 'ANA BELEN RODRIGUEZ', null);

        $this->assertEquals($requester->id, $result['id']);
        $this->assertFalse($result['pending']);
    }

    public function test_resolves_by_normalized_name_with_collapsed_spaces(): void
    {
        $requester = Requester::factory()->create([
            'company_id' => 1,
            'name' => 'Pedro Sánchez',
            'email' => 'pedro@empresa.com',
            'is_active' => true,
        ]);

        // Input with extra spaces should match
        $result = $this->resolver->resolve(1, '  Pedro    Sánchez  ', null);

        $this->assertEquals($requester->id, $result['id']);
        $this->assertFalse($result['pending']);
    }

    public function test_resolves_by_normalized_name_combining_all_normalizations(): void
    {
        $requester = Requester::factory()->create([
            'company_id' => 1,
            'name' => 'María Ángeles Muñoz',
            'email' => 'maria@empresa.com',
            'is_active' => true,
        ]);

        // No accents + uppercase + extra spaces
        $result = $this->resolver->resolve(1, '  MARIA   ANGELES   MUNOZ  ', null);

        $this->assertEquals($requester->id, $result['id']);
        $this->assertFalse($result['pending']);
    }

    // --- Pending (not found) ---

    public function test_marks_as_pending_when_no_match_found(): void
    {
        Requester::factory()->create([
            'company_id' => 1,
            'name' => 'Existing Person',
            'email' => 'existing@empresa.com',
            'is_active' => true,
        ]);

        $result = $this->resolver->resolve(1, 'Unknown Person', 'unknown@other.com');

        $this->assertNull($result['id']);
        $this->assertEquals('Unknown Person', $result['name']);
        $this->assertTrue($result['pending']);
        $this->assertEquals('unknown@other.com', $result['email']);
    }

    public function test_marks_as_pending_when_no_requesters_exist(): void
    {
        $result = $this->resolver->resolve(1, 'New Person', 'new@empresa.com');

        $this->assertNull($result['id']);
        $this->assertEquals('New Person', $result['name']);
        $this->assertTrue($result['pending']);
        $this->assertEquals('new@empresa.com', $result['email']);
    }

    public function test_marks_as_pending_with_null_email(): void
    {
        $result = $this->resolver->resolve(1, 'Unknown Person', null);

        $this->assertNull($result['id']);
        $this->assertEquals('Unknown Person', $result['name']);
        $this->assertTrue($result['pending']);
        $this->assertNull($result['email']);
    }

    public function test_truncates_name_to_255_chars_when_pending(): void
    {
        $longName = str_repeat('A', 300);

        $result = $this->resolver->resolve(1, $longName, null);

        $this->assertNull($result['id']);
        $this->assertEquals(255, mb_strlen($result['name']));
        $this->assertTrue($result['pending']);
    }

    // --- Workspace isolation ---

    public function test_does_not_match_requester_from_different_company(): void
    {
        Requester::factory()->create([
            'company_id' => 2,
            'name' => 'Juan Pérez',
            'email' => 'juan@empresa.com',
            'is_active' => true,
        ]);

        $result = $this->resolver->resolve(1, 'Juan Pérez', 'juan@empresa.com');

        $this->assertNull($result['id']);
        $this->assertTrue($result['pending']);
    }

    // --- Inactive requesters ---

    public function test_does_not_match_inactive_requester_by_email(): void
    {
        Requester::factory()->create([
            'company_id' => 1,
            'name' => 'Inactive Person',
            'email' => 'inactive@empresa.com',
            'is_active' => false,
        ]);

        $result = $this->resolver->resolve(1, 'Inactive Person', 'inactive@empresa.com');

        $this->assertNull($result['id']);
        $this->assertTrue($result['pending']);
    }

    public function test_does_not_match_inactive_requester_by_name(): void
    {
        Requester::factory()->create([
            'company_id' => 1,
            'name' => 'Inactive Person',
            'email' => 'inactive@empresa.com',
            'is_active' => false,
        ]);

        $result = $this->resolver->resolve(1, 'Inactive Person', null);

        $this->assertNull($result['id']);
        $this->assertTrue($result['pending']);
    }

    // --- Fallback from email to name ---

    public function test_falls_back_to_name_match_when_email_not_found(): void
    {
        $requester = Requester::factory()->create([
            'company_id' => 1,
            'name' => 'Ana García',
            'email' => 'ana.garcia@empresa.com',
            'is_active' => true,
        ]);

        // Email doesn't match, but name does
        $result = $this->resolver->resolve(1, 'Ana García', 'wrong@email.com');

        $this->assertEquals($requester->id, $result['id']);
        $this->assertFalse($result['pending']);
    }

    // --- normalizeName unit tests ---

    public function test_normalize_name_removes_accents(): void
    {
        $this->assertEquals('jose maria garcia', $this->resolver->normalizeName('José María García'));
    }

    public function test_normalize_name_lowercases(): void
    {
        $this->assertEquals('juan perez', $this->resolver->normalizeName('JUAN PEREZ'));
    }

    public function test_normalize_name_collapses_spaces(): void
    {
        $this->assertEquals('pedro sanchez', $this->resolver->normalizeName('  Pedro    Sánchez  '));
    }

    public function test_normalize_name_handles_empty_string(): void
    {
        $this->assertEquals('', $this->resolver->normalizeName(''));
    }

    public function test_normalize_name_handles_only_spaces(): void
    {
        $this->assertEquals('', $this->resolver->normalizeName('   '));
    }

    public function test_normalize_name_handles_special_characters(): void
    {
        $this->assertEquals('maria angeles munoz', $this->resolver->normalizeName('María Ángeles Muñoz'));
    }

    // --- Edge case: empty email string ---

    public function test_empty_email_string_skips_email_search(): void
    {
        $requester = Requester::factory()->create([
            'company_id' => 1,
            'name' => 'Test User',
            'email' => '',
            'is_active' => true,
        ]);

        // Empty email should skip email search and fall through to name match
        $result = $this->resolver->resolve(1, 'Test User', '');

        $this->assertEquals($requester->id, $result['id']);
        $this->assertFalse($result['pending']);
    }

    // --- First match wins ---

    public function test_returns_first_match_by_name(): void
    {
        $first = Requester::factory()->create([
            'company_id' => 1,
            'name' => 'Juan Pérez',
            'email' => 'juan1@empresa.com',
            'is_active' => true,
        ]);

        Requester::factory()->create([
            'company_id' => 1,
            'name' => 'Juan Pérez',
            'email' => 'juan2@empresa.com',
            'is_active' => true,
        ]);

        $result = $this->resolver->resolve(1, 'Juan Perez', null);

        // Should return the first match found
        $this->assertNotNull($result['id']);
        $this->assertFalse($result['pending']);
    }
}
