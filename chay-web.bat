@echo off
chcp 65001 > nul
title Web Truyện - Comicx Server
echo ====================================================
echo      ĐANG KHỞI ĐỘNG MÁY CHỦ WEB TRUYỆN (COMICX)
echo ====================================================

set "PATH=F:\laragon\bin\php\php-8.3.33-Win32-vs16-x64;F:\laragon\bin\composer;F:\laragon\bin\mysql\mysql-8.0.40-winx64\bin;F:\laragon\bin\nodejs\node-v22;F:\laragon\bin\git\cmd;%PATH%"

cd /d "F:\Web-truy-n-main\laravel-blade"

echo.
echo [*] Mở trình duyệt tại: http://127.0.0.1:8000
start http://127.0.0.1:8000

echo [*] Server đang chạy. Nhấn Ctrl + C để dừng.
echo.
php artisan serve --host=127.0.0.1 --port=8000
pause
