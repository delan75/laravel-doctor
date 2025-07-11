<?php

namespace LaravelDoctor;

use Illuminate\Support\ServiceProvider;

/**
 * Laravel Doctor Service Provider
 * 
 * Registers the Laravel Doctor command and services
 */
class LaravelDoctorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the LaravelDoctor class
        $this->app->singleton(LaravelDoctor::class, function ($app) {
            return new LaravelDoctor();
        });

        // Register the command
        if ($this->app->runningInConsole()) {
            $this->commands([
                LaravelDoctorCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration file if needed
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/laravel-doctor.php' => config_path('laravel-doctor.php'),
            ], 'laravel-doctor-config');
        }
    }
}
