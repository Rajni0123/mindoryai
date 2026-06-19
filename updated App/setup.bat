@echo off
REM BlinkStudy Flutter - Quick setup script for Windows
REM Run this once if flutter is not recognized in a NEW terminal

set "FLUTTER_ROOT=C:\flutter"
set "PATH=%FLUTTER_ROOT%\bin;%PATH%"

cd /d "%~dp0"

echo.
echo === BlinkStudy Flutter Setup ===
echo Flutter: %FLUTTER_ROOT%
echo.

flutter --version
echo.
flutter pub get
echo.
echo Done! Next steps:
echo   flutter run              - Run on connected device/emulator
echo   flutter build apk        - Build release APK
echo   flutter build appbundle  - Build Play Store AAB
echo.
pause
