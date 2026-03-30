<?php
// Bildirim sayısını al
if (!isset($bildirimSayisi)) {
    require_once __DIR__ . '/../config/bildirim.php';
    $bildirimSayisi = bildirim_okunmamis_sayisi($conn, $_SESSION['admin_id'] ?? 0);
}
?>
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
      <a href="bildirimler.php" class="btn-nav-ghost position-relative">
        <i class="bi bi-bell-fill"></i>
        <?php if ($bildirimSayisi > 0): ?>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
            <?= $bildirimSayisi > 9 ? '9+' : $bildirimSayisi ?>
          </span>
        <?php endif; ?>
      </a>
      <a href="logout.php" class="btn-nav-logout"><i class="bi bi-box-arrow-right"></i> Cikis</a>
    </div>
  </div>
</nav>
