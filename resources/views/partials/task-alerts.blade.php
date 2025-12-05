@php
    use App\Models\TaskAlert;
    use App\Models\Task;
    
    $user = auth()->user();
    
    // Obtener alertas según el rol
    if ($user->isAdmin()) {
        $alerts = TaskAlert::with('task.technician.user')
            ->unread()
            ->orderBy('alert_at', 'desc')
            ->take(10)
            ->get();
            
        // Estadísticas de tareas críticas para admin
        $criticalStats = [
            'overdue' => Task::critical()->overdue()->count(),
            'due_soon' => Task::critical()->dueSoon()->count(),
            'pending' => Task::critical()->pending()->count(),
        ];
    } else {
        // Técnico solo ve sus propias alertas
        $technician = \App\Models\Technician::where('user_id', $user->id)->first();
        $alerts = collect();
        $criticalStats = ['overdue' => 0, 'due_soon' => 0, 'pending' => 0];
        
        if ($technician) {
            $alerts = TaskAlert::with('task')
                ->whereHas('task', fn($q) => $q->where('technician_id', $technician->id))
                ->unread()
                ->orderBy('alert_at', 'desc')
                ->take(5)
                ->get();
        }
    }
    
    $hasAlerts = $alerts->isNotEmpty() || ($user->isAdmin() && ($criticalStats['overdue'] > 0 || $criticalStats['due_soon'] > 0));
@endphp

@if($hasAlerts)
<div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
    <div class="bg-gradient-to-r from-red-500 to-orange-500 px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2 text-white">
            <i class="fas fa-bell animate-pulse"></i>
            <h3 class="font-bold">Alertas de Tareas Críticas</h3>
        </div>
        @if($alerts->isNotEmpty())
        <span class="bg-white/20 text-white text-xs px-2 py-1 rounded-full">{{ $alerts->count() }} nuevas</span>
        @endif
    </div>
    
    @if($user->isAdmin() && ($criticalStats['overdue'] > 0 || $criticalStats['due_soon'] > 0))
    <div class="grid grid-cols-3 gap-px bg-gray-100">
        <div class="bg-white p-4 text-center {{ $criticalStats['overdue'] > 0 ? 'border-b-4 border-red-500' : '' }}">
            <div class="text-3xl font-bold {{ $criticalStats['overdue'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                {{ $criticalStats['overdue'] }}
            </div>
            <div class="text-sm text-gray-600">Vencidas</div>
        </div>
        <div class="bg-white p-4 text-center {{ $criticalStats['due_soon'] > 0 ? 'border-b-4 border-yellow-500' : '' }}">
            <div class="text-3xl font-bold {{ $criticalStats['due_soon'] > 0 ? 'text-yellow-600' : 'text-gray-400' }}">
                {{ $criticalStats['due_soon'] }}
            </div>
            <div class="text-sm text-gray-600">Próximas a vencer</div>
        </div>
        <div class="bg-white p-4 text-center">
            <div class="text-3xl font-bold text-blue-600">{{ $criticalStats['pending'] }}</div>
            <div class="text-sm text-gray-600">Pendientes</div>
        </div>
    </div>
    @endif
    
    @if($alerts->isNotEmpty())
    <div class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
        @foreach($alerts as $alert)
        <div class="px-4 py-3 hover:bg-gray-50 flex items-start gap-3">
            <div class="w-10 h-10 rounded-full flex items-center justify-center 
                        bg-{{ $alert->color }}-100 text-{{ $alert->color }}-600">
                <i class="fas {{ $alert->icon }}"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full 
                                 bg-{{ $alert->color }}-100 text-{{ $alert->color }}-700">
                        {{ $alert->label }}
                    </span>
                    <span class="text-xs text-gray-400">{{ $alert->alert_at->diffForHumans() }}</span>
                </div>
                <p class="text-sm text-gray-700 mt-1 truncate">{{ $alert->message }}</p>
                @if($alert->task)
                <div class="flex items-center gap-2 mt-1 text-xs text-gray-500">
                    <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">{{ $alert->task->task_code }}</span>
                    @if($alert->task->technician)
                    <span><i class="fas fa-user"></i> {{ $alert->task->technician->user->name }}</span>
                    @endif
                    @if($alert->task->due_date)
                    <span><i class="fas fa-calendar"></i> {{ $alert->task->due_date->format('d/m/Y') }}</span>
                    @endif
                </div>
                @endif
            </div>
            <div class="flex items-center gap-1">
                <a href="{{ route('tasks.show', $alert->task) }}" class="text-blue-600 hover:text-blue-800 p-1.5" title="Ver tarea">
                    <i class="fas fa-eye"></i>
                </a>
                <form action="{{ route('task-alerts.dismiss', $alert) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-gray-400 hover:text-gray-600 p-1.5" title="Descartar">
                        <i class="fas fa-times"></i>
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif
    
    @if($user->isAdmin() && $criticalStats['overdue'] > 0)
    <div class="px-4 py-3 bg-red-50 border-t border-red-100">
        <a href="{{ route('tasks.index', ['filter' => 'overdue']) }}" class="text-red-600 hover:text-red-800 text-sm font-medium flex items-center gap-2">
            <i class="fas fa-exclamation-triangle"></i>
            Ver todas las tareas vencidas
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    @endif
</div>
@endif
