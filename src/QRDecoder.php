<?php

namespace Mapo89\QrDecoder;

use Symfony\Component\Process\Process;
use RuntimeException;

class QrDecoder
{
    public function decode(string $imagePath): string
    {
        if (! is_file($imagePath)) {
            throw new RuntimeException('Image file not found.');
        }
        
        $base = config('qr-decoder.python_path');

        $bin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? $base . '/run_qr_decoder.bat'
            : $base . '/run_qr_decoder';

        $process = new Process([$bin, $imagePath]);
        $process->setTimeout(config('qr-decoder.timeout', 10));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput());
        }

        return trim($process->getOutput());
    }
}
