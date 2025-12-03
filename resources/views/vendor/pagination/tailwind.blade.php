@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navegación de paginación" class="flex items-center justify-between text-sm text-slate-700">
        <div class="flex justify-between flex-1 sm:hidden gap-2">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-3 py-2 font-medium bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed rounded-lg">
                    &laquo; Anterior
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-3 py-2 font-medium bg-gradient-to-r from-sky-50 via-blue-50 to-indigo-50 text-blue-700 border border-blue-200 rounded-lg hover:border-blue-300 hover:text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 transition">
                    &laquo; Anterior
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center px-3 py-2 font-medium bg-gradient-to-r from-indigo-50 via-blue-50 to-sky-50 text-blue-700 border border-blue-200 rounded-lg hover:border-blue-300 hover:text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 transition">
                    Siguiente &raquo;
                </a>
            @else
                <span class="relative inline-flex items-center px-3 py-2 font-medium bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed rounded-lg">
                    Siguiente &raquo;
                </span>
            @endif
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="leading-5 text-xs sm:text-sm text-slate-600">
                    Mostrando
                    @if ($paginator->firstItem())
                        <span class="font-semibold text-slate-900">{{ $paginator->firstItem() }}</span>
                        a
                        <span class="font-semibold text-slate-900">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    de
                    <span class="font-semibold text-slate-900">{{ $paginator->total() }}</span>
                    resultados
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex rtl:flex-row-reverse rounded-full shadow-sm overflow-hidden border border-blue-100 bg-white/80 backdrop-blur">
                    {{-- Link a la página anterior --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="Página anterior">
                            <span class="relative inline-flex items-center px-3 py-2 text-slate-400 bg-slate-50 cursor-not-allowed" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-3 py-2 text-blue-700 hover:text-blue-900 focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 bg-gradient-to-r from-sky-50 via-blue-50 to-indigo-50 border-r border-blue-100" aria-label="Página anterior">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif

                    {{-- Elementos de paginación --}}
                    @foreach ($elements as $element)
                        {{-- Separador "..." --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative inline-flex items-center px-3 py-2 text-slate-500 bg-white">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Conjunto de enlaces --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="relative inline-flex items-center px-3 py-2 text-white bg-blue-600 font-semibold shadow-inner">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="relative inline-flex items-center px-3 py-2 text-blue-700 bg-white hover:bg-blue-50 hover:text-blue-900 focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 border-l border-blue-100" aria-label="Ir a la página {{ $page }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Link a la página siguiente --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-3 py-2 text-blue-700 hover:text-blue-900 focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 bg-gradient-to-r from-indigo-50 via-blue-50 to-sky-50 border-l border-blue-100" aria-label="Página siguiente">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="Página siguiente">
                            <span class="relative inline-flex items-center px-3 py-2 text-slate-400 bg-slate-50 cursor-not-allowed" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
