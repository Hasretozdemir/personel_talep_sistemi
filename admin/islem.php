<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../config/bildirim.php';

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';

if ($requestMethod !== 'POST') {
    header('Location: index.php');
    exit;
}

$conn = db_connect();
$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
$adminId = $_SESSION['admin_id'] ?? null;
$adminAd = $_SESSION['admin_name'] ?? 'Admin';

if ($id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    header('Location: index.php?msg=Gecersiz+islem&type=danger');
    exit;
}

// Talep bilgilerini al
$talepResult = pg_query_params($conn,
    'SELECT t.*, b.birim_adi, s.sistem_adi, p.email 
     FROM talepler t
     INNER JOIN birimler b ON b.id = t.birim_id
     INNER JOIN sistemler s ON s.id = t.sistem_id
     LEFT JOIN personeller p ON p.sicil_no = t.sicil_no
     WHERE t.id = $1',
    [$id]
);
$talep = pg_fetch_assoc($talepResult);

if (!$talep) {
    header('Location: index.php?msg=Talep+bulunamadi&type=danger');
    exit;
}

if ($action === 'approve') {
    $sql = 'UPDATE talepler SET durum = 1, isleyen_admin_id = $1, islem_tarihi = NOW() WHERE id = $2';
    $result = pg_query_params($conn, $sql, [$adminId, $id]);
    
    if ($result) {
        // Talep geçmişine kaydet
        @pg_query_params($conn,
            'INSERT INTO talep_gecmisi (talep_id, admin_id, eski_durum, yeni_durum, aciklama) VALUES ($1, $2, $3, $4, $5)',
            [$id, $adminId, 0, 1, 'Talep onaylandi']
        );
        
        // Bildirim oluştur
        bildirim_talep_onaylandi($conn, $id, $talep['personel_ad_soyad'], $adminAd);
        
        // E-posta gönder
        $talep['admin_ad'] = $adminAd;
        email_talep_onaylandi($talep);
        
        header('Location: index.php?msg=Talep+onaylandi&type=success');
        exit;
    }
} else {
    $redNeden = trim($_POST['red_neden'] ?? '');
    
    if ($redNeden === '') {
        header('Location: index.php?msg=Ret+nedeni+bos+olamaz&type=danger');
        exit;
    }
    
    $sql = 'UPDATE talepler SET durum = 2, red_neden = $1, isleyen_admin_id = $2, islem_tarihi = NOW() WHERE id = $3';
    $result = pg_query_params($conn, $sql, [$redNeden, $adminId, $id]);
    
    if ($result) {
        // Talep geçmişine kaydet
        @pg_query_params($conn,
            'INSERT INTO talep_gecmisi (talep_id, admin_id, eski_durum, yeni_durum, aciklama) VALUES ($1, $2, $3, $4, $5)',
            [$id, $adminId, 0, 2, 'Talep reddedildi: ' . $redNeden]
        );
        
        // Bildirim oluştur
        bildirim_talep_reddedildi($conn, $id, $talep['personel_ad_soyad'], $adminAd);
        
        // E-posta gönder
        email_talep_reddedildi($talep, $redNeden);
        
        header('Location: index.php?msg=Talep+reddedildi&type=success');
        exit;
    }
}

header('Location: index.php?msg=Talep+guncellenemedi&type=danger');
exit;
