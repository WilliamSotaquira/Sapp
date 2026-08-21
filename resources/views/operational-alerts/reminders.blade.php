@extends('layouts.app')

@section('title', 'Recordatorios')

@section('content')
<div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    {{-- Acciones --}}
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-600">Recordatorios programados y activos</p>
        <a href="{{ route('operational-alerts.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-1"></i>Alertas operativas
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 flex items-center gap-2">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    {{-- Crear recordatorio general --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
        <h2 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
            <i class="fas fa-plus-circle text-red-500"></i>
            Nuevo recordatorio
        </h2>
        <form action="{{ route('operational-alerts.reminder.store') }}" method="POST" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Nota</label>
                <input type="text" name="reminder_note" required minlength="3" maxlength="500"
                       class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-200 focus:border-red-400"
                       placeholder="Ej: Enviar informe mensual al supervisor">
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Fecha</label>
                <input type="date" name="reminder_date" required min="{{ now()->format('Y-m-d') }}"
                       value="{{ now()->addDay()->format('Y-m-d') }}"
                       class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-200 focus:border-red-400">
            </div>
            <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-bell mr-1"></i>Programar
            </button>
        </form>
    </div>

    {{-- Recordatorios activos (ya llegó su fecha) --}}
    @if($active->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                <i class="fas fa-bell text-red-500"></i>
                Activos ({{ $active->count() }})
            </h2>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($active as $reminder)
                <div class="px-5 py-3 flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900">{{ $reminder->metadata['note'] ?? $reminder->message }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $reminder->alert_at->format('d/m/Y') }}
                            @if($reminder->alertable_type === \App\Models\ServiceRequest::class)
                                — <a href="{{ route('service-requests.show', $reminder->alertable_id) }}" class="text-red-600 hover:underline">{{ $reminder->metadata['ticket'] ?? 'Ver solicitud' }}</a>
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-1">
                        <form action="{{ route('operational-alerts.resolve', $reminder) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="p-1.5 text-gray-300 hover:text-green-600 transition" title="Marcar como hecho">
                                <i class="fas fa-check text-xs"></i>
                            </button>
                        </form>
                        <form action="{{ route('operational-alerts.reminder.destroy', $reminder) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 text-gray-300 hover:text-red-500 transition" title="Eliminar">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Recordatorios programados (futuros) --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                <i class="fas fa-clock text-blue-500"></i>
                Programados ({{ $scheduled->count() }})
            </h2>
        </div>
        @if($scheduled->isNotEmpty())
            <div class="divide-y divide-gray-100">
                @foreach($scheduled as $reminder)
                    <div class="px-5 py-3 flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-blue-400 flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900">{{ $reminder->metadata['note'] ?? $reminder->message }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                <i class="fas fa-calendar mr-0.5"></i>{{ $reminder->alert_at->format('d/m/Y') }}
                                ({{ $reminder->alert_at->diffForHumans() }})
                                @if($reminder->alertable_type === \App\Models\ServiceRequest::class)
                                    — <a href="{{ route('service-requests.show', $reminder->alertable_id) }}" class="text-red-600 hover:underline">{{ $reminder->metadata['ticket'] ?? 'Ver solicitud' }}</a>
                                @endif
                            </p>
                        </div>
                        <form action="{{ route('operational-alerts.reminder.destroy', $reminder) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 text-gray-300 hover:text-red-500 transition" title="Cancelar recordatorio">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <div class="px-5 py-8 text-center">
                <p class="text-sm text-gray-400">No hay recordatorios programados.</p>
            </div>
        @endif
    </div>
</div>
@endsection
