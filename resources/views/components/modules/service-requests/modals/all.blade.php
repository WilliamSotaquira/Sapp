
@props(['serviceRequest'])

<x-service-requests.modals.accept-modal :serviceRequest="$serviceRequest" />
<x-service-requests.modals.pause-modal :serviceRequest="$serviceRequest" />
<x-service-requests.modals.cancel-modal :serviceRequest="$serviceRequest" />
<x-service-requests.modals.close-modal :serviceRequest="$serviceRequest" />
<x-service-requests.modals.report-modal :serviceRequest="$serviceRequest" />

