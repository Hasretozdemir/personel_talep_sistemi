-- Yeni Özellikler için Veritabanı Güncellemeleri

-- 1. Talep Notları Tablosu
CREATE TABLE IF NOT EXISTS talep_notlari (
    id SERIAL PRIMARY KEY,
    talep_id BIGINT NOT NULL REFERENCES talepler(id) ON DELETE CASCADE,
    admin_id INT NOT NULL REFERENCES admin_kullanicilar(id),
    not_metni TEXT NOT NULL,
    olusturma_tarihi TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_talep_notlari_talep_id ON talep_notlari(talep_id);

-- 2. Talep Geçmişi Tablosu (Audit Log)
CREATE TABLE IF NOT EXISTS talep_gecmisi (
    id SERIAL PRIMARY KEY,
    talep_id BIGINT NOT NULL REFERENCES talepler(id) ON DELETE CASCADE,
    admin_id INT REFERENCES admin_kullanicilar(id),
    eski_durum SMALLINT,
    yeni_durum SMALLINT NOT NULL,
    aciklama TEXT,
    islem_tarihi TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_talep_gecmisi_talep_id ON talep_gecmisi(talep_id);

-- 3. Bildirimler Tablosu
CREATE TABLE IF NOT EXISTS bildirimler (
    id SERIAL PRIMARY KEY,
    kullanici_id INT NOT NULL REFERENCES admin_kullanicilar(id) ON DELETE CASCADE,
    baslik VARCHAR(200) NOT NULL,
    mesaj TEXT NOT NULL,
    tip VARCHAR(20) NOT NULL DEFAULT 'info', -- info, success, warning, danger
    talep_id BIGINT REFERENCES talepler(id) ON DELETE CASCADE,
    okundu BOOLEAN NOT NULL DEFAULT false,
    olusturma_tarihi TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_bildirimler_kullanici_id ON bildirimler(kullanici_id);
CREATE INDEX IF NOT EXISTS idx_bildirimler_okundu ON bildirimler(okundu);

-- 4. E-posta Ayarları Tablosu
CREATE TABLE IF NOT EXISTS email_ayarlari (
    id SERIAL PRIMARY KEY,
    smtp_host VARCHAR(100) NOT NULL DEFAULT 'smtp.gmail.com',
    smtp_port INT NOT NULL DEFAULT 587,
    smtp_kullanici VARCHAR(150) NOT NULL,
    smtp_sifre VARCHAR(255) NOT NULL,
    gonderici_email VARCHAR(150) NOT NULL,
    gonderici_ad VARCHAR(100) NOT NULL DEFAULT 'Gazi IT',
    aktif BOOLEAN NOT NULL DEFAULT false,
    olusturma_tarihi TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 5. E-posta Bildirimleri Ayarları (Kullanıcı bazlı)
CREATE TABLE IF NOT EXISTS kullanici_email_tercihleri (
    id SERIAL PRIMARY KEY,
    kullanici_id INT NOT NULL REFERENCES admin_kullanicilar(id) ON DELETE CASCADE,
    yeni_talep BOOLEAN NOT NULL DEFAULT true,
    talep_onaylandi BOOLEAN NOT NULL DEFAULT true,
    talep_reddedildi BOOLEAN NOT NULL DEFAULT true,
    talep_notu BOOLEAN NOT NULL DEFAULT true,
    gunluk_ozet BOOLEAN NOT NULL DEFAULT false,
    UNIQUE(kullanici_id)
);

-- Varsayılan e-posta tercihleri (mevcut kullanıcılar için)
INSERT INTO kullanici_email_tercihleri (kullanici_id)
SELECT id FROM admin_kullanicilar
ON CONFLICT (kullanici_id) DO NOTHING;

-- 6. SLA (Service Level Agreement) Ayarları
CREATE TABLE IF NOT EXISTS sla_ayarlari (
    id SERIAL PRIMARY KEY,
    sistem_id INT NOT NULL REFERENCES sistemler(id) ON DELETE CASCADE,
    hedef_sure_saat INT NOT NULL DEFAULT 24,
    uyari_sure_saat INT NOT NULL DEFAULT 20,
    aktif BOOLEAN NOT NULL DEFAULT true,
    UNIQUE(sistem_id)
);

-- Varsayılan SLA ayarları
INSERT INTO sla_ayarlari (sistem_id, hedef_sure_saat, uyari_sure_saat)
SELECT id, 24, 20 FROM sistemler
ON CONFLICT (sistem_id) DO NOTHING;

-- 7. Talep istatistikleri için view
CREATE OR REPLACE VIEW talep_istatistikleri AS
SELECT 
    DATE_TRUNC('day', tarih) as gun,
    COUNT(*) as toplam,
    COUNT(*) FILTER (WHERE durum = 0) as beklemede,
    COUNT(*) FILTER (WHERE durum = 1) as onaylandi,
    COUNT(*) FILTER (WHERE durum = 2) as reddedildi,
    AVG(EXTRACT(EPOCH FROM (islem_tarihi - tarih))/3600) FILTER (WHERE islem_tarihi IS NOT NULL) as ortalama_sure_saat
FROM talepler
GROUP BY DATE_TRUNC('day', tarih)
ORDER BY gun DESC;

-- 8. Birim bazlı istatistikler için view
CREATE OR REPLACE VIEW birim_istatistikleri AS
SELECT 
    b.id,
    b.birim_adi,
    COUNT(t.id) as toplam_talep,
    COUNT(*) FILTER (WHERE t.durum = 0) as beklemede,
    COUNT(*) FILTER (WHERE t.durum = 1) as onaylandi,
    COUNT(*) FILTER (WHERE t.durum = 2) as reddedildi
FROM birimler b
LEFT JOIN talepler t ON t.birim_id = b.id
GROUP BY b.id, b.birim_adi
ORDER BY toplam_talep DESC;

-- 9. Sistem bazlı istatistikler için view
CREATE OR REPLACE VIEW sistem_istatistikleri AS
SELECT 
    s.id,
    s.sistem_adi,
    COUNT(t.id) as toplam_talep,
    COUNT(*) FILTER (WHERE t.durum = 0) as beklemede,
    COUNT(*) FILTER (WHERE t.durum = 1) as onaylandi,
    COUNT(*) FILTER (WHERE t.durum = 2) as reddedildi,
    AVG(EXTRACT(EPOCH FROM (t.islem_tarihi - t.tarih))/3600) FILTER (WHERE t.islem_tarihi IS NOT NULL) as ortalama_sure_saat
FROM sistemler s
LEFT JOIN talepler t ON t.sistem_id = s.id
GROUP BY s.id, s.sistem_adi
ORDER BY toplam_talep DESC;

-- Başarılı mesajı
SELECT 'Yeni özellikler için tablolar başarıyla oluşturuldu!' as mesaj;
