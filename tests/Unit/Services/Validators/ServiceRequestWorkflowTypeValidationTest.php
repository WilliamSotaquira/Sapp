<?php

namespace Tests\Unit\Services\Validators;

use Tests\TestCase;
use App\Models\ServiceRequest;
use App\Models\RequestType;
use App\Services\Validators\GeneralTypeValidator;
use App\Services\Validators\MeetingTypeValidator;
use App\Contracts\RequestTypeValidatorInterface;
use Mockery;

class ServiceRequestWorkflowTypeValidationTest extends TestCase
{
    // ==================== resolveTypeValidator ====================

    public function test_resolve_type_validator_returns_general_when_request_type_is_null(): void
    {
        $sr = Mockery::mock(ServiceRequest::class)->makePartial();
        $sr->shouldReceive('getAttribute')->with('requestType')->andReturn(null);

        $method = new \ReflectionMethod(ServiceRequest::class, 'resolveTypeValidator');
        $method->setAccessible(true);

        $validator = $method->invoke($sr);

        $this->assertInstanceOf(GeneralTypeValidator::class, $validator);
    }

    public function test_resolve_type_validator_returns_general_for_general_slug(): void
    {
        $requestType = new RequestType(['slug' => 'general', 'name' => 'General']);

        $sr = Mockery::mock(ServiceRequest::class)->makePartial();
        $sr->shouldReceive('getAttribute')->with('requestType')->andReturn($requestType);

        $method = new \ReflectionMethod(ServiceRequest::class, 'resolveTypeValidator');
        $method->setAccessible(true);

        $validator = $method->invoke($sr);

        $this->assertInstanceOf(GeneralTypeValidator::class, $validator);
    }

    public function test_resolve_type_validator_returns_meeting_validator_for_reunion_slug(): void
    {
        $requestType = new RequestType(['slug' => 'reunion', 'name' => 'Reunión']);

        $sr = Mockery::mock(ServiceRequest::class)->makePartial();
        $sr->shouldReceive('getAttribute')->with('requestType')->andReturn($requestType);

        $method = new \ReflectionMethod(ServiceRequest::class, 'resolveTypeValidator');
        $method->setAccessible(true);

        $validator = $method->invoke($sr);

        $this->assertInstanceOf(MeetingTypeValidator::class, $validator);
    }

    public function test_resolve_type_validator_returns_general_for_unknown_slug(): void
    {
        $requestType = new RequestType(['slug' => 'compromiso', 'name' => 'Compromiso']);

        $sr = Mockery::mock(ServiceRequest::class)->makePartial();
        $sr->shouldReceive('getAttribute')->with('requestType')->andReturn($requestType);

        $method = new \ReflectionMethod(ServiceRequest::class, 'resolveTypeValidator');
        $method->setAccessible(true);

        $validator = $method->invoke($sr);

        $this->assertInstanceOf(GeneralTypeValidator::class, $validator);
    }

    // ==================== Type validation in state transitions ====================

    public function test_null_request_type_skips_type_validation(): void
    {
        // A service request with null request_type_id should pass transition
        // without invoking any type-specific validator
        $sr = Mockery::mock(ServiceRequest::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $sr->shouldReceive('getOriginal')->with('status')->andReturn(ServiceRequest::STATUS_PENDING);
        $sr->shouldReceive('getAttribute')->with('status')->andReturn(ServiceRequest::STATUS_ACCEPTED);
        $sr->shouldReceive('getAttribute')->with('request_type_id')->andReturn(null);
        $sr->shouldReceive('getAttribute')->with('assigned_to')->andReturn(1);
        $sr->shouldReceive('getAttribute')->with('pause_reason')->andReturn(null);

        // validateSpecificTransitions should be called but not trigger errors for this transition
        $sr->shouldReceive('validateSpecificTransitions')
            ->with(ServiceRequest::STATUS_PENDING, ServiceRequest::STATUS_ACCEPTED)
            ->once();

        // resolveTypeValidator should NOT be called when request_type_id is null
        $sr->shouldNotReceive('resolveTypeValidator');

        $method = new \ReflectionMethod(ServiceRequest::class, 'validateStateTransition');
        $method->setAccessible(true);

        // Should not throw
        $method->invoke($sr);
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    public function test_type_validation_throws_exception_on_failure(): void
    {
        $requestType = new RequestType(['slug' => 'reunion', 'name' => 'Reunión']);

        $sr = Mockery::mock(ServiceRequest::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $sr->shouldReceive('getOriginal')->with('status')->andReturn(ServiceRequest::STATUS_PENDING);
        $sr->shouldReceive('getAttribute')->with('status')->andReturn(ServiceRequest::STATUS_ACCEPTED);
        $sr->shouldReceive('getAttribute')->with('request_type_id')->andReturn(1);
        $sr->shouldReceive('getAttribute')->with('requestType')->andReturn($requestType);
        $sr->shouldReceive('getAttribute')->with('assigned_to')->andReturn(1);
        $sr->shouldReceive('getAttribute')->with('pause_reason')->andReturn(null);
        $sr->shouldReceive('getAttribute')->with('meetingDetail')->andReturn(null);

        $sr->shouldReceive('validateSpecificTransitions')
            ->with(ServiceRequest::STATUS_PENDING, ServiceRequest::STATUS_ACCEPTED)
            ->once();

        $method = new \ReflectionMethod(ServiceRequest::class, 'validateStateTransition');
        $method->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Se requiere al menos un participante con rol 'organizador' para continuar.");

        $method->invoke($sr);
    }

    public function test_type_validation_passes_and_preserves_flow(): void
    {
        $requestType = new RequestType(['slug' => 'general', 'name' => 'General']);

        $sr = Mockery::mock(ServiceRequest::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $sr->shouldReceive('getOriginal')->with('status')->andReturn(ServiceRequest::STATUS_PENDING);
        $sr->shouldReceive('getAttribute')->with('status')->andReturn(ServiceRequest::STATUS_ACCEPTED);
        $sr->shouldReceive('getAttribute')->with('request_type_id')->andReturn(1);
        $sr->shouldReceive('getAttribute')->with('requestType')->andReturn($requestType);
        $sr->shouldReceive('getAttribute')->with('assigned_to')->andReturn(1);
        $sr->shouldReceive('getAttribute')->with('pause_reason')->andReturn(null);

        $sr->shouldReceive('validateSpecificTransitions')
            ->with(ServiceRequest::STATUS_PENDING, ServiceRequest::STATUS_ACCEPTED)
            ->once();

        $method = new \ReflectionMethod(ServiceRequest::class, 'validateStateTransition');
        $method->setAccessible(true);

        // Should not throw - GeneralTypeValidator always passes
        $method->invoke($sr);
        $this->assertTrue(true);
    }
}
