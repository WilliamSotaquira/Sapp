@extends('layouts.app')

@section('title', $reporter->name . ' - SDM')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">{{ $reporter->name }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('reporters.edit', $reporter) }}" class="btn btn-outline-secondary me-2">
            <i class="fas fa-edit me-2"></i>Editar
        </a>
        <a href="{{ route('reporters.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>
</div>

<div class="row">
    <!-- Información del Reportador -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Información Personal</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Nombre:</strong>
                    <p class="mb-0">{{ $reporter->name }}</p>
                </div>

                <div class="mb-3">
                    <strong>Email:</strong>
                    <p class="mb-0">{{ $reporter->email }}</p>
                </div>

                <div class="mb-3">
                    <strong>Departamento:</strong>
                    <p class="mb-0">{{ $reporter->department }}</p>
                </div>

                @if($reporter->position)
                <div class="mb-3">
                    <strong>Cargo:</strong>
                    <p class="mb-0">{{ $reporter->position }}</p>
                </div>
                @endif

                @if($reporter->phone)
                <div class="mb-3">
                    <strong>Teléfono:</strong>
                    <p class="mb-0">{{ $reporter->phone }}</p>
                </div>
                @endif

                <div class="mb-3">
                    <strong>Estado:</strong>
                    <br>
                    @if($reporter->is_active)
                        <span class="badge bg-success">Activo</span>
                    @else
                        <span class="badge bg-danger">Inactivo</span>
                    @endif
                </div>

                <div class="mb-3">
                    <strong>Fecha de Registro:</strong>
                    <p class="mb-0">{{ $reporter->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Requerimientos del Reportador -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Requerimientos Reportados</h5>
            </div>
            <div class="card-body">
                @if($requirements->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Título</th>
                                    <th>Clasificación</th>
                                    <th>Prioridad</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requirements as $requirement)
                                <tr>
                                    <td><strong>{{ $requirement->code }}</strong></td>
                                    <td>{{ Str::limit($requirement->title, 50) }}</td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $requirement->classification->color }}">
                                            {{ $requirement->classification->name }}
                                        </span>
                                    </td>
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
                                    <td>{{ $requirement->created_at->format('d/m/Y') }}</td>
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
                    <p class="text-muted text-center">Este reportador no ha creado requerimientos</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
