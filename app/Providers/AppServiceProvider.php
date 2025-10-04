<?php

namespace App\Providers;

use App\Http\Middleware\Authenticate;
use App\View\Composers\RoleComposer;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
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

        JsonResource::withoutWrapping();
    }
}
