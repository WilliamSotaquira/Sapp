@props(['serviceRequests'])

<div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
    <div class="px-6 py-4">
        {{ $serviceRequests->links() }}
    </div>
</div>
