@extends('layouts.app')

@section('title', $requirement->code . ' - SDM')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">{{ $requirement->code }} - {{ $requirement->title }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('requirements.edit', $requirement) }}" class="btn btn-outline-secondary me-2">
            <i class="fas fa-edit me-2"></i>Editar
        </a>
        <a href="{{ route('requirements.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>
</div>

<div class="row">
    <!-- Información Principal -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Información del Requerimiento</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Título:</strong>
                        <p class="mb-0">{{ $requirement->title }}</p>
                    </div>
                    <div class="col-md-6">
                        <strong>Código:</strong>
                        <p class="mb-0">{{ $requirement->code }}</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <strong>Descripción:</strong>
                        <p class="mb-0">{{ $requirement->description }}</p>
                    </div>
                </div>

                @if($requirement->notes)
                <div class="row mb-3">
                    <div class="col-12">
                        <strong>Notas Adicionales:</strong>
                        <p class="mb-0">{{ $requirement->notes }}</p>
                    </div>
                </div>
                @endif

                <div class="row">
                    <div class="col-md-4">
                        <strong>Estado:</strong>
                        <br>
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
                    </div>
                    <div class="col-md-4">
                        <strong>Prioridad:</strong>
                        <br>
                        <span class="badge bg-{{ $requirement->getPriorityColor() }}">
                            {{ ucfirst($requirement->priority) }}
                        </span>
                    </div>
                    <div class="col-md-4">
                        <strong>Progreso:</strong>
                        <br>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar" role="progressbar"
                                 style="width: {{ $requirement->progress }}%">
                                {{ $requirement->progress }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Evidencias -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Evidencias</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEvidenceModal">
                    <i class="fas fa-plus me-1"></i>Agregar Evidencia
                </button>
            </div>
            <div class="card-body">
                @if($requirement->evidences->count() > 0)
                    <div class="row">
                        @foreach($requirement->evidences as $evidence)
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="card-title">
                                                <i class="fas fa-file me-2"></i>{{ $evidence->original_name }}
                                            </h6>
                                            @if($evidence->description)
                                                <p class="card-text small">{{ $evidence->description }}</p>
                                            @endif
                                            <small class="text-muted">
                                                Subido: {{ $evidence->created_at->format('d/m/Y H:i') }}
                                            </small>
                                        </div>
                                        <div class="btn-group">
                                            @if($evidence->isImage())
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                        data-bs-toggle="modal" data-bs-target="#imageModal{{ $evidence->id }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            @else
                                                <a href="{{ route('evidences.download', $evidence) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            @endif
                                            <form action="{{ route('evidences.destroy', $evidence) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('¿Eliminar esta evidencia?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal para imágenes -->
                        @if($evidence->isImage())
                        <div class="modal fade" id="imageModal{{ $evidence->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{ $evidence->original_name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        <img src="{{ Storage::url($evidence->file_path) }}"
                                             alt="{{ $evidence->original_name }}"
                                             class="img-fluid">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center">No hay evidencias adjuntas</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Información Secundaria -->
    <div class="col-lg-4">
        <!-- Información de Contexto -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Información de Contexto</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Reportador:</strong>
                    <p class="mb-0">{{ $requirement->reporter->name }}</p>
                    <small class="text-muted">{{ $requirement->reporter->department }}</small>
                </div>

                <div class="mb-3">
                    <strong>Clasificación:</strong>
                    <p class="mb-0">
                        <span class="badge" style="background-color: {{ $requirement->classification->color }}">
                            {{ $requirement->classification->name }}
                        </span>
                    </p>
                </div>

                @if($requirement->project)
                <div class="mb-3">
                    <strong>Proyecto:</strong>
                    <p class="mb-0">
                        <a href="{{ route('projects.show', $requirement->project) }}" class="text-decoration-none">
                            {{ $requirement->project->name }}
                        </a>
                    </p>
                    <small class="text-muted">{{ $requirement->project->code }}</small>
                </div>
                @endif

                @if($requirement->parent)
                <div class="mb-3">
                    <strong>Requerimiento Padre:</strong>
                    <p class="mb-0">
                        <a href="{{ route('requirements.show', $requirement->parent) }}" class="text-decoration-none">
                            {{ $requirement->parent->code }}
                        </a>
                    </p>
                    <small class="text-muted">{{ Str::limit($requirement->parent->title, 50) }}</small>
                </div>
                @endif

                <div class="mb-3">
                    <strong>Fecha de Creación:</strong>
                    <p class="mb-0">{{ $requirement->created_at->format('d/m/Y H:i') }}</p>
                </div>

                <div class="mb-3">
                    <strong>Última Actualización:</strong>
                    <p class="mb-0">{{ $requirement->updated_at->format('d/m/Y H:i') }}</p>
                </div>

                @if($requirement->due_date)
                <div class="mb-3">
                    <strong>Fecha Límite:</strong>
                    <p class="mb-0 {{ $requirement->isOverdue() ? 'text-danger' : '' }}">
                        {{ $requirement->due_date->format('d/m/Y') }}
                        @if($requirement->isOverdue())
                            <br><small class="text-danger">¡Vencido!</small>
                        @endif
                    </p>
                </div>
                @endif

                @if($requirement->completed_date)
                <div class="mb-3">
                    <strong>Fecha de Completado:</strong>
                    <p class="mb-0 text-success">{{ $requirement->completed_date->format('d/m/Y') }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Requerimientos Hijos -->
        @if($requirement->children->count() > 0)
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Requerimientos Hijos</h5>
            </div>
            <div class="card-body">
                @foreach($requirement->children as $child)
                <div class="mb-2 pb-2 border-bottom">
                    <a href="{{ route('requirements.show', $child) }}" class="text-decoration-none">
                        <strong>{{ $child->code }}</strong>
                    </a>
                    <p class="mb-1 small">{{ Str::limit($child->title, 60) }}</p>
                    <div class="d-flex justify-content-between">
                        <span class="badge bg-{{ $child->getPriorityColor() }} badge-sm">
                            {{ ucfirst($child->priority) }}
                        </span>
                        <span class="badge bg-{{ $statusColors[$child->status] ?? 'secondary' }} badge-sm">
                            {{ ucfirst(str_replace('_', ' ', $child->status)) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal para agregar evidencias -->
<div class="modal fade" id="addEvidenceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Evidencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('requirements.evidences.store', $requirement) }}"
                  method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="files" class="form-label">Archivos</label>
                        <input type="file" class="form-control" id="files" name="files[]"
                               multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx">
                        <div class="form-text">
                            Formatos permitidos: JPG, PNG, PDF, DOC, XLS. Máximo 10MB por archivo.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción (opcional)</label>
                        <textarea class="form-control" id="description" name="description"
                                  rows="3" placeholder="Descripción de las evidencias..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Subir Evidencias</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
