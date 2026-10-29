<?php

declare(strict_types=1);

namespace Mapo89\QrDecoder;

use RuntimeException;

class QrDecoderException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct(match ($reason) {
            'unreadable_image' => 'Image file is not readable.',
            'missing_environment' => 'QR decoder environment is missing. Run qr-decoder:install.',
            'invalid_configuration' => 'QR decoder Python path is invalid.',
            'timeout' => 'QR decoder process timed out.',
            'invalid_image' => 'QR decoder image is invalid or unsupported.',
            'image_too_large' => 'QR decoder image exceeds the pixel limit.',
            'ambiguous_image' => 'QR decoder image contains different QR codes.',
            'invalid_response' => 'QR decoder returned an invalid response.',
            default => 'QR decoder process failed.',
        });
    }
}
