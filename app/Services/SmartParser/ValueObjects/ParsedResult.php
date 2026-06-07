<?php

namespace App\Services\SmartParser\ValueObjects;

use Carbon\Carbon;

class ParsedResult
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $channel,
        public readonly string $requesterName,
        public readonly ?string $requesterEmail,
        public readonly ?int $requesterId,
        public readonly bool $requesterPending,
        public readonly ?int $subServiceId,
        public readonly ?int $serviceId,
        public readonly ?int $familyId,
        public readonly ?int $slaId,
        public readonly ?Carbon $createdAt,
        public readonly ?string $dueDate,
        public readonly string $criticalityLevel,
        public readonly array $webRoutes,
        public readonly array $tasks,
        public readonly array $confidences,
    ) {}

    /**
     * Convierte a formato payload compatible con el formulario existente.
     *
     * @return array{payload: array, meta: array}
     */
    public function toPayload(int $companyId, ?int $requestedBy): array
    {
        return [
            'payload' => [
                'company_id' => $companyId,
                'requester_id' => $this->requesterPending ? null : ($this->requesterId ?? $requestedBy),
                'title' => mb_substr($this->title, 0, 255),
                'description' => mb_substr($this->description, 0, 5000),
                'sub_service_id' => $this->subServiceId,
                'service_id' => $this->serviceId,
                'family_id' => $this->familyId,
                'sla_id' => $this->slaId,
                'requested_by' => $requestedBy,
                'entry_channel' => $this->channel,
                'criticality_level' => $this->criticalityLevel,
                'created_at' => $this->createdAt
                    ? $this->createdAt->format('Y-m-d\TH:i')
                    : Carbon::now()->format('Y-m-d\TH:i'),
                'due_date' => $this->dueDate,
                'web_routes' => json_encode($this->webRoutes),
                'is_reportable' => true,
                'tasks_template' => 'none',
                'tasks' => $this->tasks,
                '__pending_requester_name' => $this->requesterPending ? $this->requesterName : null,
                '__pending_requester_email' => $this->requesterPending ? $this->requesterEmail : null,
            ],
            'meta' => [
                'requester_name' => $this->requesterName,
                'requester_created' => false,
                'requester_pending' => $this->requesterPending,
                'sub_service_name' => null,
                'task_count' => count($this->tasks),
                'web_route_count' => count($this->webRoutes),
                'confidences' => $this->confidences,
            ],
        ];
    }
}
