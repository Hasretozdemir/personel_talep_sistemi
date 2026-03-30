// Tema Yöneticisi (Dark/Light Mode)
(function() {
  // Sayfa yüklenmeden önce çalışarak FOUC (stil gecikmesi) önler
  const savedTheme = localStorage.getItem('theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const themeToApply = savedTheme || (prefersDark ? 'dark' : 'light');
  document.documentElement.setAttribute('data-theme', themeToApply);
})();

document.addEventListener('DOMContentLoaded', () => {
  const toggles = document.querySelectorAll('.theme-toggle');
  
  function updateToggleIcons(theme) {
    toggles.forEach(toggle => {
      toggle.innerHTML = theme === 'dark' ? '<i class="bi bi-sun-fill" style="color:#fbbf24;"></i> Light Mode' : '<i class="bi bi-moon-stars-fill" style="color:#818cf8;"></i> Dark Mode';
    });
  }
  
  toggles.forEach(toggle => {
    toggle.addEventListener('click', (e) => {
      e.preventDefault();
      const currentTheme = document.documentElement.getAttribute('data-theme');
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
      
      document.documentElement.setAttribute('data-theme', newTheme);
      localStorage.setItem('theme', newTheme);
      updateToggleIcons(newTheme);
      
      // İsteğe bağlı: Küçük bir geçiş efekti animasyonu
      document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
      setTimeout(() => { document.body.style.transition = ''; }, 300);
    });
  });
  
  updateToggleIcons(document.documentElement.getAttribute('data-theme'));
});
