@props(['request'])

@include('components.service-requests.modals.accept-modal', ['request' => $request])
@include('components.service-requests.modals.pause-modal', ['request' => $request])
@include('components.service-requests.modals.cancel-modal', ['request' => $request])
@include('components.service-requests.modals.close-modal', ['request' => $request])
@include('components.service-requests.modals.report-modal', ['request' => $request])
