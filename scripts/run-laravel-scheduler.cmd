@echo off
setlocal

cd /d "C:\Users\User\Desktop\PHP -LARAVEL Mentorde\mentorde"
"C:\tools\php84\php.exe" artisan schedule:run >> "storage\logs\scheduler.log" 2>&1

endlocal

