@echo off
chcp 65001 >nul
title Backupino - React Web Portal Build
color 0b
echo ======================================================================
echo    Backupino Web Portal - Automated Production Builder
echo ======================================================================
echo.

cd /d "%~dp0"

echo [*] Checking and preparing node dependencies...
if not exist "node_modules" (
    echo [!] node_modules not found. Running npm install...
    call npm install
    if %ERRORLEVEL% NEQ 0 (
        echo [x] Failed to install dependencies!
        goto :error
    )
)

echo.
echo [*] Building production single-file package via Vite...
call npm run build

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ======================================================================
    echo   [SUCCESS] React build finished successfully!
    echo   Production output is ready at:
    echo   %~dp0dist\index.html
    echo ======================================================================
    echo.
) else (
    goto :error
)

goto :done

:error
echo.
echo ======================================================================
echo   [ERROR] Build failed! Check the console output above for details.
echo ======================================================================
echo.

:done
echo Press any key to exit...
pause >nul
