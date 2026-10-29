<?php

declare(strict_types=1);

namespace Mapo89\QrDecoder\Tests;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Mapo89\QrDecoder\QrDecoder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once __DIR__.'/../src/QrDecoder.php';

final class QrDecoderTest extends TestCase
{
    private string $directory;

    private Container $previousContainer;

    protected function setUp(): void
    {
        parent::setUp();
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('The process fixtures use a POSIX shell.');
        }
        $this->directory = sys_get_temp_dir().'/qr-decoder-test-'.bin2hex(random_bytes(8));
        mkdir($this->directory.'/venv/bin', 0700, true);
        $this->previousContainer = Container::getInstance();
        $container = new Container;
        $container->instance('config', new Repository(['qr-decoder' => ['python_path' => $this->directory, 'timeout' => 1]]));
        Container::setInstance($container);
        file_put_contents($this->directory.'/image.png', 'fixture');
    }

    protected function tearDown(): void
    {
        if (isset($this->directory)) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($this->directory);
            Container::setInstance($this->previousContainer);
        }
        parent::tearDown();
    }

    private function interpreter(string $script, bool $legacy = false): void
    {
        $directory = $legacy ? $this->directory.'/bin' : $this->directory.'/venv/bin';
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        file_put_contents($directory.'/python', "#!/bin/sh\n".$script."\n");
        chmod($directory.'/python', 0700);
    }

    public function test_uses_the_installed_venv_without_mutating_global_environment(): void
    {
        $this->interpreter("printf '  fixture-token  '");
        $before = getenv('QR_DECODER_PYTHON_PATH');
        $this->assertSame('fixture-token', (new QrDecoder)->decode($this->directory.'/image.png'));
        $this->assertSame($before, getenv('QR_DECODER_PYTHON_PATH'));
    }

    public function test_accepts_legacy_direct_venv_configuration(): void
    {
        $this->interpreter("printf 'legacy-token'", true);
        $this->assertSame('legacy-token', (new QrDecoder)->decode($this->directory.'/image.png'));
    }

    public function test_passes_image_paths_without_shell_interpolation(): void
    {
        $this->interpreter('printf \'%s\' "$2"');
        $path = $this->directory.'/image ; touch unexpected.png';
        file_put_contents($path, 'fixture');
        $this->assertSame($path, (new QrDecoder)->decode($path));
        $this->assertFileDoesNotExist($this->directory.'/unexpected.png');
    }

    public function test_does_not_include_process_output_in_errors(): void
    {
        $this->interpreter("printf 'private-decoded-token' >&2\nexit 1");
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('QR decoder process failed.');
        (new QrDecoder)->decode($this->directory.'/image.png');
    }

    public function test_reports_missing_images(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Image file is not readable.');
        (new QrDecoder)->decode($this->directory.'/missing.png');
    }

    public function test_reports_an_uninstalled_environment(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('QR decoder environment is missing.');
        (new QrDecoder)->decode($this->directory.'/image.png');
    }

    public function test_times_out_a_slow_decoder(): void
    {
        $this->interpreter('sleep 2');
        config(['qr-decoder.timeout' => 0.05]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('QR decoder process timed out.');
        (new QrDecoder)->decode($this->directory.'/image.png');
    }
}
