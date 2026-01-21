# Changelog

All notable changes to `laravel-qr-decoder` will be documented in this file

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
