<?php

declare(strict_types=1);

namespace Mapo89\QrDecoder;

use Mapo89\QrDecoder\Console\QrDecoderInstall;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class QrDecoderServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-qr-decoder')
            ->hasConfigFile()
            ->hasCommand(QrDecoderInstall::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(QrDecoder::class);
        $this->app->alias(QrDecoder::class, 'qr-decoder');
    }

    public function packageBooted(): void
    {
        if ($this->app->runningInConsole()) {
            // Preserve the original publish tag for existing applications.
            $this->publishes(static::pathsToPublish(static::class, 'qr-decoder-config'), 'config');
        }
    }
}
