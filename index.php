<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/email.php';
require_once __DIR__ . '/config/bildirim.php';

$conn = db_connect();

$message = null;
$messageType = 'success';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';

if ($requestMethod === 'POST') {
    $personelAdSoyad = trim($_POST['personel_ad_soyad'] ?? '');
    $sicilNo = strtoupper(trim($_POST['sicil_no'] ?? ''));
    $birimId = (int)($_POST['birim_id'] ?? 0);
    $sistemId = (int)($_POST['sistem_id'] ?? 0);
    $talepNotu = trim($_POST['talep_notu'] ?? '');

    if ($personelAdSoyad === '' || $sicilNo === '' || $birimId <= 0 || $sistemId <= 0) {
        $messageType = 'danger';
        $message = 'Lütfen zorunlu alanları eksiksiz doldurun.';
    } else {
        $sql = 'INSERT INTO talepler (personel_ad_soyad, sicil_no, birim_id, sistem_id, talep_notu) VALUES ($1, $2, $3, $4, $5) RETURNING id';
        $result = pg_query_params($conn, $sql, [$personelAdSoyad, $sicilNo, $birimId, $sistemId, $talepNotu]);
        if ($result) {
            $talepId = (int)pg_fetch_result($result, 0, 0);
            
            // Sistem adını al
            $sistemResult = pg_query_params($conn, 'SELECT sistem_adi FROM sistemler WHERE id = $1', [$sistemId]);
            $sistemAdi = pg_fetch_result($sistemResult, 0, 0);
            
            // Bildirim oluştur
            bildirim_yeni_talep($conn, $talepId, $personelAdSoyad, $sistemAdi);
            
            // E-posta gönder (adminlere)
            $talepDetay = pg_fetch_assoc(pg_query_params($conn,
                'SELECT t.*, b.birim_adi, s.sistem_adi 
                 FROM talepler t
                 INNER JOIN birimler b ON b.id = t.birim_id
                 INNER JOIN sistemler s ON s.id = t.sistem_id
                 WHERE t.id = $1',
                [$talepId]
            ));
            
            $adminler = pg_fetch_all(pg_query($conn, 'SELECT email FROM admin_kullanicilar WHERE aktif = true AND email IS NOT NULL')) ?: [];
            email_yeni_talep($talepDetay, $adminler);
            
            $message = 'Talebiniz başarıyla kaydedildi. Durum sorgulama ekranından takip edebilirsiniz.';
            $_POST = [];
        } else {
            $messageType = 'danger';
            $message = 'Talep kaydedilemedi. Lütfen tekrar deneyin.';
        }
    }
}

$birimler = pg_query($conn, 'SELECT id, birim_adi FROM birimler ORDER BY birim_adi ASC');
$sistemler = pg_query($conn, 'SELECT id, sistem_adi FROM sistemler ORDER BY sistem_adi ASC');
?>
<!doctype html>
<html lang="tr" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gazi Hastanesi | Personel Yetki Talebi</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
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
      --primary-gradient: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
      
      --danger: #ef4444;
      --danger-bg: #fef2f2;
      --success: #10b981;
      --success-bg: #ecfdf5;
      
      --nav-bg: rgba(255, 255, 255, 0.9);
      --input-bg: #ffffff;
      
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow-lg: 0 10px 25px -5px rgb(0 0 0 / 0.08), 0 8px 10px -6px rgb(0 0 0 / 0.04);
    }

    [data-theme="dark"] {
      --bg-body: #020617;
      --bg-surface: #0f172a;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --border-color: #1e293b;
      
      --primary: #3b82f6;
      --primary-hover: #60a5fa;
      --primary-gradient: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
      
      --danger: #f87171;
      --danger-bg: rgba(239, 68, 68, 0.15);
      --success: #34d399;
      --success-bg: rgba(16, 185, 129, 0.15);
      
      --nav-bg: rgba(15, 23, 42, 0.9);
      --input-bg: #020617;
      
      --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
    }

    /* ── Genel Stiller ── */
    body {
      font-family: var(--font-family);
      background-color: var(--bg-body);
      color: var(--text-main);
      transition: background-color 0.3s, color 0.3s;
      -webkit-font-smoothing: antialiased;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── Navbar ── */
    .public-topbar {
      background-color: var(--nav-bg);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-bottom: 1px solid var(--border-color);
      padding: 1rem 0;
      position: sticky;
      top: 0;
      z-index: 1020;
      transition: all 0.3s;
    }
    .brand-mark {
      display: flex; align-items: center; gap: 10px;
      text-decoration: none; color: var(--text-main);
      font-weight: 800; font-size: 1.25rem; letter-spacing: -0.02em;
    }
    .brand-mark-icon {
      background: var(--primary-gradient);
      color: white; width: 36px; height: 36px;
      display: flex; align-items: center; justify-content: center;
      border-radius: 10px; font-size: 1.2rem;
    }
    
    .btn-nav-ghost {
      color: var(--text-muted); background: transparent; border: none;
      padding: 0.5rem 0.75rem; border-radius: 8px; font-weight: 600;
      transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
    }
    .btn-nav-ghost:hover { background: var(--border-color); color: var(--text-main); }
    
    .btn-outline-custom {
      border: 1.5px solid var(--border-color); color: var(--text-main); font-weight: 600;
      padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; transition: all 0.2s;
      display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-outline-custom:hover { border-color: var(--text-muted); background: var(--border-color); }

    /* ── Ana Layout (Split Card) ── */
    .request-shell {
      background: var(--bg-surface);
      border-radius: 1.5rem;
      box-shadow: var(--shadow-lg);
      border: 1px solid var(--border-color);
      overflow: hidden;
      margin-top: 1.5rem;
      margin-bottom: 3rem;
    }
    
    .info-panel {
      background: var(--primary-gradient);
      padding: 3.5rem 2.5rem;
      color: white;
      display: flex;
      flex-direction: column;
      justify-content: center;
      height: 100%;
    }
    .info-chip {
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(4px);
      display: inline-flex; align-items: center; gap: 6px;
      padding: 0.4rem 1rem; border-radius: 2rem;
      font-size: 0.85rem; font-weight: 700; margin-bottom: 1.5rem; width: fit-content;
    }
    .info-panel h1 { font-weight: 800; font-size: 2.25rem; margin-bottom: 1rem; line-height: 1.2; letter-spacing: -0.03em; }
    .info-panel p { color: rgba(255, 255, 255, 0.85); font-size: 1.05rem; margin-bottom: 2rem; line-height: 1.6; }
    
    .benefit-list { list-style: none; padding: 0; margin: 0; }
    .benefit-list li {
      display: flex; align-items: center; gap: 10px;
      margin-bottom: 1rem; font-weight: 500; font-size: 1rem;
      color: rgba(255, 255, 255, 0.95);
    }
    .benefit-list i { color: #60a5fa; font-size: 1.25rem; }

    /* ── Form Alanı ── */
    .form-panel { padding: 3.5rem 3rem; background: var(--bg-surface); }
    .form-panel h2 { font-weight: 800; font-size: 1.5rem; color: var(--text-main); margin-bottom: 0.5rem; }
    .form-panel .sub-text { color: var(--text-muted); font-size: 0.95rem; margin-bottom: 2rem; }
    
    .form-label-mini {
      font-size: 0.75rem; font-weight: 700; color: var(--text-muted);
      letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 0.5rem; display: block;
    }
    
    .form-control-modern, .form-select-modern {
      background-color: var(--input-bg);
      border: 1px solid var(--border-color);
      color: var(--text-main);
      padding: 0.875rem 1rem;
      border-radius: 0.75rem;
      font-size: 0.95rem;
      transition: all 0.2s;
    }
    .form-control-modern:focus, .form-select-modern:focus {
      border-color: var(--primary); background-color: var(--input-bg); color: var(--text-main);
      box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
    }
    /* Select içi Dark Mode Düzeltmesi */
    [data-theme="dark"] select option { background-color: var(--bg-surface); color: var(--text-main); }
    
    .btn-brand {
      background-color: var(--primary); color: white;
      border: none; padding: 0.875rem 1.5rem; font-weight: 700;
      border-radius: 0.75rem; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-brand:hover { background-color: var(--primary-hover); color: white; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); }
    
    /* ── Uyarı Mesajı ── */
    .alert { border-radius: 1rem; border: none; display: flex; align-items: center; gap: 12px; font-weight: 500; padding: 1rem 1.25rem; box-shadow: var(--shadow-sm); }
    .alert-success { background-color: var(--success-bg); color: var(--success); }
    .alert-danger { background-color: var(--danger-bg); color: var(--danger); }
    [data-theme="dark"] .alert-success { border: 1px solid rgba(16, 185, 129, 0.2); }
    [data-theme="dark"] .alert-danger { border: 1px solid rgba(239, 68, 68, 0.2); }

    .fade-in { animation: fadeIn 0.4s ease-out forwards; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    
    /* Responsive düzeltmeler */
    @media (max-width: 991.98px) {
      .info-panel { padding: 2.5rem 1.5rem; }
      .form-panel { padding: 2.5rem 1.5rem; }
    }
  </style>
</head>
<body>

  <header class="public-topbar">
    <div class="container d-flex justify-content-between align-items-center">
      <a class="brand-mark" href="index.php">
        <span class="brand-mark-icon"><i class="bi bi-hospital"></i></span>
        <span class="d-none d-sm-inline">GAZİ HASTANESİ</span>
      </a>
      
      <div class="d-flex gap-2 align-items-center">
        <a href="sorgula.php" class="btn-nav-ghost"><i class="bi bi-search"></i> <span class="d-none d-md-inline">Durum Sorgula</span></a>
        
        <button class="btn-nav-ghost" id="manualThemeToggle" title="Tema Değiştir">
          <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
        </button>
        
        <div class="vr mx-1" style="background-color: var(--border-color); width: 1.5px; height: 20px;"></div>
        
        <a href="admin/login.php" class="btn-outline-custom"><i class="bi bi-shield-lock"></i> <span class="d-none d-sm-inline">Admin</span></a>
      </div>
    </div>
  </header>

  <main class="container py-4 flex-grow-1">
    
    <?php if ($message): ?>
      <div class="alert alert-<?= e($messageType) ?> fade-in mb-2" role="alert">
        <i class="bi <?= $messageType === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> fs-5"></i>
        <span><?= e($message) ?></span>
      </div>
    <?php endif; ?>

    <section class="request-shell">
      <div class="row g-0">
        <div class="col-lg-5 col-xl-4 d-none d-lg-block">
          <div class="info-panel">
            <span class="info-chip"><i class="bi bi-shield-check"></i> Güvenli Talep Akışı</span>
            <h1>Kurumsal Erişim Yönetimi</h1>
            <p>Sicil doğrulaması ile yalnızca aktif personel için yetki ve şifre talebi oluşturabilirsiniz. Tüm başvurular Bilgi İşlem birimi tarafından anında kayıt altına alınır.</p>
            <ul class="benefit-list">
              <li><i class="bi bi-check2-circle"></i> Sicil numarası ile doğrulama</li>
              <li><i class="bi bi-check2-circle"></i> Birime özel yetki standardizasyonu</li>
              <li><i class="bi bi-check2-circle"></i> Canlı talep durumu takibi</li>
            </ul>
          </div>
        </div>

        <div class="col-lg-7 col-xl-8">
          <div class="form-panel">
            <h2>Yetki ve Şifre Talep Formu</h2>
            <p class="sub-text">İşleminizin hızlı ilerlemesi için bilgileri eksiksiz ve doğru doldurun.</p>

            <form method="post" class="row g-4" id="talepForm">
              <div class="col-md-6">
                <label class="form-label form-label-mini">SİCİL NUMARASI *</label>
                <div class="input-group">
                  <span class="input-group-text bg-transparent border-end-0 text-muted" style="border-color: var(--border-color);"><i class="bi bi-person-vcard"></i></span>
                  <input type="text" id="sicilNo" name="sicil_no" class="form-control form-control-modern border-start-0 ps-0" maxlength="30" required value="<?= e($_POST['sicil_no'] ?? '') ?>" placeholder="Örn: 123456">
                </div>
              </div>
              
              <div class="col-md-6">
                <label class="form-label form-label-mini">AD SOYAD *</label>
                <div class="input-group">
                  <span class="input-group-text bg-transparent border-end-0 text-muted" style="border-color: var(--border-color);"><i class="bi bi-person"></i></span>
                  <input type="text" id="adSoyad" name="personel_ad_soyad" class="form-control form-control-modern border-start-0 ps-0" maxlength="150" required value="<?= e($_POST['personel_ad_soyad'] ?? '') ?>" placeholder="Tam adınızı girin">
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label form-label-mini">GÖREVLİ OLDUĞUNUZ BİRİM *</label>
                <select name="birim_id" class="form-select form-select-modern" required>
                  <option value="" disabled <?= empty($_POST['birim_id']) ? 'selected' : '' ?>>Lütfen birim seçin...</option>
                  <?php while ($birim = pg_fetch_assoc($birimler)): ?>
                    <option value="<?= e((string)$birim['id']) ?>" <?= ((int)($_POST['birim_id'] ?? 0) === (int)$birim['id']) ? 'selected' : '' ?>><?= e($birim['birim_adi']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label form-label-mini">İSTENİLEN SİSTEM YETKİSİ *</label>
                <select name="sistem_id" class="form-select form-select-modern" required>
                  <option value="" disabled <?= empty($_POST['sistem_id']) ? 'selected' : '' ?>>Sistem seçin...</option>
                  <?php while ($sistem = pg_fetch_assoc($sistemler)): ?>
                    <option value="<?= e((string)$sistem['id']) ?>" <?= ((int)($_POST['sistem_id'] ?? 0) === (int)$sistem['id']) ? 'selected' : '' ?>><?= e($sistem['sistem_adi']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label form-label-mini">TALEP DETAYI VE NOTUNUZ</label>
                <textarea name="talep_notu" class="form-control form-control-modern" rows="3" placeholder="Erişim nedeninizi veya yaşadığınız sorunu kısaca yazın... (Opsiyonel)"><?= e($_POST['talep_notu'] ?? '') ?></textarea>
              </div>

              <div class="col-12 d-flex flex-wrap align-items-center gap-3 mt-4 pt-2">
                <button type="submit" class="btn btn-brand flex-grow-1 flex-md-grow-0">
                  <i class="bi bi-send-fill"></i> Talebi Gönder
                </button>
                <a href="sorgula.php" class="text-muted fw-semibold text-decoration-none d-inline-flex align-items-center gap-1" style="font-size: 0.95rem;">
                  <i class="bi bi-question-circle"></i> Durum Sorgula
                </a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/app.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const themeToggleBtn = document.getElementById('manualThemeToggle');
      const themeIcon = document.getElementById('themeIcon');
      const htmlEl = document.documentElement;

      // LocalStorage'dan kontrol et
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
          themeIcon.classList.remove('bi-moon-stars-fill');
          themeIcon.classList.add('bi-sun-fill', 'text-warning');
        } else {
          themeIcon.classList.remove('bi-sun-fill', 'text-warning');
          themeIcon.classList.add('bi-moon-stars-fill');
        }
      }
    });
  </script>
</body>
</html>