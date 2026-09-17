<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator; 
use Illuminate\Support\Facades\Gate;

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
        Paginator::useBootstrapFive();

        // Implicitly grant "admin" and "super_admin" roles all permissions, and ensure "manager" can edit/reverse transactions
        Gate::before(function ($user, $ability) {
            if ($user->hasAnyRole(['super_admin', 'admin'])) {
                return true;
            }
            if ($user->hasRole('manager') && in_array($ability, ['reverse transactions', 'delete', 'update', 'manage collections'])) {
                return true;
            }
            return null;
        });
    }
}
