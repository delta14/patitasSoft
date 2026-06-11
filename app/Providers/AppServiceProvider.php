<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Services\Auth\JwtGuard;
use App\Services\Auth\JwtService;
use App\Models\User;

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
        // 1. Registrar el driver de autenticación 'jwt' personalizado
        Auth::extend('jwt', function ($app, $name, array $config) {
            return new JwtGuard(
                $app->make(JwtService::class),
                $app->make('request')
            );
        });

        // 2. Registrar el hook global de Gate para validación de roles y permisos
        Gate::before(function (User $user, string $ability) {
            // Cargar la relación de roles y permisos de forma eficiente si no se han cargado aún
            $user->loadMissing('roles.permissions');
            
            if ($user->hasRole('Super Admin')) {
                return true;
            }

            return $user->hasPermission($ability) ? true : null;
        });
    }
}
