# Changelog

All notable changes to `laravel-qr-decoder` will be documented in this file

## 1.1.1 - 2026-01-21

### 🐛 Fixed
- Fixed Windows issue where `putenv()` was not inherited by the Python process
- Environment variables are now passed explicitly via `Symfony\Component\Process\Process::setEnv()`
- Fixed path resolution issues for locally installed (path repository) packages

### 🧱 Changed
- Wrapper scripts now rely on injected environment variables instead of hardcoded paths

### 🔒 Stability
- Improved reliability across Windows, Linux, CI, and local development environments

---

## 1.1.0 - 2026-01-21

### Added
- Artisan command `qr-decoder:install` to set up the Python virtual environment
- Python venv is now created using Laravel config (`qr-decoder.python_path`)
- Fully update-safe setup (no venv inside vendor/)
- Windows and Linux support for automated setup
- Improved documentation for installation and usage

### Changed
- Removed manual venv setup instructions from README
- Recommended Artisan-based installation

---

## 1.0.0 - 2026-01-21

### Added
- Initial release of `laravel-qr-decoder` package
- Cross-platform QR code decoding for PNG files using Python (`zxing-cpp`)
- Python isolated via `venv` to avoid global dependencies
- Symfony Process wrapper for secure execution
- Laravel service `QrDecoder` for decoding QR codes
- Configurable Python path and execution timeout
- Support for Windows and Linux operating systems
- Documentation including usage examples and integration in Laravel controllers
