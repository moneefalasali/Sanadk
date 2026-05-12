@echo off
echo SANADK - Production Setup Script
echo ================================

cd /d "c:\xampp\htdocs\sanadak\sanadk"

echo Installing npm dependencies...
call npm install

if %errorlevel% neq 0 (
    echo ERROR: Failed to install npm dependencies
    pause
    exit /b 1
)

echo Building CSS for production...
call npm run build

if %errorlevel% neq 0 (
    echo ERROR: Failed to build CSS
    pause
    exit /b 1
)

echo Setup completed successfully!
echo.
echo Next steps:
echo 1. Run 'php artisan serve' to start the Laravel server
echo 2. Open http://localhost:8000 in your browser
echo 3. Test the map functionality
echo.
pause