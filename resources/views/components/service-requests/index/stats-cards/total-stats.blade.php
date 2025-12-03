@props(['count' => 0])

<a href="{{ route('service-requests.index') }}" class="block bg-white rounded-lg shadow border-l-4 border-slate-500 overflow-hidden hover:shadow-md transition-shadow focus:outline-none focus:ring-2 focus:ring-slate-300">
    <div class="bg-slate-50/50 p-2.5 md:p-4 lg:p-5">
        <div class="flex lg:flex-col items-center text-center gap-2.5 lg:gap-2">
            <div class="flex-shrink-0 bg-slate-100 rounded-lg p-1.5 lg:p-2">
                <i class="fas fa-inbox text-slate-600 text-sm md:text-base lg:text-xl"></i>
            </div>
            <div class="flex-1 lg:flex-initial min-w-0 text-left lg:text-center">
                <h3 class="text-xs md:text-sm lg:text-lg font-bold text-gray-800 leading-tight truncate">Total</h3>
                <p class="text-[10px] md:text-xs text-gray-600 lg:hidden">En sistema</p>
            </div>
            <div class="flex-shrink-0 text-right lg:text-center">
                <div class="text-xl md:text-2xl lg:text-3xl font-bold text-slate-700 leading-tight">{{ $count }}</div>
                <p class="text-[10px] md:text-xs text-gray-600 hidden lg:block mt-0.5">En sistema</p>
            </div>
        </div>
    </div>
</a>
