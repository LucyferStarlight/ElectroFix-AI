document.addEventListener('DOMContentLoaded', () => {
  const root = document.documentElement;
  const key = 'electrofix-ui-theme';
  const toggleBtn = document.querySelector('[data-ui-theme-toggle]');
  const current = localStorage.getItem(key) || 'light';

  if (current === 'dark') {
    root.setAttribute('data-theme', 'dark');
    document.body.classList.add('theme-dark');
  }

  if (!toggleBtn) {
    return;
  }

  toggleBtn.addEventListener('click', () => {
    const isDark = document.body.classList.toggle('theme-dark');
    root.setAttribute('data-theme', isDark ? 'dark' : 'light');
    localStorage.setItem(key, isDark ? 'dark' : 'light');
  });
});
