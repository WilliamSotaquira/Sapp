{{-- Tarjeta reutilizable de tarea para Mi Espacio --}}
@php
    $currentWorkspace = $currentWorkspace ?? null;
    $showActions = $showActions ?? false;
    $showOverdue = $showOverdue ?? false;
    $compact = $compact ?? false;

    $priorityStyles = [
        'critical' => 'border-l-rose-500 bg-rose-50/30',
        'high' => 'border-l-orange-500 bg-orange-50/20',
        'medium' => 'border-l-blue-400 bg-white',
        'low' => 'border-l-gray-300 bg-white',
    ];
    $statusIcons = [
        'pending' => 'far fa-circle text-gray-400',
        'confirmed' => 'fas fa-check-circle text-blue-400',
        'in_progress' => 'fas fa-play-circle text-indigo-500',
        'blocked' => 'fas fa-ban text-red-500',
        'in_review' => 'fas fa-eye text-purple-500',
    ];
    $style = $priorityStyles[$task->priority] ?? 'border-l-gray-300 bg-white';
    $icon = $statusIcons[$task->status] ?? 'far fa-circle text-gray-400';
    // Task no tiene scope de workspace: la navegación es segura sin depender de $currentWorkspace.
    $taskUrl = route('tasks.show', $task);
    // La SR sí depende del workspace; el enlace hace auto-switch al abrirse.
    $srUrl = $task->serviceRequest ? route('service-requests.show', $task->service_request_id) : null;
@endphp

<div class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all border-l-4 {{ $style }} {{ $compact ? 'px-4 py-3' : 'p-5' }}"
     style="cursor: context-menu;"
     data-task-id="{{ $task->id }}"
     data-task-code="{{ $task->task_code }}"
     data-task-status="{{ $task->status }}"
     data-task-scheduled="{{ $task->scheduled_date ? '1' : '0' }}"
     data-task-url="{{ route('tasks.show', $task) }}"
     @contextmenu.prevent="openTaskMenu($event)"
     title="Clic derecho para acciones rápidas">
    <div class="flex items-start gap-3">
        <div class="pt-1"><i class="{{ $icon }} text-lg"></i></div>
        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    @if($taskUrl)
                        <a href="{{ $taskUrl }}" class="{{ $compact ? 'text-sm' : 'text-base' }} font-semibold text-gray-900 hover:text-indigo-700 transition line-clamp-1">{{ $task->title }}</a>
                    @else
                        <h4 class="{{ $compact ? 'text-sm' : 'text-base' }} font-semibold text-gray-900 line-clamp-1">{{ $task->title }}</h4>
                    @endif
                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                        <span class="text-xs text-gray-500 font-mono">{{ $task->task_code }}</span>
                        @if($task->serviceRequest)
                            @if($srUrl)
                                <a href="{{ $srUrl }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition">{{ $task->serviceRequest->ticket_number }}</a>
                            @else
                                <span class="text-xs text-gray-500">{{ $task->serviceRequest->ticket_number }}</span>
                            @endif
                            @if($task->serviceRequest->company)
                                <span class="text-xs text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">{{ Str::limit($task->serviceRequest->company->name, 20) }}</span>
                            @endif
                        @endif
                        @if($showOverdue && $task->due_date)
                            <span class="text-xs text-rose-600 font-medium"><i class="fas fa-exclamation-circle mr-0.5"></i>Venció {{ $task->due_date->diffForHumans() }}</span>
                        @endif
                    </div>
                </div>

                @if($showActions)
                    <div class="flex items-center gap-1.5 shrink-0">
                        {{-- Programar para hoy (si no está programada hoy) --}}
                        @if(!$task->scheduled_date || !$task->scheduled_date->isToday())
                            <form action="{{ route('tasks.schedule-quick', $task) }}" method="POST" class="inline">@csrf
                                <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg text-amber-600 hover:bg-amber-50 transition" title="Programar para hoy"><i class="fas fa-calendar-day"></i></button>
                            </form>
                        @endif
                        {{-- Mover a otro día --}}
                        @if($task->scheduled_date)
                            <form action="{{ route('tasks.schedule-quick', $task) }}" method="POST" class="inline" x-data>
                                @csrf
                                <input type="date" name="scheduled_date" min="{{ now()->format('Y-m-d') }}"
                                       class="w-8 h-8 opacity-0 absolute cursor-pointer"
                                       x-ref="datePicker{{ $task->id }}"
                                       onchange="this.form.submit()">
                                <button type="button" @click="$refs.datePicker{{ $task->id }}.showPicker()"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-indigo-600 hover:bg-indigo-50 transition" title="Mover a otro día">
                                    <i class="fas fa-calendar-arrow-down text-sm"></i>
                                </button>
                            </form>
                        @endif
                        {{-- Quitar de la programación --}}
                        @if($task->scheduled_date)
                            <form action="{{ route('tasks.clear-schedule', $task) }}" method="POST" class="inline">@csrf
                                <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition" title="Quitar de la programación"><i class="fas fa-calendar-xmark text-sm"></i></button>
                            </form>
                        @endif
                        {{-- Iniciar --}}
                        @if(in_array($task->status, ['pending', 'confirmed']))
                            <form action="{{ route('my-space.tasks.start', $task) }}" method="POST" class="inline">@csrf
                                <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-600 hover:bg-blue-50 transition" title="Iniciar"><i class="fas fa-play text-sm"></i></button>
                            </form>
                        @endif
                        {{-- Completar --}}
                        @if(in_array($task->status, ['in_progress', 'pending', 'confirmed']))
                            <form action="{{ route('my-space.tasks.complete', $task) }}" method="POST" class="inline">@csrf
                                <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg text-green-600 hover:bg-green-50 transition" title="Completar"><i class="fas fa-check text-sm"></i></button>
                            </form>
                        @endif
                        {{-- Acciones rápidas (menú) --}}
                        <button type="button" @click.stop="openTaskMenuFromButton($event)"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition" title="Acciones rápidas" aria-label="Acciones rápidas">
                            <i class="fas fa-ellipsis-vertical text-sm"></i>
                        </button>
                        {{-- Ver detalle --}}
                        <a href="{{ $taskUrl }}" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition" title="Ver detalle"><i class="fas fa-arrow-right text-sm"></i></a>
                    </div>
                @else
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" @click.stop="openTaskMenuFromButton($event)"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition" title="Acciones rápidas" aria-label="Acciones rápidas">
                            <i class="fas fa-ellipsis-vertical text-sm"></i>
                        </button>
                        <a href="{{ $taskUrl }}" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition" title="Ver detalle"><i class="fas fa-arrow-right text-sm"></i></a>
                    </div>
                @endif
            </div>

            @if(!$compact)
                <div class="flex items-center gap-3 mt-2 flex-wrap">
                    @if($task->scheduled_start_time)
                        <span class="inline-flex items-center text-xs text-gray-500"><i class="far fa-clock mr-1"></i>{{ \Carbon\Carbon::parse($task->scheduled_start_time)->format('H:i') }}</span>
                    @endif
                    @if($task->estimated_duration_minutes || $task->estimated_hours)
                        <span class="inline-flex items-center text-xs text-gray-500"><i class="fas fa-hourglass-half mr-1"></i>{{ $task->estimated_duration_minutes ? $task->estimated_duration_minutes.' min' : $task->estimated_hours.'h' }}</span>
                    @endif
                    @if($task->relationLoaded('subtasks') && $task->subtasks->count() > 0)
                        @php $done = $task->subtasks->where('status','completed')->count(); $total = $task->subtasks->count(); @endphp
                        <span class="inline-flex items-center text-xs {{ $done === $total ? 'text-green-600' : 'text-gray-500' }}"><i class="fas fa-list-check mr-1"></i>{{ $done }}/{{ $total }}</span>
                    @endif
                    @if($task->is_critical)
                        <span class="px-2 py-0.5 rounded text-xs font-bold uppercase bg-rose-100 text-rose-700">Crítica</span>
                    @endif
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 capitalize">{{ str_replace('_',' ',$task->status) }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
