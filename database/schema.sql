-- PostgreSQL setup script (idempotent)
-- psql -U postgres -f database/schema.sql

SELECT 'CREATE DATABASE personel_talep'
WHERE NOT EXISTS (
    SELECT 1 FROM pg_database WHERE datname = 'personel_talep'
)\gexec

\connect personel_talep;

-- Birimler tablosu (once olusturulmali - foreign key icin)
CREATE TABLE IF NOT EXISTS birimler (
    id SERIAL PRIMARY KEY,
    birim_adi VARCHAR(120) NOT NULL UNIQUE
);

-- Sistemler tablosu
CREATE TABLE IF NOT EXISTS sistemler (
    id SERIAL PRIMARY KEY,
    sistem_adi VARCHAR(120) NOT NULL UNIQUE
);

-- Admin kullanicilar tablosu
CREATE TABLE IF NOT EXISTS admin_kullanicilar (
    id SERIAL PRIMARY KEY,
    kullanici_adi VARCHAR(50) NOT NULL UNIQUE,
    sifre VARCHAR(255) NOT NULL,
    ad_soyad VARCHAR(150) NOT NULL,
    email VARCHAR(150),
    yetki_seviyesi SMALLINT NOT NULL DEFAULT 1 CHECK (yetki_seviyesi IN (1, 2, 3)),
    aktif BOOLEAN NOT NULL DEFAULT true,
    olusturma_tarihi TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    son_giris TIMESTAMP
);

-- Personel tablosu (sicil dogrulama icin)
CREATE TABLE IF NOT EXISTS personeller (
    id SERIAL PRIMARY KEY,
    sicil_no VARCHAR(30) NOT NULL UNIQUE,
    ad_soyad VARCHAR(150) NOT NULL,
    birim_id INT NOT NULL REFERENCES birimler(id),
    email VARCHAR(150),
    telefon VARCHAR(20),
    aktif BOOLEAN NOT NULL DEFAULT true,
    kayit_tarihi TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Talepler tablosu
CREATE TABLE IF NOT EXISTS talepler (
    id BIGSERIAL PRIMARY KEY,
    personel_ad_soyad VARCHAR(150) NOT NULL,
    sicil_no VARCHAR(30) NOT NULL,
    birim_id INT NOT NULL REFERENCES birimler(id),
    sistem_id INT NOT NULL REFERENCES sistemler(id),
    talep_notu TEXT,
    durum SMALLINT NOT NULL DEFAULT 0 CHECK (durum IN (0, 1, 2)),
    red_neden TEXT,
    isleyen_admin_id INT REFERENCES admin_kullanicilar(id),
    tarih TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    islem_tarihi TIMESTAMP
);

-- Indeksler
CREATE INDEX IF NOT EXISTS idx_talepler_sicil_no ON talepler(sicil_no);
CREATE INDEX IF NOT EXISTS idx_talepler_durum_tarih ON talepler(durum, tarih DESC);
CREATE INDEX IF NOT EXISTS idx_personeller_sicil_no ON personeller(sicil_no);
CREATE INDEX IF NOT EXISTS idx_admin_kullanici_adi ON admin_kullanicilar(kullanici_adi);

-- Birimler
INSERT INTO birimler (birim_adi)
VALUES
    ('Dahiliye'),
    ('Bilgi Islem'),
    ('Arsiv'),
    ('Kardiyoloji'),
    ('Goz Hastaliklari'),
    ('Radyoloji'),
    ('Acil Servis'),
    ('Laboratuvar')
ON CONFLICT (birim_adi) DO NOTHING;

-- Sistemler
INSERT INTO sistemler (sistem_adi)
VALUES
    ('HBYS'),
    ('E-Nabiz'),
    ('Internet Yetkisi'),
    ('Kurumsal E-Posta'),
    ('Muhasebe Portali'),
    ('VPN Erisimi'),
    ('Dosya Sunucusu')
ON CONFLICT (sistem_adi) DO NOTHING;

-- Varsayilan admin kullanici (sifre: admin123)
INSERT INTO admin_kullanicilar (kullanici_adi, sifre, ad_soyad, email, yetki_seviyesi)
VALUES
    ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sistem Yoneticisi', 'admin@gazi.gov.tr', 3),
    ('birim_sorumlu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Birim Sorumlusu', 'birim@gazi.gov.tr', 2)
ON CONFLICT (kullanici_adi) DO NOTHING;

-- Ornek personeller
INSERT INTO personeller (sicil_no, ad_soyad, birim_id, email, telefon)
VALUES
    ('A12345', 'Ahmet Yilmaz', 1, 'ahmet.yilmaz@gazi.gov.tr', '0532 123 4567'),
    ('B23456', 'Ayse Demir', 2, 'ayse.demir@gazi.gov.tr', '0533 234 5678'),
    ('C34567', 'Mehmet Kaya', 3, 'mehmet.kaya@gazi.gov.tr', '0534 345 6789'),
    ('D45678', 'Fatma Ozturk', 4, 'fatma.ozturk@gazi.gov.tr', '0535 456 7890'),
    ('E56789', 'Ali Celik', 5, 'ali.celik@gazi.gov.tr', '0536 567 8901')
ON CONFLICT (sicil_no) DO NOTHING;
