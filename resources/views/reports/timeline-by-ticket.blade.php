@extends('layouts.app')

@section('title', 'Timeline por Número de Solicitud')

@section('breadcrumb')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ url('/dashboard') }}" class="text-blue-600 hover:text-blue-700">Dashboard</a>
            </li>
            <li class="inline-flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <a href="{{ route('reports.index') }}" class="text-blue-600 hover:text-blue-700">Informes</a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Timeline por Ticket</span>
                </div>
            </li>
        </ol>
    </nav>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Descargar Timeline por Número de Solicitud</h1>
            <p class="text-gray-600">Ingresa el número de ticket para generar el reporte de línea de tiempo</p>
        </div>

        <form id="ticketForm" method="GET" action="#" class="space-y-6">
            @csrf

            <!-- Campo para número de ticket -->
            <div>
                <label for="ticket_number" class="block text-sm font-medium text-gray-700 mb-2">
                    Número de Solicitud (Ticket)
                </label>
                <input
                    type="text"
                    id="ticket_number"
                    name="ticket_number"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    placeholder="Ej: INF-PU-M-251115-001 o COM-RE-U-251113-001"
                    required
                    autofocus
                    value="{{ old('ticket_number', request('ticket_number')) }}"
                >
                <p class="mt-1 text-sm text-gray-500">
                    Ingresa el número completo de la solicitud
                </p>
            </div>

            <!-- Selector de formato -->
            <div>
                <label for="format" class="block text-sm font-medium text-gray-700 mb-2">
                    Formato de Descarga
                </label>
                <select
                    id="format"
                    name="format"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                >
                    <option value="pdf" {{ request('format') == 'pdf' ? 'selected' : '' }}>PDF (Recomendado)</option>
                    <option value="excel" {{ request('format') == 'excel' ? 'selected' : '' }}>Excel (.xlsx - con respaldo CSV)</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">
                    Si Excel falla, se generará automáticamente un archivo CSV
                </p>
            </div>

            <!-- Botones de acción -->
            <div class="flex flex-col sm:flex-row gap-4 pt-4">
                <button
                    type="button"
                    id="searchButton"
                    class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition font-semibold flex items-center justify-center"
                >
                    <i class="fas fa-search mr-2"></i>
                    Buscar y Descargar
                </button>

                <a
                    href="{{ route('reports.index') }}"
                    class="flex-1 bg-gray-500 text-white py-3 px-6 rounded-lg hover:bg-gray-600 transition font-semibold flex items-center justify-center"
                >
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver a Informes
                </a>
            </div>
        </form>

        <!-- Mensajes de resultado -->
        <div id="resultMessage" class="mt-6 hidden"></div>

        <!-- Mostrar mensajes de sesión -->
        @if(session('info'))
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    <span class="text-blue-800 font-semibold">Información</span>
                </div>
                <p class="text-blue-700 mt-1">{{ session('info') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                    <span class="text-red-800 font-semibold">Error</span>
                </div>
                <p class="text-red-700 mt-1">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Mostrar errores si los hay -->
        @if($errors->any())
            <div class="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                    <span class="text-red-800 font-semibold">Error</span>
                </div>
                <ul class="text-red-700 mt-2 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Información de ayuda -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-semibold text-blue-800 mb-2 flex items-center">
                <i class="fas fa-info-circle mr-2"></i>
                ¿Cómo encontrar el número de ticket?
            </h3>
            <ul class="text-sm text-blue-700 space-y-1">
                <li>• Revisa el correo de confirmación de tu solicitud</li>
                <li>• Consulta en el listado de "Solicitudes de Servicio"</li>
                <li>• El formato puede ser: <code>INF-PU-M-251115-001</code>, <code>COM-RE-U-251113-001</code> o <code>SUP-MI-M-251113-005</code></li>
                <li>• También puedes usar el ID numérico de la solicitud</li>
            </ul>

            @if(isset($sampleTickets) && $sampleTickets->count() > 0)
            <div class="mt-3 p-3 bg-blue-100 rounded">
                <p class="text-xs text-blue-600 font-medium">💡 Algunos tickets disponibles para pruebas:</p>
                <ul class="text-xs text-blue-600 mt-1">
                    @foreach($sampleTickets as $ticket)
                    <li>• <code class="cursor-pointer hover:bg-blue-200 px-1 rounded" onclick="document.getElementById('ticket_number').value='{{ $ticket->ticket_number }}'">{{ $ticket->ticket_number }}</code> - {{ Str::limit($ticket->title, 40) }}</li>
                    @endforeach
                </ul>
                <p class="text-xs text-blue-500 mt-1 italic">💡 Haz clic en un ticket para copiarlo al campo de búsqueda</p>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    const searchButton = document.getElementById('searchButton');
    const ticketInput = document.getElementById('ticket_number');
    const formatSelect = document.getElementById('format');
    const resultDiv = document.getElementById('resultMessage');

    // Función para buscar y descargar
    function searchTicket() {
        const ticketNumber = ticketInput.value.trim();
        const format = formatSelect.value;

        if (!ticketNumber) {
            showResult('Por favor ingresa un número de ticket', 'error');
            return;
        }

        if (!format || (format !== 'pdf' && format !== 'excel')) {
            showResult('Por favor selecciona un formato válido', 'error');
            return;
        }

        // Mostrar loading
        showResult('<div class="flex items-center justify-center"><i class="fas fa-spinner fa-spin mr-2"></i> Buscando solicitud...</div>', 'loading');

        // Debug: mostrar lo que se va a enviar
        console.log('Enviando:', { ticket_number: ticketNumber, format: format });

        // Crear un formulario temporal para enviar la petición POST
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('reports.timeline.download-by-ticket') }}';
        form.target = '_blank';

        // Añadir token CSRF
        const csrfField = document.createElement('input');
        csrfField.type = 'hidden';
        csrfField.name = '_token';
        csrfField.value = '{{ csrf_token() }}';
        form.appendChild(csrfField);

        // Añadir número de ticket
        const ticketField = document.createElement('input');
        ticketField.type = 'hidden';
        ticketField.name = 'ticket_number';
        ticketField.value = ticketNumber;
        form.appendChild(ticketField);

        // Añadir formato (aunque no se use en el controlador actual)
        const formatField = document.createElement('input');
        formatField.type = 'hidden';
        formatField.name = 'format';
        formatField.value = format;
        form.appendChild(formatField);

        // Enviar formulario
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        // Mostrar mensaje de éxito después de un breve delay
        setTimeout(() => {
            showResult(`
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        <span class="text-green-800 font-semibold">Descarga iniciada</span>
                    </div>
                    <p class="text-green-700 mt-1">Timeline de <strong>${ticketNumber}</strong> se está procesando para descarga en formato ${format.toUpperCase()}</p>
                    <p class="text-green-600 text-sm mt-2">La descarga iniciará automáticamente en una nueva ventana</p>
                </div>
            `, 'success');
        }, 1000);
    }

    // Función para mostrar resultados
    function showResult(message, type) {
        resultDiv.innerHTML = message;
        resultDiv.className = 'mt-6';

        switch(type) {
            case 'error':
                resultDiv.classList.add('bg-red-50', 'border', 'border-red-200', 'rounded-lg', 'p-4');
                break;
            case 'success':
                resultDiv.classList.add('bg-green-50', 'border', 'border-green-200', 'rounded-lg', 'p-4');
                break;
            case 'loading':
                resultDiv.classList.add('bg-blue-50', 'border', 'border-blue-200', 'rounded-lg', 'p-4');
                break;
        }

        resultDiv.classList.remove('hidden');
    }

    // Asignar evento al botón
    searchButton.addEventListener('click', searchTicket);

    // Permitir buscar con Enter
    ticketInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchTicket();
        }
    });

    // Mostrar mensaje si hay parámetros en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const ticketFromUrl = urlParams.get('ticket_number');
    if (ticketFromUrl) {
        ticketInput.value = ticketFromUrl;
    }
});
</script>
@endsection
