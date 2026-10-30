# Laravel QR Decoder

[![Version](https://img.shields.io/packagist/v/mapo-89/laravel-qr-decoder.svg?label=version&color=03a9f4)](https://packagist.org/packages/mapo-89/laravel-qr-decoder)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](https://github.com/mapo-89/laravel-qr-decoder/blob/main/LICENSE.md)
[![PHP](https://img.shields.io/packagist/php-v/mapo-89/laravel-qr-decoder.svg?logo=php&logoColor=white)](https://packagist.org/packages/mapo-89/laravel-qr-decoder)
[![Laravel](https://img.shields.io/badge/Laravel-12%20%7C%2013-FF2D20.svg?logo=laravel&logoColor=white)](https://laravel.com/)
[![Total Downloads](https://img.shields.io/packagist/dt/mapo-89/laravel-qr-decoder.svg)](https://packagist.org/packages/mapo-89/laravel-qr-decoder)
[![GitHub Actions](https://github.com/mapo-89/laravel-qr-decoder/actions/workflows/main.yml/badge.svg)](https://github.com/mapo-89/laravel-qr-decoder/actions/workflows/main.yml)
[![GitHub issues](https://img.shields.io/github/issues/mapo-89/laravel-qr-decoder.svg)](https://github.com/mapo-89/laravel-qr-decoder/issues)
[![GitHub stars](https://img.shields.io/github/stars/mapo-89/laravel-qr-decoder.svg?style=flat)](https://github.com/mapo-89/laravel-qr-decoder/stargazers)
[![Last commit](https://img.shields.io/github/last-commit/mapo-89/laravel-qr-decoder.svg)](https://github.com/mapo-89/laravel-qr-decoder/commits/main)

<a href="https://buymeacoffee.com/mapo"><img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="Buy Me a Coffee" height="60"></a>

A cross‑platform Laravel package for decoding QR codes from PNG, JPEG and WebP images using Python (`zxing-cpp`).

This package wraps a Python decoder inside a virtual environment and exposes a clean Laravel API. It works on **Windows and Linux**. Laravel launches the virtual environment’s Python interpreter directly; optional wrapper scripts are available for command-line use.

---

## Features

* ✅ Cross‑platform (Windows & Linux)
* ✅ Python isolated via `venv`
* ✅ No global Python dependencies
* ✅ Secure execution via Symfony Process
* ✅ Works with logos / low‑contrast QR codes

---

## Requirements

* PHP >= 8.2
* Laravel 12 or 13
* Python >= 3.12

Version 3 requires Python 3.12 or newer and uses NumPy 2.5.4.
When upgrading from version 2, recreate any virtual environment created with
Python 3.11 using Python 3.12 or newer, then rerun `php artisan qr-decoder:install`.
Set `QR_DECODER_PYTHON_BINARY` to the newer interpreter before creating the environment.

---

## Installation

### Install the package (local / path repository)

```json
// composer.json
{
  "repositories": [
    {
      "type": "path",
      "url": "packages/laravel-qr-decoder"
    }
  ]
}
```

```bash
composer require mapo-89/laravel-qr-decoder
```

---

### Publish config

```bash
php artisan vendor:publish --tag=qr-decoder-config
```

The legacy `--tag=config` command remains supported.

---

### Configure Python path

Edit `config/qr-decoder.php`:

```php
return [
    'python_path' => env('QR_DECODER_PYTHON_PATH', base_path('python/qr-decoder')),
    'python_binary' => env('QR_DECODER_PYTHON_BINARY', PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3'),
    'timeout' => (int) env('QR_DECODER_TIMEOUT', 10),
    'install_timeout' => (int) env('QR_DECODER_INSTALL_TIMEOUT', 300),
];
```

The environment variables are optional:

| Variable | Default | Purpose |
| --- | --- | --- |
| `QR_DECODER_PYTHON_PATH` | `<application>/python/qr-decoder` | Parent directory containing `venv`; an existing virtual environment directory is also accepted. |
| `QR_DECODER_PYTHON_BINARY` | `python3` on Linux, `python` on Windows | Python >= 3.12 executable used to create the environment. Use an executable path without command-line arguments. |
| `QR_DECODER_TIMEOUT` | `10` | Timeout in seconds for one decode operation. |
| `QR_DECODER_INSTALL_TIMEOUT` | `300` | Timeout in seconds for each installation step. |

After changing environment variables in a deployment using Laravel's configuration
cache, rebuild it with `php artisan config:cache`.

### Run the installer (recommended)

```bash
php artisan qr-decoder:install
```
This command will:
- Create a Python virtual environment in the configured path
- Install all required Python dependencies
- Work on Windows and Linux

## Usage

This package does not provide controllers or views.
It exposes a single service (`QrDecoder`) that can be used inside your application’s controllers, APIs, jobs or commands.

### Example Use Cases

1. **Decode an already uploaded file on button click:**

```php
use Mapo89\QrDecoder\QrDecoder;

$result = app(QrDecoder::class)->decode(
    storage_path('app/qr_uploaded_file.png')
);
```

2. **Upload a file and decode immediately:**

```php
public function uploadAndDecode(Request $request, QrDecoder $decoder)
{
    $request->validate([
        'qrpng' => ['required', 'file', 'mimes:png', 'max:5120'],
    ]);

    // Store temporarily
    $path = $request->file('qrpng')->store('qr-decoder');
    $fullPath = storage_path('app/private/' . $path);

    try {
        $result = $decoder->decode($fullPath);
    } catch (\Mapo89\QrDecoder\QrDecoderException $e) {
        $result = null;
        // Handle error if needed
    } finally {
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }

    return response()->json(['result' => $result]);
}
```

> Note: The controller should live in your application, not in this package.
> If you need a UI, build it in your application or in a separate package that depends on this one.

### Results and errors

`decode()` returns the QR text exactly as decoded, including leading and trailing
spaces, tabs and newlines. An image without a QR code returns an empty string.
Images with different QR payloads are rejected, including payloads that differ
only in whitespace. Repeated copies of the same payload are accepted.

PNG transparency is composed onto a white background. Images are limited to
16,777,216 pixels and resized to at most 2048 × 2048 before detection.

Failures throw `Mapo89\QrDecoder\QrDecoderException`, which extends
`RuntimeException`. Its public `reason` property identifies the cause:

| Reason | Meaning |
| --- | --- |
| `unreadable_image` | The image path is missing or unreadable. |
| `missing_environment` | Run `php artisan qr-decoder:install`. |
| `invalid_configuration` | The configured Python path is empty or invalid. |
| `timeout` | Decoding exceeded the configured timeout. |
| `invalid_image` | The image is invalid or unsupported. |
| `image_too_large` | The image exceeds the pixel limit. |
| `ambiguous_image` | More than one distinct QR payload was detected. |
| `invalid_response` | Python returned an unexpected response. |
| `process_failed` | The process could not start or another decoder error occurred. |

Exception messages contain fixed descriptions, without decoded content or raw
process output. The installer reports environment creation, dependency installation
and timeout failures separately and returns a nonzero exit code.

### Dependency Injection

```php
public function decode(QrDecoder $decoder)
{
    return $decoder->decode($path);
}
```

## Configuration

```php
return [
    'python_path' => env('QR_DECODER_PYTHON_PATH', base_path('python/qr-decoder')),
    'python_binary' => env('QR_DECODER_PYTHON_BINARY', PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3'),
    'timeout' => (int) env('QR_DECODER_TIMEOUT', 10),
    'install_timeout' => (int) env('QR_DECODER_INSTALL_TIMEOUT', 300),
];
```

---

## Directory Structure

```
laravel-qr-decoder/
├── src/
│   ├── QrDecoder.php
│   ├── QrDecoderFacade.php
│   └── QrDecoderServiceProvider.php
│
├── python/
│   ├── decode_qr.py
│   ├── requirements.txt
│   ├── run_qr_decoder
│   ├── run_qr_decoder.bat
│   └── venv/
│
├── config/
│   └── qr-decoder.php
│
├── composer.json
└── README.md
```

### Testing

Install the development dependencies:

```bash
composer install
```

Create the Python environment so both PHP integration tests and Python tests can decode real images:

```bash
python3 -m venv python/venv
python/venv/bin/python -m pip install -r python/requirements.txt
python/venv/bin/python -m unittest discover -s python -p 'test_*.py' -v
composer test
```

On Windows, use `python` to create the environment and
`python/venv/Scripts/python.exe` for the remaining commands. CI runs these tests
on Linux and Windows with Python 3.12 and 3.14. PHP tests use PHPUnit 11, 12 or 13
as supported by the selected PHP and Laravel versions. The PHP CI jobs also install
Python 3.12 and exercise Laravel → Python → QR decoding and both wrapper path
layouts. Locally, real-image PHP tests skip when no virtual environment is present;
set `QR_DECODER_TEST_VENV` to an existing environment directory to use a different
location. A present but broken environment fails the tests.

The service provider uses [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools)
to register configuration and the `qr-decoder:install` command. Laravel discovers the
provider and facade automatically. The integration tests cover configuration merging,
both config publish tags, command registration, and the shared decoder instance.

### Optional command-line wrappers

Laravel calls Python directly. For standalone use, set `QR_DECODER_PYTHON_PATH`
to the same parent directory or existing virtual environment directory used by
Laravel, then run:

```bash
bash python/run_qr_decoder /path/to/image.png
```

On Windows:

```bat
python\run_qr_decoder.bat "C:\path to\image.png"
```

Without the environment variable, both wrappers look for `python/venv` next to
the decoder script. They print UTF-8 text followed by a newline. Add `--json`
before the image path for structured output: `{"text": "..."}` on success or
`{"error": "invalid_image"}` on failure. An empty image result is `{"text": ""}`.
The exit code is zero on success and nonzero on failure. PHP uses this JSON mode
to preserve payload boundaries.

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

GitHub release notes are generated from categorized commit messages using the same
convention as `core-panel-monorepo`. See the [commit and release guide](CONTRIBUTING.md#commit-messages-and-release-notes)
for the supported prefixes, release triggers, and local preview command.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email info@postler.de instead of using the issue tracker.

## Credits

-   [Manuel Postler](https://github.com/mapo-89)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Package tooling

Package configuration and command registration are managed by
[Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools).

## Shared release workflow

Commit types, explicit release triggers, and grouped release notes follow the
[shared release standard](.release/README.md). Ordinary pushes do not publish
production versions. Existing builds, artifact handling, and deployment approvals
remain project-specific.

## Release branch workflow

Feature branches start from main and are squash-merged into release/X.Y.Z.
Alternatively or additionally, commit directly on release/X.Y.Z; these commits
remain separate and do not require squashing. Ordinary commits do not rewrite
version metadata or CHANGELOG.md. Once the release scope is complete, run
`node .release/branches.mjs prepare` once to collect all changes on that branch.
If more commits follow, refresh preparation and checks together at finalization,
rather than after each commit. Then merge the prepared release into main
through a reviewed PR using a merge commit titled `🚀 Release: X.Y.Z`.
Direct pushes/local merges to main and administrator bypasses are forbidden.
Publish the tested merge commit, then remove the fully integrated release branch
after successful publication and verification. Create a new branch per version.
The prepared changelog supplies the published release notes; no post-release
changelog commit is created. See [.release/README.md](.release/README.md).

## Automated dependency maintenance

Dependabot proposes weekly dependency updates. Reviewed updates are retargeted to
the active release branch, tested, and squash-merged through their PRs.
See [.release/README.md](.release/README.md#dependency-updates) for the policy
and activation requirements. Dependency PRs never authorize a release.
