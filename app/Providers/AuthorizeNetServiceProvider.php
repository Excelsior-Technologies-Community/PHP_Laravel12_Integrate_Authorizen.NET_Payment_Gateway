<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AuthorizeNetService;

class AuthorizeNetServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(AuthorizeNetService::class, function ($app) {
            return new AuthorizeNetService();
        });
    }

    public function boot()
    {
        // Publish configuration file (optional)
        $this->publishes([
            __DIR__.'/../../config/authorize.php' => config_path('authorize.php'),
        ], 'authorize-net-config');
    }
}