@if(empty($selectedFamilyIds))
    <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-4 text-sm text-blue-800">
        Selecciona al menos una familia para visualizar las solicitudes del corte.
    </div>
@else
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Solicitudes asociadas</h3>
            <p class="mt-1 text-xs text-blue-700">
                Filtro activo por {{ count($selectedFamilyIds) }} familia{{ count($selectedFamilyIds) !== 1 ? 's' : '' }}.
            </p>
        </div>
        <span class="text-sm text-gray-600">Total: <span class="font-semibold">{{ $serviceRequests->total() }}</span></span>
    </div>
    @if(!empty($selectedFamilyLabels) && $selectedFamilyLabels->count() > 0)
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach($selectedFamilyLabels as $label)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-xs font-medium">
                    {{ $label }}
                </span>
            @endforeach
        </div>
    @endif

    <div class="overflow-hidden rounded-lg border border-gray-200" role="region" aria-label="Tabla de solicitudes asociadas del corte">
        <table class="w-full table-fixed divide-y divide-gray-200">
            <caption class="sr-only">
                Listado de solicitudes asociadas, ordenado por estado activo primero y luego por fecha de creación más reciente.
            </caption>
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="w-[14%] px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">Ticket</th>
                    <th scope="col" class="w-[36%] px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Título</th>
                    <th scope="col" class="w-[24%] px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Familia</th>
                    <th scope="col" class="w-[10%] px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">Estado</th>
                    <th scope="col" class="w-[10%] px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">Creada</th>
                    <th scope="col" class="w-[8%] px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($serviceRequests as $sr)
                    @php
                        $family = $sr->subService?->service?->family;
                        $familyName = $family?->name ?? 'Sin Familia';
                        $contractNumber = $family?->contract?->number;
                        $familyLabel = $contractNumber ? ($contractNumber . ' - ' . $familyName) : $familyName;

                        $statusMap = [
                            'PENDIENTE' => ['label' => 'Pendiente', 'classes' => 'bg-amber-100 text-amber-800 border-amber-200'],
                            'ACEPTADA' => ['label' => 'Aceptada', 'classes' => 'bg-blue-100 text-blue-800 border-blue-200'],
                            'EN_PROCESO' => ['label' => 'En proceso', 'classes' => 'bg-indigo-100 text-indigo-800 border-indigo-200'],
                            'PAUSADA' => ['label' => 'Pausada', 'classes' => 'bg-orange-100 text-orange-800 border-orange-200'],
                            'RESUELTA' => ['label' => 'Resuelta', 'classes' => 'bg-emerald-100 text-emerald-800 border-emerald-200'],
                            'CERRADA' => ['label' => 'Cerrada', 'classes' => 'bg-gray-100 text-gray-800 border-gray-200'],
                            'CANCELADA' => ['label' => 'Cancelada', 'classes' => 'bg-rose-100 text-rose-800 border-rose-200'],
                            'RECHAZADA' => ['label' => 'Rechazada', 'classes' => 'bg-red-100 text-red-800 border-red-200'],
                        ];
                        $statusData = $statusMap[$sr->status] ?? ['label' => $sr->status, 'classes' => 'bg-slate-100 text-slate-700 border-slate-200'];
                    @endphp
                    <tr class="align-top hover:bg-gray-50">
                        <th scope="row" class="w-[14%] px-4 py-3 text-sm font-semibold text-gray-900 whitespace-nowrap">
                            <a href="{{ route('service-requests.show', $sr) }}" class="inline-block whitespace-nowrap hover:text-blue-700 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500 rounded" aria-label="Ver solicitud {{ $sr->ticket_number }}">
                                {{ $sr->ticket_number }}
                            </a>
                        </th>
                        <td class="w-[36%] px-4 py-3 text-sm text-gray-700">
                            <span class="block truncate" title="{{ $sr->title }}">{{ $sr->title }}</span>
                        </td>
                        <td class="w-[24%] px-4 py-3 text-sm text-gray-700">
                            <span class="block truncate" title="{{ $familyLabel }}">{{ $familyLabel }}</span>
                        </td>
                        <td class="w-[10%] px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusData['classes'] }}">
                                {{ $statusData['label'] }}
                            </span>
                        </td>
                        <td class="w-[10%] px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                            @if($sr->created_at)
                                <time datetime="{{ $sr->created_at->toIso8601String() }}">{{ $sr->created_at->format('Y-m-d H:i') }}</time>
                            @else
                                <span class="text-gray-400">Sin fecha</span>
                            @endif
                        </td>
                        <td class="w-[8%] px-4 py-3 text-sm whitespace-nowrap">
                            <form method="POST" action="{{ route('reports.cuts.requests.remove', [$cut, $sr]) }}" onsubmit="return confirm('¿Remover esta solicitud del corte?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center justify-center px-2.5 py-1.5 rounded-lg border border-red-200 text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1" aria-label="Quitar solicitud {{ $sr->ticket_number }} del corte">
                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                    <span class="sr-only">Quitar</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">
                            No hay solicitudes asociadas a este corte para las familias seleccionadas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $serviceRequests->links() }}</div>
@endif
