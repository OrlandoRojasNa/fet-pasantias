/* Recorrido guiado de la Intranet Privada FET.
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
    dashboard: ['Inicio', is('company') ? 'El panel de tu empresa: vacantes creadas, publicadas, en revisión y postulaciones recibidas.' : 'Tu resumen: oportunidades abiertas, proyectos, convenios y tus compromisos próximos.'],
    opportunities: ['Oportunidades · ODS 8', is('company') ? 'Publica tus vacantes de práctica o empleo y decide sobre los estudiantes que se postulan.' : is('student') ? 'Prácticas, empleos y formación verificados por la FET. Aquí te postulas.' : is('admin', 'manager') ? 'Verifica las vacantes que envían las empresas antes de publicarlas.' : 'Prácticas, empleos y formación verificados por la FET.'],
    internships: ['Pasantías', is('student') ? 'Sube aquí los documentos de tu pasantía y consulta las observaciones, la evaluación de la empresa y el resultado final.' : is('company') ? 'Revisa y avala los documentos de tus pasantes y registra la evaluación de su desempeño.' : is('leader') ? 'Pasantías que validas: apruebas los documentos y, al final, decides si el estudiante aprueba o reprueba la práctica.' : 'Seguimiento de todas las pasantías: asigna la empresa evaluadora y el docente que valida.'],
    projects: ['Innovación · ODS 9', 'Proyectos con equipo, hitos y evidencias.'],
    alliances: ['Alianzas · ODS 17', 'Aliados, convenios y actividades con resultados.'],
    minutes: ['Actas y compromisos', 'Actas en Formato 1 y compromisos con responsable, fecha y evidencia.'],
    reports: ['Indicadores ODS', 'Indicadores por periodo y exportación a Excel (CSV).'],
    quality: ['Calidad y pruebas', 'Comprobaciones automáticas, pruebas de flujos, hallazgos y respaldo.'],
    users: ['Usuarios', 'Cuentas, perfiles y contraseñas.'],
    guide: ['Guía de uso', 'Explicación de cada módulo y preguntas frecuentes.']
  };

  /* Resumen inicial del recorrido: qué hace cada perfil en la plataforma. */
  const roleIntro = {
    student: 'Como estudiante buscas oportunidades, te postulas y, cuando una empresa te acepta en una práctica, subes aquí los documentos de tu pasantía. La empresa evalúa tu desempeño y el docente decide si apruebas.',
    company: 'Como empresa publicas vacantes, eliges a tus pasantes, avalas los documentos que suben y, al final, evalúas su desempeño para que el docente emita el resultado.',
    leader: 'Como docente lideras proyectos y actas y validas las pasantías que te asignan: apruebas documentos y emites el veredicto final con base en la evaluación de la empresa.',
    manager: 'Como gestor institucional verificas las vacantes de las empresas, asignas el docente y la empresa de cada pasantía y sigues convenios, actas e indicadores.',
    admin: 'Como administrador gestionas cuentas y perfiles, verificas vacantes, supervisas las pasantías y respaldas la información.'
  };

  const intro = [
    { el: '#tour-sidebar', title: 'Bienvenido a la Intranet Privada FET', text: (roleIntro[role] ? roleIntro[role] + ' ' : '') + 'Este menú reúne solo los módulos de tu perfil.' },
    { el: '#tour-help', title: 'Tutorial siempre disponible', text: 'Con este botón, o con el círculo (?) de abajo a la derecha, repites la explicación de la pantalla en la que estés.' },
    { el: '#tour-bell', title: 'Avisos', text: 'La campana muestra cuántos avisos tienes sin leer: compromisos asignados, resultados de postulaciones y solicitudes por revisar.' },
    { el: '#tour-user', title: 'Tu cuenta', text: 'Aquí ves tu nombre y perfil. El ícono de salida cierra la sesión de forma segura.' }
  ];

  const pages = {
    dashboard: [
      { el: '#tour-welcome', title: 'Tu panel', text: is('company') ? 'Este es el panel de tu empresa: vacantes creadas, publicadas, en revisión y postulaciones recibidas.' : 'El panel resume lo más importante para tu perfil y la fecha de hoy.' },
      { el: '#tour-metrics', title: 'Cifras rápidas', text: 'Cada tarjeta lleva al módulo correspondiente. Pulsa el enlace inferior para ir directo.' },
      { el: '#tour-my-commitments', title: is('company') ? 'Vacantes recientes' : 'Tus compromisos', text: is('company') ? 'Tus últimas vacantes con su estado y número de postulaciones. Los pasantes que aceptes aparecerán en «Pasantías».' : 'Los compromisos de las actas que te asignaron, ordenados por fecha. Un punto rojo indica que ya venció.' },
      { el: '#tour-ods', title: 'Tres ejes del proyecto', text: 'La intranet organiza el trabajo en torno a los ODS 8, 9 y 17.' }
    ],
    opportunities: [
      { el: '#tour-filters', title: 'Filtra por tipo', text: 'Muestra solo prácticas, empleos o formación.' },
      { el: '#tour-new-opp', title: 'Crear una oportunidad', text: is('company') ? 'Registra tu vacante y envíala a revisión. La FET la verifica antes de publicarla y recibirás los postulantes aquí mismo.' : 'Registra una convocatoria. Puedes publicarla verificada o dejarla en revisión.' },
      { el: '#tour-opps', title: 'Convocatorias', text: is('student') ? 'Pulsa «Postularme» y cuenta por qué te interesa. Verás el resultado en «Mis postulaciones» y en la campana.' : 'Cada tarjeta muestra estado, cierre y número de postulaciones. Cambia el estado desde el selector.' },
      { el: '#tour-apps', title: is('student') ? 'Mis postulaciones' : 'Postulaciones recibidas', text: is('student') ? 'Sigue el estado de cada postulación. Si una empresa te acepta en una práctica, tu pasantía aparece en «Pasantías».' : 'Cambia el estado de cada postulación; el estudiante recibe un aviso. Al aceptar una postulación a una práctica se crea la pasantía.' }
    ],
    internships: detail ? [
      { el: '#tour-int-info', title: 'Datos de la pasantía', text: 'Estudiante, empresa evaluadora, docente que valida, periodo y cuántos documentos están aprobados.' },
      { el: '#tour-int-upload', title: 'Subir documento', text: 'Elige el tipo (plan de práctica, informe parcial, informe final, certificado…) y adjunta el archivo. Tu empresa y tu docente reciben un aviso.' },
      { el: '#tour-int-eval', title: 'Evaluar el desempeño', text: 'Al terminar la práctica califica al pasante de 1 a 5 y describe su desempeño. El docente usará esta evaluación para decidir el resultado.' },
      { el: '#tour-int-verdict', title: 'Veredicto final', text: 'Con promedio de notas de 3.0 o más la pasantía se aprueba; con menos de 3.0 se reprueba. La plataforma sugiere el resultado; tú lo confirmas con una justificación. El veredicto no se puede cambiar.' },
      { el: '#tour-int-assign', title: 'Asignar responsables', text: 'Define la empresa evaluadora, el docente que valida y el periodo de la pasantía.' },
      { el: '#tour-int-final', title: 'Evaluación final', text: is('student') ? 'Aquí verás la calificación y los comentarios de la empresa y, al final, el resultado y la justificación del docente.' : is('company') ? 'Tu evaluación de desempeño queda aquí y el docente la ve. Cuando él decida, verás el resultado.' : 'Evaluación de la empresa a la izquierda y veredicto del docente a la derecha.' },
      { el: '#tour-int-docs', title: 'Documentos de la pasantía', text: is('student') ? 'Cada documento muestra su estado. Si te lo devuelven, lee la observación y pulsa «Corregir» para subir la nueva versión.' : is('company') ? 'Descarga cada documento y pulsa «Revisar» para avalarlo o devolverlo con observaciones.' : is('leader') ? 'Pulsa «Revisar», escribe la nota de 0.0 a 5.0 y aprueba el documento (3.0 o más) o devuélvelo con observaciones.' : 'Estado de cada documento y las observaciones de la empresa y el docente.' }
    ] : [
      { el: '#tour-int-intro', title: 'Pasantías', text: is('student') ? 'Tu pasantía se crea cuando una empresa acepta tu postulación a una práctica.' : is('company') ? 'Aquí están los estudiantes que aceptaste como pasantes.' : is('leader') ? 'Aquí están las pasantías que te asignaron para validar.' : 'Todas las pasantías de la FET y su avance.' },
      { el: '#tour-int-new', title: 'Registrar pasantía', text: 'Registra una pasantía a mano cuando no viene de una postulación, con su estudiante, empresa y docente.' },
      { el: '#tour-int-list', title: 'Seguimiento', text: 'Cada fila muestra la empresa, el docente, cuántos documentos están aprobados o por revisar y el estado o resultado final. Pulsa «Abrir» para ver el detalle.' }
    ],
    projects: [
      { el: '#tour-new-project', title: 'Nuevo proyecto', text: 'Define nombre, ODS, líder y fechas. El líder queda como integrante del equipo.' },
      { el: '#tour-projects', title: 'Seguimiento de proyectos', text: 'El porcentaje se calcula con los hitos completados. En cada hito puedes escribir la evidencia o adjuntar un archivo, y agregar integrantes al equipo.' }
    ],
    alliances: [
      { el: '#tour-new-partner', title: 'Aliados y convenios', text: 'Registra primero el aliado y luego el convenio con su responsable, vigencia y referencia de evidencia.' },
      { el: '#tour-agreements', title: 'Convenios', text: 'Cada convenio acumula actividades con resultados. Así se mide el ODS 17.' }
    ],
    minutes: detail ? [
      { el: '#tour-acta', title: 'Acta en Formato 1', text: 'El acta conserva la estructura institucional: participantes, objetivos, desarrollo, compromisos, conclusión, agenda y firmas. Pulsa «Imprimir o guardar PDF» para obtener el documento.' },
      { el: '#tour-commitments', title: 'Compromisos del acta', text: 'La barra muestra cuántos están cumplidos. El responsable pulsa «Actualizar» para registrar la evidencia.' }
    ] : [
      { el: '#tour-kpis', title: 'Cumplimiento', text: 'Actas registradas, compromisos cumplidos, pendientes, vencidos y el porcentaje general de cumplimiento.' },
      { el: '#tour-new-minute', title: 'Registrar un acta', text: 'El formulario sigue el Formato 1 de la FET. Después de guardarla, agrega sus compromisos desde el detalle.' },
      { el: '#tour-minutes', title: 'Actas 1 a 6', text: 'Cada tarjeta abre el acta completa y muestra el avance de sus compromisos.' },
      { el: '#tour-matrix', title: 'Matriz de compromisos', text: 'Filtra por acta, responsable o estado. Con «Actualizar» registras el avance y adjuntas la evidencia; sin evidencia no se puede completar.' }
    ],
    reports: [
      { el: '#tour-period', title: 'Reporte por periodo', text: 'Elige las fechas y pulsa «Aplicar». «Exportar CSV» descarga los indicadores para abrirlos en Excel.' },
      { el: '#tour-indicators', title: 'Indicadores ODS', text: 'Cada indicador muestra su meta y cuántos registros se usaron en el cálculo.' },
      { el: '#tour-compliance', title: 'Cumplimiento por acta', text: 'Compara cuántos compromisos de cada acta se cumplieron.' },
      { el: '#tour-audit', title: 'Auditoría', text: 'Registro de quién hizo qué y cuándo.' }
    ],
    quality: [
      { el: '#tour-checks', title: 'Comprobaciones automáticas', text: 'Se recalculan en cada visita: control de acceso, CSRF, contraseñas, llaves foráneas, evidencias y auditoría.' },
      { el: '#tour-flows', title: 'Flujos de prueba', text: 'Resultado de la prueba automática que inicia sesión con cada perfil. Abre un flujo para ver cada verificación.' },
      { el: '#hallazgos', title: 'Hallazgos', text: 'Registra defectos con prioridad y responsable y cambia su estado hasta verificarlos.' },
      { el: '#tour-backup', title: 'Respaldo SQL', text: 'Descarga una copia completa de la base de datos con su huella SHA-256 para verificarla.' }
    ],
    users: [
      { el: '#tour-new-user', title: 'Crear usuario', text: 'Asigna identificación, correo, perfil y una contraseña inicial de al menos ocho caracteres.' },
      { el: '#tour-users-table', title: 'Cuentas', text: 'Cambia el perfil, restablece la contraseña o desactiva una cuenta. No puedes desactivar tu propia cuenta.' }
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
