<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

$conn = db_connect();

// Tarih aralığı
$baslangic = $_GET['baslangic'] ?? date('Y-m-01');
$bitis = $_GET['bitis'] ?? date('Y-m-d');

// Genel istatistikler
$genelStats = pg_fetch_assoc(pg_query_params($conn, 
    "SELECT 
        COUNT(*) as toplam,
        COUNT(*) FILTER (WHERE durum = 0) as beklemede,
        COUNT(*) FILTER (WHERE durum = 1) as onaylandi,
        COUNT(*) FILTER (WHERE durum = 2) as reddedildi,
        AVG(EXTRACT(EPOCH FROM (islem_tarihi - tarih))/3600) FILTER (WHERE islem_tarihi IS NOT NULL) as ortalama_sure
    FROM talepler 
    WHERE tarih BETWEEN $1 AND $2",
    [$baslangic, $bitis . ' 23:59:59']
)) ?: ['toplam' => 0, 'beklemede' => 0, 'onaylandi' => 0, 'reddedildi' => 0, 'ortalama_sure' => 0];

// Günlük istatistikler (grafik için)
$gunlukResult = pg_query_params($conn,
    "SELECT 
        DATE(tarih) as gun,
        COUNT(*) as toplam,
        COUNT(*) FILTER (WHERE durum = 1) as onaylandi,
        COUNT(*) FILTER (WHERE durum = 2) as reddedildi
    FROM talepler
    WHERE tarih BETWEEN $1 AND $2
    GROUP BY DATE(tarih)
    ORDER BY gun ASC",
    [$baslangic, $bitis . ' 23:59:59']
);
$gunlukData = pg_fetch_all($gunlukResult) ?: [];

// Birim bazlı istatistikler
$birimResult = pg_query_params($conn,
    "SELECT 
        b.birim_adi,
        COUNT(t.id) as toplam,
        COUNT(*) FILTER (WHERE t.durum = 1) as onaylandi,
        COUNT(*) FILTER (WHERE t.durum = 2) as reddedildi
    FROM birimler b
    LEFT JOIN talepler t ON t.birim_id = b.id AND t.tarih BETWEEN $1 AND $2
    GROUP BY b.birim_adi
    ORDER BY toplam DESC",
    [$baslangic, $bitis . ' 23:59:59']
);
$birimData = pg_fetch_all($birimResult) ?: [];

// Sistem bazlı istatistikler
$sistemResult = pg_query_params($conn,
    "SELECT 
        s.sistem_adi,
        COUNT(t.id) as toplam,
        COUNT(*) FILTER (WHERE t.durum = 1) as onaylandi,
        COUNT(*) FILTER (WHERE t.durum = 2) as reddedildi,
        AVG(EXTRACT(EPOCH FROM (t.islem_tarihi - t.tarih))/3600) FILTER (WHERE t.islem_tarihi IS NOT NULL) as ortalama_sure
    FROM sistemler s
    LEFT JOIN talepler t ON t.sistem_id = s.id AND t.tarih BETWEEN $1 AND $2
    GROUP BY s.sistem_adi
    ORDER BY toplam DESC",
    [$baslangic, $bitis . ' 23:59:59']
);
$sistemData = pg_fetch_all($sistemResult) ?: [];

// JSON formatında veriler (grafik için)
$gunler = array_column($gunlukData, 'gun');
$gunlukToplam = array_column($gunlukData, 'toplam');
$gunlukOnay = array_column($gunlukData, 'onaylandi');
$gunlukRed = array_column($gunlukData, 'reddedildi');
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gazi IT | Raporlar ve İstatistikler</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="../assets/css/style.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="../assets/js/theme.js"></script>
</head>
<body class="admin-body">
  <nav class="admin-navbar">
    <div class="container d-flex align-items-center justify-content-between">
      <a class="admin-brand" href="index.php"><span class="brand-dot">G</span> GAZI IT PANEL</a>
      <div class="d-flex align-items-center gap-2">
        <a href="index.php" class="btn-nav-ghost"><i class="bi bi-grid"></i> Dashboard</a>
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
        <h1 class="page-title"><i class="bi bi-graph-up"></i> Raporlar ve İstatistikler</h1>
        <p class="text-secondary" style="margin-top: 0.5rem;">Talep verilerini analiz edin ve raporlayın</p>
      </div>
      <button class="btn btn-brand" onclick="window.print()">
        <i class="bi bi-printer-fill"></i> Yazdır
      </button>
    </div>

    <!-- Tarih Filtresi -->
    <section class="filter-card mb-4">
      <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.5rem;">
        <i class="bi bi-calendar-range"></i> Tarih Aralığı Seçin
      </h3>
      <form method="get" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="filter-label">Başlangıç Tarihi</label>
          <input type="date" name="baslangic" class="form-control form-control-modern" value="<?= e($baslangic) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="filter-label">Bitiş Tarihi</label>
          <input type="date" name="bitis" class="form-control form-control-modern" value="<?= e($bitis) ?>" required>
        </div>
        <div class="col-md-4 d-grid">
          <button type="submit" class="btn btn-brand"><i class="bi bi-search"></i> Raporla</button>
        </div>
      </form>
    </section>

    <!-- Genel İstatistikler -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon si-blue"><i class="bi bi-collection"></i></div>
          <div class="stat-label">Toplam Talep</div>
          <div class="stat-value"><?= e((string)$genelStats['toplam']) ?></div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon si-amber"><i class="bi bi-hourglass-split"></i></div>
          <div class="stat-label">Beklemede</div>
          <div class="stat-value sv-amber"><?= e((string)$genelStats['beklemede']) ?></div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon si-green"><i class="bi bi-check-circle"></i></div>
          <div class="stat-label">Onaylandı</div>
          <div class="stat-value sv-green"><?= e((string)$genelStats['onaylandi']) ?></div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon si-red"><i class="bi bi-x-circle"></i></div>
          <div class="stat-label">Reddedildi</div>
          <div class="stat-value sv-red"><?= e((string)$genelStats['reddedildi']) ?></div>
        </div>
      </div>
    </div>

    <!-- Ortalama Yanıt Süresi -->
    <div class="row g-3 mb-4">
      <div class="col-md-12">
        <div class="stat-card">
          <div class="stat-icon si-blue"><i class="bi bi-clock-history"></i></div>
          <div class="stat-label">Ortalama Yanıt Süresi</div>
          <div class="stat-value"><?= number_format((float)$genelStats['ortalama_sure'], 1) ?> saat</div>
        </div>
      </div>
    </div>

    <!-- Günlük Talep Grafiği -->
    <section class="adm-card mb-4">
      <div class="adm-card-header">
        <h2><i class="bi bi-graph-up"></i> Günlük Talep Grafiği</h2>
      </div>
      <div class="p-4">
        <canvas id="gunlukGrafik" height="80"></canvas>
      </div>
    </section>

    <div class="row g-3 mb-4">
      <!-- Birim Bazlı İstatistikler -->
      <div class="col-md-6">
        <section class="adm-card">
          <div class="adm-card-header">
            <h2><i class="bi bi-building"></i> Birim Bazlı İstatistikler</h2>
          </div>
          <div class="table-responsive">
            <table class="table adm-table mb-0">
              <thead>
                <tr>
                  <th>Birim</th>
                  <th class="text-center">Toplam</th>
                  <th class="text-center">Onay</th>
                  <th class="text-center">Red</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($birimData as $birim): ?>
                <tr>
                  <td><strong><?= e($birim['birim_adi']) ?></strong></td>
                  <td class="text-center"><span class="badge-sistem"><?= e((string)$birim['toplam']) ?></span></td>
                  <td class="text-center"><span class="badge" style="background: #d4edda; color: #155724;"><?= e((string)$birim['onaylandi']) ?></span></td>
                  <td class="text-center"><span class="badge" style="background: #f8d7da; color: #721c24;"><?= e((string)$birim['reddedildi']) ?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <!-- Sistem Bazlı İstatistikler -->
      <div class="col-md-6">
        <section class="adm-card">
          <div class="adm-card-header">
            <h2><i class="bi bi-gear-fill"></i> Sistem Bazlı İstatistikler</h2>
          </div>
          <div class="table-responsive">
            <table class="table adm-table mb-0">
              <thead>
                <tr>
                  <th>Sistem</th>
                  <th class="text-center">Toplam</th>
                  <th class="text-center">Ort. Süre</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($sistemData as $sistem): ?>
                <tr>
                  <td><strong><?= e($sistem['sistem_adi']) ?></strong></td>
                  <td class="text-center"><span class="badge-sistem"><?= e((string)$sistem['toplam']) ?></span></td>
                  <td class="text-center"><?= number_format((float)($sistem['ortalama_sure'] ?? 0), 1) ?> saat</td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </div>

    <!-- Birim Dağılımı Grafiği -->
    <section class="adm-card mb-4">
      <div class="adm-card-header">
        <h2><i class="bi bi-pie-chart-fill"></i> Birim Dağılımı</h2>
      </div>
      <div class="p-4">
        <canvas id="birimGrafik" height="80"></canvas>
      </div>
    </section>

  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Günlük Talep Grafiği
    const gunlukCtx = document.getElementById('gunlukGrafik').getContext('2d');
    new Chart(gunlukCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode($gunler) ?>,
        datasets: [
          {
            label: 'Toplam',
            data: <?= json_encode($gunlukToplam) ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4
          },
          {
            label: 'Onaylanan',
            data: <?= json_encode($gunlukOnay) ?>,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4
          },
          {
            label: 'Reddedilen',
            data: <?= json_encode($gunlukRed) ?>,
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.4
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'top' },
          title: { display: false }
        },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });

    // Birim Dağılımı Grafiği
    const birimCtx = document.getElementById('birimGrafik').getContext('2d');
    new Chart(birimCtx, {
      type: 'bar',
      data: {
        labels: <?= json_encode(array_column($birimData, 'birim_adi')) ?>,
        datasets: [{
          label: 'Talep Sayısı',
          data: <?= json_encode(array_column($birimData, 'toplam')) ?>,
          backgroundColor: [
            'rgba(102, 126, 234, 0.8)',
            'rgba(118, 75, 162, 0.8)',
            'rgba(237, 100, 166, 0.8)',
            'rgba(255, 154, 158, 0.8)',
            'rgba(250, 208, 196, 0.8)',
            'rgba(212, 252, 121, 0.8)',
            'rgba(150, 230, 161, 0.8)',
            'rgba(40, 167, 69, 0.8)'
          ]
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  </script>
</body>
</html>
