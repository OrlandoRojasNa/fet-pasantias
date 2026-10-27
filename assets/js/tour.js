/* Recorrido guiado de Pasantías FET.
 * Resalta elementos reales de cada pantalla y explica su uso según el perfil.
 * Se abre solo la primera vez que cada usuario entra al panel; después, con el botón (?). */
(() => {
  'use strict';
  const body = document.body;
  const role = body.dataset.role || '';
  const user = body.dataset.user || '0';
  const page = body.dataset.page || '';
  const detail = /[?&]id=\d+/.test(location.search);
  const seenKey = 'fet-tour-visto-' + user;
  const store = {
    get(k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set(k, v) { try { localStorage.setItem(k, v); } catch (e) { /* almacenamiento no disponible */ } }
  };

  const is = (...r) => r.includes(role);

  /* Textos del menú según el perfil: cada uno explica para qué le sirve ese módulo a quien lo usa. */
  const navText = {
    dashboard: [
      { el: '#tour-welcome', title: 'Tu panel', text: 'El panel resume lo más importante para tu perfil y la fecha de hoy.' },
      { el: '#tour-metrics', title: 'Cifras rápidas', text: 'Cada tarjeta lleva al módulo correspondiente. Pulsa el enlace inferior para ir directo.' },
      { el: '#tour-my-work', title: is('student') ? 'Mis pasantías' : is('company') ? 'Tus pasantes' : 'Cola de revisión', text: is('student') ? 'Tu pasantía, cuántos documentos van aprobados y si alguno debe corregirse.' : is('company') ? 'Tus pasantes, los documentos por avalar y si falta tu evaluación de desempeño.' : 'Pasantías con documentos esperando tu calificación, empezando por las más antiguas.' }
    ],
    opportunities: [
      { el: '#tour-alliance', title: 'Tu alianza', text: 'Cupos de pasantía en uso y disponibles. Cada pasante aceptado ocupa un cupo hasta que su pasantía termina.' },
      { el: '#tour-filters', title: 'Filtra por tipo', text: 'Muestra solo convocatorias de pasantía u ofertas laborales.' },
      { el: '#tour-new-opp', title: 'Nueva publicación', text: is('company') ? 'Crea la convocatoria u oferta y envíala a verificación. La FET la revisa antes de mostrarla a los estudiantes.' : 'Registra una publicación; puedes dejarla verificada directamente.' },
      { el: '#tour-opps', title: 'Publicaciones', text: is('student') ? 'Pulsa «Postularme» o «Aplicar con mi hoja de vida». En las ofertas laborales la hoja de vida en PDF es obligatoria.' : 'Cada tarjeta muestra estado, cierre y postulaciones. Cambia el estado desde el selector (con «Publicada» la verificas).' },
      { el: '#tour-apps', title: is('student') ? 'Mis postulaciones' : 'Postulaciones recibidas', text: is('student') ? 'Sigue el estado de cada postulación. Si te aceptan en una pasantía, aparece en «Pasantías».' : 'Descarga la hoja de vida y cambia el estado. Al aceptar una postulación de pasantía se crea la pasantía y se ocupa un cupo.' }
    ],
    internships: detail ? [
      { el: '#tour-int-info', title: 'Datos de la pasantía', text: 'Estudiante, empresa evaluadora, docente líder asignado, periodo y cuántos documentos están aprobados.' },
      { el: '#tour-int-upload', title: 'Subir documento', text: 'Elige el tipo (plan de práctica, informe parcial, informe final, certificado…) y adjunta el archivo. Tu empresa y tu docente reciben un aviso.' },
      { el: '#tour-int-eval', title: 'Evaluar el desempeño', text: 'Al terminar la práctica califica al pasante de 1 a 5 y describe su desempeño. El docente usará esta evaluación para decidir el resultado.' },
      { el: '#tour-int-verdict', title: 'Veredicto final', text: 'Con promedio de notas de 3.0 o más la pasantía se aprueba; con menos de 3.0 se reprueba. La plataforma sugiere el resultado; tú lo confirmas con una justificación. El veredicto no se puede cambiar.' },
      { el: '#tour-int-assign', title: 'Asignar responsables', text: 'Define la empresa evaluadora, el docente líder asignado, el periodo y el estado de la pasantía.' },
      { el: '#tour-int-final', title: 'Evaluación final', text: is('student') ? 'Aquí verás la calificación y los comentarios de la empresa y, al final, el resultado y la justificación del docente.' : is('company') ? 'Tu evaluación de desempeño queda aquí y el docente la ve. Cuando él decida, verás el resultado.' : 'Evaluación de la empresa a la izquierda y veredicto del docente a la derecha.' },
      { el: '#tour-int-history', title: 'Historial de auditoría', text: 'Quién subió, revisó, calificó o descargó cada documento y cuándo. Solo lo ven los docentes líderes y el administrador.' },
      { el: '#tour-int-docs', title: 'Documentos de la pasantía', text: is('student') ? 'Cada documento muestra su estado. Si te lo devuelven, lee la observación y pulsa «Corregir» para subir la nueva versión.' : is('company') ? 'Descarga cada documento y pulsa «Revisar» para avalarlo o devolverlo con observaciones.' : is('leader') ? 'Pulsa «Revisar», escribe la nota de 0.0 a 5.0 y aprueba el documento (3.0 o más) o devuélvelo con observaciones.' : 'Estado de cada documento y las observaciones de la empresa y el docente.' }
    ] : [
      { el: '#tour-int-intro', title: 'Pasantías', text: is('student') ? 'Tu pasantía se crea cuando una empresa acepta tu postulación a una práctica.' : is('company') ? 'Aquí están los estudiantes que aceptaste como pasantes.' : 'Todas las pasantías de la FET y su avance.' },
      { el: '#tour-int-new', title: 'Registrar pasantía', text: 'Registra una pasantía a mano cuando no viene de una postulación, con su estudiante, empresa y docente.' },
      { el: '#tour-int-list', title: 'Seguimiento', text: 'Cada fila muestra la empresa, el docente, cuántos documentos están aprobados o por revisar y el estado o resultado final. Pulsa «Abrir» para ver el detalle.' }
    ],
    alliances: [
      { el: '#tour-new-alliance', title: 'Nueva alianza', text: 'Registra la empresa, crea su cuenta de acceso (NIT o correo y contraseña inicial) y define sus cupos de pasantía.' },
      { el: '#tour-alliance-kpis', title: 'Cupos', text: 'Alianzas vigentes, cupos totales, pasantes en curso y cupos disponibles.' },
      { el: '#tour-alliances', title: 'Empresas aliadas', text: is('admin') ? 'Pulsa «Editar o ampliar» para subir los cupos, renovar la vigencia o suspender la alianza.' : 'Ocupación de cupos y convocatorias abiertas de cada empresa.' }
    ],
    reports: [
      { el: '#tour-period', title: 'Reporte por periodo', text: 'Elige las fechas y pulsa «Aplicar». «Exportar CSV» descarga el detalle para Excel.' },
      { el: '#tour-indicators', title: 'Resumen', text: 'Pasantías, resultados, nota promedio y documentos pendientes.' },
      { el: '#tour-compliance', title: 'Por alianza', text: 'Ocupación de cupos y resultados de cada empresa.' },
      { el: '#tour-audit', title: 'Bitácora de auditoría', text: 'Registro de quién hizo qué y cuándo. Filtra por usuario.' }
    ],
    users: [
      { el: '#tour-new-user', title: 'Crear usuario', text: 'Asigna identificación, correo, perfil y una contraseña inicial de al menos ocho caracteres.' },
      { el: '#tour-backup', title: 'Respaldo SQL', text: 'Descarga una copia completa de la base de datos con su huella SHA-256 para verificarla.' },
      { el: '#tour-users-table', title: 'Cuentas', text: 'Cambia el perfil, desbloquea, restablece la contraseña o desactiva una cuenta. No puedes modificar tu propia cuenta.' }
    ],
    guide: [
      { el: '#tour-guide-hero', title: 'Guía de uso', text: 'Aquí encuentras el flujo de trabajo de tu perfil, la descripción de cada módulo y las preguntas frecuentes.' }
    ],
    notifications: []
  };

  function buildSteps(full) {
    let steps = (pages[page] || []).slice();
    if (full) {
      const nav = [];
      document.querySelectorAll('[data-tour-nav]').forEach(a => {
        const t = navText[a.dataset.tourNav];
        if (t) nav.push({ el: a, title: t[0], text: t[1] });
      });
      steps = intro.slice(0, 1).concat(nav, intro.slice(1), steps);
    }
    return steps.filter(s => {
      const el = typeof s.el === 'string' ? document.querySelector(s.el) : s.el;
      if (!el) return false;
      const r = el.getBoundingClientRect();
      return r.width > 0 && r.height > 0;
    });
  }

  let steps = [], index = 0, layer, spot, card, lastFocus;
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function build() {
    layer = document.createElement('div');
    layer.className = 'tour-layer';
    layer.innerHTML = '<div class="tour-spot"></div><div class="tour-card" role="dialog" aria-modal="true" aria-labelledby="tour-title" tabindex="-1">' +
      '<div class="tour-progress"><i></i></div><span class="tour-count"></span><h2 id="tour-title"></h2><p class="tour-text"></p>' +
      '<div class="tour-actions"><button type="button" class="tour-skip">Salir</button><span></span>' +
      '<button type="button" class="button button-ghost button-small tour-prev">Anterior</button>' +
      '<button type="button" class="button button-primary button-small tour-next">Siguiente</button></div></div>';
    document.body.appendChild(layer);
    spot = layer.querySelector('.tour-spot');
    card = layer.querySelector('.tour-card');
    layer.querySelector('.tour-skip').addEventListener('click', end);
    layer.querySelector('.tour-prev').addEventListener('click', () => go(index - 1));
    layer.querySelector('.tour-next').addEventListener('click', () => (index < steps.length - 1 ? go(index + 1) : end()));
    layer.addEventListener('click', e => { if (e.target === layer) end(); });
    document.addEventListener('keydown', onKey);
    window.addEventListener('resize', place);
    window.addEventListener('scroll', place, { passive: true });
  }

  function onKey(e) {
    if (!layer) return;
    if (e.key === 'Escape') end();
    else if (e.key === 'ArrowRight') index < steps.length - 1 ? go(index + 1) : end();
    else if (e.key === 'ArrowLeft' && index > 0) go(index - 1);
  }

  function target() { const s = steps[index]; return typeof s.el === 'string' ? document.querySelector(s.el) : s.el; }

  function go(i) {
    index = Math.max(0, Math.min(i, steps.length - 1));
    const s = steps[index], el = target();
    const inSidebar = el.closest && el.closest('[data-sidebar]');
    const sidebar = document.querySelector('[data-sidebar]');
    if (sidebar && innerWidth <= 900) sidebar.classList.toggle('open', !!inSidebar);
    el.scrollIntoView({ block: 'center', behavior: reduce ? 'auto' : 'smooth' });
    card.querySelector('.tour-count').textContent = 'Paso ' + (index + 1) + ' de ' + steps.length;
    card.querySelector('#tour-title').textContent = s.title;
    card.querySelector('.tour-text').textContent = s.text;
    card.querySelector('.tour-progress i').style.width = ((index + 1) / steps.length * 100) + '%';
    card.querySelector('.tour-prev').disabled = index === 0;
    card.querySelector('.tour-next').textContent = index === steps.length - 1 ? 'Terminar' : 'Siguiente';
    setTimeout(place, reduce ? 0 : 260);
    place();
    card.focus({ preventScroll: true });
  }

  function place() {
    if (!layer) return;
    const r = target().getBoundingClientRect(), pad = 8;
    Object.assign(spot.style, { top: (r.top - pad) + 'px', left: (r.left - pad) + 'px', width: (r.width + pad * 2) + 'px', height: (r.height + pad * 2) + 'px' });
    const cw = Math.min(360, innerWidth - 32), ch = card.offsetHeight || 200;
    let top = r.bottom + 16, left = r.left;
    if (top + ch > innerHeight - 12) top = r.top - ch - 16;
    if (top < 12) { top = Math.min(innerHeight - ch - 12, Math.max(12, r.top + 12)); left = r.right + 16; }
    if (left + cw > innerWidth - 16) left = innerWidth - cw - 16;
    card.style.top = Math.max(12, top) + 'px';
    card.style.left = Math.max(16, left) + 'px';
    card.style.width = cw + 'px';
  }

  function start(full) {
    if (layer) return;
    steps = buildSteps(full);
    if (!steps.length) steps = buildSteps(true);
    if (!steps.length) return;
    lastFocus = document.activeElement;
    build();
    go(0);
  }

  function end() {
    if (!layer) return;
    store.set(seenKey, '1');
    document.removeEventListener('keydown', onKey);
    window.removeEventListener('resize', place);
    window.removeEventListener('scroll', place);
    layer.remove(); layer = null;
    const sidebar = document.querySelector('[data-sidebar]');
    if (sidebar) sidebar.classList.remove('open');
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-tour-start]').forEach(b => b.addEventListener('click', () => start(page === 'dashboard' || page === 'guide')));
    const forced = /[?&]tour=1/.test(location.search);
    if (forced || (page === 'dashboard' && !store.get(seenKey))) setTimeout(() => start(true), 500);
  });
})();
