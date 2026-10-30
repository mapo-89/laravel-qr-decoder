# Changelog

All notable changes to `laravel-qr-decoder` will be documented in this file

## v2.0.1 - 2026-10-30

### 📝 Documentation
- Define PR-only releases and branch retirement

### 🔧 Chores
- Require release PRs and safely clean published branches
- Isolate release fixtures from runner environment
- Bump actions/setup-node from 4 to 7
- Bump actions/checkout from 5 to 7
- Bump actions/setup-python from 6 to 7
- Allow release cleanup to verify open pull requests

## v2.0.0 - 2026-10-30

### 💥 Breaking Changes
- Drop Laravel 11 support

### 🐛 Fixes
- Omit release comparison lines and empty changelog sections
- Preserve QR payloads and decode transparent images
- Unify Python environment setup and wrapper paths

### 📝 Documentation
- Explain decoder configuration and error handling
- Document release branches and direct commits

### 🔧 Chores
- Exercise real QR decoding on Linux and Windows
- Configure weekly Dependabot updates
- Adopt validated release branch automation

## v1.2.1 - 2026-10-29

### 🔧 Chores
- Upgrade Python imaging dependencies and support PHPUnit 13

## v1.2.0 - 2026-10-29

### ✨ Features
- Support Laravel 13 and reliable QR image decoding

### 🐛 Fixes
- Restore CI compatibility tests for Laravel 11 through 13
- Configure CI compatibility policy portably on Windows
- Fetch missing CI comparison commits after force pushes

### ♻️ Refactoring
- Register QR Decoder with Spatie Package Tools

### 📝 Documentation
- Refresh package setup, badges and support links

### 🔧 Chores
- Standardize release and changelog automation with Node.js

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
