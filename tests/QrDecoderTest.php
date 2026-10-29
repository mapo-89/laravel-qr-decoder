<?php

declare(strict_types=1);

namespace Mapo89\QrDecoder\Tests;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Mapo89\QrDecoder\PythonEnvironment;
use Mapo89\QrDecoder\QrDecoder;
use Mapo89\QrDecoder\QrDecoderException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

final class QrDecoderTest extends TestCase
{
    private string $directory;

    private Container $previousContainer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/qr-decoder-test-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700, true);
        $this->previousContainer = Container::getInstance();
        $container = new Container;
        $container->instance('config', new Repository(['qr-decoder' => ['python_path' => $this->directory, 'timeout' => 1]]));
        Container::setInstance($container);
        file_put_contents($this->directory.'/image.png', 'fixture');
    }

    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->directory);
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    private function decoder(string $script): QrDecoder
    {
        $fixture = $this->directory.'/process.php';
        file_put_contents($fixture, '<?php '.$script);
        $environment = $this->createStub(PythonEnvironment::class);
        $environment->method('python')->willReturn(PHP_BINARY);
        $environment->method('process')->willReturnCallback(function (array $command, float $timeout) use ($fixture): Process {
            // Exercise real process launching on both Windows and Linux.
            return (new PythonEnvironment)->process([PHP_BINARY, $fixture, ...array_slice($command, 2)], $timeout);
        });

        return new QrDecoder($environment);
    }

    public function test_preserves_payload_and_does_not_mutate_global_environment(): void
    {
        $decoder = $this->decoder('echo json_encode(["text" => "  ä 日本語\n\t\0  "]);');
        $before = getenv('PYTHONIOENCODING');
        $this->assertSame("  ä 日本語\n\t\0  ", $decoder->decode($this->directory.'/image.png'));
        $this->assertSame($before, getenv('PYTHONIOENCODING'));
    }

    public function test_passes_image_paths_without_shell_interpolation(): void
    {
        $decoder = $this->decoder('echo json_encode(["text" => $argv[2]]);');
        $path = $this->directory.'/image ; unexpected.png';
        file_put_contents($path, 'fixture');
        $this->assertSame($path, $decoder->decode($path));
    }

    public function test_sets_utf8_only_for_the_child_process(): void
    {
        $before = getenv('PYTHONUTF8');
        $decoder = $this->decoder('echo json_encode(["text" => getenv("PYTHONIOENCODING").":".getenv("PYTHONUTF8")]);');
        $this->assertSame('utf-8:1', $decoder->decode($this->directory.'/image.png'));
        $this->assertSame($before, getenv('PYTHONUTF8'));
    }

    public function test_reports_an_empty_environment_path(): void
    {
        config(['qr-decoder.python_path' => '']);
        try {
            (new QrDecoder)->decode($this->directory.'/image.png');
            $this->fail('Expected invalid_configuration.');
        } catch (QrDecoderException $error) {
            $this->assertSame('invalid_configuration', $error->reason);
        }
    }

    public function test_reports_recognized_errors_without_exposing_process_output(): void
    {
        $decoder = $this->decoder('echo json_encode(["error" => "ambiguous_image", "private" => "secret-token"]); fwrite(STDERR, "secret-token"); exit(1);');
        try {
            $decoder->decode($this->directory.'/image.png');
            $this->fail('Expected a decoder exception.');
        } catch (QrDecoderException $error) {
            $this->assertSame('ambiguous_image', $error->reason);
            $this->assertStringNotContainsString('secret-token', (string) $error);
        }
    }

    public function test_does_not_expose_unknown_errors(): void
    {
        $decoder = $this->decoder('echo json_encode(["error" => "secret-token"]); exit(1);');
        $this->expectExceptionMessage('QR decoder process failed.');
        $decoder->decode($this->directory.'/image.png');
    }

    public function test_rejects_invalid_success_responses(): void
    {
        foreach (['not json', 'null', '{"text":12}', '{}'] as $response) {
            $decoder = $this->decoder('echo '.var_export($response, true).';');
            try {
                $decoder->decode($this->directory.'/image.png');
                $this->fail('Expected invalid_response.');
            } catch (QrDecoderException $error) {
                $this->assertSame('invalid_response', $error->reason);
            }
        }
    }

    public function test_reports_missing_images(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Image file is not readable.');
        (new QrDecoder)->decode($this->directory.'/missing.png');
    }

    public function test_reports_an_uninstalled_environment(): void
    {
        $this->expectExceptionMessage('QR decoder environment is missing.');
        (new QrDecoder)->decode($this->directory.'/image.png');
    }

    public function test_times_out_a_slow_decoder(): void
    {
        $decoder = $this->decoder('usleep(2000000);');
        config(['qr-decoder.timeout' => 0.05]);
        $this->expectExceptionMessage('QR decoder process timed out.');
        $decoder->decode($this->directory.'/image.png');
    }

    public function test_resolves_nested_and_legacy_environments_consistently(): void
    {
        $suffix = PHP_OS_FAMILY === 'Windows' ? '/Scripts/python.exe' : '/bin/python';
        foreach ([$this->directory, $this->directory.'/venv'] as $venv) {
            mkdir(dirname($venv.$suffix), 0700, true);
            file_put_contents($venv.$suffix, 'fixture');
            $this->assertSame($venv, (new PythonEnvironment)->directory());
            $this->assertSame($venv.$suffix, (new PythonEnvironment)->python());
        }
    }
}
