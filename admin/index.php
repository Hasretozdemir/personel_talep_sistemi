<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

$conn = db_connect();

$statsSql = 'SELECT
                COUNT(*) AS toplam,
                COUNT(*) FILTER (WHERE durum = 0) AS beklemede,
                COUNT(*) FILTER (WHERE durum = 1) AS onaylandi,
                COUNT(*) FILTER (WHERE durum = 2) AS reddedildi
             FROM talepler';
$stats = pg_fetch_assoc(pg_query($conn, $statsSql)) ?: ['toplam' => 0, 'beklemede' => 0, 'onaylandi' => 0, 'reddedildi' => 0];

$pendingSql = 'SELECT t.id, t.personel_ad_soyad, t.sicil_no, t.talep_notu, t.tarih, b.birim_adi, s.sistem_adi
               FROM talepler t
               INNER JOIN birimler b ON b.id = t.birim_id
               INNER JOIN sistemler s ON s.id = t.sistem_id
               WHERE t.durum = 0
               ORDER BY t.tarih DESC';
$pendingRows = pg_fetch_all(pg_query($conn, $pendingSql)) ?: [];

$msg = trim($_GET['msg'] ?? '');
$type = trim($_GET['type'] ?? 'success');
$alertType = in_array($type, ['success', 'danger', 'warning', 'info'], true) ? $type : 'success';
?>
<!doctype html>
<html lang="tr" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gazi IT | Yönetim Paneli</title>
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
      --nav-bg: #ffffff;
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.05);
      --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.05);
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
      --nav-bg: #1e293b;
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.3);
      --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.4);
      --table-hover: #334155;
      --input-bg: #0f172a;
    }

    /* ── Genel Kurallar & Karanlık Mod Ezmeleri ── */
    body.admin-body {
      font-family: var(--font-family);
      background-color: var(--bg-body);
      color: var(--text-main);
      transition: background-color 0.3s, color 0.3s;
      -webkit-font-smoothing: antialiased;
    }
    
    [data-theme="dark"] .admin-brand, [data-theme="dark"] .page-title,
    [data-theme="dark"] .stat-value, [data-theme="dark"] .adm-card-header h2,
    [data-theme="dark"] .table.adm-table td, [data-theme="dark"] .fw-semibold {
      color: var(--text-main) !important;
    }
    [data-theme="dark"] p.text-muted, [data-theme="dark"] .text-secondary,
    [data-theme="dark"] .stat-label, [data-theme="dark"] .table.adm-table th {
      color: var(--text-muted) !important;
    }

    /* ── Navbar ── */
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
      display: flex; align-items: center; gap: 10px; color: var(--text-main);
    }
    .brand-dot {
      background: linear-gradient(135deg, var(--primary), #8b5cf6);
      color: white !important;
      width: 32px; height: 32px;
      display: flex; align-items: center; justify-content: center;
      border-radius: 8px; font-size: 1.1rem;
    }
    .btn-nav-ghost {
      color: var(--text-muted); font-weight: 600; padding: 0.5rem 0.8rem;
      border-radius: 8px; text-decoration: none; transition: all 0.2s;
      border: none; background: transparent; display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-nav-ghost:hover { background-color: rgba(148, 163, 184, 0.1); color: var(--text-main); }
    .btn-nav-logout {
      color: var(--danger); font-weight: 600; padding: 0.5rem 0.8rem;
      border-radius: 8px; text-decoration: none; transition: all 0.2s;
      display: flex; align-items: center; gap: 6px;
    }
    .btn-nav-logout:hover { background-color: var(--danger-bg); }

    /* ── Sayfa Başlığı ── */
    .page-title { font-size: 1.75rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 12px; }
    .page-title i { color: var(--primary); }

    /* ── İstatistik Kartları ── */
    .stat-card {
      background-color: var(--bg-surface);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 1.5rem;
      display: flex; flex-direction: column;
      box-shadow: var(--shadow-sm);
      transition: transform 0.2s, box-shadow 0.2s, background-color 0.3s;
      height: 100%;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
    .stat-icon {
      width: 48px; height: 48px; border-radius: 12px;
      display: grid; place-items: center; font-size: 1.4rem; margin-bottom: 1rem;
    }
    .si-blue { background: rgba(37, 99, 235, 0.1); color: var(--primary); }
    .si-amber { background: var(--warning-bg); color: var(--warning); }
    .si-green { background: var(--success-bg); color: var(--success); }
    .si-red { background: var(--danger-bg); color: var(--danger); }
    .stat-label { font-size: 0.9rem; font-weight: 600; margin-bottom: 0.25rem; }
    .stat-value { font-size: 2.2rem; font-weight: 800; line-height: 1; color: var(--text-main); }
    
    [data-theme="dark"] .sv-amber { color: var(--warning) !important; }
    [data-theme="dark"] .sv-green { color: var(--success) !important; }
    [data-theme="dark"] .sv-red { color: var(--danger) !important; }
    .sv-amber { color: var(--warning); }
    .sv-green { color: var(--success); }
    .sv-red { color: var(--danger); }

    /* ── Tablo ve Ana Kart Tasarımı ── */
    .adm-card {
      background-color: var(--bg-surface);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      box-shadow: var(--shadow-md);
      overflow: hidden;
      transition: background-color 0.3s, border-color 0.3s;
    }
    .adm-card-header {
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid var(--border-color);
      display: flex; justify-content: space-between; align-items: center;
      background-color: rgba(0,0,0,0.01);
    }
    .adm-card-header h2 { font-size: 1.1rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px; }
    
    .adm-table { margin-bottom: 0; }
    .adm-table th {
      background-color: transparent !important; color: var(--text-muted);
      font-weight: 600; font-size: 0.85rem; text-transform: uppercase;
      letter-spacing: 0.5px; padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color) !important;
    }
    .adm-table td {
      padding: 1rem 1.25rem; vertical-align: middle;
      border-bottom: 1px solid var(--border-color) !important; background-color: transparent !important;
    }
    .adm-table tbody tr { transition: background-color 0.2s; }
    .adm-table tbody tr:hover td { background-color: var(--table-hover) !important; }
    .adm-table tbody tr:last-child td { border-bottom: none !important; }

    /* ── Rozetler ── */
    .badge-birim {
      display: inline-block; background-color: var(--bg-body); border: 1px solid var(--border-color);
      padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; color: var(--text-main); margin-bottom: 4px;
    }
    .badge-sistem {
      display: inline-block; background-color: rgba(37, 99, 235, 0.08); color: var(--primary);
      padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600;
    }

    /* ── Butonlar ── */
    .btn-brand {
      background-color: var(--primary); color: #fff !important; border: none; font-weight: 600;
      padding: 0.6rem 1.2rem; border-radius: 8px; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-brand:hover { background-color: var(--primary-hover); transform: translateY(-1px); }
    
    .btn-tbl-approve, .btn-tbl-reject {
      border: none; width: 36px; height: 36px; border-radius: 10px;
      display: inline-flex; align-items: center; justify-content: center;
      transition: all 0.2s; cursor: pointer; font-size: 1.1rem;
    }
    .btn-tbl-approve { background-color: var(--success-bg); color: var(--success); }
    .btn-tbl-approve:hover { background-color: var(--success); color: #fff; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(34, 197, 94, 0.2); }
    .btn-tbl-reject { background-color: var(--danger-bg); color: var(--danger); }
    .btn-tbl-reject:hover { background-color: var(--danger); color: #fff; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(239, 68, 68, 0.2); }

    /* ── Özel JS Modal Tasarımı ── */
    .custom-modal-overlay {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);
      display: flex; align-items: center; justify-content: center;
      z-index: 9999; opacity: 0; visibility: hidden; transition: all 0.3s ease;
    }
    .custom-modal-overlay.active { opacity: 1; visibility: visible; }
    .custom-modal {
      background: var(--bg-surface); width: 90%; max-width: 460px;
      border-radius: 24px; padding: 2.5rem 2rem; transform: translateY(30px);
      transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      box-shadow: var(--shadow-lg); border: 1px solid var(--border-color);
    }
    .custom-modal-overlay.active .custom-modal { transform: translateY(0); }
    
    .custom-modal-icon {
      width: 72px; height: 72px; border-radius: 50%; margin: 0 auto 1.5rem auto;
      display: flex; align-items: center; justify-content: center; font-size: 2.2rem;
    }
    .custom-modal-icon.success { background: var(--success-bg); color: var(--success); }
    .custom-modal-icon.danger { background: var(--danger-bg); color: var(--danger); }
    
    .custom-modal-title { text-align: center; font-weight: 800; font-size: 1.4rem; margin-bottom: 0.5rem; color: var(--text-main); }
    .custom-modal-text { text-align: center; color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem; line-height: 1.6; }
    
    .custom-modal-input {
      width: 100%; background: var(--input-bg); border: 1px solid var(--border-color);
      color: var(--text-main); border-radius: 12px; padding: 0.85rem 1rem;
      outline: none; resize: vertical; transition: all 0.2s; font-family: inherit; font-size: 0.95rem;
    }
    .custom-modal-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }
    
    .custom-modal-buttons { display: flex; gap: 12px; margin-top: 1.5rem; }
    .custom-modal-btn { 
      flex: 1; padding: 0.85rem; border-radius: 12px; font-weight: 700; font-size: 1rem;
      cursor: pointer; border: none; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .custom-modal-btn.cancel { background: var(--bg-body); color: var(--text-muted); border: 1px solid var(--border-color); }
    .custom-modal-btn.cancel:hover { background: var(--border-color); color: var(--text-main); }
    .custom-modal-btn.confirm { background: var(--success); color: white; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3); }
    .custom-modal-btn.confirm:hover { background: #16a34a; transform: translateY(-2px); }
    .custom-modal-btn.reject { background: var(--danger); color: white; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); }
    .custom-modal-btn.reject:hover { background: var(--danger-hover); transform: translateY(-2px); }

    .reason-chip-btn {
      padding: 0.4rem 0.9rem; border-radius: 50px; border: 1.5px solid var(--border-color);
      background: var(--bg-body); color: var(--text-muted); font-size: 0.8rem; font-weight: 600;
      cursor: pointer; transition: all 0.2s;
    }
    .reason-chip-btn:hover, .reason-chip-btn.selected { border-color: var(--danger); background: var(--danger-bg); color: var(--danger); }

    /* ── Uyarı Mesajları ── */
    .alert { border-radius: 12px; font-weight: 500; border: none; }
    .alert-success { background-color: #dcfce7; color: #166534; }
    [data-theme="dark"] .alert-success { background-color: rgba(22, 101, 52, 0.2); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.2); }
    .alert-danger { background-color: var(--danger-bg); color: var(--danger); }
    .alert-warning { background-color: var(--warning-bg); color: var(--warning); }
  </style>
</head>
<body class="admin-body">
  
  <nav class="admin-navbar">
    <div class="container d-flex align-items-center justify-content-between">
      <a class="admin-brand" href="index.php"><span class="brand-dot">G</span> GAZI IT PANEL</a>
      <div class="d-flex align-items-center gap-2">
        <a href="gecmis.php" class="btn-nav-ghost"><i class="bi bi-clock-history"></i> Geçmiş</a>
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
    <?php if ($msg !== ''): ?>
      <div class="alert alert-<?= e($alertType) ?> mb-4 shadow-sm">
        <i class="bi <?= $alertType === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i> <?= e($msg) ?>
      </div>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-3">
      <div>
        <h1 class="page-title"><i class="bi bi-grid-fill"></i> Yönetim Paneli</h1>
        <span class="text-secondary mt-1 d-block" style="font-size: 0.95rem; font-weight: 500;"><i class="bi bi-calendar3 me-1"></i> <?= e(date('d F Y, l')) ?></span>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon si-blue"><i class="bi bi-collection-fill"></i></div>
          <div class="stat-label">Toplam Talep</div>
          <div class="stat-value"><?= e((string)$stats['toplam']) ?></div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon si-amber"><i class="bi bi-hourglass-top"></i></div>
          <div class="stat-label">Beklemede</div>
          <div class="stat-value sv-amber"><?= e((string)$stats['beklemede']) ?></div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon si-green"><i class="bi bi-check-circle-fill"></i></div>
          <div class="stat-label">Onaylandı</div>
          <div class="stat-value sv-green"><?= e((string)$stats['onaylandi']) ?></div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon si-red"><i class="bi bi-x-circle-fill"></i></div>
          <div class="stat-label">Reddedildi</div>
          <div class="stat-value sv-red"><?= e((string)$stats['reddedildi']) ?></div>
        </div>
      </div>
    </div>

    <section class="adm-card">
      <div class="adm-card-header">
        <h2><i class="bi bi-inbox-fill text-primary"></i> Bekleyen Talepler</h2>
        <a href="../index.php" class="btn btn-sm btn-brand" target="_blank"><i class="bi bi-plus-lg"></i> Yeni Talep Aç</a>
      </div>

      <div class="table-responsive">
        <table class="table adm-table mb-0">
          <thead>
            <tr>
              <th style="width: 70px;">ID</th>
              <th>Personel</th>
              <th>Birim / Sistem</th>
              <th>Açıklama</th>
              <th>Tarih</th>
              <th class="text-center" style="width: 120px;">İşlem</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($pendingRows)): ?>
            <?php foreach ($pendingRows as $row): ?>
              <tr>
                <td class="text-muted"><strong>#<?= e((string)$row['id']) ?></strong></td>
                <td>
                  <div class="fw-semibold text-main"><?= e($row['personel_ad_soyad']) ?></div>
                  <div class="text-secondary mt-1" style="font-size: 0.85rem;">Sicil: <?= e($row['sicil_no']) ?></div>
                </td>
                <td>
                  <span class="badge-birim"><?= e($row['birim_adi']) ?></span><br>
                  <span class="badge-sistem"><i class="bi bi-hdd-network me-1"></i> <?= e($row['sistem_adi']) ?></span>
                </td>
                <td class="text-main" style="max-width: 250px; font-size: 0.95rem;"><?= e($row['talep_notu'] ?: '-') ?></td>
                <td class="text-muted" style="font-size: 0.9rem;"><?= e(date('d.m.Y H:i', strtotime((string)$row['tarih']))) ?></td>
                <td class="text-center">
                  <div class="d-inline-flex gap-2">
                    <button type="button" class="btn-tbl-approve" title="Talebi Onayla" 
                      onclick="showApproveModal(<?= e((string)$row['id']) ?>, '<?= e($row['personel_ad_soyad']) ?>')">
                      <i class="bi bi-check-lg"></i>
                    </button>
                    <button type="button" class="btn-tbl-reject" title="Talebi Reddet" 
                      onclick="showRejectModal(<?= e((string)$row['id']) ?>, '<?= e($row['personel_ad_soyad']) ?>')">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6" class="text-center py-5">
              <i class="bi bi-check2-all" style="font-size: 3.5rem; color: var(--success); display: block; margin-bottom: 1rem; opacity: 0.8;"></i>
              <span class="text-muted" style="font-size: 1.1rem; font-weight: 500;">Harika! Bekleyen talep bulunmuyor.</span>
            </td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // --- JS Temelli Özelleştirilmiş Modallar ---

    function showApproveModal(id, name) {
      var overlay = document.createElement('div');
      overlay.className = 'custom-modal-overlay';
      overlay.innerHTML = `
        <div class="custom-modal">
          <div class="custom-modal-icon success">
            <i class="bi bi-shield-check"></i>
          </div>
          <h3 class="custom-modal-title">Talebi Onayla</h3>
          <p class="custom-modal-text"><strong>${name}</strong> adlı personelin sistem erişim talebini onaylamak istediğinize emin misiniz?</p>
          <div class="custom-modal-buttons">
            <button class="custom-modal-btn cancel" onclick="closeModal(this)">İptal</button>
            <button class="custom-modal-btn confirm" onclick="approveTalep(${id})">
              <i class="bi bi-check2"></i> Onayla
            </button>
          </div>
        </div>
      `;
      document.body.appendChild(overlay);
      // Animasyon için kısa bir gecikme
      setTimeout(() => overlay.classList.add('active'), 10);
    }

    function showRejectModal(id, name) {
      var overlay = document.createElement('div');
      overlay.className = 'custom-modal-overlay';
      overlay.innerHTML = `
        <div class="custom-modal">
          <div class="custom-modal-icon danger">
            <i class="bi bi-exclamation-triangle"></i>
          </div>
          <h3 class="custom-modal-title">Talebi Reddet</h3>
          <p class="custom-modal-text" style="margin-bottom: 1rem;"><strong>${name}</strong> adlı personelin talebini reddetmek üzeresiniz. Lütfen bir neden belirtin.</p>
          
          <p style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 0.5rem; text-align: left;">Hızlı Sebep Seç</p>
          <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 1rem; justify-content: flex-start;">
            <button type="button" class="reason-chip-btn" onclick="setReason(this, 'Yetkisiz erişim talebi. Sistem politikasına uygun değil.')">🚫 Yetkisiz erişim</button>
            <button type="button" class="reason-chip-btn" onclick="setReason(this, 'Talep formu eksik veya yanlış doldurulmuş. Lütfen tekrar başvurun.')">📋 Eksik/yanlış bilgi</button>
            <button type="button" class="reason-chip-btn" onclick="setReason(this, 'Talep, amirin onayı alınmadan yapılmıştır. Lütfen önce onay alın.')">👤 Amir onayı gerekli</button>
          </div>
          
          <textarea id="rejectReason" class="custom-modal-input" rows="3" placeholder="Veya ret nedenini buraya yazın..."></textarea>
          
          <div class="custom-modal-buttons">
            <button class="custom-modal-btn cancel" onclick="closeModal(this)">İptal</button>
            <button class="custom-modal-btn reject" onclick="rejectTalep(${id})">
              <i class="bi bi-x-lg"></i> Reddet
            </button>
          </div>
        </div>
      `;
      document.body.appendChild(overlay);
      setTimeout(() => overlay.classList.add('active'), 10);
    }

    function setReason(btn, text) {
      document.getElementById('rejectReason').value = text;
      // Aktif olan çipi belirginleştir
      btn.closest('div').querySelectorAll('button').forEach(b => {
        b.classList.remove('selected');
      });
      btn.classList.add('selected');
    }

    function closeModal(btn) {
      var overlay = btn.closest('.custom-modal-overlay');
      overlay.classList.remove('active');
      setTimeout(() => overlay.remove(), 300); // animasyon süresi kadar bekle
    }

    function approveTalep(id) {
      var form = document.createElement('form');
      form.method = 'POST';
      form.action = 'islem.php';
      form.innerHTML = `
        <input type="hidden" name="id" value="${id}">
        <input type="hidden" name="action" value="approve">
      `;
      document.body.appendChild(form);
      form.submit();
    }

    function rejectTalep(id) {
      var reason = document.getElementById('rejectReason').value.trim();
      if (reason === '') {
        alert('Lütfen reddetme nedenini boş bırakmayın.');
        document.getElementById('rejectReason').focus();
        return;
      }
      
      var form = document.createElement('form');
      form.method = 'POST';
      form.action = 'islem.php';
      form.innerHTML = `
        <input type="hidden" name="id" value="${id}">
        <input type="hidden" name="action" value="reject">
        <input type="hidden" name="red_neden" value="${reason}">
      `;
      document.body.appendChild(form);
      form.submit();
    }

    // --- Tema Değiştirme Scripti ---
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