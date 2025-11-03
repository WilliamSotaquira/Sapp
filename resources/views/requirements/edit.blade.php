@extends('layouts.app')

@section('title', 'Editar ' . $requirement->code . ' - SDM')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Editar Requerimiento: {{ $requirement->code }}</h1>
    <a href="{{ route('requirements.show', $requirement) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('requirements.update', $requirement) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="title" class="form-label">Título *</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                               id="title" name="title" value="{{ old('title', $requirement->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción *</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description" name="description" rows="5" required>{{ old('description', $requirement->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas Adicionales</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror"
                                  id="notes" name="notes" rows="3">{{ old('notes', $requirement->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="reporter_id" class="form-label">Reportador *</label>
                        <select class="form-select @error('reporter_id') is-invalid @enderror"
                                id="reporter_id" name="reporter_id" required>
                            <option value="">Seleccionar Reportador</option>
                            @foreach($reporters as $reporter)
                                <option value="{{ $reporter->id }}"
                                    {{ old('reporter_id', $requirement->reporter_id) == $reporter->id ? 'selected' : '' }}>
                                    {{ $reporter->name }} - {{ $reporter->department }}
                                </option>
                            @endforeach
                        </select>
                        @error('reporter_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="classification_id" class="form-label">Clasificación *</label>
                        <select class="form-select @error('classification_id') is-invalid @enderror"
                                id="classification_id" name="classification_id" required>
                            <option value="">Seleccionar Clasificación</option>
                            @foreach($classifications as $classification)
                                <option value="{{ $classification->id }}"
                                    {{ old('classification_id', $requirement->classification_id) == $classification->id ? 'selected' : '' }}>
                                    {{ $classification->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('classification_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="project_id" class="form-label">Proyecto</label>
                        <select class="form-select @error('project_id') is-invalid @enderror"
                                id="project_id" name="project_id">
                            <option value="">Sin Proyecto</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}"
                                    {{ old('project_id', $requirement->project_id) == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Requerimiento Padre</label>
                        <select class="form-select @error('parent_id') is-invalid @enderror"
                                id="parent_id" name="parent_id">
                            <option value="">Sin Padre</option>
                            @foreach($parentRequirements as $parent)
                                <option value="{{ $parent->id }}"
                                    {{ old('parent_id', $requirement->parent_id) == $parent->id ? 'selected' : '' }}>
                                    {{ $parent->code }} - {{ Str::limit($parent->title, 40) }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="priority" class="form-label">Prioridad *</label>
                                <select class="form-select @error('priority') is-invalid @enderror"
                                        id="priority" name="priority" required>
                                    <option value="low" {{ old('priority', $requirement->priority) == 'low' ? 'selected' : '' }}>Baja</option>
                                    <option value="medium" {{ old('priority', $requirement->priority) == 'medium' ? 'selected' : '' }}>Media</option>
                                    <option value="high" {{ old('priority', $requirement->priority) == 'high' ? 'selected' : '' }}>Alta</option>
                                    <option value="urgent" {{ old('priority', $requirement->priority) == 'urgent' ? 'selected' : '' }}>Urgente</option>
                                </select>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Estado *</label>
                                <select class="form-select @error('status') is-invalid @enderror"
                                        id="status" name="status" required>
                                    <option value="pending" {{ old('status', $requirement->status) == 'pending' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="in_progress" {{ old('status', $requirement->status) == 'in_progress' ? 'selected' : '' }}>En Progreso</option>
                                    <option value="completed" {{ old('status', $requirement->status) == 'completed' ? 'selected' : '' }}>Completado</option>
                                    <option value="cancelled" {{ old('status', $requirement->status) == 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="progress" class="form-label">Progreso (%)</label>
                        <input type="number" class="form-control @error('progress') is-invalid @enderror"
                               id="progress" name="progress" min="0" max="100"
                               value="{{ old('progress', $requirement->progress) }}">
                        @error('progress')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="due_date" class="form-label">Fecha Límite</label>
                        <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                               id="due_date" name="due_date"
                               value="{{ old('due_date', $requirement->due_date ? $requirement->due_date->format('Y-m-d') : '') }}">
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="completed_date" class="form-label">Fecha de Completado</label>
                        <input type="date" class="form-control @error('completed_date') is-invalid @enderror"
                               id="completed_date" name="completed_date"
                               value="{{ old('completed_date', $requirement->completed_date ? $requirement->completed_date->format('Y-m-d') : '') }}">
                        @error('completed_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Actualizar Requerimiento
                </button>
                <a href="{{ route('requirements.show', $requirement) }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
