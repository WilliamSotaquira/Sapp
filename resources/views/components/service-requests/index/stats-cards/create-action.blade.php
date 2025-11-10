<div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg overflow-hidden">
    <div class="p-6 text-center text-white h-full flex flex-col justify-center">
        <div class="mb-4">
            <i class="fas fa-plus-circle text-3xl text-white/80"></i>
        </div>
        <h3 class="font-bold text-lg mb-2">Nueva Solicitud</h3>
        <p class="text-blue-100 text-sm mb-4">Crear una nueva solicitud de servicio</p>
        <a href="{{ route('service-requests.create') }}"
           class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-50 transition duration-200 font-semibold inline-flex items-center justify-center">
            <i class="fas fa-plus mr-2"></i>
            Crear
        </a>
    </div>
</div>
