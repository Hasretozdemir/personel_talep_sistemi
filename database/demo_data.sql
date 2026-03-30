-- Demo veri ekleme scripti
-- psql -U postgres -d personel_talep -f database/demo_data.sql

\connect personel_talep;

-- Demo talepler
INSERT INTO talepler (personel_ad_soyad, sicil_no, birim_id, sistem_id, talep_notu, durum, red_neden, tarih)
VALUES
    ('Ahmet Yilmaz', 'A12345', 1, 1, 'HBYS sistemine erisim yetkisi talep ediyorum. Hasta kayitlarini goruntulemem gerekiyor.', 0, NULL, NOW() - INTERVAL '2 hours'),
    ('Ayse Demir', 'B23456', 2, 3, 'Internet erisimi icin yetki talep ediyorum. Teknik dokumanlara erismem gerekiyor.', 1, NULL, NOW() - INTERVAL '1 day'),
    ('Mehmet Kaya', 'C34567', 3, 2, 'E-Nabiz sistemine giris yapabilmek icin kullanici adi ve sifre talep ediyorum.', 2, 'Sicil numarasi sistemde bulunamadi. Lutfen insan kaynaklari ile iletisime gecin.', NOW() - INTERVAL '2 days'),
    ('Fatma Ozturk', 'D45678', 4, 4, 'Kurumsal e-posta hesabi acilmasi talep ediyorum.', 0, NULL, NOW() - INTERVAL '3 hours'),
    ('Ali Celik', 'E56789', 5, 5, 'Muhasebe portalina erisim yetkisi talep ediyorum. Fatura islemleri yapacagim.', 1, NULL, NOW() - INTERVAL '3 days'),
    ('Zeynep Arslan', 'F67890', 1, 1, 'HBYS sifre sifirlama talep ediyorum. Eski sifremi unuttum.', 0, NULL, NOW() - INTERVAL '30 minutes'),
    ('Hasan Yildiz', 'G78901', 2, 3, 'VPN baglantisi icin yetki talep ediyorum.', 2, 'VPN yetkisi sadece uzaktan calisma onayı olan personele verilmektedir.', NOW() - INTERVAL '5 days'),
    ('Elif Sahin', 'H89012', 3, 2, 'E-Nabiz erisim yetkisi yenileme talep ediyorum.', 1, NULL, NOW() - INTERVAL '1 week');

-- Istatistikleri guncelle
ANALYZE talepler;

SELECT 
    'Demo veri basariyla eklendi!' as mesaj,
    COUNT(*) as toplam_talep,
    COUNT(*) FILTER (WHERE durum = 0) as beklemede,
    COUNT(*) FILTER (WHERE durum = 1) as onaylandi,
    COUNT(*) FILTER (WHERE durum = 2) as reddedildi
FROM talepler;
