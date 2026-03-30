#!/bin/bash

echo "========================================"
echo "  GAZI PERSONEL TALEP SISTEMI"
echo "========================================"
echo ""

# PHP kontrolu
if ! command -v php &> /dev/null; then
    echo "[HATA] PHP bulunamadi!"
    echo "PHP yuklemek icin: sudo apt install php php-pgsql"
    exit 1
fi

echo "[OK] PHP bulundu: $(php -v | head -n 1)"
echo ""

# PostgreSQL kontrolu
if ! command -v psql &> /dev/null; then
    echo "[UYARI] PostgreSQL komut satiri bulunamadi"
    echo "Veritabani kurulumunu manuel yapmaniz gerekebilir"
    echo ""
else
    echo "[OK] PostgreSQL bulundu"
    echo ""
fi

echo "Sunucu baslatiliyor..."
echo ""
echo "Tarayicinizda su adresi acin:"
echo "  http://127.0.0.1:9000"
echo ""
echo "Admin paneli:"
echo "  http://127.0.0.1:9000/admin/login.php"
echo "  Kullanici: admin"
echo "  Sifre: 1123"
echo ""
echo "Sunucuyu durdurmak icin CTRL+C basin"
echo "========================================"
echo ""

php -S 127.0.0.1:9000 -t .
