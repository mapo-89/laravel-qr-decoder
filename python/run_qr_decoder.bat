@echo off
set SCRIPT_DIR=%~dp0
REM Prefer ENV-configured venv path
if defined QR_DECODER_PYTHON_PATH (
    set VENV_PATH=%QR_DECODER_PYTHON_PATH%\venv
) else (
    REM Fallback: assume venv next to script (legacy)
    set VENV_PATH=%SCRIPT_DIR%venv
)

"%VENV_PATH%\Scripts\python.exe" "%SCRIPT_DIR%decode_qr.py" "%1"
