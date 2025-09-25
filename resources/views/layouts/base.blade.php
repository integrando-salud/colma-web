<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="shortcut icon" href="{{ asset(Storage::url('favicon.png')) }}" type="image/png">
        <title>{{ config('app.name') }}</title>
    </head>
    <body class="bg-light">
        <div class="container-fluid">
            <div id="top-bar" class="row position-fixed w-100">
                <div class="col-12 d-flex justify-content-between justify-content-sm-start align-items-center">
                    <span class="fs-4 me-sm-4 fw-bold ms-md-5 text-primary-emphasis">{{ config('app.name') }}</span>
{{--                    <span class="fs-4 fw-semibold color-title text-primary-emphasis">Pacientes</span>--}}
                </div>
            </div>

            <div id="content">
                @yield('content')
            </div>
        </div>

        @vite('resources/js/app.js')
    </body>
</html>
