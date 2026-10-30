<?php

declare(strict_types=1);

namespace Mapo89\QrDecoder;

use JsonException;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException as ProcessException;

class QrDecoder
{
    public function __construct(private PythonEnvironment $environment = new PythonEnvironment) {}

    public function decode(string $imagePath): string
    {
        if (! is_file($imagePath) || ! is_readable($imagePath)) {
            throw new QrDecoderException('unreadable_image');
        }

        try {
            $python = $this->environment->python();
        } catch (RuntimeException) {
            throw new QrDecoderException('invalid_configuration');
        }
        if (! is_file($python)) {
            throw new QrDecoderException('missing_environment');
        }

        $process = $this->environment->process(
            [$python, __DIR__.'/../python/decode_qr.py', '--json', $imagePath],
            (float) config('qr-decoder.timeout', 10),
        );

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            throw new QrDecoderException('timeout');
        } catch (ProcessException) {
            throw new QrDecoderException('process_failed');
        }

        try {
            $response = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new QrDecoderException($process->isSuccessful() ? 'invalid_response' : 'process_failed');
        }

        if (! $process->isSuccessful()) {
            $reason = is_array($response) ? ($response['error'] ?? null) : null;
            throw new QrDecoderException(in_array($reason, ['invalid_image', 'image_too_large', 'ambiguous_image'], true) ? $reason : 'process_failed');
        }

        if (! is_array($response) || ! isset($response['text']) || ! is_string($response['text'])) {
            throw new QrDecoderException('invalid_response');
        }

        return $response['text'];
    }
}
