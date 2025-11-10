@props(['activeFiltersCount' => 0])

<div class="bg-white/10 px-4 py-2 rounded-full backdrop-blur-sm">
    <span class="text-sm font-semibold flex items-center">
        <i class="fas fa-filter mr-2"></i>
        @if($activeFiltersCount > 0)
            {{ $activeFiltersCount }} Filtro(s) Activo(s)
        @else
            Filtros Activos
        @endif
    </span>
</div>
