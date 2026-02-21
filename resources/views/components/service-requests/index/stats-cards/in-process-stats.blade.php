@props(['count' => 0])

<a href="{{ route('service-requests.index', ['in_process' => 1]) }}" class="block bg-white rounded-lg shadow border-l-4 border-violet-500 overflow-hidden hover:shadow-md transition-shadow focus:outline-none focus:ring-2 focus:ring-violet-300">
    <div class="bg-violet-50/50 p-2.5 md:p-4 lg:p-5">
        <div class="flex lg:flex-col items-center text-center gap-2.5 lg:gap-2">
            <div class="flex-shrink-0 bg-violet-100 rounded-lg p-1.5 lg:p-2">
                <i class="fas fa-spinner text-sm md:text-base lg:text-xl text-violet-600"></i>
            </div>
            <div class="flex-1 lg:flex-initial min-w-0 text-left lg:text-center">
                <h3 class="font-bold text-xs md:text-sm lg:text-lg leading-tight truncate text-gray-800">En proceso</h3>
                <p class="text-[10px] md:text-xs text-gray-600 lg:hidden inline-flex items-center gap-1">
                    <i class="fas fa-cog text-[9px] text-violet-600"></i>Ejecutandose
                </p>
            </div>
            <div class="flex-shrink-0 text-right lg:text-center">
                <div class="text-xl md:text-2xl lg:text-3xl font-bold text-violet-600 leading-tight">{{ $count }}</div>
                <p class="text-[10px] md:text-xs text-gray-600 hidden lg:inline-flex items-center gap-1 mt-0.5">
                    <i class="fas fa-cog text-[10px] text-violet-600"></i>Ejecutandose
                </p>
            </div>
        </div>
    </div>
</a>
