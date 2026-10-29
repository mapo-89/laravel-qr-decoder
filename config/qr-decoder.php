<?php

return [
    'python_path' => env('QR_DECODER_PYTHON_PATH', base_path('python/qr-decoder')),
    'python_binary' => env('QR_DECODER_PYTHON_BINARY', PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3'),
    'timeout' => (int) env('QR_DECODER_TIMEOUT', 10),
    'install_timeout' => (int) env('QR_DECODER_INSTALL_TIMEOUT', 300),
];
