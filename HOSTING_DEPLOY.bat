@echo off
setlocal
cd /d "%~dp0"
echo.
echo =========================================
echo   MentorDE Hostinger Deploy Builder
echo =========================================
echo.
echo Deploy ZIP paketleri olusturuluyor...
echo Vendor klasoru buyuk olabilir, lutfen bekleyin.
echo.
powershell -NoProfile -ExecutionPolicy Bypass -File ".\scripts\build-hostinger-deploy.ps1"
endlocal
