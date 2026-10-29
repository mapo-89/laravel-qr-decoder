<?php

declare(strict_types=1);

namespace Mapo89\QrDecoder\Tests;

use Illuminate\Support\ServiceProvider;
use Mapo89\QrDecoder\QrDecoder;
use Mapo89\QrDecoder\QrDecoderFacade;
use Mapo89\QrDecoder\QrDecoderServiceProvider;
use Orchestra\Testbench\TestCase;

final class QrDecoderServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [QrDecoderServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('qr-decoder.timeout', 23);
    }

    public function test_merges_defaults_without_overwriting_application_config(): void
    {
        $this->assertSame(23, config('qr-decoder.timeout'));
        $this->assertSame(300, config('qr-decoder.install_timeout'));
        $this->assertSame(base_path('python/qr-decoder'), config('qr-decoder.python_path'));
        $this->assertSame(PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3', config('qr-decoder.python_binary'));
    }

    public function test_resolves_the_same_decoder_through_the_class_alias_and_facade(): void
    {
        $decoder = $this->app->make(QrDecoder::class);

        $this->assertInstanceOf(QrDecoder::class, $decoder);
        $this->assertSame($decoder, $this->app->make(QrDecoder::class));
        $this->assertSame($decoder, $this->app->make('qr-decoder'));
        $this->assertSame($decoder, QrDecoderFacade::getFacadeRoot());
    }

    public function test_registers_the_python_installer(): void
    {
        $this->artisan('help', ['command_name' => 'qr-decoder:install'])
            ->expectsOutputToContain('Set up or update the Python environment for QR Decoder')
            ->assertExitCode(0);
    }

    public function test_publishes_config_with_the_package_and_legacy_tags(): void
    {
        foreach (['qr-decoder-config', 'config'] as $tag) {
            $paths = ServiceProvider::pathsToPublish(QrDecoderServiceProvider::class, $tag);

            $this->assertCount(1, $paths);
            $this->assertSame(config_path('qr-decoder.php'), reset($paths));
            $this->assertSame(realpath(dirname(__DIR__).'/config/qr-decoder.php'), realpath(array_key_first($paths)));
        }
    }
}
