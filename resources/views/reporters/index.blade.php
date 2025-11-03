@extends('layouts.app')

@section('title', 'Reportadores - SDM')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Reportadores</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('reporters.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nuevo Reportador
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
                        <th>Email</th>
                        <th>Departamento</th>
                        <th>Cargo</th>
                        <th>Requerimientos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reporters as $reporter)
                    <tr>
                        <td>
                            <a href="{{ route('reporters.show', $reporter) }}" class="text-decoration-none">
                                {{ $reporter->name }}
                            </a>
                        </td>
                        <td>{{ $reporter->email }}</td>
                        <td>{{ $reporter->department }}</td>
                        <td>{{ $reporter->position ?? 'No especificado' }}</td>
                        <td>
                            <span class="badge bg-info">{{ $reporter->requirements_count }}</span>
                        </td>
                        <td>
                            @if($reporter->is_active)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('reporters.show', $reporter) }}"
                                   class="btn btn-outline-primary" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('reporters.edit', $reporter) }}"
                                   class="btn btn-outline-secondary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('reporters.destroy', $reporter) }}"
                                      method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger"
                                            title="Eliminar"
                                            onclick="return confirm('¿Estás seguro de eliminar este reportador?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <p>No se encontraron reportadores</p>
                            <a href="{{ route('reporters.create') }}" class="btn btn-primary">
                                Crear Primer Reportador
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
                Mostrando {{ $reporters->firstItem() }} - {{ $reporters->lastItem() }} de {{ $reporters->total() }} registros
            </div>
            {{ $reporters->links() }}
        </div>
    </div>
</div>
@endsection
