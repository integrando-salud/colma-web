<?php

namespace App\Providers;

use App\Http\Middleware\Authenticate;
use App\Models\Paciente;
use App\Models\User;
use App\Policies\PacientePolicy;
use App\Policies\UserPolicy;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::aliasMiddleware('auth', Authenticate::class);
        Gate::policy(Paciente::class, PacientePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        JsonResource::withoutWrapping();
    }
}
