<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Incident;
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
        Gate::before(function (User $user, string $ability, array $args = []) {
            if ($ability === 'delete') {
                if (isset($args[0]) && $args[0] instanceof Category) {
                    if ($args[0]->incidents()->count() > 0) {
                        return false;
                    }

                    return $user->isAdmin();
                }

                if (isset($args[0]) && $args[0] instanceof Incident) {
                    return $user->isAdmin() || $user->hasPermission('incidents.delete');
                }

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
