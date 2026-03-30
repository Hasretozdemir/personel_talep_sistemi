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
    
    if ($action === 'add_birim') {
        $birimAdi = trim($_POST['birim_adi'] ?? '');
        if ($birimAdi !== '') {
            $result = pg_query_params($conn, 'INSERT INTO birimler (birim_adi) VALUES ($1)', [$birimAdi]);
            if ($result) {
                header('Location: ayarlar.php?msg=Birim+eklendi&type=success');
                exit;
            }
        }
        header('Location: ayarlar.php?msg=Birim+eklenemedi&type=danger');
        exit;
    }
    
    if ($action === 'add_sistem') {
        $sistemAdi = trim($_POST['sistem_adi'] ?? '');
        if ($sistemAdi !== '') {
            $result = pg_query_params($conn, 'INSERT INTO sistemler (sistem_adi) VALUES ($1)', [$sistemAdi]);
            if ($result) {
                header('Location: ayarlar.php?msg=Sistem+eklendi&type=success');
                exit;
            }
        }
        header('Location: ayarlar.php?msg=Sistem+eklenemedi&type=danger');
        exit;
    }
    
    if ($action === 'delete_birim') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $result = pg_query_params($conn, 'DELETE FROM birimler WHERE id = $1', [$id]);
            if ($result) {
                header('Location: ayarlar.php?msg=Birim+silindi&type=success');
                exit;
            }
        }
        header('Location: ayarlar.php?msg=Birim+silinemedi&type=danger');
        exit;
    }
    
    if ($action === 'delete_sistem') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $result = pg_query_params($conn, 'DELETE FROM sistemler WHERE id = $1', [$id]);
            if ($result) {
                header('Location: ayarlar.php?msg=Sistem+silindi&type=success');
                exit;
            }
        }
        header('Location: ayarlar.php?msg=Sistem+silinemedi&type=danger');
        exit;
    }
}

$birimler = pg_fetch_all(pg_query($conn, 'SELECT id, birim_adi FROM birimler ORDER BY birim_adi ASC')) ?: [];
$sistemler = pg_fetch_all(pg_query($conn, 'SELECT id, sistem_adi FROM sistemler ORDER BY sistem_adi ASC')) ?: [];
?>
<!doctype html>
<html lang="tr" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gazi IT | Ayarlar</title>
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
      --bg-body: #f1f5f9;
      --bg-surface: #ffffff;
      --text-main: #0f172a;
      --text-muted: #64748b;
      --border-color: #e2e8f0;
      --primary: #2563eb;
      --primary-hover: #1d4ed8;
      --danger: #ef4444;
      --danger-bg: #fef2f2;
      --nav-bg: #ffffff;
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
      --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
      --table-hover: #f8fafc;
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

    [data-theme="dark"] .admin-brand,
    [data-theme="dark"] .page-title,
    [data-theme="dark"] .adm-card-header h2,
    [data-theme="dark"] .form-label,
    [data-theme="dark"] .modal-title,
    [data-theme="dark"] .table.adm-table td,
    [data-theme="dark"] .table.adm-table th {
      color: var(--text-main) !important;
    }

    [data-theme="dark"] p.text-muted,
    [data-theme="dark"] .text-secondary,
    [data-theme="dark"] .table.adm-table td.text-muted,
    [data-theme="dark"] .form-control-modern::placeholder {
      color: var(--text-muted) !important;
    }

    [data-theme="dark"] .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }

    /* ── Navbar Tasarımı ── */
    .admin-navbar {
      background-color: var(--nav-bg);
      border-bottom: 1px solid var(--border-color);
      padding: 1rem 0;
      position: sticky; top: 0; z-index: 1020;
      box-shadow: var(--shadow-sm);
      transition: background-color 0.3s, border-color 0.3s;
    }
    .admin-brand {
      font-weight: 800; font-size: 1.25rem; color: var(--text-main);
      text-decoration: none; display: flex; align-items: center; gap: 10px;
    }
    .brand-dot {
      background: linear-gradient(135deg, var(--primary), #8b5cf6);
      color: white !important; width: 32px; height: 32px;
      display: flex; align-items: center; justify-content: center;
      border-radius: 8px; font-size: 1.1rem;
    }
    .btn-nav-ghost, .btn-ghost {
      color: var(--text-muted); font-weight: 600; padding: 0.5rem 0.8rem;
      border-radius: 8px; text-decoration: none; transition: all 0.2s;
      border: none; background: transparent; display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-nav-ghost:hover, .btn-ghost:hover {
      background-color: rgba(148, 163, 184, 0.1); color: var(--text-main);
    }
    .btn-nav-logout {
      color: var(--danger); font-weight: 600; padding: 0.5rem 0.8rem;
      border-radius: 8px; text-decoration: none; transition: all 0.2s;
      display: flex; align-items: center; gap: 6px;
    }
    .btn-nav-logout:hover { background-color: var(--danger-bg); }

    /* ── Sayfa Başlığı ── */
    .page-title {
      font-size: 1.75rem; font-weight: 800; color: var(--text-main);
      margin: 0; display: flex; align-items: center; gap: 12px;
    }
    .page-title i { color: var(--primary); }

    /* ── Kart Tasarımı ── */
    .adm-card {
      background-color: var(--bg-surface);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      box-shadow: var(--shadow-md);
      overflow: hidden;
      transition: background-color 0.3s, border-color 0.3s, transform 0.2s;
      height: 100%; display: flex; flex-direction: column;
    }
    .adm-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
    
    .adm-card-header {
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid var(--border-color);
      display: flex; justify-content: space-between; align-items: center;
      background-color: rgba(0,0,0,0.01);
    }
    .adm-card-header h2 {
      font-size: 1.1rem; font-weight: 700; margin: 0;
      color: var(--text-main); display: flex; align-items: center; gap: 8px;
    }

    /* ── Tablo Tasarımı ── */
    .table-responsive { flex: 1; }
    .adm-table { color: var(--text-main); margin-bottom: 0; width: 100%; }
    .adm-table th {
      background-color: transparent !important; color: var(--text-muted);
      font-weight: 600; font-size: 0.8rem; text-transform: uppercase;
      letter-spacing: 0.5px; padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color) !important;
    }
    .adm-table td {
      padding: 1rem 1.5rem; vertical-align: middle;
      border-bottom: 1px solid var(--border-color) !important;
      background-color: transparent !important; color: var(--text-main); font-weight: 500;
    }
    .adm-table tbody tr { transition: background-color 0.2s; }
    .adm-table tbody tr:hover td { background-color: var(--table-hover) !important; }
    .adm-table tbody tr:last-child td { border-bottom: none !important; }

    /* ── ID ve Tag Stilleri ── */
    .id-badge {
      font-size: 0.8rem; color: var(--text-muted); font-family: monospace;
      background: rgba(100, 116, 139, 0.1); padding: 0.2rem 0.5rem; border-radius: 6px;
    }
    .name-text { font-weight: 600; font-size: 0.95rem; }

    /* ── Butonlar ── */
    .btn-brand {
      background-color: var(--primary); color: #fff !important; border: none;
      font-weight: 600; padding: 0.5rem 1.2rem; border-radius: 8px;
      transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-brand:hover { background-color: var(--primary-hover); transform: translateY(-1px); }
    
    .btn-outline-primary {
      color: var(--primary); border: 1px solid var(--primary); background: transparent;
      font-weight: 600; border-radius: 8px; transition: all 0.2s;
    }
    .btn-outline-primary:hover { background-color: var(--primary); color: white !important; }
    
    .btn-tbl-reject {
      background-color: var(--danger-bg); color: var(--danger); border: none;
      width: 32px; height: 32px; border-radius: 8px; display: inline-flex;
      align-items: center; justify-content: center; transition: all 0.2s; cursor: pointer;
    }
    .btn-tbl-reject:hover { background-color: var(--danger); color: #fff; transform: translateY(-2px); }

    /* ── Modal Tasarımı ── */
    .modal-content {
      background-color: var(--bg-surface); border: 1px solid var(--border-color);
      border-radius: 20px; box-shadow: var(--shadow-lg); overflow: hidden;
    }
    .modal-header { border-bottom: 1px solid var(--border-color); padding: 1.5rem; background-color: rgba(0,0,0,0.01); }
    .modal-title { font-weight: 800; color: var(--text-main); font-size: 1.2rem;}
    .modal-body { padding: 1.5rem; }
    .modal-footer { border-top: 1px solid var(--border-color); padding: 1.25rem 1.5rem; background-color: rgba(0,0,0,0.01); }
    
    .form-control-modern {
      background-color: var(--input-bg) !important; border: 1.5px solid var(--border-color) !important;
      color: var(--text-main) !important; border-radius: 10px; padding: 0.75rem 1rem;
      transition: all 0.2s; font-size: 0.95rem;
    }
    .form-control-modern:focus {
      border-color: var(--primary) !important; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1) !important;
    }
    .form-label { font-weight: 600; color: var(--text-main); margin-bottom: 0.5rem; font-size: 0.9rem; }

    /* Alert / Mesajlar */
    .alert { border-radius: 12px; font-weight: 600; border: none; display: flex; align-items: center; }
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

  <main class="container py-5">
    
    <?php if ($msg !== ''): ?>
      <div class="alert alert-<?= e($type) ?> mb-4 shadow-sm">
        <i class="bi <?= $type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2 fs-5"></i> <?= e($msg) ?>
      </div>
    <?php endif; ?>

    <div class="page-header-row mb-4">
      <div>
        <h1 class="page-title"><i class="bi bi-gear-fill"></i> Sistem Ayarları</h1>
        <p class="text-muted" style="margin-top: 0.5rem; font-size: 0.95rem;">Hastanenin birim ve erişim sistemi tanımlamalarını buradan yönetebilirsiniz.</p>
      </div>
    </div>

    <div class="row g-4 align-items-stretch">
      
      <div class="col-lg-6">
        <section class="adm-card">
          <div class="adm-card-header">
            <h2><i class="bi bi-building text-primary"></i> Hastane Birimleri</h2>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addBirimModal">
              <i class="bi bi-plus-lg"></i> Yeni Ekle
            </button>
          </div>
          <div class="table-responsive">
            <table class="table adm-table">
              <thead>
                <tr>
                  <th style="width: 80px;">ID</th>
                  <th>Birim Adı</th>
                  <th class="text-center" style="width: 100px;">İşlem</th>
                </tr>
              </thead>
              <tbody>
              <?php if (count($birimler) > 0): ?>
                <?php foreach ($birimler as $birim): ?>
                  <tr>
                    <td><span class="id-badge">#<?= e((string)$birim['id']) ?></span></td>
                    <td class="name-text"><?= e($birim['birim_adi']) ?></td>
                    <td class="text-center">
                      <form method="post" style="display:inline;" onsubmit="return confirm('Bu birimi silmek istediğinize emin misiniz? İşlem geri alınamaz.');">
                        <input type="hidden" name="action" value="delete_birim">
                        <input type="hidden" name="id" value="<?= e((string)$birim['id']) ?>">
                        <button type="submit" class="btn-tbl-reject" title="Birimi Sil">
                          <i class="bi bi-trash3-fill"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="3" class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-1 mb-2 d-block opacity-50"></i>
                    Henüz kayıtlı birim bulunmuyor.
                  </td>
                </tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <div class="col-lg-6">
        <section class="adm-card">
          <div class="adm-card-header">
            <h2><i class="bi bi-hdd-network text-primary"></i> IT Sistemleri</h2>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addSistemModal">
              <i class="bi bi-plus-lg"></i> Yeni Ekle
            </button>
          </div>
          <div class="table-responsive">
            <table class="table adm-table">
              <thead>
                <tr>
                  <th style="width: 80px;">ID</th>
                  <th>Sistem Adı</th>
                  <th class="text-center" style="width: 100px;">İşlem</th>
                </tr>
              </thead>
              <tbody>
              <?php if (count($sistemler) > 0): ?>
                <?php foreach ($sistemler as $sistem): ?>
                  <tr>
                    <td><span class="id-badge">#<?= e((string)$sistem['id']) ?></span></td>
                    <td class="name-text"><?= e($sistem['sistem_adi']) ?></td>
                    <td class="text-center">
                      <form method="post" style="display:inline;" onsubmit="return confirm('Bu sistemi silmek istediğinize emin misiniz? İşlem geri alınamaz.');">
                        <input type="hidden" name="action" value="delete_sistem">
                        <input type="hidden" name="id" value="<?= e((string)$sistem['id']) ?>">
                        <button type="submit" class="btn-tbl-reject" title="Sistemi Sil">
                          <i class="bi bi-trash3-fill"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="3" class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-1 mb-2 d-block opacity-50"></i>
                    Henüz kayıtlı sistem bulunmuyor.
                  </td>
                </tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>

    </div>
  </main>

  <div class="modal fade" id="addBirimModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-building-add text-primary me-2"></i> Yeni Birim Ekle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post">
          <div class="modal-body">
            <input type="hidden" name="action" value="add_birim">
            <label class="form-label">Birim Adı</label>
            <input type="text" name="birim_adi" class="form-control form-control-modern" required placeholder="Örn: Kardiyoloji, İnsan Kaynakları vs.">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">İptal</button>
            <button type="submit" class="btn btn-brand"><i class="bi bi-check2"></i> Kaydet</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="addSistemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-pc-display text-primary me-2"></i> Yeni Sistem Ekle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post">
          <div class="modal-body">
            <input type="hidden" name="action" value="add_sistem">
            <label class="form-label">Sistem Adı</label>
            <input type="text" name="sistem_adi" class="form-control form-control-modern" required placeholder="Örn: HBYS, VPN Erişimi, E-Posta">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">İptal</button>
            <button type="submit" class="btn btn-brand"><i class="bi bi-check2"></i> Kaydet</button>
          </div>
        </form>
      </div>
    </div>
  </div>

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