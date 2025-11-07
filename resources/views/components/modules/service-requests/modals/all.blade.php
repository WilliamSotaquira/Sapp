@props(['serviceRequest'])

@include('components.service-requests.modals.accept-modal', ['request' => $serviceRequest])
@include('components.service-requests.modals.pause-modal', ['request' => $serviceRequest])
@include('components.service-requests.modals.cancel-modal', ['request' => $serviceRequest])
@include('components.service-requests.modals.close-modal', ['request' => $serviceRequest])
@include('components.service-requests.modals.report-modal', ['request' => $serviceRequest])
