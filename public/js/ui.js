document.addEventListener('DOMContentLoaded', () => {
  const root = document.documentElement;
  const key = 'electrofix-ui-theme';
  const toggles = document.querySelectorAll('[data-ui-theme-toggle]');
  const labels = document.querySelectorAll('[data-ui-theme-toggle-label]');
  const stored = localStorage.getItem(key);
  const preferredDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  const current = stored || (preferredDark ? 'dark' : 'light');

  const applyTheme = (theme) => {
    const isDark = theme === 'dark';
    document.body.classList.toggle('theme-dark', isDark);
    root.setAttribute('data-theme', isDark ? 'dark' : 'light');
    root.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');
    localStorage.setItem(key, isDark ? 'dark' : 'light');

    labels.forEach((label) => {
      label.textContent = isDark ? 'Modo oscuro' : 'Modo claro';
    });

    toggles.forEach((btn) => {
      btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
      btn.setAttribute('title', isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
    });
  };

  applyTheme(current);

  if (!toggles.length) return;

  toggles.forEach((toggleBtn) => {
    toggleBtn.addEventListener('click', () => {
      const nextTheme = document.body.classList.contains('theme-dark') ? 'light' : 'dark';
      applyTheme(nextTheme);
    });
  });

  const alertsRoot = document.querySelector('[data-alerts-root]');
  if (alertsRoot) {
    const alerts = Array.from(alertsRoot.querySelectorAll('.alert'));
    if (alerts.length) {
      const sessionId = alertsRoot.dataset.sessionId;
      const sessionDismissKey = sessionId ? `electrofix-session-alert-dismissed:${sessionId}` : '';
      const sessionAlert = alertsRoot.querySelector('[data-session-alert]');
      if (sessionAlert && sessionDismissKey && localStorage.getItem(sessionDismissKey)) {
        sessionAlert.remove();
      }

      const removeAlerts = () => {
        alerts.forEach((alert) => {
          if (alert.hasAttribute('data-session-alert') && sessionDismissKey) {
            localStorage.setItem(sessionDismissKey, '1');
          }
          alert.remove();
        });
      };

      setTimeout(removeAlerts, 60000);
      window.addEventListener('pagehide', removeAlerts, { once: true });
      window.addEventListener('beforeunload', removeAlerts, { once: true });

      alertsRoot.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (target.matches('[data-alert-dismiss]')) {
          const alert = target.closest('.alert');
          if (alert) {
            if (alert.hasAttribute('data-session-alert') && sessionDismissKey) {
              localStorage.setItem(sessionDismissKey, '1');
            }
            alert.remove();
          }
        }
      });
    }
  }
});
