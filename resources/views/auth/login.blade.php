@extends('layouts.base')

@section('content')
    <div class="row">
        <div class="col-12 vh-100 d-flex flex-column justify-content-center align-items-center" style="padding-top: 55px;">
            <div class="w-100 mb-1" style="max-width: 300px;">
                <div class="mb-5 text-center">
                    <img src="{{ asset(Storage::url('logo.webp')) }}" class="img-fluid" alt="{{ config('app.name') }}">
                </div>
                <div>
                    <form action="{{ route('dashboard.login') }}" method="post">
                        @csrf
                        @error('login')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        <div class="mb-3">
                            <label for="email" class="form-label">Usuario:</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" @class(['form-control', 'is-invalid' => $errors->has('email')])>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Contraseña:</label>
                            <input type="password" id="password" name="password" @class(['form-control', 'is-invalid' => $errors->has('password')])>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 text-white mb-3">Iniciar sesión</button>
                    </form>
                </div>
            </div>

            <a href="#" class="small">Recuperar contraseña</a>
        </div>
    </div>
@endsection
