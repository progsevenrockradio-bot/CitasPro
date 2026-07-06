<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('view-pro-appointments', function ($user) {
            if ($user instanceof \App\Models\User) {
                return true;
            }
            return $user->type === 'general' || in_array($user->rol, ['dueño', 'admin']);
        });

        Gate::define('view-medical-appointments', function ($user) {
            if ($user instanceof \App\Models\User) {
                return true;
            }
            return $user->type === 'medical' || in_array($user->rol, ['dueño', 'admin']);
        });

        Gate::define('view-dental-appointments', function ($user) {
            if ($user instanceof \App\Models\User) {
                return true;
            }
            return $user->type === 'dental' || in_array($user->rol, ['dueño', 'admin']);
        });
    }
}
