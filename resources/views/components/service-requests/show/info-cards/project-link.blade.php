@props(['serviceRequest'])

@php
    $project = $serviceRequest->project;
    $companyId = (int) session('current_company_id');
    $availableProjects = \App\Models\Project::where('company_id', $companyId)
        ->active()
        ->orderBy('name')
        ->get(['id', 'name', 'code']);
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
    <div class="flex items-center justify-between mb-2">
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide flex items-center gap-1.5">
            <i class="fas fa-project-diagram text-indigo-400" aria-hidden="true"></i>
            Proyecto
        </h4>
    </div>

    @if($project)
        {{-- Ya está vinculada a un proyecto --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('projects.show', $project) }}"
               class="text-sm font-semibold text-indigo-700 hover:text-indigo-900 transition flex items-center gap-1.5">
                <span>{{ $project->name }}</span>
                <i class="fas fa-external-link-alt text-[10px]" aria-hidden="true"></i>
            </a>
            <form action="{{ route('projects.unlink-request', [$project, $serviceRequest]) }}" method="POST" class="inline"
                  onsubmit="return confirm('¿Desvincular esta solicitud del proyecto?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs text-gray-400 hover:text-red-500 transition" title="Desvincular del proyecto">
                    <i class="fas fa-unlink" aria-hidden="true"></i>
                </button>
            </form>
        </div>
        <p class="text-[10px] text-gray-400 mt-1 font-mono">{{ $project->code }}</p>
    @else
        {{-- No está vinculada — mostrar selector para asociar --}}
        @if($availableProjects->isNotEmpty())
            <form method="POST" id="linkProjectForm">
                @csrf
                <input type="hidden" name="service_request_id" value="{{ $serviceRequest->id }}">
                <div class="flex items-center gap-2">
                    <select id="projectSelector" required
                            class="flex-1 text-xs border border-gray-300 rounded-lg px-2.5 py-1.5 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
                        <option value="">Asociar a proyecto...</option>
                        @foreach($availableProjects as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="p-1.5 text-indigo-600 hover:text-indigo-800 transition" title="Vincular"
                            onclick="var sel = document.getElementById('projectSelector'); if(sel.value) { this.closest('form').action = '/projects/' + sel.value + '/link-request'; return true; } return false;">
                        <i class="fas fa-link text-sm" aria-hidden="true"></i>
                    </button>
                </div>
            </form>
        @else
            <p class="text-xs text-gray-400">Sin proyectos activos.
                <a href="{{ route('projects.create') }}" class="text-indigo-600 hover:underline">Crear uno</a>
            </p>
        @endif
    @endif
</div>
