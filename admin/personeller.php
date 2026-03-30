<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

$conn = db_connect();
$msg = trim($_GET['msg'] ?? '');
$type = trim($_GET['type'] ?? 'success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $sicilNo = strtoupper(trim($_POST['sicil_no'] ?? ''));
        $adSoyad = trim($_POST['ad_soyad'] ?? '');
        $birimId = (int)($_POST['birim_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $telefon = trim($_POST['telefon'] ?? '');
        
        if ($sicilNo !== '' && $adSoyad !== '' && $birimId > 0) {
            $result = pg_query_params($conn, 
                'INSERT INTO personeller (sicil_no, ad_soyad, birim_id, email, telefon) VALUES ($1, $2, $3, $4, $5)',
                [$sicilNo, $adSoyad, $birimId, $email, $telefon]
            );
            
            if ($result) {
                header('Location: personeller.php?msg=Personel+eklendi&type=success');
                exit;
            } else {
                header('Location: personeller.php?msg=Bu+sicil+numarasi+zaten+kayitli&type=danger');
                exit;
            }
        }
        header('Location: personeller.php?msg=Lutfen+tum+alanlari+doldurun&type=danger');
        exit;
    }
    
    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $sicilNo = strtoupper(trim($_POST['sicil_no'] ?? ''));
        $adSoyad = trim($_POST['ad_soyad'] ?? '');
        $birimId = (int)($_POST['birim_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $telefon = trim($_POST['telefon'] ?? '');
        
        if ($id > 0 && $sicilNo !== '' && $adSoyad !== '' && $birimId > 0) {
            $result = pg_query_params($conn, 
                'UPDATE personeller SET sicil_no = $1, ad_soyad = $2, birim_id = $3, email = $4, telefon = $5 WHERE id = $6',
                [$sicilNo, $adSoyad, $birimId, $email, $telefon, $id]
            );
            
            if ($result) {
                header('Location: personeller.php?msg=Personel+guncellendi&type=success');
                exit;
            }
        }
        header('Location: personeller.php?msg=Personel+guncellenemedi&type=danger');
        exit;
    }
    
    if ($action === 'toggle_active') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $result = pg_query_params($conn, 
                'UPDATE personeller SET aktif = NOT aktif WHERE id = $1',
                [$id]
            );
            
            if ($result) {
                header('Location: personeller.php?msg=Durum+guncellendi&type=success');
                exit;
            }
        }
        header('Location: personeller.php?msg=Islem+basarisiz&type=danger');
        exit;
    }
    
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Önce bu personele ait talep var mı kontrol et
            $checkResult = pg_query_params($conn, 'SELECT COUNT(*) FROM talepler WHERE sicil_no = (SELECT sicil_no FROM personeller WHERE id = $1)', [$id]);
            $count = pg_fetch_result($checkResult, 0, 0);
            
            if ($count > 0) {
                header('Location: personeller.php?msg=Bu+personele+ait+talepler+var,+silinemez&type=danger');
                exit;
            }
            
            $result = pg_query_params($conn, 'DELETE FROM personeller WHERE id = $1', [$id]);
            
            if ($result) {
                header('Location: personeller.php?msg=Personel+silindi&type=success');
                exit;
            }
        }
        header('Location: personeller.php?msg=Personel+silinemedi&type=danger');
        exit;
    }
}

// Filtreleme
$filterSicil = strtoupper(trim($_GET['sicil'] ?? ''));
$filterBirim = (int)($_GET['birim'] ?? 0);
$filterDurum = $_GET['durum'] ?? '';
$filterArama = trim($_GET['arama'] ?? '');

$conditions = [];
$params = [];
$idx = 1;

if ($filterSicil !== '') {
    $conditions[] = "p.sicil_no ILIKE $" . $idx;
    $params[] = '%' . $filterSicil . '%';
    $idx++;
}

if ($filterBirim > 0) {
    $conditions[] = "p.birim_id = $" . $idx;
    $params[] = $filterBirim;
    $idx++;
}

if ($filterDurum !== '') {
    $conditions[] = "p.aktif = $" . $idx;
    $params[] = $filterDurum === '1' ? 't' : 'f';
    $idx++;
}

if ($filterArama !== '') {
    $conditions[] = "p.ad_soyad ILIKE $" . $idx;
    $params[] = '%' . $filterArama . '%';
    $idx++;
}

$where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';
$sql = "SELECT p.*, b.birim_adi FROM personeller p 
        INNER JOIN birimler b ON b.id = p.birim_id 
        $where
        ORDER BY p.id DESC";

$result = $params ? pg_query_params($conn, $sql, $params) : pg_query($conn, $sql);
$personeller = $result ? (pg_fetch_all($result) ?: []) : [];

$birimler = pg_fetch_all(pg_query($conn, 'SELECT id, birim_adi FROM birimler ORDER BY birim_adi ASC')) ?: [];

// İstatistikler
$statsResult = pg_query($conn, 'SELECT COUNT(*) as toplam, COUNT(*) FILTER (WHERE aktif = true) as aktif, COUNT(*) FILTER (WHERE aktif = false) as pasif FROM personeller');
$stats = pg_fetch_assoc($statsResult) ?: ['toplam' => 0, 'aktif' => 0, 'pasif' => 0];
?>
<!doctype html>
<html lang="tr" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gazi IT | Personel Yönetimi</title>
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
      --danger-bg: #fef2f2;
      --success: #22c55e;
      --success-bg: #f0fdf4;
      --nav-bg: #ffffff;
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.05);
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
      --danger-bg: rgba(239, 68, 68, 0.1);
      --success: #4ade80;
      --success-bg: rgba(34, 197, 94, 0.1);
      --nav-bg: #1e293b;
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.3);
      --table-hover: #334155;
      --input-bg: #0f172a;
    }

    /* ── Bootstrap Renk Ezmeleri (Karanlık Mod İçin) ── */
    body.admin-body {
      font-family: var(--font-family);
      background-color: var(--bg-body);
      color: var(--text-main);
      transition: background-color 0.3s, color 0.3s;
      -webkit-font-smoothing: antialiased;
    }

    [data-theme="dark"] .admin-brand,
    [data-theme="dark"] .page-title,
    [data-theme="dark"] .stat-value,
    [data-theme="dark"] .filter-card h3,
    [data-theme="dark"] .adm-card-header h2,
    [data-theme="dark"] .form-label,
    [data-theme="dark"] .modal-title,
    [data-theme="dark"] .table.adm-table td,
    [data-theme="dark"] .text-secondary {
      color: var(--text-main) !important;
    }

    [data-theme="dark"] p.text-muted,
    [data-theme="dark"] .filter-label,
    [data-theme="dark"] .stat-label,
    [data-theme="dark"] .table.adm-table th,
    [data-theme="dark"] .form-control-modern::placeholder {
      color: var(--text-muted) !important;
    }

    [data-theme="dark"] .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }

    /* ── Navbar Tasarımı ── */
    .admin-navbar {
      background-color: var(--nav-bg);
      border-bottom: 1px solid var(--border-color);
      padding: 1rem 0;
      position: sticky;
      top: 0;
      z-index: 1020;
      box-shadow: var(--shadow-sm);
      transition: background-color 0.3s, border-color 0.3s;
    }
    .admin-brand {
      font-weight: 800;
      font-size: 1.25rem;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .brand-dot {
      background: linear-gradient(135deg, var(--primary), #8b5cf6);
      color: white !important;
      width: 32px; height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      font-size: 1.1rem;
    }
    .btn-nav-ghost, .btn-ghost {
      color: var(--text-muted);
      font-weight: 600;
      padding: 0.5rem 0.8rem;
      border-radius: 8px;
      text-decoration: none;
      transition: all 0.2s;
      border: none;
      background: transparent;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .btn-nav-ghost:hover, .btn-ghost:hover {
      background-color: rgba(148, 163, 184, 0.1);
      color: var(--text-main);
    }
    .btn-nav-logout {
      color: var(--danger);
      font-weight: 600;
      padding: 0.5rem 0.8rem;
      border-radius: 8px;
      text-decoration: none;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .btn-nav-logout:hover { background-color: var(--danger-bg); }

    /* ── Sayfa Başlığı ── */
    .page-title {
      font-size: 1.75rem;
      font-weight: 800;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .page-title i { color: var(--primary); }

    /* ── İstatistik Kartları ── */
    .stat-card {
      background-color: var(--bg-surface);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 1.5rem;
      display: flex;
      flex-direction: column;
      box-shadow: var(--shadow-sm);
      transition: transform 0.2s, box-shadow 0.2s, background-color 0.3s;
      height: 100%;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
    .stat-icon {
      width: 48px; height: 48px;
      border-radius: 12px;
      display: grid; place-items: center;
      font-size: 1.4rem;
      margin-bottom: 1rem;
    }
    .si-blue { background: rgba(37, 99, 235, 0.1); color: var(--primary); }
    .si-green { background: var(--success-bg); color: var(--success); }
    .si-red { background: var(--danger-bg); color: var(--danger); }
    .stat-label { font-size: 0.9rem; font-weight: 600; margin-bottom: 0.25rem; }
    .stat-value { font-size: 2rem; font-weight: 800; line-height: 1; }
    
    [data-theme="dark"] .sv-green { color: var(--success) !important; }
    [data-theme="dark"] .sv-red { color: var(--danger) !important; }
    .sv-green { color: var(--success); }
    .sv-red { color: var(--danger); }

    /* ── Filtre Kartı ── */
    .filter-card {
      background-color: var(--bg-surface);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: var(--shadow-sm);
      transition: background-color 0.3s, border-color 0.3s;
    }
    .filter-label { font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem; display: block; text-transform: uppercase; letter-spacing: 0.5px;}

    /* ── Kart ve Tablo Tasarımı ── */
    .adm-card {
      background-color: var(--bg-surface);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      box-shadow: var(--shadow-md);
      overflow: hidden;
      transition: background-color 0.3s, border-color 0.3s;
    }
    .adm-table { margin-bottom: 0; }
    .adm-table th {
      background-color: transparent !important;
      color: var(--text-muted);
      font-weight: 600;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 1rem 1.25rem;
      border-bottom: 1px solid var(--border-color) !important;
    }
    .adm-table td {
      padding: 1rem 1.25rem;
      vertical-align: middle;
      border-bottom: 1px solid var(--border-color) !important;
      background-color: transparent !important;
    }
    .adm-table tbody tr { transition: background-color 0.2s; }
    .adm-table tbody tr:hover td { background-color: var(--table-hover) !important; }
    .adm-table tbody tr:last-child td { border-bottom: none !important; }

    /* ── Badges & Pills ── */
    .badge-birim {
      background-color: var(--bg-body);
      border: 1px solid var(--border-color);
      padding: 0.35rem 0.6rem;
      border-radius: 6px;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-main);
      font-family: monospace;
      letter-spacing: 0.5px;
    }
    .badge-sistem {
      background-color: rgba(37, 99, 235, 0.08);
      color: var(--primary);
      padding: 0.35rem 0.6rem;
      border-radius: 6px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    .status-pill {
      padding: 0.35rem 0.75rem;
      border-radius: 50px;
      font-size: 0.8rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .status-1 { background-color: var(--success-bg); color: var(--success); }
    .status-2 { background-color: var(--danger-bg); color: var(--danger); }

    /* ── Butonlar ── */
    .btn-brand {
      background-color: var(--primary);
      color: #fff !important;
      border: none;
      font-weight: 600;
      padding: 0.6rem 1.2rem;
      border-radius: 8px;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      justify-content: center;
    }
    .btn-brand:hover { background-color: var(--primary-hover); transform: translateY(-1px); }
    
    .btn-tbl-approve, .btn-tbl-reject {
      border: none;
      width: 34px; height: 34px;
      border-radius: 8px;
      display: inline-flex; align-items: center; justify-content: center;
      transition: all 0.2s; cursor: pointer;
    }
    .btn-tbl-approve { background-color: rgba(37, 99, 235, 0.1); color: var(--primary); }
    .btn-tbl-approve:hover { background-color: var(--primary); color: #fff; }
    .btn-tbl-reject { background-color: var(--danger-bg); color: var(--danger); }
    .btn-tbl-reject:hover { background-color: var(--danger); color: #fff; }

    /* ── Modallar & Form Elemanları ── */
    .modal-content {
      background-color: var(--bg-surface);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1);
    }
    .modal-header { border-bottom: 1px solid var(--border-color); padding: 1.5rem; }
    .modal-title { font-weight: 700; }
    .modal-body { padding: 1.5rem; }
    .modal-footer { border-top: 1px solid var(--border-color); padding: 1.25rem 1.5rem; }
    
    .form-control-modern {
      background-color: var(--input-bg) !important;
      border: 1px solid var(--border-color) !important;
      color: var(--text-main) !important;
      border-radius: 10px;
      padding: 0.75rem 1rem;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-control-modern:focus {
      border-color: var(--primary) !important;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
    }
    .form-label { font-weight: 600; margin-bottom: 0.5rem; }

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
        <a href="ayarlar.php" class="btn-nav-ghost"><i class="bi bi-gear"></i> Ayarlar</a>
        <?php if (($_SESSION['admin_level'] ?? 0) >= 3): ?>
          <a href="kullanicilar.php" class="btn-nav-ghost"><i class="bi bi-people"></i> Kullanıcılar</a>
        <?php endif; ?>
        
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
        <h1 class="page-title"><i class="bi bi-person-badge-fill"></i> Personel Veritabanı</h1>
        <p class="text-muted" style="margin-top: 0.5rem; margin-bottom: 0;">Tüm hastane personellerini yönetin (Sicil numaralı çalışanlar)</p>
      </div>
      <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#addPersonelModal">
        <i class="bi bi-person-plus-fill"></i> Yeni Personel Ekle
      </button>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon si-blue"><i class="bi bi-people-fill"></i></div>
          <div class="stat-label">Toplam Personel</div>
          <div class="stat-value"><?= e((string)$stats['toplam']) ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon si-green"><i class="bi bi-check-circle-fill"></i></div>
          <div class="stat-label">Aktif Personel</div>
          <div class="stat-value sv-green"><?= e((string)$stats['aktif']) ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon si-red"><i class="bi bi-x-circle-fill"></i></div>
          <div class="stat-label">Pasif Personel</div>
          <div class="stat-value sv-red"><?= e((string)$stats['pasif']) ?></div>
        </div>
      </div>
    </div>

    <section class="filter-card mb-4">
      <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
        <i class="bi bi-funnel text-primary"></i> Arama ve Filtreleme
      </h3>
      <form method="get" class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="filter-label">Ad Soyad</label>
          <input type="text" name="arama" class="form-control form-control-modern" value="<?= e($filterArama) ?>" placeholder="Personel ara...">
        </div>
        <div class="col-md-3">
          <label class="filter-label">Sicil No</label>
          <input type="text" name="sicil" class="form-control form-control-modern" value="<?= e($filterSicil) ?>" placeholder="Sicil ara..." style="text-transform: uppercase;">
        </div>
        <div class="col-md-2">
          <label class="filter-label">Birim</label>
          <select name="birim" class="form-select form-control-modern">
            <option value="">Tüm Birimler</option>
            <?php foreach ($birimler as $birim): ?>
              <option value="<?= e((string)$birim['id']) ?>" <?= $filterBirim === (int)$birim['id'] ? 'selected' : '' ?>><?= e($birim['birim_adi']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="filter-label">Durum</label>
          <select name="durum" class="form-select form-control-modern">
            <option value="">Hepsi</option>
            <option value="1" <?= $filterDurum === '1' ? 'selected' : '' ?>>Aktif</option>
            <option value="0" <?= $filterDurum === '0' ? 'selected' : '' ?>>Pasif</option>
          </select>
        </div>
        <div class="col-md-2 d-grid">
          <button type="submit" class="btn btn-brand"><i class="bi bi-search"></i> Ara</button>
        </div>
      </form>
    </section>

    <div class="d-flex justify-content-between align-items-center mb-3 px-1">
      <span class="text-secondary" style="font-size: 0.95rem;">
        <i class="bi bi-list-ul me-1"></i> Bulunan kayıt: <strong class="text-main"><?= count($personeller) ?></strong>
      </span>
      <?php if ($filterSicil !== '' || $filterBirim > 0 || $filterDurum !== '' || $filterArama !== ''): ?>
        <a href="personeller.php" class="btn btn-sm btn-ghost"><i class="bi bi-x-circle"></i> Filtreleri Temizle</a>
      <?php endif; ?>
    </div>

    <section class="adm-card">
      <div class="table-responsive">
        <table class="table adm-table mb-0">
          <thead>
            <tr>
              <th style="width: 70px;">ID</th>
              <th>Sicil No</th>
              <th>Ad Soyad</th>
              <th>Birim</th>
              <th>E-Posta</th>
              <th>Telefon</th>
              <th>Durum</th>
              <th>Kayıt Tarihi</th>
              <th class="text-center">İşlem</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($personeller)): ?>
            <?php foreach ($personeller as $personel): ?>
            <tr>
              <td class="text-muted"><strong>#<?= e((string)$personel['id']) ?></strong></td>
              <td><span class="badge-birim"><?= e($personel['sicil_no']) ?></span></td>
              <td><strong><?= e($personel['ad_soyad']) ?></strong></td>
              <td><span class="badge-sistem"><?= e($personel['birim_adi']) ?></span></td>
              <td><?= e($personel['email'] ?: '-') ?></td>
              <td><?= e($personel['telefon'] ?: '-') ?></td>
              <td>
                <?php $pAktif = !in_array($personel['aktif'], ['f', false, '0', 0], true); ?>
                <?php if ($pAktif): ?>
                  <span class="status-pill status-1"><i class="bi bi-check-circle-fill"></i> Aktif</span>
                <?php else: ?>
                  <span class="status-pill status-2"><i class="bi bi-x-circle-fill"></i> Pasif</span>
                <?php endif; ?>
              </td>
              <td class="text-muted"><?= e(date('d.m.Y', strtotime((string)$personel['kayit_tarihi']))) ?></td>
              <td class="text-center">
                <div class="d-inline-flex gap-2">
                  <button type="button" class="btn-tbl-approve" title="Düzenle"
                    data-bs-toggle="modal" data-bs-target="#editPersonelModal"
                    data-id="<?= e((string)$personel['id']) ?>"
                    data-sicil="<?= e($personel['sicil_no']) ?>"
                    data-adsoyad="<?= e($personel['ad_soyad']) ?>"
                    data-birim="<?= e((string)$personel['birim_id']) ?>"
                    data-email="<?= e($personel['email'] ?? '') ?>"
                    data-telefon="<?= e($personel['telefon'] ?? '') ?>">
                    <i class="bi bi-pencil-fill"></i>
                  </button>
                  <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="id" value="<?= e((string)$personel['id']) ?>">
                    <button type="submit" class="<?= $pAktif ? 'btn-tbl-reject' : 'btn-tbl-approve' ?>" 
                      title="<?= $pAktif ? 'Pasif Et' : 'Aktif Et' ?>"
                      onclick="return confirm('<?= $pAktif ? 'Pasif' : 'Aktif' ?> yapmak istediğinize emin misiniz?')">
                      <i class="bi bi-<?= $pAktif ? 'x-circle' : 'check-circle' ?>-fill"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="9" class="text-center py-5">
              <i class="bi bi-inbox" style="font-size: 3rem; color: var(--border-color); display: block; margin-bottom: 1rem;"></i>
              <span class="text-muted">Arama kriterlerine uygun personel bulunamadı.</span>
            </td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <div class="modal fade" id="addPersonelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-person-plus-fill text-primary me-2"></i> Yeni Personel Ekle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post">
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
              <label class="form-label">Sicil No <span class="text-danger">*</span></label>
              <input type="text" name="sicil_no" class="form-control form-control-modern" required style="text-transform: uppercase;" placeholder="Örn: A12345">
            </div>
            <div class="mb-3">
              <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
              <input type="text" name="ad_soyad" class="form-control form-control-modern" required placeholder="Örn: Ahmet Yılmaz">
            </div>
            <div class="mb-3">
              <label class="form-label">Birim <span class="text-danger">*</span></label>
              <select name="birim_id" class="form-select form-control-modern" required>
                <option value="">Birim seçin...</option>
                <?php foreach ($birimler as $birim): ?>
                  <option value="<?= e((string)$birim['id']) ?>"><?= e($birim['birim_adi']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">E-Posta</label>
              <input type="email" name="email" class="form-control form-control-modern" placeholder="ornek@gazi.gov.tr">
            </div>
            <div class="mb-3">
              <label class="form-label">Telefon</label>
              <input type="text" name="telefon" class="form-control form-control-modern" placeholder="0532 123 4567">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">İptal</button>
            <button type="submit" class="btn btn-brand">Kaydet</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editPersonelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-pencil-square text-primary me-2"></i> Personel Düzenle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post">
          <div class="modal-body">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editPersonelId">
            <div class="mb-3">
              <label class="form-label">Sicil No <span class="text-danger">*</span></label>
              <input type="text" name="sicil_no" id="editSicilNo" class="form-control form-control-modern" required style="text-transform: uppercase;">
            </div>
            <div class="mb-3">
              <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
              <input type="text" name="ad_soyad" id="editAdSoyad" class="form-control form-control-modern" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Birim <span class="text-danger">*</span></label>
              <select name="birim_id" id="editBirimId" class="form-select form-control-modern" required>
                <option value="">Birim seçin...</option>
                <?php foreach ($birimler as $birim): ?>
                  <option value="<?= e((string)$birim['id']) ?>"><?= e($birim['birim_adi']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">E-Posta</label>
              <input type="email" name="email" id="editEmail" class="form-control form-control-modern">
            </div>
            <div class="mb-3">
              <label class="form-label">Telefon</label>
              <input type="text" name="telefon" id="editTelefon" class="form-control form-control-modern">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">İptal</button>
            <button type="submit" class="btn btn-brand">Güncelle</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Düzenleme Modalına veri çekme
    var editModal = document.getElementById('editPersonelModal');
    if (editModal) {
      editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('editPersonelId').value = button.getAttribute('data-id');
        document.getElementById('editSicilNo').value = button.getAttribute('data-sicil');
        document.getElementById('editAdSoyad').value = button.getAttribute('data-adsoyad');
        document.getElementById('editBirimId').value = button.getAttribute('data-birim');
        document.getElementById('editEmail').value = button.getAttribute('data-email');
        document.getElementById('editTelefon').value = button.getAttribute('data-telefon');
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