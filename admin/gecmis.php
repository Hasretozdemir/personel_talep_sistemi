<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

$conn = db_connect();

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = pg_fetch_all(pg_query($conn, 'SELECT t.id, t.personel_ad_soyad, t.sicil_no, b.birim_adi, s.sistem_adi, t.talep_notu, t.red_neden, t.durum, t.tarih FROM talepler t INNER JOIN birimler b ON b.id = t.birim_id INNER JOIN sistemler s ON s.id = t.sistem_id ORDER BY t.tarih DESC')) ?: [];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="talepler_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Ad Soyad', 'Sicil', 'Birim', 'Sistem', 'Not', 'Ret Nedeni', 'Durum', 'Tarih'], ';');
    foreach ($rows as $row) {
        fputcsv($out, [$row['id'], $row['personel_ad_soyad'], $row['sicil_no'], $row['birim_adi'], $row['sistem_adi'], $row['talep_notu'] ?? '', $row['red_neden'] ?? '', durum_text((int)$row['durum']), date('d.m.Y H:i', strtotime((string)$row['tarih']))], ';');
    }
    fclose($out);
    exit;
}

$filterDurum = $_GET['durum'] ?? '';
$filterSicil = strtoupper(trim($_GET['sicil'] ?? ''));
$filterBaslangic = $_GET['baslangic'] ?? '';
$filterBitis = $_GET['bitis'] ?? '';

$conditions = [];
$params = [];
$idx = 1;
if ($filterDurum !== '' && in_array($filterDurum, ['0', '1', '2'], true)) { $conditions[] = "t.durum = $$idx"; $params[] = $filterDurum; $idx++; }
if ($filterSicil !== '') { $conditions[] = "t.sicil_no ILIKE $$idx"; $params[] = '%' . $filterSicil . '%'; $idx++; }
if ($filterBaslangic !== '') { $conditions[] = "t.tarih >= $$idx::date"; $params[] = $filterBaslangic; $idx++; }
if ($filterBitis !== '') { $conditions[] = "t.tarih < ($$idx::date + interval '1 day')"; $params[] = $filterBitis; $idx++; }

$where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';
$sql = "SELECT t.id, t.personel_ad_soyad, t.sicil_no, t.talep_notu, t.red_neden, t.durum, t.tarih, b.birim_adi, s.sistem_adi FROM talepler t INNER JOIN birimler b ON b.id = t.birim_id INNER JOIN sistemler s ON s.id = t.sistem_id $where ORDER BY t.tarih DESC";
$result = $params ? pg_query_params($conn, $sql, $params) : pg_query($conn, $sql);
$rows = $result ? (pg_fetch_all($result) ?: []) : [];
?>
<!doctype html>
<html lang="tr" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gazi IT | Talep Arşivi</title>
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
      --warning: #eab308;
      --warning-bg: #fef9c3;
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
      --danger-bg: rgba(239, 68, 68, 0.15);
      --success: #4ade80;
      --success-bg: rgba(34, 197, 94, 0.15);
      --warning: #facc15;
      --warning-bg: rgba(234, 179, 8, 0.15);
      --nav-bg: #1e293b;
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.3);
      --table-hover: #334155;
      --input-bg: #0f172a;
    }

    /* ── Bootstrap Renk Ezmeleri (Karanlık Mod) ── */
    body.admin-body {
      font-family: var(--font-family);
      background-color: var(--bg-body);
      color: var(--text-main);
      transition: background-color 0.3s, color 0.3s;
      -webkit-font-smoothing: antialiased;
    }

    [data-theme="dark"] .admin-brand,
    [data-theme="dark"] .page-title,
    [data-theme="dark"] .filter-card h3,
    [data-theme="dark"] .form-label,
    [data-theme="dark"] .table.adm-table td,
    [data-theme="dark"] .fw-semibold {
      color: var(--text-main) !important;
    }

    [data-theme="dark"] p.text-muted,
    [data-theme="dark"] .text-secondary,
    [data-theme="dark"] .filter-label,
    [data-theme="dark"] .table.adm-table th,
    [data-theme="dark"] .form-control-modern::placeholder {
      color: var(--text-muted) !important;
    }

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
      color: var(--text-main);
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

    /* ── Butonlar ── */
    .btn-brand {
      background-color: var(--primary);
      color: #fff !important;
      border: none;
      font-weight: 600;
      padding: 0.75rem 1.2rem;
      border-radius: 10px;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      justify-content: center;
    }
    .btn-brand:hover { background-color: var(--primary-hover); transform: translateY(-1px); }

    .btn-csv {
      background-color: #10b981;
      color: white !important;
      font-weight: 600;
      padding: 0.6rem 1.2rem;
      border-radius: 10px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s;
      border: 1px solid #059669;
    }
    .btn-csv:hover { background-color: #059669; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }
    [data-theme="dark"] .btn-csv { background-color: rgba(16, 185, 129, 0.15); color: #34d399 !important; border-color: rgba(52, 211, 153, 0.3); }
    [data-theme="dark"] .btn-csv:hover { background-color: rgba(16, 185, 129, 0.25); }

    /* ── Tablo ve Kart Tasarımı ── */
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

    /* ── Rozetler ve Pill'ler ── */
    .badge-birim {
      display: inline-block;
      background-color: var(--bg-body);
      border: 1px solid var(--border-color);
      padding: 0.2rem 0.5rem;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--text-main);
      margin-bottom: 4px;
    }
    .badge-sistem {
      display: inline-block;
      background-color: rgba(37, 99, 235, 0.08);
      color: var(--primary);
      padding: 0.2rem 0.5rem;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    .status-pill {
      padding: 0.35rem 0.75rem;
      border-radius: 50px;
      font-size: 0.85rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .status-0 { background-color: var(--warning-bg); color: var(--warning); }
    .status-1 { background-color: var(--success-bg); color: var(--success); }
    .status-2 { background-color: var(--danger-bg); color: var(--danger); }

    .reason-chip {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 0.8rem;
      padding: 0.3rem 0.6rem;
      border-radius: 6px;
      background-color: var(--danger-bg);
      color: var(--danger);
      font-weight: 600;
      margin-top: 6px;
      border: 1px solid rgba(239, 68, 68, 0.2);
    }
  </style>
</head>
<body class="admin-body">
  <nav class="admin-navbar">
    <div class="container d-flex align-items-center justify-content-between">
      <a class="admin-brand" href="index.php"><span class="brand-dot">G</span> GAZI IT PANEL</a>
      <div class="d-flex align-items-center gap-2">
        <a href="index.php" class="btn-nav-ghost"><i class="bi bi-grid"></i> Dashboard</a>
        <a href="personeller.php" class="btn-nav-ghost"><i class="bi bi-person-badge"></i> Personeller</a>
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
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
      <div>
        <h1 class="page-title"><i class="bi bi-clock-history"></i> Talep Arşivi</h1>
        <p class="text-muted" style="margin-top: 0.5rem; margin-bottom: 0;">Tüm personel taleplerini filtreleyin, inceleyin ve raporlayın.</p>
      </div>
      <a href="gecmis.php?export=csv" class="btn-csv">
        <i class="bi bi-file-earmark-spreadsheet-fill"></i> CSV İndir
      </a>
    </div>

    <section class="filter-card mb-4">
      <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 8px;">
        <i class="bi bi-funnel-fill text-primary"></i> Filtreleme Seçenekleri
      </h3>
      <form method="get" class="row g-3 align-items-end">
        <div class="col-md-3 col-lg-2">
          <label class="filter-label">Durum</label>
          <select name="durum" class="form-select form-control-modern">
            <option value="">Tümü</option>
            <option value="0" <?= $filterDurum === '0' ? 'selected' : '' ?>>Beklemede</option>
            <option value="1" <?= $filterDurum === '1' ? 'selected' : '' ?>>Onaylandı</option>
            <option value="2" <?= $filterDurum === '2' ? 'selected' : '' ?>>Reddedildi</option>
          </select>
        </div>
        <div class="col-md-3 col-lg-3">
          <label class="filter-label">Sicil No</label>
          <input type="text" name="sicil" class="form-control form-control-modern" value="<?= e($filterSicil) ?>" placeholder="Sicil ara..." style="text-transform: uppercase;">
        </div>
        <div class="col-md-3 col-lg-3">
          <label class="filter-label">Başlangıç Tarihi</label>
          <input type="date" name="baslangic" class="form-control form-control-modern" value="<?= e($filterBaslangic) ?>">
        </div>
        <div class="col-md-3 col-lg-2">
          <label class="filter-label">Bitiş Tarihi</label>
          <input type="date" name="bitis" class="form-control form-control-modern" value="<?= e($filterBitis) ?>">
        </div>
        <div class="col-12 col-lg-2 d-grid">
          <button type="submit" class="btn btn-brand"><i class="bi bi-search"></i> Sorgula</button>
        </div>
      </form>
    </section>

    <div class="d-flex justify-content-between align-items-center mb-3 px-1">
      <span class="text-secondary" style="font-size: 0.95rem;">
        <i class="bi bi-list-ul me-1"></i> Bulunan kayıt: <strong class="text-main"><?= count($rows) ?></strong>
      </span>
      <?php if ($filterDurum !== '' || $filterSicil !== '' || $filterBaslangic !== '' || $filterBitis !== ''): ?>
        <a href="gecmis.php" class="btn btn-sm btn-ghost"><i class="bi bi-x-circle"></i> Filtreleri Temizle</a>
      <?php endif; ?>
    </div>

    <section class="adm-card">
      <div class="table-responsive">
        <table class="table adm-table mb-0">
          <thead>
            <tr>
              <th style="width: 70px;">ID</th>
              <th>Personel ve Sicil</th>
              <th>Birim / Sistem</th>
              <th>Notlar</th>
              <th>Durum</th>
              <th>Tarih</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td class="text-muted"><strong>#<?= e((string)$row['id']) ?></strong></td>
                <td>
                  <div class="fw-semibold text-main"><?= e($row['personel_ad_soyad']) ?></div>
                  <div class="text-secondary" style="font-size: 0.85rem; margin-top: 2px;">Sicil: <?= e($row['sicil_no']) ?></div>
                </td>
                <td>
                  <span class="badge-birim"><?= e($row['birim_adi']) ?></span><br>
                  <span class="badge-sistem"><i class="bi bi-hdd-network me-1"></i> <?= e($row['sistem_adi']) ?></span>
                </td>
                <td style="max-width: 300px;">
                  <div class="text-main" style="font-size: 0.9rem;"><?= e($row['talep_notu'] ?: '-') ?></div>
                  <?php if (!empty($row['red_neden'])): ?>
                    <div class="reason-chip"><i class="bi bi-exclamation-triangle-fill"></i> Sebep: <?= e($row['red_neden']) ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php
                    $durumClass = match((int)$row['durum']) { 1 => 'status-1', 2 => 'status-2', default => 'status-0' };
                    $durumIcon = match((int)$row['durum']) { 1 => 'check-circle-fill', 2 => 'x-circle-fill', default => 'hourglass-split' };
                  ?>
                  <span class="status-pill <?= $durumClass ?>">
                    <i class="bi bi-<?= $durumIcon ?>"></i> <?= durum_text((int)$row['durum']) ?>
                  </span>
                </td>
                <td class="text-muted" style="font-size: 0.9rem;">
                  <?= e(date('d.m.Y H:i', strtotime((string)$row['tarih']))) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6" class="text-center py-5">
              <i class="bi bi-inbox" style="font-size: 3rem; color: var(--border-color); display: block; margin-bottom: 1rem;"></i>
              <span class="text-muted">Aranan kriterlerde talep bulunamadı.</span>
            </td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
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