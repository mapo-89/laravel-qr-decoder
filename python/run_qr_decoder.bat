@echo off
set DIR=%~dp0
"%DIR%venv\Scripts\python.exe" "%DIR%decode_qr.py" "%1"
