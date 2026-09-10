<?php

namespace App\Providers;

use App\Models\User;
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
        // Regla aeronáutica: La eliminación de incidencias está estrictamente prohibida para todos
        Gate::before(function (User $user, string $ability) {
            if ($ability === 'delete') {
                return false;
            }

            if ($user->isAdmin()) {
                return true;
            }
        });

        // Gates institucionales para vistas y navegación
        Gate::define('access-apoc', fn (User $user) => $user->isApoc() || $user->isAdmin());
        Gate::define('access-gts', fn (User $user) => $user->isGts() || $user->isAdmin());
        Gate::define('access-admin', fn (User $user) => $user->isAdmin());
    }
}

