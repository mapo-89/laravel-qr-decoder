<?php

namespace Mapo89\QrDecoder;

use Illuminate\Support\ServiceProvider;
use Mapo89\QrDecoder\Console\QrDecoderInstall;

class QrDecoderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'qr-decoder');
        //$this->loadViewsFrom(__DIR__.'/../resources/views', 'qr-decoder');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        //$this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/qr-decoder.php' => config_path('qr-decoder.php'),
            ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/qr-decoder'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/laravel-qr-decoder'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/laravel-qr-decoder'),
            ], 'lang');*/

            // Registering package commands.
             $this->commands([
                QrDecoderInstall::class,
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/qr-decoder.php', 'qr-decoder');

        // Register the main class to use with the facade
        $this->app->singleton('qr-decoder', function () {
            return new QrDecoder;
        });
    }
}
