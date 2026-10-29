<?php

declare(strict_types=1);

namespace Mapo89\QrDecoder\Console;

use Illuminate\Console\Command;
use Mapo89\QrDecoder\PythonEnvironment;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class QrDecoderInstall extends Command
{
    protected $signature = 'qr-decoder:install';

    protected $description = 'Set up or update the Python environment for QR Decoder';

    public function handle(PythonEnvironment $environment): int
    {
        $stage = 'create the Python environment';
        try {
            $python = $environment->python();
            $timeout = (float) config('qr-decoder.install_timeout', 300);
            if (! is_file($python)) {
                $create = $environment->process([(string) config('qr-decoder.python_binary'), '-m', 'venv', $environment->directory()], $timeout);
                $create->run();
                if (! $create->isSuccessful()) {
                    $this->error('Could not create the Python environment. Check Python >= 3.11 and venv support.');

                    return self::FAILURE;
                }
            }

            $stage = 'install QR decoder dependencies';
            $install = $environment->process([
                $python, '-m', 'pip', 'install', '--disable-pip-version-check', '--no-input', '--only-binary=:all:',
                '-r', dirname(__DIR__, 2).'/python/requirements.txt',
            ], $timeout);
            $install->run(function (string $type, string $output): void {
                $this->output->write($output);
            });
            if (! $install->isSuccessful()) {
                $this->error('Could not install QR decoder dependencies. Check Python version, wheel availability and package index access.');

                return self::FAILURE;
            }
        } catch (ProcessTimedOutException) {
            $this->error('Timed out while trying to '.$stage.'. Check qr-decoder.install_timeout.');

            return self::FAILURE;
        } catch (RuntimeException) {
            $this->error('Could not '.$stage.'. Check the configured Python path, executable and permissions.');

            return self::FAILURE;
        }

        $this->info('QR decoder environment is ready.');

        return self::SUCCESS;
    }
}
