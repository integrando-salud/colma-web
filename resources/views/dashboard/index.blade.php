<h1>Dashboard {{ config('app.name') }}</h1>
<p>
    Usuario: {{ auth()->user()->name }}
</p>
<p>
    <a href="{{ route('dashboard.logout') }}">Cerrar sesión</a>
</p>
