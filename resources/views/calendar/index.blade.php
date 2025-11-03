@extends('layouts.app')

@section('title', 'Calendario - SDM')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Calendario de Actividades</h1>
</div>

<div class="card">
    <div class="card-body">
        <div id="calendar"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #calendar {
        max-width: 100%;
        margin: 0 auto;
    }
    .fc-event {
        cursor: pointer;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: '{{ route("calendar.index") }}/events',
            eventClick: function(info) {
                if (info.event.url) {
                    window.open(info.event.url, '_self');
                    info.jsEvent.preventDefault();
                }
            },
            eventDidMount: function(info) {
                // Agregar tooltip con información adicional
                if (info.event.extendedProps.description) {
                    $(info.el).tooltip({
                        title: info.event.extendedProps.description,
                        placement: 'top',
                        trigger: 'hover',
                        container: 'body'
                    });
                }
            }
        });
        calendar.render();
    });
</script>
@endpush
