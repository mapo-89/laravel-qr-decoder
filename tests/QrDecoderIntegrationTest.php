<?php

declare(strict_types=1);

namespace Mapo89\QrDecoder\Tests;

use Mapo89\QrDecoder\PythonEnvironment;
use Mapo89\QrDecoder\QrDecoder;
use Mapo89\QrDecoder\QrDecoderException;
use Mapo89\QrDecoder\QrDecoderServiceProvider;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Process\Process;

final class QrDecoderIntegrationTest extends TestCase
{
    private string $directory;

    protected function getPackageProviders($app): array
    {
        return [QrDecoderServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['qr-decoder.python_path' => getenv('QR_DECODER_TEST_VENV') ?: dirname(__DIR__).'/python/venv']);
        if (! is_file((new PythonEnvironment)->python())) {
            $this->markTestSkipped('Install python/requirements.txt in python/venv or set QR_DECODER_TEST_VENV.');
        }
        $this->directory = sys_get_temp_dir().'/qr integration '.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700, true);
    }

    protected function tearDown(): void
    {
        if (isset($this->directory)) {
            foreach (glob($this->directory.'/*') as $file) {
                unlink($file);
            }
            rmdir($this->directory);
        }
        parent::tearDown();
    }

    public function test_decodes_real_images_through_the_laravel_container(): void
    {
        $payload = "  ä 日本語\nsecond line\t  ";
        foreach (['PNG', 'JPEG', 'WEBP'] as $format) {
            $path = $this->directory.'/image ; '.$format;
            $process = (new PythonEnvironment)->process([
                (new PythonEnvironment)->python(), __DIR__.'/fixtures/create_qr.py', $path,
                json_encode($payload, JSON_THROW_ON_ERROR), $format,
            ], 15);
            $process->mustRun();
            $this->assertSame($payload, $this->app->make(QrDecoder::class)->decode($path));
        }
    }

    public function test_reports_invalid_real_images_with_a_safe_reason(): void
    {
        $path = $this->directory.'/invalid.png';
        file_put_contents($path, 'not an image');
        try {
            $this->app->make(QrDecoder::class)->decode($path);
            $this->fail('Expected invalid_image.');
        } catch (QrDecoderException $error) {
            $this->assertSame('invalid_image', $error->reason);
        }
    }

    public function test_wrappers_accept_root_and_direct_venv_paths(): void
    {
        $payload = '  ä 日本語  ';
        $environment = new PythonEnvironment;
        $path = $this->directory.'/wrapper image & %payload%.png';
        $environment->process([$environment->python(), __DIR__.'/fixtures/create_qr.py', $path, json_encode($payload, JSON_THROW_ON_ERROR), 'PNG'], 15)->mustRun();
        $venv = $environment->directory();
        $roots = [$venv];
        if (basename($venv) === 'venv') {
            $roots[] = dirname($venv);
        }
        foreach ($roots as $root) {
            $wrapper = dirname(__DIR__).'/python/run_qr_decoder'.(PHP_OS_FAMILY === 'Windows' ? '.bat' : '');
            $command = PHP_OS_FAMILY === 'Windows' ? [$wrapper, '--json', $path] : ['bash', $wrapper, '--json', $path];
            $process = new Process($command, null, ['QR_DECODER_PYTHON_PATH' => $root], null, 15);
            $process->mustRun();
            $this->assertSame(['text' => $payload], json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR));
        }
    }
}
