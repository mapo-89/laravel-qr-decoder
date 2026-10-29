<?php

declare(strict_types=1);

namespace Mapo89\QrDecoder\Tests;

use Mapo89\QrDecoder\PythonEnvironment;
use Mapo89\QrDecoder\QrDecoderServiceProvider;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Process\Process;

final class QrDecoderInstallTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [QrDecoderServiceProvider::class];
    }

    private function environment(bool $existing, array $scripts): void
    {
        $environment = $this->createMock(PythonEnvironment::class);
        $environment->method('python')->willReturn($existing ? PHP_BINARY : __DIR__.'/missing-python');
        $environment->method('directory')->willReturn('/test-environment/venv');
        $environment->expects($this->exactly(count($scripts)))->method('process')->willReturnCallback(
            function (array $command, float $timeout) use (&$scripts): Process {
                $this->assertSame(300.0, $timeout);
                $script = array_shift($scripts);
                if (($command[2] ?? null) === 'venv') {
                    $this->assertSame(['-m', 'venv', '/test-environment/venv'], array_slice($command, 1));
                } else {
                    $this->assertSame(['-m', 'pip', 'install'], array_slice($command, 1, 3));
                    $this->assertContains('--only-binary=:all:', $command);
                    $this->assertSame(realpath(__DIR__.'/../python/requirements.txt'), realpath(end($command)));
                }

                return new Process([PHP_BINARY, '-r', $script], null, null, null, $timeout);
            },
        );
        $this->app->instance(PythonEnvironment::class, $environment);
    }

    public function test_creates_an_environment_and_installs_dependencies(): void
    {
        $this->environment(false, ['exit(0);', 'echo "Dependencies installed";']);
        $this->artisan('qr-decoder:install')->expectsOutputToContain('QR decoder environment is ready.')->assertExitCode(0);
    }

    public function test_repeated_installation_reuses_an_existing_environment(): void
    {
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->environment(true, ['exit(0);']);
            $this->artisan('qr-decoder:install')->assertExitCode(0);
        }
    }

    public function test_reports_creation_failure(): void
    {
        $this->environment(false, ['exit(1);']);
        $this->artisan('qr-decoder:install')->expectsOutputToContain('Could not create the Python environment.')->assertExitCode(1);
    }

    public function test_reports_dependency_installation_failure(): void
    {
        $this->environment(true, ['exit(1);']);
        $this->artisan('qr-decoder:install')->expectsOutputToContain('Could not install QR decoder dependencies.')->assertExitCode(1);
    }

    public function test_reports_timeout_without_a_stack_trace(): void
    {
        $environment = $this->createStub(PythonEnvironment::class);
        $environment->method('python')->willReturn(PHP_BINARY);
        $environment->method('process')->willReturn(new Process([PHP_BINARY, '-r', 'usleep(2000000);'], null, null, null, 0.05));
        $this->app->instance(PythonEnvironment::class, $environment);
        $this->artisan('qr-decoder:install')->expectsOutputToContain('Timed out while trying to install QR decoder dependencies.')->assertExitCode(1);
    }

    public function test_reports_an_unavailable_python_executable(): void
    {
        $environment = $this->createStub(PythonEnvironment::class);
        $environment->method('python')->willReturn(PHP_BINARY);
        $environment->method('process')->willThrowException(new \Symfony\Component\Process\Exception\RuntimeException('private startup detail'));
        $this->app->instance(PythonEnvironment::class, $environment);
        $this->artisan('qr-decoder:install')->expectsOutputToContain('Check the configured Python path, executable and permissions.')->assertExitCode(1);
    }
}
