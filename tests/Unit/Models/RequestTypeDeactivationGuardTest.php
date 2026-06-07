<?php

namespace Tests\Unit\Models;

use App\Models\RequestType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestTypeDeactivationGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_type_cannot_be_deactivated(): void
    {
        $general = RequestType::create([
            'slug' => 'general',
            'name' => 'General',
            'is_active' => true,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("No se puede desactivar el tipo 'general'.");

        $general->is_active = false;
        $general->save();
    }

    public function test_general_type_can_be_updated_without_deactivation(): void
    {
        $general = RequestType::create([
            'slug' => 'general',
            'name' => 'General',
            'is_active' => true,
        ]);

        $general->name = 'General Updated';
        $general->save();

        $this->assertEquals('General Updated', $general->fresh()->name);
        $this->assertTrue($general->fresh()->is_active);
    }

    public function test_other_types_can_be_deactivated(): void
    {
        $reunion = RequestType::create([
            'slug' => 'reunion',
            'name' => 'Reunión',
            'is_active' => true,
        ]);

        $reunion->is_active = false;
        $reunion->save();

        $this->assertFalse($reunion->fresh()->is_active);
    }

    public function test_general_type_stays_active_after_failed_deactivation(): void
    {
        $general = RequestType::create([
            'slug' => 'general',
            'name' => 'General',
            'is_active' => true,
        ]);

        try {
            $general->is_active = false;
            $general->save();
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertTrue($general->fresh()->is_active);
    }
}
