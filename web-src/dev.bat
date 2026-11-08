@echo off
chcp 65001 >nul
title Backupino - Dev Server (http://localhost:5173)
color 0a
echo ======================================================================
echo    Backupino Web Portal - Vite Development Server
echo ======================================================================
echo.

cd /d "%~dp0"

if not exist "node_modules" (
    echo [*] Running npm install...
    call npm install
)

echo [*] Starting Vite Dev Server...
echo [*] Open http://localhost:5173 in your browser
echo.
call npm run dev
pause
