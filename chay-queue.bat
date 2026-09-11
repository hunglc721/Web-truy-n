@echo off
chcp 65001 > nul
title Web Truyện - Queue Worker
echo ====================================================
echo      ĐANG CHẠY QUEUE WORKER (XỬ LÝ ẢNH & THÔNG BÁO)
echo ====================================================

set "PATH=F:\laragon\bin\php\php-8.3.33-Win32-vs16-x64;F:\laragon\bin\composer;F:\laragon\bin\mysql\mysql-8.0.40-winx64\bin;F:\laragon\bin\nodejs\node-v22;F:\laragon\bin\git\cmd;%PATH%"

cd /d "F:\Web-truy-n-main\laravel-blade"

echo.
echo [*] Queue worker đang lắng nghe. Nhấn Ctrl + C để dừng.
echo.
php artisan queue:work --queue=notifications,chapter-images,default
pause
