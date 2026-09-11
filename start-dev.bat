@echo off
title FinAI Dev Server
echo ============================================
echo   FinAI - Personal Finance App
echo   Starting Development Servers...
echo ============================================
echo.

:: Start Vite (npm run dev) in a new window
start "Vite (CSS/JS)" cmd /k "cd /d %~dp0 && npm run dev"

:: Wait 3 seconds for Vite to boot first
timeout /t 3 /nobreak > nul

:: Start Laravel in this window
echo [Laravel] Starting php artisan serve...
echo [Laravel] App running at: http://127.0.0.1:8000
echo.
echo  Close this window to stop the Laravel server.
echo  Close the "Vite" window to stop CSS/JS processing.
echo.
php artisan serve
