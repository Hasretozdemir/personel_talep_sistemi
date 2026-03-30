<?php
declare(strict_types=1);

session_start();

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$error = null;
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';

if ($requestMethod === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Kullanıcı adı ve şifre boş olamaz.';
    } else {
        // GEÇİCİ: Basit test için
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = 1;
            $_SESSION['admin_username'] = 'admin';
            $_SESSION['admin_name'] = 'Sistem Yöneticisi';
            $_SESSION['admin_level'] = 3;
            header('Location: index.php');
            exit;
        }
        
        // Veritabanı kontrolü
        $conn = db_connect();
        $result = pg_query_params($conn, 
            'SELECT id, kullanici_adi, sifre, ad_soyad, yetki_seviyesi, aktif FROM admin_kullanicilar WHERE kullanici_adi = $1', 
            [$username]
        );

        if ($result && pg_num_rows($result) > 0) {
            $admin = pg_fetch_assoc($result);
            
            if ($admin['aktif'] === 'f' || $admin['aktif'] === false || $admin['aktif'] === '0') {
                $error = 'Bu hesap devre dışı bırakılmış. Lütfen yönetici ile iletişime geçin.';
            } elseif (password_verify($password, $admin['sifre'])) {
                // Başarılı giriş
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = (int)$admin['id'];
                $_SESSION['admin_username'] = $admin['kullanici_adi'];
                $_SESSION['admin_name'] = $admin['ad_soyad'];
                $_SESSION['admin_level'] = (int)$admin['yetki_seviyesi'];
                
                // Son giriş tarihini güncelle
                pg_query_params($conn, 'UPDATE admin_kullanicilar SET son_giris = NOW() WHERE id = $1', [$admin['id']]);
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Kullanıcı adı veya şifre hatalı.';
            }
        } else {
            $error = 'Kullanıcı adı veya şifre hatalı.';
        }
    }
}
?>
<!doctype html>
<html lang="tr" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gazi IT | Yönetici Girişi</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <script src="../assets/js/theme.js"></script>
  
  <style>
    /* CSS Değişkenleri ile Tema Yönetimi */
    :root {
      --font-family: 'Plus Jakarta Sans', sans-serif;
      --bg-body: #f8fafc;
      --bg-surface: #ffffff;
      --text-main: #0f172a;
      --text-muted: #64748b;
      --border-color: #e2e8f0;
      --input-bg: #f8fafc;
      --primary: #2563eb;
      --primary-hover: #1d4ed8;
      --primary-light: #eff6ff;
      --error-bg: #fef2f2;
      --error-text: #ef4444;
      --error-border: #fca5a5;
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
      --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    }

    [data-theme="dark"] {
      --bg-body: #020617;
      --bg-surface: #0f172a;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --border-color: #1e293b;
      --input-bg: #1e293b;
      --primary: #3b82f6;
      --primary-hover: #60a5fa;
      --primary-light: rgba(59, 130, 246, 0.1);
      --error-bg: rgba(239, 68, 68, 0.1);
      --error-text: #f87171;
      --error-border: rgba(239, 68, 68, 0.3);
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.3);
      --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.4);
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: var(--font-family);
      background-color: var(--bg-body);
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* ── Sol Panel (Branding / Görsel Alan) ── */
    .login-left {
      flex: 1;
      background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 4rem;
      position: relative;
      overflow: hidden;
    }

    /* Kurumsal Arka Plan Deseni */
    .login-left::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background-image: radial-gradient(rgba(255, 255, 255, 0.1) 1px, transparent 1px);
      background-size: 30px 30px;
      opacity: 0.5;
    }
    
    /* Dekoratif Cam Efektli Şekiller */
    .glass-shape {
      position: absolute;
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 24px;
      transform: rotate(-15deg);
    }
    .shape-1 { width: 300px; height: 300px; top: -50px; left: -100px; }
    .shape-2 { width: 400px; height: 400px; bottom: -100px; right: -150px; border-radius: 100px; }

    .left-content {
      position: relative;
      z-index: 1;
      max-width: 480px;
      width: 100%;
    }

    .brand-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .brand-logo {
      width: 56px; height: 56px;
      background: white;
      border-radius: 16px;
      display: grid;
      place-items: center;
      font-size: 1.8rem;
      color: #2563eb;
      box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }

    .left-content h1 {
      font-size: 2.5rem;
      font-weight: 800;
      color: #ffffff;
      line-height: 1.2;
      margin-bottom: 1rem;
    }

    .left-content p {
      color: #bfdbfe;
      font-size: 1.1rem;
      line-height: 1.6;
      margin-bottom: 3rem;
    }

    .feature-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
    }

    .feature-card {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.15);
      padding: 1.2rem;
      border-radius: 16px;
      color: white;
      backdrop-filter: blur(10px);
    }

    .feature-card i {
      font-size: 1.5rem;
      color: #93c5fd;
      margin-bottom: 0.8rem;
      display: block;
    }

    .feature-card h3 {
      font-size: 0.95rem;
      font-weight: 600;
      margin-bottom: 0.4rem;
    }

    .feature-card p {
      font-size: 0.8rem;
      color: #bfdbfe;
      margin: 0;
    }

    /* ── Sağ Panel (Giriş Formu) ── */
    .login-right {
      width: 550px;
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 3rem 4rem;
      position: relative;
    }

    .theme-toggle-wrapper {
      position: absolute;
      top: 2rem;
      right: 2rem;
    }

    .theme-btn {
      width: 44px; height: 44px;
      border-radius: 12px;
      border: 1px solid var(--border-color);
      background: var(--bg-surface);
      color: var(--text-muted);
      cursor: pointer;
      display: grid;
      place-items: center;
      font-size: 1.2rem;
      transition: all 0.2s;
      box-shadow: var(--shadow-sm);
    }
    .theme-btn:hover {
      color: var(--primary);
      border-color: var(--primary);
    }

    .login-box {
      background: var(--bg-surface);
      padding: 3rem;
      border-radius: 24px;
      box-shadow: var(--shadow-lg);
      border: 1px solid var(--border-color);
      width: 100%;
    }

    .login-box h2 {
      font-size: 1.75rem;
      font-weight: 800;
      margin-bottom: 0.5rem;
      color: var(--text-main);
    }

    .login-box .sub-title {
      color: var(--text-muted);
      font-size: 0.95rem;
      margin-bottom: 2rem;
    }

    .input-group {
      margin-bottom: 1.5rem;
    }

    .input-group label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-main);
      margin-bottom: 0.5rem;
    }

    .input-wrapper {
      position: relative;
    }

    .input-wrapper i {
      position: absolute;
      left: 1.2rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      font-size: 1.1rem;
      transition: color 0.2s;
    }

    .input-wrapper input {
      width: 100%;
      height: 54px;
      background: var(--input-bg);
      border: 1.5px solid var(--border-color);
      border-radius: 12px;
      color: var(--text-main);
      padding: 0 1rem 0 3rem;
      font-size: 1rem;
      font-family: inherit;
      transition: all 0.2s;
      outline: none;
    }

    .input-wrapper input:focus {
      border-color: var(--primary);
      background: var(--bg-surface);
      box-shadow: 0 0 0 4px var(--primary-light);
    }

    .input-wrapper input:focus + i,
    .input-wrapper input:focus ~ i {
      color: var(--primary);
    }

    .submit-btn {
      width: 100%;
      height: 54px;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 12px;
      font-family: inherit;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      margin-top: 1rem;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .submit-btn:hover {
      background: var(--primary-hover);
      transform: translateY(-2px);
      box-shadow: 0 8px 20px var(--primary-light);
    }

    .error-box {
      background: var(--error-bg);
      border: 1px solid var(--error-border);
      color: var(--error-text);
      padding: 1rem;
      border-radius: 12px;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 1.5rem;
      font-weight: 500;
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 2rem;
      color: var(--text-muted);
      font-size: 0.95rem;
      text-decoration: none;
      transition: color 0.2s;
      width: 100%;
    }

    .back-link:hover {
      color: var(--primary);
    }

    /* Mobil Uyumluluk */
    @media (max-width: 992px) {
      .login-left { display: none; }
      .login-right { width: 100%; padding: 2rem 1.5rem; }
      .login-box { padding: 2rem 1.5rem; border: none; box-shadow: none; background: transparent;}
      body { background-color: var(--bg-surface); }
      .theme-toggle-wrapper { top: 1rem; right: 1rem; }
    }
  </style>
</head>
<body>

  <div class="login-left">
    <div class="glass-shape shape-1"></div>
    <div class="glass-shape shape-2"></div>
    
    <div class="left-content">
      <div class="brand-header">
        <div class="brand-logo"><i class="bi bi-hospital"></i></div>
        <h2 style="color: white; font-weight: 700; font-size: 1.5rem;">Gazi Hastanesi</h2>
      </div>
      
      <h1>IT Yönetim Paneli</h1>
      <p>Sistem kaynaklarını yönetin, personel yetkilendirmelerini yapılandırın ve ağ güvenliğini tek bir merkezden izleyin.</p>
      
      <div class="feature-grid">
        <div class="feature-card">
          <i class="bi bi-shield-lock"></i>
          <h3>Güvenli Erişim</h3>
          <p>Uçtan uca şifreli oturum yönetimi</p>
        </div>
        <div class="feature-card">
          <i class="bi bi-people"></i>
          <h3>Personel Yönetimi</h3>
          <p>Rol ve yetki tabanlı kontrol</p>
        </div>
        <div class="feature-card">
          <i class="bi bi-activity"></i>
          <h3>Sistem İzleme</h3>
          <p>Anlık log ve hata takibi</p>
        </div>
        <div class="feature-card">
          <i class="bi bi-hdd-network"></i>
          <h3>Veritabanı</h3>
          <p>Güvenli veri yedekleme ve erişim</p>
        </div>
      </div>
    </div>
  </div>

  <div class="login-right">
    <div class="theme-toggle-wrapper">
      <button class="theme-btn" id="manualThemeToggle" title="Temayı Değiştir">
        <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
      </button>
    </div>

    <div class="login-box">
      <h2>Yönetici Girişi</h2>
      <p class="sub-title">IT sistemine erişmek için bilgilerinizi girin.</p>

      <?php if ($error): ?>
        <div class="error-box">
          <i class="bi bi-exclamation-octagon-fill" style="font-size: 1.2rem;"></i>
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="input-group">
          <label for="username">Kullanıcı Adı</label>
          <div class="input-wrapper">
            <i class="bi bi-person-badge"></i>
            <input type="text" id="username" name="username" placeholder="Örn: sysadmin" required autocomplete="username">
          </div>
        </div>

        <div class="input-group">
          <label for="password">Şifre</label>
          <div class="input-wrapper">
            <i class="bi bi-key"></i>
            <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
          </div>
        </div>

        <button type="submit" class="submit-btn">
          Giriş Yap <i class="bi bi-arrow-right-short" style="font-size: 1.4rem;"></i>
        </button>
      </form>

      <a href="../index.php" class="back-link">
        <i class="bi bi-arrow-left"></i> Hastane Portalı'na Dön
      </a>
    </div>
  </div>

  <script>
    const themeToggleBtn = document.getElementById('manualThemeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const htmlEl = document.documentElement;

    // Eğer localStorage'da kayıtlı tema varsa uygula
    const savedTheme = localStorage.getItem('theme') || 'light';
    htmlEl.setAttribute('data-theme', savedTheme);
    updateIcon(savedTheme);

    themeToggleBtn.addEventListener('click', () => {
      const currentTheme = htmlEl.getAttribute('data-theme');
      const newTheme = currentTheme === 'light' ? 'dark' : 'light';
      
      htmlEl.setAttribute('data-theme', newTheme);
      localStorage.setItem('theme', newTheme);
      updateIcon(newTheme);
    });

    function updateIcon(theme) {
      if (theme === 'dark') {
        themeIcon.classList.remove('bi-moon-stars-fill');
        themeIcon.classList.add('bi-sun-fill');
      } else {
        themeIcon.classList.remove('bi-sun-fill');
        themeIcon.classList.add('bi-moon-stars-fill');
      }
    }
  </script>
</body>
</html>