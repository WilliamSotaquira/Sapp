@extends('layouts.app')

@section('title', 'Clasificaciones - SDM')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Clasificaciones</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('classifications.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nueva Clasificación
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Color</th>
                        <th>Descripción</th>
                        <th>Requerimientos</th>
                        <th>Orden</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classifications as $classification)
                    <tr>
                        <td>
                            <a href="{{ route('classifications.show', $classification) }}" class="text-decoration-none">
                                {{ $classification->name }}
                            </a>
                        </td>
                        <td>
                            <span class="badge" style="background-color: {{ $classification->color }}; color: white;">
                                {{ $classification->color }}
                            </span>
                        </td>
                        <td>{{ $classification->description ? Str::limit($classification->description, 50) : 'Sin descripción' }}</td>
                        <td>
                            <span class="badge bg-info">{{ $classification->requirements_count }}</span>
                        </td>
                        <td>{{ $classification->order }}</td>
                        <td>
                            @if($classification->is_active)
                                <span class="badge bg-success">Activa</span>
                            @else
                                <span class="badge bg-danger">Inactiva</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('classifications.show', $classification) }}"
                                   class="btn btn-outline-primary" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('classifications.edit', $classification) }}"
                                   class="btn btn-outline-secondary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('classifications.destroy', $classification) }}"
                                      method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger"
                                            title="Eliminar"
                                            onclick="return confirm('¿Estás seguro de eliminar esta clasificación?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-tags fa-3x mb-3"></i>
                            <p>No se encontraron clasificaciones</p>
                            <a href="{{ route('classifications.create') }}" class="btn btn-primary">
                                Crear Primera Clasificación
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
                Mostrando {{ $classifications->firstItem() }} - {{ $classifications->lastItem() }} de {{ $classifications->total() }} registros
            </div>
            {{ $classifications->links() }}
        </div>
    </div>
</div>
@endsection
