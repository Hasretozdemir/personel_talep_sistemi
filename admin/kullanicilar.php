<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

// Sadece yetki seviyesi 3 olanlar erisebilir
if (($_SESSION['admin_level'] ?? 0) < 3) {
    header('Location: index.php?msg=Bu+sayfaya+erismek+icin+yetkiniz+yok&type=danger');
    exit;
}

$conn = db_connect();
$msg = trim($_GET['msg'] ?? '');
$type = trim($_GET['type'] ?? 'success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $kullaniciAdi = trim($_POST['kullanici_adi'] ?? '');
        $sifre = trim($_POST['sifre'] ?? '');
        $adSoyad = trim($_POST['ad_soyad'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $yetkiSeviyesi = (int)($_POST['yetki_seviyesi'] ?? 1);
        
        if ($kullaniciAdi !== '' && $sifre !== '' && $adSoyad !== '') {
            // Şifre uzunluk kontrolü
            if (strlen($sifre) < 6) {
                header('Location: kullanicilar.php?msg=Sifre+en+az+6+karakter+olmali&type=danger');
                exit;
            }
            
            $hashedPassword = password_hash($sifre, PASSWORD_DEFAULT);
            $result = pg_query_params($conn, 
                'INSERT INTO admin_kullanicilar (kullanici_adi, sifre, ad_soyad, email, yetki_seviyesi) VALUES ($1, $2, $3, $4, $5)',
                [$kullaniciAdi, $hashedPassword, $adSoyad, $email, $yetkiSeviyesi]
            );
            
            if ($result) {
                header('Location: kullanicilar.php?msg=Yetkili+kullanici+eklendi&type=success');
                exit;
            } else {
                header('Location: kullanicilar.php?msg=Bu+kullanici+adi+zaten+kayitli&type=danger');
                exit;
            }
        }
        header('Location: kullanicilar.php?msg=Lutfen+tum+alanlari+doldurun&type=danger');
        exit;
    }
    
    if ($action === 'toggle_active') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $id !== $_SESSION['admin_id']) {
            $result = pg_query_params($conn, 
                'UPDATE admin_kullanicilar SET aktif = NOT aktif WHERE id = $1',
                [$id]
            );
            
            if ($result) {
                header('Location: kullanicilar.php?msg=Durum+guncellendi&type=success');
                exit;
            }
        } elseif ($id === $_SESSION['admin_id']) {
            header('Location: kullanicilar.php?msg=Kendi+hesabinizi+pasif+yapamazsiniz&type=danger');
            exit;
        }
        header('Location: kullanicilar.php?msg=Islem+basarisiz&type=danger');
        exit;
    }
    
    if ($action === 'change_password') {
        $id = (int)($_POST['id'] ?? 0);
        $yeniSifre = trim($_POST['yeni_sifre'] ?? '');
        
        if ($id > 0 && $yeniSifre !== '') {
            if (strlen($yeniSifre) < 6) {
                header('Location: kullanicilar.php?msg=Sifre+en+az+6+karakter+olmali&type=danger');
                exit;
            }
            
            $hashedPassword = password_hash($yeniSifre, PASSWORD_DEFAULT);
            $result = pg_query_params($conn, 
                'UPDATE admin_kullanicilar SET sifre = $1 WHERE id = $2',
                [$hashedPassword, $id]
            );
            
            if ($result) {
                header('Location: kullanicilar.php?msg=Sifre+degistirildi&type=success');
                exit;
            }
        }
        header('Location: kullanicilar.php?msg=Sifre+degistirilemedi&type=danger');
        exit;
    }
    
    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $adSoyad = trim($_POST['ad_soyad'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $yetkiSeviyesi = (int)($_POST['yetki_seviyesi'] ?? 1);
        
        if ($id > 0 && $adSoyad !== '') {
            $result = pg_query_params($conn, 
                'UPDATE admin_kullanicilar SET ad_soyad = $1, email = $2, yetki_seviyesi = $3 WHERE id = $4',
                [$adSoyad, $email, $yetkiSeviyesi, $id]
            );
            
            if ($result) {
                header('Location: kullanicilar.php?msg=Kullanici+guncellendi&type=success');
                exit;
            }
        }
        header('Location: kullanicilar.php?msg=Kullanici+guncellenemedi&type=danger');
        exit;
    }
}

$kullanicilar = pg_fetch_all(pg_query($conn, 'SELECT * FROM admin_kullanicilar ORDER BY id ASC')) ?: [];

// İstatistikler
$statsResult = pg_query($conn, 'SELECT COUNT(*) as toplam, COUNT(*) FILTER (WHERE aktif = true) as aktif, COUNT(*) FILTER (WHERE yetki_seviyesi = 3) as yonetici FROM admin_kullanicilar');
$stats = pg_fetch_assoc($statsResult) ?: ['toplam' => 0, 'aktif' => 0, 'yonetici' => 0];
?>
<!doctype html>
<html lang="tr" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gazi IT | Yetkili Kullanıcı Yönetimi</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="../assets/css/style.css" rel="stylesheet">
  <script src="../assets/js/theme.js"></script>

  <style>
    /* ── Tema Değişkenleri ── */
    :root {
      --font-family: 'Plus Jakarta Sans', sans-serif;
      --bg-body: #f8fafc;
      --bg-surface: #ffffff;
      --text-main: #0f172a;
      --text-muted: #64748b;
      --border-color: #e2e8f0;
      --primary: #2563eb;
      --primary-hover: #1d4ed8;
      --danger: #ef4444;
      --danger-hover: #dc2626;
      --danger-bg: #fef2f2;
      --success: #22c55e;
      --success-bg: #f0fdf4;
      --warning: #f59e0b;
      --warning-bg: #fffbeb;
      --info: #0ea5e9;
      --info-bg: #f0f9ff;
      --nav-bg: #ffffff;
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.05);
      --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
      --table-hover: #f1f5f9;
      --input-bg: #ffffff;
    }

    [data-theme="dark"] {
      --bg-body: #0f172a;
      --bg-surface: #1e293b;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --border-color: #334155;
      --primary: #3b82f6;
      --primary-hover: #60a5fa;
      --danger: #f87171;
      --danger-bg: rgba(239, 68, 68, 0.15);
      --success: #4ade80;
      --success-bg: rgba(34, 197, 94, 0.15);
      --warning: #fbbf24;
      --warning-bg: rgba(245, 158, 11, 0.15);
      --info: #38bdf8;
      --info-bg: rgba(14, 165, 233, 0.15);
      --nav-bg: #1e293b;
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.3);
      --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.4);
      --table-hover: #334155;
      --input-bg: #0f172a;
    }

    /* ── Karanlık Mod (Dark Mode) Kesin Ezmeleri ── */
    body.admin-body {
      font-family: var(--font-family);
      background-color: var(--bg-body);
      color: var(--text-main);
      transition: background-color 0.3s, color 0.3s;
      -webkit-font-smoothing: antialiased;
    }

    [data-theme="dark"] .admin-brand, [data-theme="dark"] .page-title,
    [data-theme="dark"] .stat-value, [data-theme="dark"] .adm-card-header h2,
    [data-theme="dark"] .form-label, [data-theme="dark"] .modal-title,
    [data-theme="dark"] .table.adm-table td, [data-theme="dark"] .table.adm-table th {
      color: var(--text-main) !important;
    }

    [data-theme="dark"] p.text-muted, [data-theme="dark"] .text-secondary,
    [data-theme="dark"] .stat-label, [data-theme="dark"] .table.adm-table td.text-muted,
    [data-theme="dark"] .form-control-modern::placeholder {
      color: var(--text-muted) !important;
    }

    [data-theme="dark"] .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }

    /* ── Navbar Tasarımı ── */
    .admin-navbar {
      background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color);
      padding: 1rem 0; position: sticky; top: 0; z-index: 1020; box-shadow: var(--shadow-sm);
      transition: background-color 0.3s, border-color 0.3s;
    }
    .admin-brand { font-weight: 800; font-size: 1.25rem; color: var(--text-main); text-decoration: none; display: flex; align-items: center; gap: 10px; }
    .brand-dot {
      background: linear-gradient(135deg, var(--primary), #8b5cf6); color: white !important; width: 32px; height: 32px;
      display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 1.1rem;
    }
    .btn-nav-ghost, .btn-ghost {
      color: var(--text-muted); font-weight: 600; padding: 0.5rem 0.8rem; border-radius: 8px; text-decoration: none;
      transition: all 0.2s; border: none; background: transparent; display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-nav-ghost:hover, .btn-ghost:hover { background-color: rgba(148, 163, 184, 0.1); color: var(--text-main); }
    .btn-nav-logout {
      color: var(--danger); font-weight: 600; padding: 0.5rem 0.8rem; border-radius: 8px;
      text-decoration: none; transition: all 0.2s; display: flex; align-items: center; gap: 6px;
    }
    .btn-nav-logout:hover { background-color: var(--danger-bg); }

    /* ── Sayfa Başlığı ve İstatistikler ── */
    .page-title { font-size: 1.75rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 12px; }
    .page-title i { color: var(--primary); }

    .stat-card {
      background-color: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem;
      display: flex; flex-direction: column; box-shadow: var(--shadow-sm); transition: transform 0.2s, box-shadow 0.2s, background-color 0.3s; height: 100%;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: grid; place-items: center; font-size: 1.4rem; margin-bottom: 1rem; }
    .si-blue { background: rgba(37, 99, 235, 0.1); color: var(--primary); }
    .si-green { background: var(--success-bg); color: var(--success); }
    .si-red { background: var(--danger-bg); color: var(--danger); }
    .stat-label { font-size: 0.9rem; font-weight: 600; margin-bottom: 0.25rem; }
    .stat-value { font-size: 2rem; font-weight: 800; line-height: 1; color: var(--text-main); }
    [data-theme="dark"] .sv-green { color: var(--success) !important; }
    [data-theme="dark"] .sv-red { color: var(--danger) !important; }
    .sv-green { color: var(--success); }
    .sv-red { color: var(--danger); }

    /* ── Tablo Tasarımı ── */
    .adm-card {
      background-color: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 16px;
      box-shadow: var(--shadow-md); overflow: hidden; transition: background-color 0.3s, border-color 0.3s;
    }
    .adm-card-header {
      padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background-color: rgba(0,0,0,0.01);
    }
    .adm-card-header h2 { font-size: 1.1rem; font-weight: 700; margin: 0; color: var(--text-main); display: flex; align-items: center; gap: 8px; }
    
    .adm-table { color: var(--text-main); margin-bottom: 0; }
    .adm-table th {
      background-color: transparent !important; color: var(--text-muted); font-weight: 600; font-size: 0.85rem;
      text-transform: uppercase; letter-spacing: 0.5px; padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color) !important;
    }
    .adm-table td {
      padding: 1rem 1.25rem; vertical-align: middle; border-bottom: 1px solid var(--border-color) !important;
      background-color: transparent !important; color: var(--text-main); font-weight: 500;
    }
    .adm-table tbody tr { transition: background-color 0.2s; }
    .adm-table tbody tr:hover td { background-color: var(--table-hover) !important; }
    .adm-table tbody tr:last-child td { border-bottom: none !important; }

    /* ── Rozetler ve Pill'ler ── */
    .badge-username { display: inline-block; background-color: var(--bg-body); border: 1px solid var(--border-color); padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.85rem; font-weight: 700; font-family: monospace; }
    .badge-you { display: inline-block; background-color: var(--info-bg); color: var(--info); padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-left: 6px; border: 1px solid rgba(14, 165, 233, 0.2); }
    
    .status-pill { padding: 0.35rem 0.75rem; border-radius: 50px; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
    .role-admin { background-color: var(--danger-bg); color: var(--danger); }
    .role-manager { background-color: var(--warning-bg); color: var(--warning); }
    .role-user { background-color: var(--info-bg); color: var(--info); }
    
    .status-active { color: var(--success); font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 5px;}
    .status-passive { color: var(--danger); font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 5px;}

    /* ── Butonlar ── */
    .btn-brand {
      background-color: var(--primary); color: #fff !important; border: none; font-weight: 600; padding: 0.6rem 1.2rem;
      border-radius: 8px; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-brand:hover { background-color: var(--primary-hover); transform: translateY(-1px); }
    
    /* Tablo Eylem Butonları */
    .btn-act {
      border: none; width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;
      transition: all 0.2s; cursor: pointer; font-size: 1rem; margin-right: 4px;
    }
    .btn-act-edit { background-color: rgba(37, 99, 235, 0.1); color: var(--primary); }
    .btn-act-edit:hover { background-color: var(--primary); color: #fff; }
    .btn-act-key { background-color: var(--warning-bg); color: var(--warning); }
    .btn-act-key:hover { background-color: var(--warning); color: #fff; }
    .btn-act-toggle-on { background-color: var(--success-bg); color: var(--success); }
    .btn-act-toggle-on:hover { background-color: var(--success); color: #fff; }
    .btn-act-toggle-off { background-color: var(--danger-bg); color: var(--danger); }
    .btn-act-toggle-off:hover { background-color: var(--danger); color: #fff; }

    /* ── Modal Tasarımı ── */
    .modal-content { background-color: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 16px; box-shadow: var(--shadow-lg); }
    .modal-header { border-bottom: 1px solid var(--border-color); padding: 1.5rem; background: rgba(0,0,0,0.01); }
    .modal-title { font-weight: 700; color: var(--text-main); display: flex; align-items: center; }
    .modal-body { padding: 1.5rem; }
    .modal-footer { border-top: 1px solid var(--border-color); padding: 1.25rem 1.5rem; background: rgba(0,0,0,0.01); }
    
    .form-control-modern {
      background-color: var(--input-bg) !important; border: 1px solid var(--border-color) !important;
      color: var(--text-main) !important; border-radius: 10px; padding: 0.75rem 1rem; transition: all 0.2s; font-size: 0.95rem;
    }
    .form-control-modern:focus { border-color: var(--primary) !important; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important; }
    .form-label { font-weight: 600; color: var(--text-main); margin-bottom: 0.4rem; font-size: 0.9rem; }

    /* Uyarılar */
    .alert { border-radius: 12px; font-weight: 500; border: none; }
    .alert-success { background-color: #dcfce7; color: #166534; }
    [data-theme="dark"] .alert-success { background-color: rgba(22, 101, 52, 0.2); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.2); }
    .alert-danger { background-color: var(--danger-bg); color: var(--danger); }
  </style>
</head>
<body class="admin-body">
  
  <nav class="admin-navbar">
    <div class="container d-flex align-items-center justify-content-between">
      <a class="admin-brand" href="index.php"><span class="brand-dot">G</span> GAZI IT PANEL</a>
      <div class="d-flex align-items-center gap-2">
        <a href="index.php" class="btn-nav-ghost"><i class="bi bi-grid"></i> Dashboard</a>
        <a href="gecmis.php" class="btn-nav-ghost"><i class="bi bi-clock-history"></i> Geçmiş</a>
        <a href="personeller.php" class="btn-nav-ghost"><i class="bi bi-person-badge"></i> Personeller</a>
        <a href="ayarlar.php" class="btn-nav-ghost"><i class="bi bi-gear"></i> Ayarlar</a>
        
        <button class="btn btn-nav-ghost" id="manualThemeToggle" title="Tema Değiştir">
          <i class="bi bi-moon-stars" id="themeIcon"></i>
        </button>
        
        <div class="vr mx-2" style="background-color: var(--border-color); width: 1px; height: 24px;"></div>
        <a href="logout.php" class="btn-nav-logout"><i class="bi bi-box-arrow-right"></i> Çıkış</a>
      </div>
    </div>
  </nav>

  <main class="container py-4">
    <?php if ($msg !== ''): ?>
      <div class="alert alert-<?= e($type) ?> mb-4 shadow-sm">
        <i class="bi <?= $type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i> <?= e($msg) ?>
      </div>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
      <div>
        <h1 class="page-title"><i class="bi bi-shield-lock-fill"></i> Yetkili Kullanıcı Yönetimi</h1>
        <p class="text-muted" style="margin-top: 0.5rem; margin-bottom: 0;">Sisteme giriş yapabilecek yönetici ve kullanıcıları buradan yapılandırın.</p>
      </div>
      <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-person-plus-fill"></i> Yeni Yetkili Ekle
      </button>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon si-blue"><i class="bi bi-people-fill"></i></div>
          <div class="stat-label">Toplam Yetkili</div>
          <div class="stat-value"><?= e((string)$stats['toplam']) ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon si-green"><i class="bi bi-check-circle-fill"></i></div>
          <div class="stat-label">Aktif Yetkili</div>
          <div class="stat-value sv-green"><?= e((string)$stats['aktif']) ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon si-red"><i class="bi bi-shield-fill-check"></i></div>
          <div class="stat-label">Sistem Yöneticisi</div>
          <div class="stat-value sv-red"><?= e((string)$stats['yonetici']) ?></div>
        </div>
      </div>
    </div>

    <section class="adm-card">
      <div class="adm-card-header">
        <h2><i class="bi bi-people-fill text-primary"></i> Kayıtlı Yöneticiler</h2>
      </div>
      <div class="table-responsive">
        <table class="table adm-table mb-0">
          <thead>
            <tr>
              <th style="width: 70px;">ID</th>
              <th>Kullanıcı Adı</th>
              <th>Ad Soyad</th>
              <th>E-Posta</th>
              <th>Yetki Seviyesi</th>
              <th>Durum</th>
              <th>Son Giriş</th>
              <th class="text-center" style="width: 140px;">İşlem</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($kullanicilar as $user): ?>
            <tr>
              <td class="text-muted"><strong>#<?= e((string)$user['id']) ?></strong></td>
              <td>
                <span class="badge-username"><?= e($user['kullanici_adi']) ?></span>
                <?php if ((int)$user['id'] === $_SESSION['admin_id']): ?>
                  <span class="badge-you">Siz</span>
                <?php endif; ?>
              </td>
              <td style="font-weight: 600;"><?= e($user['ad_soyad']) ?></td>
              <td><?= e($user['email'] ?: '-') ?></td>
              <td>
                <?php
                $yetkiText = match((int)$user['yetki_seviyesi']) {
                    3 => 'Sistem Yöneticisi',
                    2 => 'Birim Sorumlusu',
                    default => 'Kullanıcı'
                };
                $yetkiClass = match((int)$user['yetki_seviyesi']) {
                    3 => 'role-admin',
                    2 => 'role-manager',
                    default => 'role-user'
                };
                $yetkiIcon = match((int)$user['yetki_seviyesi']) {
                    3 => 'shield-fill-check',
                    2 => 'shield-check',
                    default => 'person-fill'
                };
                ?>
                <span class="status-pill <?= $yetkiClass ?>">
                  <i class="bi bi-<?= $yetkiIcon ?>"></i> <?= e($yetkiText) ?>
                </span>
              </td>
              <td>
                <?php $uAktif = !in_array($user['aktif'], ['f', false, '0', 0], true); ?>
                <?php if ($uAktif): ?>
                  <span class="status-active"><i class="bi bi-check-circle-fill"></i> Aktif</span>
                <?php else: ?>
                  <span class="status-passive"><i class="bi bi-x-circle-fill"></i> Pasif</span>
                <?php endif; ?>
              </td>
              <td class="text-muted" style="font-size: 0.9rem;">
                <?= $user['son_giris'] ? e(date('d.m.Y H:i', strtotime((string)$user['son_giris']))) : 'Giriş Yapılmadı' ?>
              </td>
              <td class="text-center">
                <div class="d-inline-flex">
                  <button type="button" class="btn-act btn-act-edit" title="Düzenle"
                    data-bs-toggle="modal" data-bs-target="#editUserModal"
                    data-id="<?= e((string)$user['id']) ?>"
                    data-adsoyad="<?= e($user['ad_soyad']) ?>"
                    data-email="<?= e($user['email'] ?? '') ?>"
                    data-yetki="<?= e((string)$user['yetki_seviyesi']) ?>">
                    <i class="bi bi-pencil-fill"></i>
                  </button>
                  
                  <button type="button" class="btn-act btn-act-key" title="Şifre Değiştir" 
                    data-bs-toggle="modal" data-bs-target="#changePasswordModal"
                    data-id="<?= e((string)$user['id']) ?>"
                    data-name="<?= e($user['ad_soyad']) ?>">
                    <i class="bi bi-key-fill"></i>
                  </button>
                  
                  <?php if ((int)$user['id'] !== $_SESSION['admin_id']): ?>
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="action" value="toggle_active">
                      <input type="hidden" name="id" value="<?= e((string)$user['id']) ?>">
                      <button type="submit" class="btn-act <?= $uAktif ? 'btn-act-toggle-off' : 'btn-act-toggle-on' ?>" 
                        title="<?= $uAktif ? 'Hesabı Pasif Et' : 'Hesabı Aktif Et' ?>"
                        onclick="return confirm('<?= e($user['ad_soyad']) ?> adlı kullanıcıyı <?= $uAktif ? 'pasif' : 'aktif' ?> yapmak istediğinize emin misiniz?')">
                        <i class="bi bi-<?= $uAktif ? 'x-circle' : 'check-circle' ?>-fill"></i>
                      </button>
                    </form>
                  <?php else: ?>
                    <button type="button" class="btn-act" disabled style="opacity: 0.3; cursor: not-allowed;" title="Kendi hesabınızı pasif edemezsiniz"><i class="bi bi-slash-circle"></i></button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-person-plus-fill text-primary me-2"></i> Yeni Yetkili Ekle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post">
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
              <label class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
              <input type="text" name="kullanici_adi" class="form-control form-control-modern" required placeholder="Örn: ahmet.yilmaz">
              <small class="text-muted" style="font-size: 0.8rem;">Sisteme giriş için kullanılacak</small>
            </div>
            <div class="mb-3">
              <label class="form-label">Şifre <span class="text-danger">*</span></label>
              <input type="password" name="sifre" class="form-control form-control-modern" required minlength="6" placeholder="En az 6 karakter">
            </div>
            <div class="mb-3">
              <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
              <input type="text" name="ad_soyad" class="form-control form-control-modern" required placeholder="Örn: Ahmet Yılmaz">
            </div>
            <div class="mb-3">
              <label class="form-label">E-Posta</label>
              <input type="email" name="email" class="form-control form-control-modern" placeholder="ornek@gazi.gov.tr">
            </div>
            <div class="mb-3">
              <label class="form-label">Yetki Seviyesi <span class="text-danger">*</span></label>
              <select name="yetki_seviyesi" class="form-select form-control-modern" required>
                <option value="1">Kullanıcı (Temel yetkiler)</option>
                <option value="2">Birim Sorumlusu (Gelişmiş yetkiler)</option>
                <option value="3">Sistem Yöneticisi (Tüm yetkiler)</option>
              </select>
              <div class="mt-2" style="font-size: 0.8rem; color: var(--text-muted); padding: 10px; background: var(--bg-body); border-radius: 8px; border: 1px solid var(--border-color);">
                <strong>Kullanıcı:</strong> Talep görüntüleme ve işlem yapma.<br>
                <strong>Birim Sorumlusu:</strong> Temel yetkiler + Raporlama.<br>
                <strong>Sistem Yöneticisi:</strong> Tam erişim + Kullanıcı yönetimi.
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">İptal</button>
            <button type="submit" class="btn btn-brand"><i class="bi bi-check2"></i> Kaydet</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-pencil-square text-primary me-2"></i> Kullanıcı Düzenle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post">
          <div class="modal-body">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editUserId">
            <div class="mb-3">
              <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
              <input type="text" name="ad_soyad" id="editAdSoyad" class="form-control form-control-modern" required>
            </div>
            <div class="mb-3">
              <label class="form-label">E-Posta</label>
              <input type="email" name="email" id="editEmail" class="form-control form-control-modern">
            </div>
            <div class="mb-3">
              <label class="form-label">Yetki Seviyesi <span class="text-danger">*</span></label>
              <select name="yetki_seviyesi" id="editYetkiSeviyesi" class="form-select form-control-modern" required>
                <option value="1">Kullanıcı</option>
                <option value="2">Birim Sorumlusu</option>
                <option value="3">Sistem Yöneticisi</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">İptal</button>
            <button type="submit" class="btn btn-brand"><i class="bi bi-check2"></i> Güncelle</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-key-fill text-warning me-2"></i> Şifre Değiştir</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post">
          <div class="modal-body">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="id" id="changePasswordId">
            <div class="alert alert-warning mb-3" style="font-size: 0.85rem; padding: 0.75rem 1rem;">
              <i class="bi bi-info-circle-fill me-2"></i>
              <strong id="changePasswordName"></strong> adlı kullanıcının şifresini değiştiriyorsunuz.
            </div>
            <div class="mb-3">
              <label class="form-label">Yeni Şifre <span class="text-danger">*</span></label>
              <input type="password" name="yeni_sifre" class="form-control form-control-modern" required minlength="6" placeholder="En az 6 karakter girin">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">İptal</button>
            <button type="submit" class="btn btn-brand" style="background-color: var(--warning); color: #000 !important;"><i class="bi bi-check2"></i> Şifreyi Değiştir</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Modal Veri Aktarımı
    var editModal = document.getElementById('editUserModal');
    if (editModal) {
      editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('editUserId').value = button.getAttribute('data-id');
        document.getElementById('editAdSoyad').value = button.getAttribute('data-adsoyad');
        document.getElementById('editEmail').value = button.getAttribute('data-email');
        document.getElementById('editYetkiSeviyesi').value = button.getAttribute('data-yetki');
      });
    }

    var changePasswordModal = document.getElementById('changePasswordModal');
    if (changePasswordModal) {
      changePasswordModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('changePasswordId').value = button.getAttribute('data-id');
        document.getElementById('changePasswordName').textContent = button.getAttribute('data-name');
      });
    }

    // Tema Değiştirme Scripti
    document.addEventListener('DOMContentLoaded', () => {
      const themeToggleBtn = document.getElementById('manualThemeToggle');
      const themeIcon = document.getElementById('themeIcon');
      const htmlEl = document.documentElement;

      const savedTheme = localStorage.getItem('theme') || 'light';
      htmlEl.setAttribute('data-theme', savedTheme);
      updateIcon(savedTheme);

      if(themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
          const currentTheme = htmlEl.getAttribute('data-theme');
          const newTheme = currentTheme === 'light' ? 'dark' : 'light';
          
          htmlEl.setAttribute('data-theme', newTheme);
          localStorage.setItem('theme', newTheme);
          updateIcon(newTheme);
        });
      }

      function updateIcon(theme) {
        if (!themeIcon) return;
        if (theme === 'dark') {
          themeIcon.classList.remove('bi-moon-stars');
          themeIcon.classList.add('bi-sun');
        } else {
          themeIcon.classList.remove('bi-sun');
          themeIcon.classList.add('bi-moon-stars');
        }
      }
    });
  </script>
</body>
</html>