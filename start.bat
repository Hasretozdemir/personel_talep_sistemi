@echo off
echo ========================================
echo   GAZI PERSONEL TALEP SISTEMI
echo ========================================
echo.

REM PHP kontrolu
set PHP_BIN=php
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    if exist "C:\xampp\php\php.exe" (
        set PHP_BIN="C:\xampp\php\php.exe"
        echo [OK] XAMPP PHP bulundu
    ) else (
        echo [HATA] PHP bulunamadi!
        echo Lufen bilgisayariniza PHP veya XAMPP yukleyin.
        pause
        exit /b 1
    )
) else (
    echo [OK] Sisteme kayitli PHP bulundu
)

echo [OK] PHP bulundu
echo.

REM PostgreSQL kontrolu
where psql >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [UYARI] PostgreSQL komut satiri bulunamadi
    echo Veritabani kurulumunu manuel yapmaniz gerekebilir
    echo.
) else (
    echo [OK] PostgreSQL bulundu
    echo.
)

echo Sunucu baslatiliyor...
echo.
echo Tarayicinizda su adresi acin:
echo   http://127.0.0.1:9000
echo.
echo Admin paneli:
echo   http://127.0.0.1:9000/admin/login.php
echo   Kullanici: admin
echo   Sifre: 1123
echo.
echo Sunucuyu durdurmak icin CTRL+C basin
echo ========================================
echo.

%PHP_BIN% -S 127.0.0.1:9000 -t .
