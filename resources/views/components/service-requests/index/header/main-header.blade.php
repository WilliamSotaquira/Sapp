<!-- Header Principal -->
<div class="bg-gradient-to-r from-blue-600 to-indigo-700 shadow-xl rounded-2xl overflow-hidden mb-8">
    <div class="px-8 py-6 text-white">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-4 mb-4 lg:mb-0">
                <div class="bg-white/20 p-3 rounded-2xl backdrop-blur-sm">
                    <i class="fas fa-tasks text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Solicitudes de Servicio</h1>
                    <p class="text-blue-100 opacity-90 mt-1">Gestión y seguimiento de todas las solicitudes del sistema</p>
                </div>
            </div>
            <x-service-requests.index.header.filters-badge />
        </div>
    </div>
</div>
