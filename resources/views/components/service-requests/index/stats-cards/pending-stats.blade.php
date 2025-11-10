@props(['count' => 0])

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-yellow-50 to-amber-50 px-6 py-4 border-b border-yellow-100">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <i class="fas fa-clock text-yellow-600 mr-3"></i>
            Pendientes
        </h3>
    </div>
    <div class="p-6 text-center">
        <div class="text-3xl font-bold text-gray-800 mb-2">{{ $count }}</div>
        <p class="text-sm text-gray-600">Por atender</p>
    </div>
</div>
