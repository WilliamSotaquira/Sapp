@extends('layouts.app')

@section('title', 'Proyectos - SDM')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Proyectos</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('projects.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nuevo Proyecto
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Estado</th>
                        <th>Progreso</th>
                        <th>Requerimientos</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                    <tr>
                        <td><strong>{{ $project->code }}</strong></td>
                        <td>
                            <a href="{{ route('projects.show', $project) }}" class="text-decoration-none">
                                {{ $project->name }}
                            </a>
                            @if($project->description)
                                <br><small class="text-muted">{{ Str::limit($project->description, 60) }}</small>
                            @endif
                        </td>
                        <td>
                            @php
                                $statusColors = [
                                    'active' => 'success',
                                    'completed' => 'primary',
                                    'cancelled' => 'danger',
                                    'on_hold' => 'warning'
                                ];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$project->status] ?? 'secondary' }}">
                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                    <div class="progress-bar" role="progressbar"
                                         style="width: {{ $project->progress }}%"></div>
                                </div>
                                <small>{{ $project->progress }}%</small>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $project->requirements_count }} total</span>
                            @if($project->active_requirements_count > 0)
                                <span class="badge bg-warning">{{ $project->active_requirements_count }} activos</span>
                            @endif
                        </td>
                        <td>{{ $project->start_date->format('d/m/Y') }}</td>
                        <td>
                            @if($project->end_date)
                                {{ $project->end_date->format('d/m/Y') }}
                            @else
                                <span class="text-muted">No definida</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('projects.show', $project) }}"
                                   class="btn btn-outline-primary" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('projects.edit', $project) }}"
                                   class="btn btn-outline-secondary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('projects.destroy', $project) }}"
                                      method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger"
                                            title="Eliminar"
                                            onclick="return confirm('¿Estás seguro de eliminar este proyecto?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-project-diagram fa-3x mb-3"></i>
                            <p>No se encontraron proyectos</p>
                            <a href="{{ route('projects.create') }}" class="btn btn-primary">
                                Crear Primer Proyecto
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
                Mostrando {{ $projects->firstItem() }} - {{ $projects->lastItem() }} de {{ $projects->total() }} registros
            </div>
            {{ $projects->links() }}
        </div>
    </div>
</div>
@endsection
