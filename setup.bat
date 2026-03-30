@echo off
chcp 65001 >nul
echo ========================================
echo   GAZI PERSONEL TALEP SISTEMI
echo   Otomatik Kurulum
echo ========================================
echo.

REM Veritabani bilgileri
set DB_HOST=127.0.0.1
set DB_PORT=5432
set DB_NAME=personel_talep
set DB_USER=postgres
set /p DB_PASS="PostgreSQL sifrenizi girin (varsayilan: 1123): "
if "%DB_PASS%"=="" set DB_PASS=1123

echo.
echo [1/4] Veritabani baglantisi test ediliyor...
echo.

REM PostgreSQL yolunu bul
set PSQL_PATH="C:\Program Files\PostgreSQL\14\bin\psql.exe"
if not exist %PSQL_PATH% (
    set PSQL_PATH="C:\Program Files\PostgreSQL\15\bin\psql.exe"
)
if not exist %PSQL_PATH% (
    set PSQL_PATH="C:\Program Files\PostgreSQL\16\bin\psql.exe"
)
if not exist %PSQL_PATH% (
    echo [HATA] PostgreSQL bulunamadi!
    echo Lutfen PostgreSQL yukleyin: https://www.postgresql.org/download/
    pause
    exit /b 1
)

echo [OK] PostgreSQL bulundu: %PSQL_PATH%
echo.

echo [2/4] Veritabani olusturuluyor...
echo.

REM Veritabanini olustur
set PGPASSWORD=%DB_PASS%
%PSQL_PATH% -h %DB_HOST% -p %DB_PORT% -U %DB_USER% -f database\schema.sql 2>nul

if %ERRORLEVEL% NEQ 0 (
    echo [HATA] Veritabani olusturulamadi!
    echo.
    echo Olasi nedenler:
    echo 1. PostgreSQL servisi calismiyordur
    echo 2. Sifre yanlis olabilir
    echo 3. Kullanici yetkisi olmayabilir
    echo.
    echo Manuel kurulum icin:
    echo   psql -U postgres -f database\schema.sql
    echo.
    pause
    exit /b 1
)

echo [OK] Veritabani basariyla olusturuldu!
echo.

echo [3/4] PHP baglanti testi yapiliyor...
echo.

REM PHP kontrolu
set PHP_BIN=php
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    if exist "C:\xampp\php\php.exe" (
        set PHP_BIN="C:\xampp\php\php.exe"
        echo [OK] XAMPP PHP bulundu
    ) else (
        echo [UYARI] PHP bulunamadi!
        echo Lutfen PHP kurun veya XAMPP calistirin. Pencereleri kapatabilirsiniz.
        echo.
    )
)

if not "%PHP_BIN%"=="" (
    %PHP_BIN% test_connection.php
    echo.
    echo Yeni ozellikler (tablolar) kuruluyor...
    %PHP_BIN% install_yeni_ozellikler.php >nul 2>nul
    echo.
)

echo [4/4] Kurulum tamamlandi!
echo.
echo ========================================
echo   SISTEM HAZIR!
echo ========================================
echo.
echo Sunucuyu baslatmak icin: start.bat
echo.
echo Veya manuel olarak:
echo   php -S 127.0.0.1:9000 -t .
echo.
echo Tarayicinizda acin:
echo   http://127.0.0.1:9000
echo.
echo Admin Paneli:
echo   http://127.0.0.1:9000/admin/login.php
echo   Kullanici: admin
echo   Sifre: admin123
echo.
echo ========================================
pause
