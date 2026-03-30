<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) { return false; }
    echo json_encode(['found' => false, 'error' => "PHP Hatasi [$errno]: $errstr on line $errline"]);
    exit;
});

try {

$sicilNo = strtoupper(trim($_GET['sicil_no'] ?? ''));
if ($sicilNo === '') {
    echo json_encode(['found' => false, 'error' => 'Sicil numarasi bos olamaz']);
    exit;
}

$conn = db_connect();

// Personel tablosundan kontrol et
$result = pg_query_params($conn, 
    'SELECT p.ad_soyad, p.birim_id, b.birim_adi, p.email, p.telefon, p.aktif 
     FROM personeller p 
     INNER JOIN birimler b ON b.id = p.birim_id 
     WHERE p.sicil_no = $1', 
    [$sicilNo]
);

if ($result && pg_num_rows($result) > 0) {
    $row = pg_fetch_assoc($result);
    
    if ($row['aktif'] === 'f' || $row['aktif'] === false || $row['aktif'] === '0') {
        echo json_encode([
            'found' => false, 
            'error' => 'Bu sicil numarasi aktif degil. Lutfen insan kaynaklari ile iletisime gecin.'
        ]);
        exit;
    }
    
    echo json_encode([
        'found' => true,
        'ad_soyad' => $row['ad_soyad'],
        'birim_id' => (int)$row['birim_id'],
        'birim_adi' => $row['birim_adi'],
        'email' => $row['email'],
        'telefon' => $row['telefon']
    ]);
} else {
    echo json_encode([
        'found' => false,
        'error' => 'Bu sicil numarasi sistemde kayitli degil. Lutfen insan kaynaklari ile iletisime gecin.'
    ]);
}

} catch (Throwable $e) {
    echo json_encode(['found' => false, 'error' => 'Sistem Hatasi: ' . $e->getMessage()]);
}
