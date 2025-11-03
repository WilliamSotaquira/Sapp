@extends('layouts.app')

@section('title', $project->name . ' - SDM')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">{{ $project->name }} <small class="text-muted">{{ $project->code }}</small></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('projects.edit', $project) }}" class="btn btn-outline-secondary me-2">
            <i class="fas fa-edit me-2"></i>Editar
        </a>
        <a href="{{ route('projects.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>
</div>

<div class="row">
    <!-- Información del Proyecto -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Información del Proyecto</h5>
            </div>
            <div class="card-body">
                @if($project->description)
                <div class="mb-3">
                    <strong>Descripción:</strong>
                    <p class="mb-0">{{ $project->description }}</p>
                </div>
                @endif

                <div class="row">
                    <div class="col-md-4">
                        <strong>Estado:</strong>
                        <br>
                        @php
                            $statusColors = [
                                'active' => 'success',
                                'completed' => 'primary',
                                'cancelled' => 'danger',
                                'on_hold' => 'warning'
                            ];
                        @endphp
                        <span class="badge bg-{{ $statusColors[$project->status] }}">
                            {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                        </span>
                    </div>
                    <div class="col-md-4">
                        <strong>Progreso:</strong>
                        <br>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar" role="progressbar"
                                 style="width: {{ $project->progress }}%">
                                {{ $project->progress }}%
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <strong>Requerimientos:</strong>
                        <br>
                        <span class="badge bg-info">{{ $project->requirements->count() }} total</span>
                        <span class="badge bg-warning">{{ $project->active_requirements_count }} activos</span>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4">
                        <strong>Fecha de Inicio:</strong>
                        <p class="mb-0">{{ $project->start_date->format('d/m/Y') }}</p>
                    </div>
                    <div class="col-md-4">
                        <strong>Fecha de Fin:</strong>
                        <p class="mb-0">
                            @if($project->end_date)
                                {{ $project->end_date->format('d/m/Y') }}
                            @else
                                <span class="text-muted">No definida</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-4">
                        <strong>Presupuesto:</strong>
                        <p class="mb-0">
                            @if($project->budget)
                                ${{ number_format($project->budget, 2) }}
                            @else
                                <span class="text-muted">No definido</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Requerimientos del Proyecto -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Requerimientos del Proyecto</h5>
            </div>
            <div class="card-body">
                @if($requirements->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Título</th>
                                    <th>Prioridad</th>
                                    <th>Estado</th>
                                    <th>Fecha Límite</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requirements as $requirement)
                                <tr>
                                    <td><strong>{{ $requirement->code }}</strong></td>
                                    <td>{{ Str::limit($requirement->title, 50) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $requirement->getPriorityColor() }}">
                                            {{ ucfirst($requirement->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'in_progress' => 'info',
                                                'completed' => 'success',
                                                'cancelled' => 'danger'
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $statusColors[$requirement->status] }}">
                                            {{ ucfirst(str_replace('_', ' ', $requirement->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($requirement->due_date)
                                            {{ $requirement->due_date->format('d/m/Y') }}
                                        @else
                                            <span class="text-muted">No definida</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('requirements.show', $requirement) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando {{ $requirements->firstItem() }} - {{ $requirements->lastItem() }} de {{ $requirements->total() }} registros
                        </div>
                        {{ $requirements->links() }}
                    </div>
                @else
                    <p class="text-muted text-center">No hay requerimientos en este proyecto</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Información Adicional -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Estadísticas</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Fecha de Creación:</strong>
                    <p class="mb-0">{{ $project->created_at->format('d/m/Y H:i') }}</p>
                </div>

                <div class="mb-3">
                    <strong>Última Actualización:</strong>
                    <p class="mb-0">{{ $project->updated_at->format('d/m/Y H:i') }}</p>
                </div>

                <div class="mb-3">
                    <strong>Duración:</strong>
                    <p class="mb-0">
                        @if($project->end_date)
                            {{ $project->start_date->diffInDays($project->end_date) }} días
                        @else
                            <span class="text-muted">En curso</span>
                        @endif
                    </p>
                </div>

                <div class="mb-3">
                    <strong>Requerimientos por Estado:</strong>
                    <div class="mt-2">
                        @php
                            $statusCounts = $project->requirements->groupBy('status')->map->count();
                        @endphp
                        @foreach($statusCounts as $status => $count)
                            <div class="d-flex justify-content-between mb-1">
                                <span>{{ ucfirst(str_replace('_', ' ', $status)) }}:</span>
                                <span class="badge bg-{{ $statusColors[$status] ?? 'secondary' }}">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <form action="{{ route('projects.progress.update', $project) }}" method="POST" class="mt-3">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-sync-alt me-2"></i>Recalcular Progreso
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
