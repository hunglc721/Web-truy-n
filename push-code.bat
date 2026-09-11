@echo off
title Push Code - WebComics
echo ====================================================
echo             DANG DAY CODE LEN GITHUB
echo ====================================================

set "PATH=F:\laragon\bin\git\cmd;F:\laragon\bin\git\mingw64\bin;%PATH%"

cd /d "F:\Web-truy-n-main"

echo [*] Dang push code len GitHub (origin main)...
echo.
git push origin main

echo.
if %errorlevel% equ 0 (
    echo ====================================================
    echo        [OK] DAY CODE LEN GITHUB THANH CONG!
    echo ====================================================
) else (
    echo ====================================================
    echo     [!] CO LOI XAY RA HOAC CAN DANG NHAP GITHUB
    echo ====================================================
)
echo.
pause
