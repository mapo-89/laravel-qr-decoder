@echo off
setlocal
set "SCRIPT_DIR=%~dp0"
set "ROOT=%SCRIPT_DIR%"
if defined QR_DECODER_PYTHON_PATH set "ROOT=%QR_DECODER_PYTHON_PATH%"
set "VENV_PATH=%ROOT%"
if exist "%ROOT%\venv\Scripts\python.exe" set "VENV_PATH=%ROOT%\venv"
set "PYTHONIOENCODING=utf-8"
set "PYTHONUTF8=1"
"%VENV_PATH%\Scripts\python.exe" "%SCRIPT_DIR%decode_qr.py" %*
exit /b %errorlevel%
