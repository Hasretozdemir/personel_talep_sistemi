# Gazi Personel Talep Sistemi - Kullanım Kılavuzu

## Hızlı Başlangıç

### 1. Kurulum (İlk Kez)

Windows için:
```bash
install.bat
```

Linux/Mac için:
```bash
chmod +x install.sh
./install.sh
```

### 2. Sunucuyu Başlatma

Windows için:
```bash
start.bat
```

Linux/Mac için:
```bash
chmod +x start.sh
./start.sh
```

### 3. Tarayıcıda Açma

Ana sayfa: http://127.0.0.1:9000
Admin paneli: http://127.0.0.1:9000/admin/login.php

## Personel Kullanımı

### Yeni Talep Oluşturma

1. Ana sayfayı açın (http://127.0.0.1:9000)
2. Sicil numaranızı girin
   - Daha önce talep oluşturduysanız, ad-soyad otomatik doldurulur
3. Ad-soyad bilginizi girin
4. Biriminizi seçin
5. Yetki talep ettiğiniz sistemi seçin
6. Talep notunuzu yazın (opsiyonel)
7. "Talebi Gönder" butonuna tıklayın

### Talep Durumu Sorgulama

1. "Durum Sorgula" butonuna tıklayın veya http://127.0.0.1:9000/sorgula.php adresine gidin
2. Sicil numaranızı girin
3. "Sorgula" butonuna tıklayın
4. Tüm taleplerinizi ve durumlarını görün:
   - 🟡 Beklemede: Talep henüz işleme alınmadı
   - 🟢 Onaylandı: Talep onaylandı
   - 🔴 Reddedildi: Talep reddedildi (ret nedeni gösterilir)

## Admin Kullanımı

### Giriş Yapma

1. http://127.0.0.1:9000/admin/login.php adresine gidin
2. Varsayılan giriş bilgileri:
   - Kullanıcı Adı: `admin`
   - Şifre: `1123`
3. "Sisteme Giriş Yap" butonuna tıklayın

### Dashboard (Ana Sayfa)

Dashboard'da şunları görebilirsiniz:
- **İstatistikler**: Toplam, beklemede, onaylanan ve reddedilen talep sayıları
- **Bekleyen Talepler**: İşlem bekleyen tüm talepler

#### Talep Onaylama
1. Bekleyen talepler listesinde ✓ (onay) butonuna tıklayın
2. Onay mesajını kabul edin
3. Talep otomatik olarak "Onaylandı" durumuna geçer

#### Talep Reddetme
1. Bekleyen talepler listesinde ✗ (red) butonuna tıklayın
2. Açılan pencerede ret nedenini yazın
3. "Reddet" butonuna tıklayın
4. Talep "Reddedildi" durumuna geçer ve personel ret nedenini görebilir

### Geçmiş Sayfası

Tüm talepleri görüntüleyin ve filtreleyin:

#### Filtreleme Seçenekleri
- **Başvuru Durumu**: Hepsi / Beklemede / Onaylandı / Reddedildi
- **Sicil No**: Belirli bir personelin taleplerine bakın
- **Başlangıç Tarihi**: Bu tarihten sonraki talepler
- **Bitiş Tarihi**: Bu tarihten önceki talepler

#### CSV Export
1. "CSV indir" butonuna tıklayın
2. Tüm talepler Excel'de açılabilir formatta indirilir
3. Dosya adı: `talepler_YYYYMMDD_HHMMSS.csv`

### Ayarlar Sayfası

Sistem birimlerini ve sistemlerini yönetin:

#### Yeni Birim Ekleme
1. Birimler bölümünde "Ekle" butonuna tıklayın
2. Birim adını girin (örn: "Radyoloji")
3. "Ekle" butonuna tıklayın

#### Yeni Sistem Ekleme
1. Sistemler bölümünde "Ekle" butonuna tıklayın
2. Sistem adını girin (örn: "VPN Erişimi")
3. "Ekle" butonuna tıklayın

#### Birim/Sistem Silme
1. Silmek istediğiniz öğenin yanındaki çöp kutusu ikonuna tıklayın
2. Onay mesajını kabul edin
3. Öğe silinir

⚠️ **Uyarı**: Üzerinde talep olan birim/sistem silinemez!

## Güvenlik

### Admin Şifresini Değiştirme

Ortam değişkeni ile:
```bash
set ADMIN_USERNAME=yeni_kullanici
set ADMIN_PASSWORD=yeni_sifre
```

Veya `admin/login.php` dosyasındaki varsayılan değerleri değiştirin.

### Veritabanı Şifresini Değiştirme

Ortam değişkeni ile:
```bash
set DB_PASSWORD=yeni_sifre
```

Veya `config/db.php` dosyasındaki varsayılan değeri değiştirin.

## Sorun Giderme

### PHP Bulunamadı Hatası

XAMPP kullanıyorsanız tam yolu belirtin:
```bash
"C:\xampp\php\php.exe" -S 127.0.0.1:9000 -t .
```

### PostgreSQL Bağlantı Hatası

1. PostgreSQL servisinin çalıştığından emin olun
2. Veritabanı şifresini kontrol edin
3. `config/db.php` dosyasındaki bağlantı bilgilerini kontrol edin

### Sayfa Açılmıyor

1. Sunucunun çalıştığından emin olun
2. Port 9000'in başka bir uygulama tarafından kullanılmadığını kontrol edin
3. Farklı bir port deneyin: `php -S 127.0.0.1:8080 -t .`

## Teknik Destek

Sorun yaşarsanız:
1. Tarayıcı konsolunu kontrol edin (F12)
2. PHP hata loglarını kontrol edin
3. PostgreSQL loglarını kontrol edin

## Özellikler

✅ Modern ve responsive tasarım
✅ Sicil numarası ile otomatik ad-soyad tamamlama
✅ Gerçek zamanlı talep durumu takibi
✅ Gelişmiş filtreleme ve arama
✅ CSV export
✅ Dinamik birim/sistem yönetimi
✅ Güvenli admin paneli
✅ SQL injection koruması
✅ XSS koruması
