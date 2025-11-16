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
    </div>
</div>
