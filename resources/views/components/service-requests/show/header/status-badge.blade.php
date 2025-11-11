<!-- resources/views/components/service-requests/show/header/criticality-indicator.blade.php -->
@props(['criticality'])
<x-service-requests.badge type="criticality" :value="$criticality" />
