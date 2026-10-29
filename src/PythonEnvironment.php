<?php

declare(strict_types=1);

namespace Mapo89\QrDecoder;

use RuntimeException;
use Symfony\Component\Process\Process;

class PythonEnvironment
{
    public function directory(): string
    {
        $root = rtrim((string) config('qr-decoder.python_path'), '/\\');
        if ($root === '') {
            throw new RuntimeException('QR decoder Python path must not be empty.');
        }

        $suffix = $this->suffix();
        if (is_file($root.'/venv'.$suffix)) {
            return $root.'/venv';
        }

        return is_file($root.$suffix) ? $root : $root.'/venv';
    }

    public function python(): string
    {
        return $this->directory().$this->suffix();
    }

    public function process(array $command, float $timeout): Process
    {
        $process = new Process($command, null, ['PYTHONIOENCODING' => 'utf-8', 'PYTHONUTF8' => '1']);
        $process->setTimeout($timeout);

        return $process;
    }

    private function suffix(): string
    {
        return PHP_OS_FAMILY === 'Windows' ? '/Scripts/python.exe' : '/bin/python';
    }
}
