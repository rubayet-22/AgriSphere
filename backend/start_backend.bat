@echo off
REM ============================================================================
REM  Starts the persistent AgriSphere C++ backend.
REM  Keep this window open while you use the website - closing it stops the
REM  service (and with it the single DatabaseManager instance).
REM ============================================================================
cd /d "%~dp0"

if not exist "bin\agrisphere_backend.exe" (
    echo [INFO] Backend not built yet, building now...
    call build.bat
    if errorlevel 1 exit /b 1
)

echo.
echo  AgriSphere C++ backend
echo  ---------------------------------------------------------------
echo  Make sure MySQL is running in the XAMPP Control Panel first.
echo  Press Ctrl+C to stop.
echo  ---------------------------------------------------------------
echo.

bin\agrisphere_backend.exe --config config\backend.ini
