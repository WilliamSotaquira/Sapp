<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDM - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <span class="navbar-brand mb-0 h1">
                <i class="fas fa-landmark me-2"></i>SDM - Portal Gubernamental
            </span>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Dashboard</h1>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body text-center">
                        <h3>{{ $stats['total_requirements'] }}</h3>
                        <p>Total Requerimientos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body text-center">
                        <h3>{{ $stats['pending_requirements'] }}</h3>
                        <p>Pendientes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <h3>{{ $stats['active_projects'] }}</h3>
                        <p>Proyectos Activos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body text-center">
                        <h3>{{ $stats['total_reporters'] }}</h3>
                        <p>Reportadores</p>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($recentRequirements) && $recentRequirements->count() > 0)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Requerimientos Recientes
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Título</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentRequirements as $requirement)
                            <tr>
                                <td><strong>{{ $requirement->code ?? 'N/A' }}</strong></td>
                                <td>{{ $requirement->title ?? 'Sin título' }}</td>
                                <td>
                                    <span class="badge bg-{{ ($requirement->status ?? 'pending') == 'pending' ? 'warning' : 'success' }}">
                                        {{ $requirement->status ?? 'unknown' }}
                                    </span>
                                </td>
                                <td>{{ isset($requirement->created_at) ? \Carbon\Carbon::parse($requirement->created_at)->format('d/m/Y') : 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
