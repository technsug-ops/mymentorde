@echo off
setlocal
cd /d "%~dp0\.."
"C:\tools\php84\php.exe" artisan schedule:run >> storage\logs\scheduler-task.log 2>&1
endlocal
