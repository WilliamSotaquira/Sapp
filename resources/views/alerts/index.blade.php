@extends('layouts.app')

@section('title', 'Alertas - SDM')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Alertas</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('alerts.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nueva Alerta
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Mensaje</th>
                        <th>Tipo</th>
                        <th>Fecha Alerta</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alerts as $alert)
                    <tr>
                        <td>{{ Str::limit($alert->title, 40) }}</td>
                        <td>{{ Str::limit($alert->message, 60) }}</td>
                        <td>
                            <span class="badge bg-{{ $alert->getAlertTypeClass() }}">
                                {{ ucfirst($alert->type) }}
                            </span>
                        </td>
                        <td>{{ $alert->alert_date->format('d/m/Y H:i') }}</td>
                        <td>
                            @if($alert->is_active)
                                <span class="badge bg-success">Activa</span>
                            @else
                                <span class="badge bg-secondary">Inactiva</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('alerts.show', $alert) }}"
                                   class="btn btn-outline-primary" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('alerts.edit', $alert) }}"
                                   class="btn btn-outline-secondary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('alerts.destroy', $alert) }}"
                                      method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger"
                                            title="Eliminar"
                                            onclick="return confirm('¿Estás seguro de eliminar esta alerta?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-bell fa-3x mb-3"></i>
                            <p>No se encontraron alertas</p>
                            <a href="{{ route('alerts.create') }}" class="btn btn-primary">
                                Crear Primera Alerta
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
                Mostrando {{ $alerts->firstItem() }} - {{ $alerts->lastItem() }} de {{ $alerts->total() }} registros
            </div>
            {{ $alerts->links() }}
        </div>
    </div>
</div>
@endsection
