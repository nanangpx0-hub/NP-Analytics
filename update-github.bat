@echo off
echo =================================
echo Git Update Helper - NP Analytics
echo =================================
echo.

:: 1. Cek status
echo Checking status...
git status
echo.

:: 2. Tanya pesan commit
set /p commit_msg="Masukkan pesan perubahan (kemudian tekan Enter): "

:: Jika pesan kosong, gunakan default
if "%commit_msg%"=="" set commit_msg="Update rutin"

echo.
echo Menambahkan file...
git add .
echo.

echo Menyimpan perubahan...
git commit -m "%commit_msg%"
echo.

echo Mengupload ke GitHub...
git push

echo.
echo =================================
echo Selesai!
echo =================================
pause
