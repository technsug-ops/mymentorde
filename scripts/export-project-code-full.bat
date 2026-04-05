@echo off
setlocal
cd /d "%~dp0.."
powershell -NoProfile -ExecutionPolicy Bypass -File ".\scripts\export-project-code.ps1" -IncludeVendor -IncludeEnv -OpenFolder
endlocal

