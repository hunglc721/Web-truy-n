@echo off
chcp 65001 > nul
title Web Truyện - Khởi động toàn bộ
echo ====================================================
echo      KHỞI ĐỘNG HỆ THỐNG WEB TRUYỆN ĐẦY ĐỦ
echo ====================================================

set "PATH=F:\laragon\bin\php\php-8.3.33-Win32-vs16-x64;F:\laragon\bin\composer;F:\laragon\bin\mysql\mysql-8.0.40-winx64\bin;F:\laragon\bin\nodejs\node-v22;F:\laragon\bin\git\cmd;%PATH%"

cd /d "%~dp0"

echo [*] Khởi động Queue Worker trong cửa sổ mới...
start "Web Truyen Queue Worker" cmd /c "%~dp0chay-queue.bat"

echo [*] Khởi động Web Server...
call "%~dp0chay-web.bat"
