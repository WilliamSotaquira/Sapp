@props(['scope' => 'current', 'scopeEntities' => null])

@php
    $scope = $scope === 'all' ? 'all' : 'current';
    $scopeEntities = $scopeEntities ?? collect();
    // company_id explícito activo en la URL (filtro a una entidad concreta).
    $activeCompanyId = (int) request('company_id', 0);
    // Parámetros base a preservar al cambiar el alcance (todo menos page y las
    // claves de alcance, que se re-setean según la opción elegida).
    $scopeBaseParams = request()->except(['page', 'scope', 'company_id']);
    $urlAll = route('service-requests.index', array_merge($scopeBaseParams, ['scope' => 'all']));
    $urlCurrent = route('service-requests.index', $scopeBaseParams);
    // ¿Estamos viendo TODAS las entidades? (scope=all y sin filtro de entidad concreta)
    $viewingAll = $scope === 'all' && $activeCompanyId === 0;
@endphp

<!-- Header Principal -->
<div class="bg-gradient-to-r from-blue-600 to-indigo-700 shadow-xl rounded-xl sm:rounded-2xl overflow-hidden mb-4 sm:mb-6 md:mb-8">
    <div class="px-4 sm:px-6 md:px-8 py-4 sm:py-5 md:py-6 text-white">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-3 sm:space-x-4 mb-3 sm:mb-4 lg:mb-0">
                <div class="bg-white/20 p-2 sm:p-3 rounded-xl sm:rounded-2xl backdrop-blur-sm">
                    <i class="fas fa-tasks text-xl sm:text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-lg sm:text-xl md:text-2xl font-bold">Solicitudes de Servicio</h1>
                    <p class="text-blue-100 opacity-90 mt-0.5 sm:mt-1 text-xs sm:text-sm md:text-base hidden sm:block">Gestión y seguimiento de todas las solicitudes del sistema</p>
                    <p class="text-blue-100 opacity-90 mt-0.5 text-xs sm:hidden">Gestión de solicitudes</p>
                </div>
            </div>
            <x-service-requests.index.header.filters-badge />
        </div>

        {{-- Selector de alcance: ver TODAS mis entidades o una concreta. --}}
        {{-- Solo tiene sentido si el usuario tiene más de una entidad accesible. --}}
        @if ($scopeEntities->count() > 1)
            <div class="mt-4 pt-4 border-t border-white/20 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                <span class="text-xs font-semibold uppercase tracking-wide text-blue-100/90 flex items-center gap-1.5">
                    <i class="fas fa-layer-group"></i>
                    Mostrando
                </span>
                <div class="flex flex-wrap items-center gap-1.5">
                    {{-- Opción: Todas mis entidades --}}
                    <a href="{{ $urlAll }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $viewingAll ? 'bg-white text-blue-700 shadow-sm' : 'bg-white/10 text-white hover:bg-white/20' }}">
                        <i class="fas fa-globe-americas"></i>
                        Todas mis entidades
                    </a>
                    {{-- Opción: entidad activa en sesión (contexto clásico) --}}
                    <a href="{{ $urlCurrent }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ !$viewingAll && $activeCompanyId === 0 ? 'bg-white text-blue-700 shadow-sm' : 'bg-white/10 text-white hover:bg-white/20' }}"
                       title="Solo la entidad activa en tu sesión">
                        <i class="fas fa-building"></i>
                        Entidad activa
                    </a>
                    {{-- Filtro directo a una entidad concreta --}}
                    @foreach ($scopeEntities as $entity)
                        <a href="{{ route('service-requests.index', array_merge($scopeBaseParams, ['scope' => 'all', 'company_id' => $entity->id])) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition {{ $activeCompanyId === (int) $entity->id ? 'bg-white text-blue-700 shadow-sm' : 'bg-white/10 text-white/90 hover:bg-white/20' }}">
                            {{ \Illuminate\Support\Str::limit($entity->name, 22) }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
