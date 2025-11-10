@props(['serviceRequests'])

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-blue-100">
        <h3 class="text-lg font-bold text-gray-800 flex items-center justify-between">
            <span class="flex items-center">
                <i class="fas fa-list text-blue-600 mr-3"></i>
                Lista de Solicitudes
            </span>
            <span class="text-sm font-normal text-blue-600 bg-blue-100 px-3 py-1 rounded-full">
                {{ $serviceRequests->total() }} resultados
            </span>
        </h3>
    </div>
    <div class="p-6">
        @if($serviceRequests->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <x-service-requests.index.content.table-header />
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($serviceRequests as $request)
                    <x-service-requests.index.content.table-row :request="$request" />
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <x-service-requests.index.content.empty-state />
        @endif
    </div>
</div>
