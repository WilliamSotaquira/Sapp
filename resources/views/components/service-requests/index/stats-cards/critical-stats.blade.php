@props(['count' => 0])

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-red-50 to-pink-50 px-6 py-4 border-b border-red-100">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
            Críticas
        </h3>
    </div>
    <div class="p-6 text-center">
        <div class="text-3xl font-bold text-gray-800 mb-2">{{ $count }}</div>
        <p class="text-sm text-gray-600">Alta prioridad</p>
    </div>
</div>
