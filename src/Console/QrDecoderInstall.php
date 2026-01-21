<?php

namespace Mapo89\QrDecoder\Console;

use Illuminate\Console\Command;
use RuntimeException;

class QrDecoderInstall extends Command
{
    protected $signature = 'qr-decoder:install';
    protected $description = 'Set up Python venv for QR Decoder';

    public function handle()
    {
        $basePath = config('qr-decoder.python_path');
        $venvPath = $basePath . '/venv';

        if (is_dir($venvPath)) {
            $this->info("Python venv already exists at $venvPath");
            return 0;
        }

        $pythonCmd = 'python';
        $requirements = escapeshellarg( 'vendor/mapo-89/laravel-qr-decoder/python/requirements.txt');

        $this->info("Creating Python virtual environment at $venvPath...");

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        $commands = [
            "$pythonCmd -m venv " . escapeshellarg($venvPath),
            $isWindows
                ? "$venvPath\\Scripts\\python.exe -m pip install --upgrade pip"
                : "$venvPath/bin/python -m pip install --upgrade pip",
            $isWindows
                ? "$venvPath\\Scripts\\python.exe -m pip install -r $requirements"
                : "$venvPath/bin/python -m pip install -r $requirements",
        ];

        foreach ($commands as $cmd) {
            system($cmd, $ret);
            if ($ret !== 0) {
                throw new RuntimeException("Failed to execute: $cmd");
            }
        }

        $this->info("Python venv setup completed at $venvPath");

        return 0;
    }
}
