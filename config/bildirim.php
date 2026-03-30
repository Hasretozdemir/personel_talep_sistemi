<?php
declare(strict_types=1);

/**
 * Bildirim Sistemi Fonksiyonları
 */

/**
 * Bildirim oluştur
 */
function bildirim_olustur($conn, int $kullaniciId, string $baslik, string $mesaj, string $tip = 'info', ?int $talepId = null): bool {
    $result = @pg_query_params($conn,
        'INSERT INTO bildirimler (kullanici_id, baslik, mesaj, tip, talep_id) VALUES ($1, $2, $3, $4, $5)',
        [$kullaniciId, $baslik, $mesaj, $tip, $talepId]
    );
    
    return $result !== false;
}

/**
 * Tüm adminlere bildirim gönder
 */
function bildirim_tum_adminlere($conn, string $baslik, string $mesaj, string $tip = 'info', ?int $talepId = null): void {
    $result = @pg_query($conn, 'SELECT id FROM admin_kullanicilar WHERE aktif = true');
    if (!$result) return;
    
    while ($admin = pg_fetch_assoc($result)) {
        bildirim_olustur($conn, (int)$admin['id'], $baslik, $mesaj, $tip, $talepId);
    }
}

/**
 * Okunmamış bildirim sayısı
 */
function bildirim_okunmamis_sayisi($conn, int $kullaniciId): int {
    $result = @pg_query_params($conn,
        'SELECT COUNT(*) FROM bildirimler WHERE kullanici_id = $1 AND okundu = false',
        [$kullaniciId]
    );
    if (!$result) return 0;
    
    return (int)pg_fetch_result($result, 0, 0);
}

/**
 * Bildirimleri getir
 */
function bildirim_listele($conn, int $kullaniciId, int $limit = 10): array {
    $result = pg_query_params($conn,
        'SELECT * FROM bildirimler WHERE kullanici_id = $1 ORDER BY olusturma_tarihi DESC LIMIT $2',
        [$kullaniciId, $limit]
    );
    
    return pg_fetch_all($result) ?: [];
}

/**
 * Bildirimi okundu olarak işaretle
 */
function bildirim_okundu_isaretle($conn, int $bildirimId): bool {
    $result = pg_query_params($conn,
        'UPDATE bildirimler SET okundu = true WHERE id = $1',
        [$bildirimId]
    );
    
    return $result !== false;
}

/**
 * Tüm bildirimleri okundu işaretle
 */
function bildirim_tumunu_okundu_isaretle($conn, int $kullaniciId): bool {
    $result = pg_query_params($conn,
        'UPDATE bildirimler SET okundu = true WHERE kullanici_id = $1 AND okundu = false',
        [$kullaniciId]
    );
    
    return $result !== false;
}

/**
 * Yeni talep bildirimi
 */
function bildirim_yeni_talep($conn, int $talepId, string $personelAd, string $sistemAdi): void {
    $baslik = 'Yeni Talep';
    $mesaj = "$personelAd tarafından $sistemAdi için yeni bir talep oluşturuldu.";
    
    bildirim_tum_adminlere($conn, $baslik, $mesaj, 'info', $talepId);
}

/**
 * Talep onaylandı bildirimi
 */
function bildirim_talep_onaylandi($conn, int $talepId, string $personelAd, string $adminAd): void {
    $baslik = 'Talep Onaylandı';
    $mesaj = "$personelAd adlı personelin talebi $adminAd tarafından onaylandı.";
    
    bildirim_tum_adminlere($conn, $baslik, $mesaj, 'success', $talepId);
}

/**
 * Talep reddedildi bildirimi
 */
function bildirim_talep_reddedildi($conn, int $talepId, string $personelAd, string $adminAd): void {
    $baslik = 'Talep Reddedildi';
    $mesaj = "$personelAd adlı personelin talebi $adminAd tarafından reddedildi.";
    
    bildirim_tum_adminlere($conn, $baslik, $mesaj, 'warning', $talepId);
}

/**
 * Talep notu eklendi bildirimi
 */
function bildirim_talep_notu($conn, int $talepId, string $personelAd, string $adminAd): void {
    $baslik = 'Talebe Not Eklendi';
    $mesaj = "$adminAd, $personelAd adlı personelin talebine not ekledi.";
    
    bildirim_tum_adminlere($conn, $baslik, $mesaj, 'info', $talepId);
}
