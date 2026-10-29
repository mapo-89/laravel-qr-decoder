<?php

declare(strict_types=1);

namespace Mapo89\QrDecoder;

use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class QrDecoder
{
    public function decode(string $imagePath): string
    {
        if (! is_file($imagePath) || ! is_readable($imagePath)) {
            throw new RuntimeException('Image file is not readable.');
        }

        $root = rtrim((string) config('qr-decoder.python_path'), '/\\');
        $suffix = PHP_OS_FAMILY === 'Windows' ? '/Scripts/python.exe' : '/bin/python';
        $python = is_file($root.'/venv'.$suffix) ? $root.'/venv'.$suffix : $root.$suffix;

        if (! is_file($python)) {
            throw new RuntimeException('QR decoder environment is missing. Run qr-decoder:install.');
        }

        $process = new Process([$python, __DIR__.'/../python/decode_qr.py', $imagePath]);
        $process->setTimeout((float) config('qr-decoder.timeout', 10));

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            throw new RuntimeException('QR decoder process timed out.');
        }

        if (! $process->isSuccessful()) {
            throw new RuntimeException('QR decoder process failed.');
        }

        return trim($process->getOutput());
    }
}
