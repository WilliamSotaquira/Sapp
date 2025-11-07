{{--
Configuración de alias para componentes de service-requests
--}}

@php
$serviceRequestComponents = [
// Layout
'service-requests.layout.breadcrumb' => 'service-requests/layout/breadcrumb',
'service-requests.layout.header' => 'service-requests/layout/header',
'service-requests.layout.scripts' => 'service-requests/layout/scripts',

// Form Fields
'service-requests.form.fields.basic-fields' => 'service-requests/form/fields/basic-fields',
'service-requests.form.fields.assignment-fields' => 'service-requests/form/fields/assignment-fields',
'service-requests.form.fields.service-family-filter' => 'service-requests/form/fields/service-family-filter',
'service-requests.form.fields.sub-service-select' => 'service-requests/form/fields/sub-service-select',
'service-requests.form.fields.web-routes' => 'service-requests/form/fields/web-routes',

// Form SLA
'service-requests.form.sla.sla-fields' => 'service-requests/form/sla/sla-fields',
'service-requests.form.sla.sla-timers' => 'service-requests/form/sla/sla-timers',

// Form Sections
'service-requests.form.sections.description' => 'service-requests/form/sections/description',
'service-requests.form.sections.evidences-section' => 'service-requests/form/sections/evidences-section',

// Display
'service-requests.display.service-details' => 'service-requests/display/service-details',
'service-requests.display.general-info' => 'service-requests/display/general-info',
'service-requests.display.history-timeline' => 'service-requests/display/history-timeline',
'service-requests.display.pause-info' => 'service-requests/display/pause-info',
'service-requests.display.resolution-notes' => 'service-requests/display/resolution-notes',
'service-requests.display.satisfaction-score' => 'service-requests/display/satisfaction-score',

// Modals
'service-requests.modals.sla-create' => 'service-requests/modals/sla-create',
'service-requests.modals.accept-modal' => 'service-requests/modals/accept-modal',
'service-requests.modals.cancel-modal' => 'service-requests/modals/cancel-modal',
'service-requests.modals.close-modal' => 'service-requests/modals/close-modal',
'service-requests.modals.pause-modal' => 'service-requests/modals/pause-modal',
'service-requests.modals.report-modal' => 'service-requests/modals/report-modal',
];
@endphp
