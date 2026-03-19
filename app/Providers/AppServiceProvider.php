<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        Gate::define('admin',    fn($user) => $user->isAdmin());
        Gate::define('clinical', fn($user) => $user->isDentist() || $user->isAdmin());
        Gate::define('billing',  fn($user) => $user->isReceptionist() || $user->isAdmin());
    }
}
