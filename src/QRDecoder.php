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
        putenv('QR_DECODER_PYTHON_PATH=' . config('qr-decoder.python_path'));
        $packageRoot = realpath(__DIR__ . '/..');
        $scriptBase  = $packageRoot . '/python';
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            $script = $scriptBase . '/run_qr_decoder.bat';
            $process = new Process(['cmd', '/c', $script, $imagePath]);
        } else {
            $script = $scriptBase . '/run_qr_decoder';
            $process = new Process([$script, $imagePath]);
        }
        $process->setEnv([
            'QR_DECODER_PYTHON_PATH' => config('qr-decoder.python_path'),
        ]);
        $process->setTimeout(config('qr-decoder.timeout', 10));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput());
        }

        return trim($process->getOutput());
    }
}
