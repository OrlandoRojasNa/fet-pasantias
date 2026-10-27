document.addEventListener('DOMContentLoaded', () => {
  'use strict';
  const toggle = document.querySelector('[data-password-toggle]');
  if (toggle) {
    toggle.addEventListener('click', () => {
      const input = document.querySelector('#password');
      const visible = input.type === 'text';
      input.type = visible ? 'password' : 'text';
      toggle.textContent = visible ? 'Ver' : 'Ocultar';
      toggle.setAttribute('aria-pressed', String(!visible));
    });
  }

  document.querySelectorAll('[data-open]').forEach(button => button.addEventListener('click', () => {
    const dialog = document.getElementById(button.dataset.open);
    if (dialog) dialog.showModal();
  }));
  document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog')?.close()));
  document.querySelectorAll('dialog').forEach(dialog => dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); }));
  document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));
  document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  }));

  const menu = document.querySelector('[data-menu]');
  const sidebar = document.querySelector('[data-sidebar]');
  if (menu && sidebar) {
    menu.addEventListener('click', event => {
      event.stopPropagation();
      const open = sidebar.classList.toggle('open');
      menu.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('click', event => {
      if (innerWidth <= 900 && !sidebar.contains(event.target) && !menu.contains(event.target) && !event.target.closest('.tour-layer')) {
        sidebar.classList.remove('open');
        menu.setAttribute('aria-expanded', 'false');
      }
    });
  }

  const bell = document.querySelector('[data-bell]');
  const panel = document.querySelector('[data-bell-panel]');
  if (bell && panel) {
    const close = () => { panel.hidden = true; bell.setAttribute('aria-expanded', 'false'); };
    bell.addEventListener('click', event => {
      event.stopPropagation();
      panel.hidden = !panel.hidden;
      bell.setAttribute('aria-expanded', String(!panel.hidden));
    });
    document.addEventListener('click', event => { if (!panel.contains(event.target)) close(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
  }
});
