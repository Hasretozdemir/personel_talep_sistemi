<?php
declare(strict_types=1);

echo "=== ADMIN SIFRE DUZELTME ===\n\n";

require_once __DIR__ . '/config/db.php';

$conn = db_connect();

if (!$conn) {
    echo "[HATA] Veritabani baglantisi kurulamadi!\n";
    exit(1);
}

echo "Veritabani baglantisi basarili.\n\n";

// Yeni şifre: admin123
$yeniSifre = 'admin123';
$hashedPassword = password_hash($yeniSifre, PASSWORD_DEFAULT);

echo "Yeni sifre hash'i olusturuluyor...\n";
echo "Sifre: $yeniSifre\n";
echo "Hash: $hashedPassword\n\n";

// Admin kullanıcısını güncelle
$result = pg_query_params($conn, 
    "UPDATE admin_kullanicilar SET sifre = $1 WHERE kullanici_adi = 'admin'",
    [$hashedPassword]
);

if ($result && pg_affected_rows($result) > 0) {
    echo "[BASARILI] Admin sifresi guncellendi!\n\n";
    echo "Giris bilgileri:\n";
    echo "Kullanici: admin\n";
    echo "Sifre: admin123\n\n";
    echo "Simdi admin/login.php sayfasina gidip giris yapabilirsiniz.\n";
} else {
    echo "[HATA] Sifre guncellenemedi!\n";
    echo "Admin kullanicisi var mi kontrol ediliyor...\n\n";
    
    $checkResult = pg_query($conn, "SELECT * FROM admin_kullanicilar WHERE kullanici_adi = 'admin'");
    
    if ($checkResult && pg_num_rows($checkResult) > 0) {
        echo "Admin kullanicisi mevcut ama guncellenemedi.\n";
    } else {
        echo "Admin kullanicisi bulunamadi. Olusturuluyor...\n";
        
        $insertResult = pg_query_params($conn,
            "INSERT INTO admin_kullanicilar (kullanici_adi, sifre, ad_soyad, email, yetki_seviyesi) 
             VALUES ($1, $2, $3, $4, $5)",
            ['admin', $hashedPassword, 'Sistem Yoneticisi', 'admin@gazi.gov.tr', 3]
        );
        
        if ($insertResult) {
            echo "[BASARILI] Admin kullanicisi olusturuldu!\n\n";
            echo "Giris bilgileri:\n";
            echo "Kullanici: admin\n";
            echo "Sifre: admin123\n";
        } else {
            echo "[HATA] Admin kullanicisi olusturulamadi!\n";
        }
    }
}

pg_close($conn);
