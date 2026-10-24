@echo off
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0check-local.ps1"
if errorlevel 1 pause
