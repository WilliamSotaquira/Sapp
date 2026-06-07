<?php

namespace App\Services;

use App\Models\ServiceRequest;

class TraceabilityChainService
{
    /**
     * Build a hierarchical tree of related service requests up to a maximum depth.
     *
     * Uses eager loading with constrained depth to prevent N+1 queries.
     * Returns a tree structure starting from the given service request.
     *
     * @param ServiceRequest $sr The root service request
     * @param int $maxDepth Maximum depth of child levels to include (default 3)
     * @return array The tree structure with nested children
     */
    public function buildChain(ServiceRequest $sr, int $maxDepth = 3): array
    {
        $this->eagerLoadChain($sr, $maxDepth);

        return $this->buildNode($sr, 0, $maxDepth);
    }

    /**
     * Get the traceability chain formatted for view display.
     *
     * Includes commitments (tasks with type='impact') as distinct nodes with type_label "compromiso".
     * Returns null if the request has no parent and no children (chain section should be hidden).
     *
     * @param ServiceRequest $sr The service request to build the view chain for
     * @return array|null The chain data for the view, or null if no chain exists
     */
    public function getChainForView(ServiceRequest $sr): ?array
    {
        // Check if the request has parent or children before loading
        $hasParent = $sr->service_request_id !== null;
        $hasChildren = $sr->childRequests()->exists();

        if (!$hasParent && !$hasChildren) {
            return null;
        }

        $this->eagerLoadChain($sr, 3);

        $node = $this->buildNode($sr, 0, 3);

        // Add commitment nodes from tasks with type='impact'
        $node['children'] = array_merge(
            $this->buildCommitmentNodes($sr),
            $node['children']
        );

        return $node;
    }

    /**
     * Eager load the chain relationships with constrained depth and N+1 prevention.
     */
    protected function eagerLoadChain(ServiceRequest $sr, int $maxDepth): void
    {
        $eagerLoads = [
            'requestType:id,slug,name',
            'assignee:id,name',
            'tasks' => function ($query) {
                $query->where('type', 'impact')
                    ->select(['id', 'service_request_id', 'title', 'status', 'technician_id', 'created_at'])
                    ->with('technician.user:id,name');
            },
        ];

        if ($maxDepth >= 1) {
            $eagerLoads['childRequests'] = function ($query) {
                $query->select(['id', 'service_request_id', 'ticket_number', 'title', 'status', 'request_type_id', 'assigned_to', 'created_at'])
                    ->with(['requestType:id,slug,name', 'assignee:id,name'])
                    ->limit(50);
            };
        }

        if ($maxDepth >= 2) {
            $eagerLoads['childRequests.childRequests'] = function ($query) {
                $query->select(['id', 'service_request_id', 'ticket_number', 'title', 'status', 'request_type_id', 'assigned_to', 'created_at'])
                    ->with(['requestType:id,slug,name', 'assignee:id,name'])
                    ->limit(50);
            };
        }

        if ($maxDepth >= 3) {
            $eagerLoads['childRequests.childRequests.childRequests'] = function ($query) {
                $query->select(['id', 'service_request_id', 'ticket_number', 'title', 'status', 'request_type_id', 'assigned_to', 'created_at'])
                    ->with(['requestType:id,slug,name', 'assignee:id,name'])
                    ->withCount('childRequests')
                    ->limit(50);
            };
        }

        $sr->load($eagerLoads);
    }

    /**
     * Build a single node in the tree from a ServiceRequest.
     *
     * @param ServiceRequest $sr The service request to convert to a node
     * @param int $currentDepth The current depth in the tree (0 = root)
     * @param int $maxDepth The maximum depth allowed
     * @return array The node data
     */
    protected function buildNode(ServiceRequest $sr, int $currentDepth, int $maxDepth): array
    {
        $typeLabel = $sr->relationLoaded('requestType') && $sr->requestType
            ? $sr->requestType->slug
            : 'general';

        $assignedTechnician = $sr->relationLoaded('assignee') && $sr->assignee
            ? $sr->assignee->name
            : 'Sin asignar';

        $node = [
            'id' => $sr->id,
            'ticket_number' => $sr->ticket_number,
            'title' => $sr->title,
            'status' => $sr->status,
            'type_label' => $typeLabel,
            'assigned_technician' => $assignedTechnician,
            'created_at' => $sr->created_at ? $sr->created_at->format('Y-m-d H:i') : null,
            'is_commitment' => false,
            'children' => [],
            'hidden_children_count' => null,
        ];

        // At max depth, report hidden children count instead of recursing
        if ($currentDepth >= $maxDepth) {
            $node['hidden_children_count'] = $sr->child_requests_count ?? null;
            return $node;
        }

        // Build children nodes recursively
        if ($sr->relationLoaded('childRequests')) {
            foreach ($sr->childRequests as $child) {
                $node['children'][] = $this->buildNode($child, $currentDepth + 1, $maxDepth);
            }
        }

        return $node;
    }

    /**
     * Build commitment nodes from tasks with type='impact' for a service request.
     *
     * @param ServiceRequest $sr The service request
     * @return array Array of commitment nodes
     */
    protected function buildCommitmentNodes(ServiceRequest $sr): array
    {
        $nodes = [];

        if (!$sr->relationLoaded('tasks')) {
            return $nodes;
        }

        foreach ($sr->tasks as $task) {
            $technicianName = 'Sin asignar';
            if ($task->relationLoaded('technician') && $task->technician) {
                if ($task->technician->relationLoaded('user') && $task->technician->user) {
                    $technicianName = $task->technician->user->name;
                }
            }

            $nodes[] = [
                'ticket_number' => $task->task_code ?? null,
                'title' => $task->title,
                'status' => $task->status,
                'type_label' => 'compromiso',
                'assigned_technician' => $technicianName,
                'created_at' => $task->created_at ? $task->created_at->format('Y-m-d H:i') : null,
                'is_commitment' => true,
                'children' => [],
                'hidden_children_count' => null,
            ];
        }

        return $nodes;
    }
}
