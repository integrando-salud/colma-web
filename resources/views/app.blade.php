<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="shortcut icon" href="{{ asset(Storage::url('favicon.png')) }}" type="image/png">
        <title inertia>{{ config('app.name') }}</title>

        <link rel="stylesheet" href="{{ asset('assets/font-awesome/css/all.min.css') }}">

        @routes()
        @vite('resources/js/app.js')
        @inertiaHead
    </head>
    <body class="bg-light">
        @inertia
    </body>
</html>
