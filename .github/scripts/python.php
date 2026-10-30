<?php

declare(strict_types=1);

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$root = dirname(__DIR__, 2);
$configured = getenv('QR_DECODER_TEST_VENV');
$environment = $configured !== false && $configured !== '' ? $configured : $root.'/python/venv';
$python = rtrim($environment, '/\\').(PHP_OS_FAMILY === 'Windows' ? '/Scripts/python.exe' : '/bin/python');

if (! is_file($python)) {
    if (($configured !== false && $configured !== '') || is_dir($environment)) {
        fwrite(STDERR, "The Python development environment has no interpreter: {$python}\n");
        fwrite(STDERR, "Create a Python >= 3.12 virtual environment and install python/requirements-dev.txt.\n");
        exit(1);
    }

    $python = (new ExecutableFinder)->find(PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3');
    if ($python === null) {
        fwrite(STDERR, "Python was not found. Create python/venv with Python >= 3.12 and install python/requirements-dev.txt.\n");
        exit(1);
    }
}

$process = new Process([$python, ...array_slice($argv, 1)], $root, ['PYTHONIOENCODING' => 'utf-8', 'PYTHONUTF8' => '1']);
$process->setTimeout(null);
$process->run(static function (string $type, string $output): void {
    fwrite($type === Process::ERR ? STDERR : STDOUT, $output);
});

exit($process->getExitCode() ?? 1);
