<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no">
    <link rel="shortcut icon" href="{{ asset(Storage::url('favicon.png')) }}" type="image/png">
    <title>{{ $title ? config('app.name') . " - $title" : config('app.name') }}</title>

    <link rel="stylesheet" href="{{ asset('assets/css/all.min.css') }}">
    @vite('resources/css/app.css')
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div id="top-bar" class="row position-fixed w-100">
            <div class="col-12 d-flex justify-content-between justify-content-sm-start align-items-center">
                <a href="{{ route('dashboard.index') }}" class="fs-4 me-sm-4 fw-bold ms-md-5 text-primary-emphasis text-decoration-none">
                    {{ config('app.name') }}
                </a>
                @auth()
                    <x-menu :$rol />
                @endauth
            </div>
        </div>

        <div id="main" @style('padding-top: 55px')>
            {{ $slot }}
        </div>
    </div>

    @vite('resources/js/app.js')
    {{ $scripts ?? '' }}
</body>
</html>
