@echo off
setlocal

:: Konfigurasi waktu tunggu (dalam detik)
set INTERVAL=60

:loop
cls
echo ==========================================
echo      NP Analytics Auto-Sync System
echo ==========================================
echo.
echo [INFO] Menunggu perubahan... (Cek setiap %INTERVAL% detik)
echo [Time] %time%

:: Cek perubahan git
git status --porcelain > changes.tmp

:: Cek ukuran file changes.tmp
for %%I in (changes.tmp) do set size=%%~zI

if %size% gtr 0 (
    echo.
    echo [DETECTED] Perubahan ditemukan! Mulai sinkronisasi...
    
    :: Tambahkan semua file
    git add .
    
    :: Commit otomatis dengan timestamp
    git commit -m "Auto-sync: %date% %time%"
    
    :: Push ke server
    echo [PUSH] Mengirim ke GitHub...
    git push origin main
    
    echo.
    echo [SUCCESS] Sinkronisasi selesai pada %time%
) else (
    echo [INFO] Tidak ada perubahan baru.
)

:: Bersihkan file temp
if exist changes.tmp del changes.tmp

:: Tunggu sebelum cek lagi
timeout /t %INTERVAL% >nul
goto loop
