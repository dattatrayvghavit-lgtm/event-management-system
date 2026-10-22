@echo off
cd /d "%~dp0"
if not exist "C:\xampp\php\php.exe" (
  echo PHP not found at C:\xampp\php\php.exe. Follow README.md for manual setup.
  pause
  exit /b 1
)
if not exist config.php copy config.example.php config.php >nul
echo Ensure MySQL is running in XAMPP Control Panel first.
"C:\xampp\php\php.exe" scripts\setup.php
if errorlevel 1 (
  pause
  exit /b 1
)
echo Open http://127.0.0.1:8000 in your browser. Keep this window open.
"C:\xampp\php\php.exe" -S 127.0.0.1:8000 -t public
pause
