<a href="{{ route('service-requests.create') }}" class="block bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg shadow hover:shadow-md transition-shadow">
    <div class="p-2.5 md:p-4 lg:p-5">
        <!-- Diseño horizontal ultra compacto en móvil, vertical centrado en desktop -->
        <div class="flex lg:flex-col items-center text-center gap-2.5 lg:gap-2">
            <div class="flex-shrink-0 bg-white/10 rounded-lg p-2 lg:p-2">
                <i class="fas fa-plus-circle text-lg md:text-xl lg:text-xl text-white"></i>
            </div>
            <div class="flex-1 lg:flex-initial text-white text-left lg:text-center">
                <h3 class="font-bold text-xs md:text-sm lg:text-lg leading-tight">Nueva Solicitud</h3>
                <p class="text-blue-100 text-[10px] md:text-xs lg:hidden">Crear solicitud</p>
            </div>
            <div class="flex-shrink-0 lg:mt-1">
                <span class="bg-white text-blue-600 px-2.5 py-1 md:px-3 md:py-1.5 lg:px-3 lg:py-1.5 rounded-md font-semibold inline-flex items-center justify-center text-[10px] md:text-xs lg:text-xs">
                    <i class="fas fa-plus mr-1"></i>
                    Crear
                </span>
            </div>
        </div>
    </div>
</a>
