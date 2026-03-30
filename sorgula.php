<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$conn = db_connect();
$sicilNo = strtoupper(trim($_GET['sicil_no'] ?? ''));
$results = [];

if ($sicilNo !== '') {
    $sql = 'SELECT t.id, t.sicil_no, t.personel_ad_soyad, t.talep_notu, t.durum, t.red_neden, t.tarih, b.birim_adi, s.sistem_adi
            FROM talepler t
            INNER JOIN birimler b ON b.id = t.birim_id
            INNER JOIN sistemler s ON s.id = t.sistem_id
            WHERE t.sicil_no = $1
            ORDER BY t.tarih DESC';
    $query = pg_query_params($conn, $sql, [$sicilNo]);
    if ($query) {
        $results = pg_fetch_all($query) ?: [];
    }
}

// Durum badge'leri için yardımcı bir fonksiyon (örnek amaçlı, durum ID'lerine göre kendi yapına uyarlayabilirsin)
function getStatusClass(int $durum): string {
    return match ($durum) {
        0 => 'status-pending',
        1 => 'status-approved',
        2 => 'status-rejected',
        default => 'status-default',
    };
}
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gazi IT | Talep Durumu</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <script src="assets/js/theme.js"></script>

  <style>
    :root {
      --primary-color: #2563eb;
      --primary-hover: #1d4ed8;
      --bg-color: #f8fafc;
      --text-main: #0f172a;
      --text-muted: #64748b;
      --card-bg: #ffffff;
      --border-color: #e2e8f0;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-color);
      color: var(--text-main);
      -webkit-font-smoothing: antialiased;
    }

    /* Hero Banner */
    .hero-section {
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
      color: white;
      padding: 4rem 0 5rem;
      text-align: center;
      border-radius: 0 0 2rem 2rem;
      margin-bottom: -3.5rem;
    }

    .hero-section h1 {
      font-weight: 700;
      font-size: 2.25rem;
      letter-spacing: -0.025em;
      margin-bottom: 0.5rem;
    }

    .hero-section p {
      color: #94a3b8;
      font-size: 1.1rem;
    }

    /* Cards */
    .glass-card {
      background: var(--card-bg);
      border-radius: 1rem;
      padding: 2rem;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
      border: 1px solid var(--border-color);
      position: relative;
      z-index: 10;
    }

    /* Form Elements */
    .form-label-mini {
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--text-muted);
      letter-spacing: 0.05em;
      text-transform: uppercase;
      margin-bottom: 0.5rem;
      display: block;
    }

    .custom-input {
      border: 1px solid var(--border-color);
      padding: 0.875rem 1rem;
      font-size: 1rem;
      border-radius: 0.5rem;
      box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }

    .custom-input:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
    }

    .input-group-text {
      background-color: #f8fafc;
      border-color: var(--border-color);
      color: var(--text-muted);
    }

    /* Buttons */
    .btn-brand {
      background-color: var(--primary-color);
      color: white;
      font-weight: 600;
      padding: 0.875rem 1.5rem;
      border-radius: 0.5rem;
      border: none;
      transition: all 0.2s ease-in-out;
      box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
    }

    .btn-brand:hover {
      background-color: var(--primary-hover);
      transform: translateY(-1px);
      box-shadow: 0 6px 8px -1px rgba(37, 99, 235, 0.3);
    }

    /* Table Styling */
    .modern-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }
    
    .modern-table th {
      background-color: #f8fafc;
      color: var(--text-muted);
      font-weight: 600;
      font-size: 0.875rem;
      padding: 1rem;
      border-bottom: 1px solid var(--border-color);
    }

    .modern-table td {
      padding: 1.25rem 1rem;
      vertical-align: middle;
      border-bottom: 1px solid var(--border-color);
    }

    .modern-table tr:last-child td {
      border-bottom: none;
    }

    .item-title {
      font-weight: 600;
      color: var(--text-main);
      font-size: 1rem;
    }

    .item-sub {
      font-size: 0.875rem;
      color: var(--text-muted);
      margin-top: 0.25rem;
    }

    /* Status Badges */
    .status-pill {
      padding: 0.35rem 0.875rem;
      border-radius: 2rem;
      font-size: 0.85rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
    }

    .status-pending { background-color: #fef3c7; color: #b45309; }
    .status-approved { background-color: #dcfce7; color: #15803d; }
    .status-rejected { background-color: #fee2e2; color: #b91c1c; }
    .status-default { background-color: #f1f5f9; color: #475569; }

    .reason-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      background-color: #fef2f2;
      color: #991b1b;
      padding: 0.25rem 0.75rem;
      border-radius: 0.375rem;
      font-size: 0.85rem;
      margin-top: 0.5rem;
      border: 1px solid #fecaca;
    }

    /* Animations */
    .fade-in {
      animation: fadeIn 0.4s ease-out forwards;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

  <header class="hero-section">
    <div class="container">
      <h1><i class="bi bi-search me-2"></i>Talep Durumu Sorgulama</h1>
      <p>Yetki taleplerinizin son durumunu sicil numaranızla anında takip edin.</p>
    </div>
  </header>

  <main class="container">
    <section class="glass-card">
      <form method="get" class="row g-3 align-items-end">
        <div class="col-md-9">
          <label class="form-label-mini">SİCİL NUMARANIZ</label>
          <div class="input-group">
            <span class="input-group-text border-end-0"><i class="bi bi-person-badge"></i></span>
            <input type="text" name="sicil_no" class="form-control custom-input border-start-0" value="<?= e($sicilNo) ?>" placeholder="Sicil numaranızı girin" required autocomplete="off">
          </div>
        </div>
        <div class="col-md-3 d-grid">
          <button type="submit" class="btn btn-brand">
            <i class="bi bi-search me-2"></i>Sorgula
          </button>
        </div>
      </form>
    </section>

    <?php if ($sicilNo !== ''): ?>
      <section class="glass-card mt-4 fade-in">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
          <h2 class="h4 mb-0 fw-bold"><i class="bi bi-list-check me-2 text-primary"></i>Sonuçlar</h2>
          <span class="badge bg-light text-dark border px-3 py-2 fs-6">
            <i class="bi bi-person-badge me-1"></i> Sicil: <?= e($sicilNo) ?>
          </span>
        </div>

        <?php if (empty($results)): ?>
          <div class="text-center py-5">
            <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle mb-3" style="width: 80px; height: 80px;">
              <i class="bi bi-inbox text-muted" style="font-size: 2.5rem;"></i>
            </div>
            <h5 class="fw-bold text-dark">Kayıt Bulunamadı</h5>
            <p class="text-muted">Bu sicil numarasına ait henüz bir talep oluşturulmamış.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="modern-table">
              <thead>
                <tr>
                  <th>Sistem Detayı</th>
                  <th>Açıklama</th>
                  <th>Durum</th>
                  <th>Tarih</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($results as $row): ?>
                <tr>
                  <td>
                    <div class="item-title"><?= e($row['sistem_adi']) ?></div>
                    <div class="item-sub"><?= e($row['birim_adi']) ?> • Talep #<?= e((string)$row['id']) ?></div>
                  </td>
                  <td>
                    <div class="text-main"><?= e($row['talep_notu'] ?? '-') ?></div>
                    <?php if ((int)$row['durum'] === 2 && !empty($row['red_neden'])): ?>
                      <div class="reason-chip">
                        <i class="bi bi-exclamation-circle"></i> Ret Nedeni: <?= e($row['red_neden']) ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="status-pill <?= getStatusClass((int)$row['durum']) ?>">
                      <?php if((int)$row['durum'] === 0): ?> <i class="bi bi-hourglass-split"></i>
                      <?php elseif((int)$row['durum'] === 1): ?> <i class="bi bi-check-circle"></i>
                      <?php elseif((int)$row['durum'] === 2): ?> <i class="bi bi-x-circle"></i>
                      <?php endif; ?>
                      <?= e(durum_text((int)$row['durum'])) ?>
                    </span>
                  </td>
                  <td class="text-muted fw-medium">
                    <?= e(date('d.m.Y', strtotime((string)$row['tarih']))) ?> <br>
                    <small><?= e(date('H:i', strtotime((string)$row['tarih']))) ?></small>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <div class="text-center mt-5 mb-5">
      <a href="index.php" class="text-decoration-none text-muted fw-semibold hover-primary">
        <i class="bi bi-arrow-left-circle me-1"></i> Yeni Talep Oluştur
      </a>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>