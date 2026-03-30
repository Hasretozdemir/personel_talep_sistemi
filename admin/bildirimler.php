<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/bildirim.php';

$conn = db_connect();
$adminId = $_SESSION['admin_id'] ?? 0;

// İşlem
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'okundu') {
        $bildirimId = (int)($_POST['bildirim_id'] ?? 0);
        bildirim_okundu_isaretle($conn, $bildirimId);
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'tumunu_okundu') {
        bildirim_tumunu_okundu_isaretle($conn, $adminId);
        echo json_encode(['success' => true]);
        exit;
    }
}

// Bildirimleri getir
$bildirimler = bildirim_listele($conn, $adminId, 50);
$okunmamisSayisi = bildirim_okunmamis_sayisi($conn, $adminId);
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gazi IT | Bildirimler</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="../assets/css/style.css" rel="stylesheet">
  <script src="../assets/js/theme.js"></script>
</head>
<body class="admin-body">
  <nav class="admin-navbar">
    <div class="container d-flex align-items-center justify-content-between">
      <a class="admin-brand" href="index.php"><span class="brand-dot">G</span> GAZI IT PANEL</a>
      <div class="d-flex align-items-center gap-2">
        <a href="index.php" class="btn-nav-ghost"><i class="bi bi-grid"></i> Dashboard</a>
        <a href="raporlar.php" class="btn-nav-ghost"><i class="bi bi-graph-up"></i> Raporlar</a>
        <a href="gecmis.php" class="btn-nav-ghost"><i class="bi bi-clock-history"></i> Gecmis</a>
        <a href="personeller.php" class="btn-nav-ghost"><i class="bi bi-person-badge"></i> Personeller</a>
        <a href="ayarlar.php" class="btn-nav-ghost"><i class="bi bi-gear"></i> Ayarlar</a>
        <?php if (($_SESSION['admin_level'] ?? 0) >= 3): ?>
          <a href="kullanicilar.php" class="btn-nav-ghost"><i class="bi bi-people"></i> Kullanicilar</a>
        <?php endif; ?>
        <button class="btn btn-nav-ghost theme-toggle" title="Tema Degistir" style="display:inline-flex; align-items:center; justify-content:center;"></button>
        <a href="logout.php" class="btn-nav-logout"><i class="bi bi-box-arrow-right"></i> Cikis</a>
      </div>
    </div>
  </nav>

  <main class="container py-4">
    <div class="page-header-row mb-4">
      <div>
        <h1 class="page-title"><i class="bi bi-bell-fill"></i> Bildirimler</h1>
        <p class="text-secondary" style="margin-top: 0.5rem;"><?= $okunmamisSayisi ?> okunmamış bildirim</p>
      </div>
      <?php if ($okunmamisSayisi > 0): ?>
        <button class="btn btn-brand" onclick="tumunuOkundu()">
          <i class="bi bi-check-all"></i> Tümünü Okundu İşaretle
        </button>
      <?php endif; ?>
    </div>

    <section class="adm-card">
      <?php if (empty($bildirimler)): ?>
        <div class="text-center py-5">
          <i class="bi bi-bell-slash" style="font-size: 3rem; color: var(--text-muted); display: block; margin-bottom: 1rem;"></i>
          <span class="text-secondary">Henüz bildiriminiz yok.</span>
        </div>
      <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach ($bildirimler as $bildirim): ?>
            <div class="list-group-item <?= $bildirim['okundu'] ? '' : 'bg-light' ?>" style="border-left: 4px solid <?= match($bildirim['tip']) {
              'success' => '#28a745',
              'warning' => '#ffc107',
              'danger' => '#dc3545',
              default => '#17a2b8'
            } ?>;">
              <div class="d-flex w-100 justify-content-between align-items-start">
                <div class="flex-grow-1">
                  <h5 class="mb-1">
                    <?php
                    $icon = match($bildirim['tip']) {
                      'success' => 'check-circle-fill',
                      'warning' => 'exclamation-triangle-fill',
                      'danger' => 'x-circle-fill',
                      default => 'info-circle-fill'
                    };
                    ?>
                    <i class="bi bi-<?= $icon ?>"></i>
                    <?= htmlspecialchars($bildirim['baslik']) ?>
                    <?php if (!$bildirim['okundu']): ?>
                      <span class="badge bg-primary ms-2">Yeni</span>
                    <?php endif; ?>
                  </h5>
                  <p class="mb-1"><?= htmlspecialchars($bildirim['mesaj']) ?></p>
                  <small class="text-muted">
                    <i class="bi bi-clock"></i>
                    <?= date('d.m.Y H:i', strtotime($bildirim['olusturma_tarihi'])) ?>
                  </small>
                </div>
                <div class="d-flex gap-2">
                  <?php if ($bildirim['talep_id']): ?>
                    <a href="gecmis.php?id=<?= $bildirim['talep_id'] ?>" class="btn btn-sm btn-brand">
                      <i class="bi bi-eye"></i> Görüntüle
                    </a>
                  <?php endif; ?>
                  <?php if (!$bildirim['okundu']): ?>
                    <button class="btn btn-sm btn-ghost" onclick="okunduIsaretle(<?= $bildirim['id'] ?>)">
                      <i class="bi bi-check"></i> Okundu
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function okunduIsaretle(bildirimId) {
      fetch('bildirimler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=okundu&bildirim_id=' + bildirimId
      })
      .then(() => location.reload());
    }

    function tumunuOkundu() {
      fetch('bildirimler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=tumunu_okundu'
      })
      .then(() => location.reload());
    }
  </script>
</body>
</html>
