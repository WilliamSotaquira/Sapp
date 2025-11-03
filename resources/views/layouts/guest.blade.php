<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

<!-- Estilos temporales para la página de login -->
<style>
    /* Reset y fuentes */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Figtree', sans-serif;
        background-color: #f3f4f6;
        color: #1f2937;
        line-height: 1.5;
        -webkit-font-smoothing: antialiased;
    }

    /* Estilos del layout */
    .min-h-screen {
        min-height: 100vh;
    }
    .flex {
        display: flex;
    }
    .flex-col {
        flex-direction: column;
    }
    .sm\:justify-center {
        justify-content: center;
    }
    .items-center {
        align-items: center;
    }
    .pt-6 {
        padding-top: 1.5rem;
    }
    .sm\:pt-0 {
        padding-top: 0;
    }
    .bg-gray-100 {
        background-color: #f3f4f6;
    }

    /* Estilos de la tarjeta */
    .w-full {
        width: 100%;
    }
    .sm\:max-w-md {
        max-width: 28rem;
    }
    .mt-6 {
        margin-top: 1.5rem;
    }
    .px-6 {
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }
    .py-4 {
        padding-top: 1rem;
        padding-bottom: 1rem;
    }
    .bg-white {
        background-color: white;
    }
    .shadow-md {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    .overflow-hidden {
        overflow: hidden;
    }
    .sm\:rounded-lg {
        border-radius: 0.5rem;
    }

    /* Estilos de texto */
    .font-sans {
        font-family: ui-sans-serif, system-ui, sans-serif;
    }
    .text-gray-900 {
        color: #1f2937;
    }
    .antialiased {
        -webkit-font-smoothing: antialiased;
    }

    /* Estilos de formulario */
    input[type="email"],
    input[type="password"],
    input[type="text"] {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        font-size: 1rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    input[type="email"]:focus,
    input[type="password"]:focus,
    input[type="text"]:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* Estilos de botón */
    button[type="submit"] {
        width: 100%;
        background-color: #3b82f6;
        color: white;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.375rem;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.15s ease-in-out;
    }

    button[type="submit"]:hover {
        background-color: #2563eb;
    }

    /* Estilos de enlaces */
    a {
        color: #3b82f6;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }

    /* Checkbox remember me */
    input[type="checkbox"] {
        margin-right: 0.5rem;
    }

    .block {
        display: block;
    }

    .font-medium {
        font-weight: 500;
    }

    .text-sm {
        font-size: 0.875rem;
    }

    .text-gray-600 {
        color: #6b7280;
    }

    .mt-4 {
        margin-top: 1rem;
    }

    .flex {
        display: flex;
    }

    .items-center {
        align-items: center;
    }

    .justify-between {
        justify-content: space-between;
    }

    /* Responsive */
    @media (min-width: 640px) {
        .sm\:justify-center {
            justify-content: center;
        }
        .sm\:pt-0 {
            padding-top: 0;
        } .sm\:rounded-lg {
            border-radius: 0.5rem;
        }
    }
</style>
</head>

<body class="font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div>
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            {{ $slot }}
        </div>
    </div>
</body>

</html>
