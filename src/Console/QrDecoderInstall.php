<?php

declare(strict_types=1);

namespace Mapo89\QrDecoder\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class QrDecoderInstall extends Command
{
    protected $signature = 'qr-decoder:install';

    protected $description = 'Set up or update the Python environment for QR Decoder';

    public function handle(): int
    {
        $root = rtrim((string) config('qr-decoder.python_path'), '/\\');
        $venv = $root.'/venv';
        $python = $venv.(PHP_OS_FAMILY === 'Windows' ? '/Scripts/python.exe' : '/bin/python');

        if (! is_file($python)) {
            $create = new Process([(string) config('qr-decoder.python_binary'), '-m', 'venv', $venv]);
            $create->setTimeout((float) config('qr-decoder.install_timeout', 300));
            $create->run();

            if (! $create->isSuccessful()) {
                $this->error('Could not create the Python environment. Check Python and venv support.');

                return self::FAILURE;
            }
        }

        $install = new Process([
            $python, '-m', 'pip', 'install', '--disable-pip-version-check', '--no-input', '--only-binary=:all:',
            '-r', dirname(__DIR__, 2).'/python/requirements.txt',
        ]);
        $install->setTimeout((float) config('qr-decoder.install_timeout', 300));
        $install->run(function (string $type, string $output): void {
            $this->output->write($output);
        });

        if (! $install->isSuccessful()) {
            $this->error('Could not install QR decoder dependencies.');

            return self::FAILURE;
        }

        $this->info('QR decoder environment is ready.');

        return self::SUCCESS;
    }
}
