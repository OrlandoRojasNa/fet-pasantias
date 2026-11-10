// Genera los manuales de producto de Pasantías FET en PDF: portada, ficha del documento,
// índice con números de página, capítulos numerados y contraportada.
// Requiere pandoc, poppler-utils (pdftotext, pdfunite), Node.js con Playwright y el paquete mermaid.
// Uso (desde la raíz del repositorio): node docs/pdf/generar_manuales.js
const {chromium} = require('playwright');
const {execFileSync} = require('child_process');
const fs = require('fs'), path = require('path'), os = require('os');

const root = path.resolve(__dirname, '..', '..');
const b64 = f => 'data:image/png;base64,' + fs.readFileSync(path.join(root, f)).toString('base64');
const LOGO_BLANCO = b64('assets/img/logo-fet-white.png');
const LOGO = b64('assets/img/logo-fet.png');
let mermaidJs = '';
try { mermaidJs = fs.readFileSync(require.resolve('mermaid/dist/mermaid.min.js'), 'utf8'); }
catch (e) { console.warn('Sin el paquete mermaid: los diagramas saldrán como texto.'); }

const PRODUCTO = 'Pasantías FET';
const VERSION = '2.0.0';
const FECHA = 'Noviembre de 2026';
const FECHA_CORTA = '10/11/2026';
const EQUIPO = ['Orlando Rojas Narváez', 'Hernán Felipe Patiño Rico', 'Juan José Sabogal García'];
const CLIENTE = 'Fundación Escuela Tecnológica de Neiva Jesús Oviedo Pérez';
const REPO = 'github.com/OrlandoRojasNa/fet-pasantias';

const MANUALES = [
  {
    md: 'docs/manual/MANUAL_USUARIO.md', pdf: 'docs/pdf/Manual_de_Usuario_Pasantias_FET_v2.0.0.pdf',
    codigo: 'PF-MU-200', titulo: 'Manual de usuario',
    lema: 'Guía completa para estudiantes, empresas aliadas, docentes líderes de pasantías y administradores',
    publico: 'Estudiantes, empresas aliadas, docentes líderes de pasantías y administradores de la plataforma',
    portada: {tipo: 'captura', img: 'docs/manual/img/02-admin-inicio.png', img2: 'docs/manual/img/15-estudiante-inicio.png'},
  },
  {
    md: 'docs/MANUAL_DESPLIEGUE.md', pdf: 'docs/pdf/Manual_de_Despliegue_Pasantias_FET_v2.0.0.pdf',
    codigo: 'PF-MD-200', titulo: 'Manual de despliegue',
    lema: 'Instalación, configuración, operación y actualización de la plataforma en servidores Linux y Windows',
    publico: 'Personal de sistemas e infraestructura responsable del servidor',
    portada: {tipo: 'terminal'},
  },
];

const VERDE = '#143a21', VERDE2 = '#1f5430', VERDE3 = '#2f7a47', MENTA = '#e7f2ea', ORO = '#c9962b', TINTA = '#16241b';

const cssBase = `
@page{size:Letter;margin:0}
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{font-family:Inter,"Liberation Sans",Arial,sans-serif;color:${TINTA};-webkit-print-color-adjust:exact;print-color-adjust:exact}
`;

const cssCuerpo = `
@page{size:Letter;margin:24mm 20mm 22mm 20mm}
body{font-size:10pt;line-height:1.6;color:#24332a}
p{margin:0 0 8pt;orphans:3;widows:3}
a{color:${VERDE3};text-decoration:none}
strong{color:${TINTA}}
ul,ol{margin:0 0 9pt;padding-left:18pt}li{margin-bottom:3pt}
h1{display:none}
h2{break-before:page;margin:0 0 16pt;padding:0 0 12pt;border-bottom:1.5pt solid ${MENTA};font-family:"Inter Display",Inter,sans-serif;font-weight:700;font-size:22pt;line-height:1.2;color:${VERDE}}
h2 .num{display:block;font-size:9pt;font-weight:600;letter-spacing:.16em;color:${ORO};text-transform:uppercase;margin-bottom:6pt}
h2.sin-salto{break-before:auto}
h3{font-family:"Inter Display",Inter,sans-serif;font-size:12.5pt;font-weight:650;color:${VERDE2};margin:18pt 0 7pt;break-after:avoid}
h3 .num{color:${ORO};margin-right:7pt;font-weight:700}
h4{font-size:10.5pt;font-weight:650;color:${VERDE2};margin:12pt 0 5pt;break-after:avoid}
.leyenda{font-size:8.5pt;color:#53645a;margin:12pt 0 5pt;break-after:avoid;line-height:1.4}
.leyenda b{color:${VERDE2};font-weight:700;letter-spacing:.02em;margin-right:4pt}
table{border-collapse:separate;border-spacing:0;width:100%;margin:0 0 14pt;font-size:8.6pt;line-height:1.45;break-inside:avoid;border:1px solid #d4e0d8;border-radius:5pt;overflow:hidden}
th{background:${VERDE2};color:#fff;font-weight:600;text-align:left;padding:6pt 8pt;vertical-align:bottom}
td{padding:5pt 8pt;vertical-align:top;border-top:1px solid #e3ebe5}
tr:nth-child(even) td{background:#f5f9f6}
td:first-child{font-weight:500;color:${TINTA}}
table.corta td:first-child{white-space:nowrap}
.fig{text-align:center;margin:0 0 14pt;break-inside:avoid}
.fig img{max-width:100%;max-height:92mm;border:1px solid #d4e0d8;border-radius:6pt;box-shadow:0 3pt 10pt rgba(20,58,33,.10)}
pre{background:#10261a;color:#e3f1e7;border-radius:6pt;padding:10pt 12pt;font-size:8.2pt;line-height:1.5;white-space:pre-wrap;word-break:break-word;margin:0 0 12pt;break-inside:avoid}
code{font-family:"JetBrains Mono","DejaVu Sans Mono",monospace;font-size:8.4pt;background:${MENTA};color:${VERDE};padding:.5pt 3pt;border-radius:3pt}
pre code{background:none;color:inherit;padding:0;font-size:8.2pt}
th code{background:rgba(255,255,255,.15);color:#fff}
pre.mermaid{background:none;color:inherit;text-align:center;padding:0}
blockquote,.aviso{border-left:3pt solid ${ORO};background:#fbf5e6;margin:0 0 12pt;padding:8pt 12pt;border-radius:0 5pt 5pt 0;break-inside:avoid}
blockquote p:last-child{margin:0}
.marca{position:absolute;font-size:1px;color:#fff;line-height:1}
/* Ficha del documento e índice */
.pagina-fm{break-after:page}
.fm-titulo{font-family:"Inter Display",Inter,sans-serif;font-weight:700;font-size:20pt;color:${VERDE};margin:0 0 4pt}
.fm-sub{font-size:9pt;color:#5f6f66;margin:0 0 12pt}
.fm-h{font-size:8pt;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:${ORO};margin:12pt 0 5pt}
.pagina-fm table{margin-bottom:6pt}.pagina-fm td,.pagina-fm th{padding:4pt 8pt}
table.ficha td:first-child{width:38%;color:#5f6f66;font-weight:500;background:#f5f9f6}
table.ficha tr:nth-child(even) td{background:transparent}
table.ficha tr td:first-child{background:#f5f9f6}
.legal{font-size:7.6pt;color:#5f6f66;line-height:1.5;border-top:1px solid #dbe4dd;padding-top:7pt;margin-top:10pt}
.indice{list-style:none;padding:0;margin:0}
.indice li{display:flex;align-items:baseline;margin:0;padding:3.2pt 0;font-size:9.4pt}
.indice li.n2{font-weight:650;color:${VERDE};padding-top:7pt;font-size:10pt}
.indice li.n3{padding-left:26pt;color:#3d4d43}
.indice .nn{width:26pt;flex:none;color:${ORO};font-weight:700}
.indice li.n3 .nn{width:30pt;font-weight:600}
.indice .tt{flex:none;max-width:78%}
.indice .pt{flex:1;border-bottom:1px dotted #b8c6bc;margin:0 6pt;transform:translateY(-3pt)}
.indice .pg{flex:none;font-variant-numeric:tabular-nums;color:${VERDE2};font-weight:600;min-width:16pt;text-align:right}
`;

function escaparHtml(s) { return s.replace(/&/g, '&amp;').replace(/</g, '&lt;'); }

function portadaHtml(m) {
  const equipo = EQUIPO.join('  ·  ');
  let visual = '';
  if (m.portada.tipo === 'captura') {
    visual = `<div class="disp disp2"><div class="barra"><i></i><i></i><i></i><span>pasantias.fet.edu.co</span></div><img src="${b64(m.portada.img2)}"></div>
      <div class="disp"><div class="barra"><i></i><i></i><i></i><span>pasantias.fet.edu.co</span></div><img src="${b64(m.portada.img)}"></div>`;
  } else {
    const l = [
      ['c', '$ sudo DOMINIO=pasantias.fet.edu.co \\'],
      ['c', '    bash deploy/instalar_servidor.sh'],
      ['o', '[1/7] Apache 2.4, PHP 8.3 y MariaDB ............ listo'],
      ['o', '[2/7] Base de datos fet_intranet ................ listo'],
      ['o', '[3/7] Código de la versión 2.0.0 ................ listo'],
      ['o', '[4/7] Configuración de producción (.env) ........ listo'],
      ['o', '[5/7] Sitio virtual y cabeceras de seguridad ... listo'],
      ['o', '[6/7] Certificado HTTPS ......................... listo'],
      ['o', '[7/7] Respaldo diario programado ................ listo'],
      ['k', 'Pasantías FET 2.0.0 disponible en https://pasantias.fet.edu.co'],
    ];
    visual = `<div class="term"><div class="barra"><i></i><i></i><i></i><span>servidor de producción</span></div><div class="tx">${l.map(([c, t]) => `<div class="${c}">${escaparHtml(t)}</div>`).join('')}</div></div>`;
  }
  return `<!doctype html><html lang="es"><head><meta charset="utf-8"><style>${cssBase}
  .cov{width:215.9mm;height:279.4mm;position:relative;overflow:hidden;background:linear-gradient(155deg,${VERDE2} 0%,${VERDE} 55%,#0d2716 100%);color:#fff}
  .cov svg.fondo{position:absolute;inset:0;width:100%;height:100%}
  .cab{position:absolute;top:20mm;left:20mm;right:20mm;display:flex;justify-content:space-between;align-items:center}
  .cab img{height:15mm}
  .cab .cod{font-size:7.5pt;letter-spacing:.18em;color:rgba(255,255,255,.65);text-transform:uppercase;text-align:right;line-height:1.7}
  .bloque{position:absolute;top:50mm;left:20mm;width:150mm}
  .sobre{font-size:8.5pt;font-weight:600;letter-spacing:.24em;color:${ORO};text-transform:uppercase}
  .prod{font-family:"Inter Display",Inter,sans-serif;font-size:15pt;font-weight:500;color:rgba(255,255,255,.85);margin-top:14mm}
  h1{font-family:"Inter Display",Inter,sans-serif;font-size:46pt;line-height:1.02;font-weight:800;margin:3mm 0 0;letter-spacing:-.01em}
  .raya{width:22mm;height:2.2mm;background:${ORO};margin:8mm 0 7mm;border-radius:1mm}
  .lema{font-size:12pt;line-height:1.5;color:rgba(255,255,255,.82);max-width:120mm;font-weight:400}
  .chip{display:inline-block;margin-top:9mm;border:1px solid rgba(255,255,255,.35);border-radius:20mm;padding:2.2mm 5mm;font-size:8.5pt;letter-spacing:.06em;color:#fff}
  .chip b{color:${ORO};font-weight:700;margin-right:2mm}
  .disp{position:absolute;right:-22mm;bottom:31mm;width:134mm;border-radius:3.5mm;overflow:hidden;background:#fff;box-shadow:0 10mm 25mm rgba(0,0,0,.45)}
  .disp img{display:block;width:100%}
  .disp2{right:62mm;bottom:50mm;width:100mm;opacity:.95;transform:none;box-shadow:0 8mm 20mm rgba(0,0,0,.4)}
  .barra{height:6.5mm;background:#eef2ef;display:flex;align-items:center;padding:0 3mm;gap:1.5mm}
  .barra i{width:2mm;height:2mm;border-radius:50%;background:#c3cdc6;display:block}
  .barra span{margin-left:4mm;font-size:6.5pt;color:#6b7a70;background:#fff;border-radius:2mm;padding:.6mm 4mm}
  .term{position:absolute;right:-12mm;bottom:34mm;width:162mm;border-radius:3.5mm;overflow:hidden;background:#0b1c12;box-shadow:0 10mm 25mm rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.08)}
  .term .barra{background:#16301f}.term .barra i{background:#3d5a47}.term .barra span{background:#0b1c12;color:#8fb39b}
  .tx{padding:6mm 7mm;font-family:"JetBrains Mono",monospace;font-size:8.6pt;line-height:1.85}
  .tx .c{color:#ffffff}.tx .o{color:#8fb39b}.tx .k{color:${ORO};margin-top:2mm;font-weight:600}
  .pie{position:absolute;left:0;right:0;bottom:0;height:26mm;background:rgba(5,20,11,.55);border-top:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:space-between;padding:0 20mm;font-size:7.8pt;color:rgba(255,255,255,.72);line-height:1.6}
  .pie b{color:#fff;font-weight:600}
  .pie>div:first-child{flex:none;width:112mm}.pie>div:last-child{flex:none;width:62mm}
  </style></head><body><div class="cov">
  <svg class="fondo" viewBox="0 0 216 279" preserveAspectRatio="none">
    <circle cx="200" cy="40" r="70" fill="none" stroke="rgba(255,255,255,.06)" stroke-width="0.4"/>
    <circle cx="200" cy="40" r="95" fill="none" stroke="rgba(255,255,255,.05)" stroke-width="0.4"/>
    <circle cx="200" cy="40" r="120" fill="none" stroke="rgba(201,150,43,.12)" stroke-width="0.4"/>
    <path d="M0 215 L216 150 L216 279 L0 279 Z" fill="rgba(255,255,255,.03)"/>
  </svg>
  <div class="cab"><img src="${LOGO_BLANCO}"><div class="cod">Documentación del producto<br>${m.codigo} · Edición 1.0</div></div>
  <div class="bloque">
    <div class="sobre">Plataforma de gestión de pasantías</div>
    <div class="prod">${PRODUCTO} ${VERSION}</div>
    <h1>${m.titulo}</h1>
    <div class="raya"></div>
    <div class="lema">${m.lema}</div>
    <div class="chip"><b>v${VERSION}</b>${FECHA}</div>
  </div>
  ${visual}
  <div class="pie"><div><b>Equipo de desarrollo ${PRODUCTO}</b><br>${equipo}</div><div style="text-align:right">Desarrollado para la<br><b>${CLIENTE}</b></div></div>
  </div></body></html>`;
}

function contraportadaHtml(m) {
  return `<!doctype html><html lang="es"><head><meta charset="utf-8"><style>${cssBase}
  .cov{width:215.9mm;height:279.4mm;position:relative;overflow:hidden;background:linear-gradient(200deg,${VERDE2} 0%,${VERDE} 60%,#0d2716 100%);color:#fff}
  .centro{position:absolute;top:80mm;left:20mm;right:20mm;text-align:center}
  .centro img{height:20mm}
  .prod{font-family:"Inter Display",Inter,sans-serif;font-size:26pt;font-weight:800;margin-top:12mm}
  .lema{font-size:11pt;color:rgba(255,255,255,.78);margin-top:3mm}
  .raya{width:18mm;height:1.8mm;background:${ORO};margin:10mm auto;border-radius:1mm}
  .cols{display:flex;justify-content:center;gap:18mm;font-size:8.5pt;line-height:1.7;color:rgba(255,255,255,.8);text-align:left}
  .cols b{display:block;color:${ORO};font-size:7.5pt;letter-spacing:.16em;text-transform:uppercase;margin-bottom:1.5mm}
  .pie{position:absolute;left:20mm;right:20mm;bottom:20mm;font-size:7.5pt;color:rgba(255,255,255,.55);text-align:center;line-height:1.7;border-top:1px solid rgba(255,255,255,.12);padding-top:6mm}
  </style></head><body><div class="cov"><div class="centro">
  <img src="${LOGO_BLANCO}">
  <div class="prod">${PRODUCTO}</div>
  <div class="lema">Gestión documental de pasantías, alianzas empresariales y convocatorias</div>
  <div class="raya"></div>
  <div class="cols">
   <div><b>Documentación</b>Manual de usuario<br>Manual de despliegue</div>
   <div><b>Soporte</b>Coordinación de pasantías<br>Área de sistemas de la institución</div>
   <div><b>Producto</b>Versión ${VERSION}<br>${REPO}</div>
  </div></div>
  <div class="pie">${m.titulo} · ${m.codigo} · Edición 1.0 · ${FECHA}<br>© 2026 Equipo de desarrollo ${PRODUCTO}. Desarrollado para la ${CLIENTE}.</div>
  </div></body></html>`;
}

function fichaHtml(m) {
  return `<section class="pagina-fm">
  <div class="fm-titulo">Información del documento</div>
  <div class="fm-sub">${PRODUCTO} ${VERSION} · ${m.titulo}</div>
  <table class="ficha corta">
   <tr><td>Producto</td><td>${PRODUCTO} ${VERSION}, plataforma de gestión documental de pasantías</td></tr>
   <tr><td>Documento</td><td>${m.titulo}</td></tr>
   <tr><td>Código y edición</td><td>${m.codigo}, edición 1.0</td></tr>
   <tr><td>Fecha de publicación</td><td>${FECHA}</td></tr>
   <tr><td>Dirigido a</td><td>${m.publico}</td></tr>
   <tr><td>Cliente</td><td>${CLIENTE}</td></tr>
  </table>
  <div class="fm-h">Equipo de desarrollo</div>
  <table class="ficha"><tr><th>Nombre</th><th>Participación</th></tr>
   ${EQUIPO.map(n => `<tr><td>${n}</td><td>Análisis, desarrollo, pruebas y documentación</td></tr>`).join('')}
  </table>
  <div class="fm-h">Historial de ediciones</div>
  <table><tr><th>Edición</th><th>Fecha</th><th>Versión del producto</th><th>Descripción</th></tr>
   <tr><td>1.0</td><td>${FECHA_CORTA}</td><td>${VERSION}</td><td>Primera edición del manual para el lanzamiento de la versión ${VERSION}</td></tr>
  </table>
  <div class="fm-h">Convenciones</div>
  <table class="corta"><tr><th>Formato</th><th>Uso</th></tr>
   <tr><td><i>Cursiva</i></td><td>Nombres de botones, menús, campos y estados de la plataforma</td></tr>
   <tr><td><code>Monoespaciado</code></td><td>Comandos, archivos, rutas y valores que se escriben tal como aparecen</td></tr>
   <tr><td>Tabla N / Figura N</td><td>Tablas y capturas de pantalla, numeradas en orden dentro del manual</td></tr>
  </table>
  <div class="legal">La información de este manual corresponde a la versión ${VERSION} de ${PRODUCTO} y puede cambiar en ediciones posteriores. Las capturas de pantalla usan datos de ejemplo; los nombres, las empresas y las fechas de una instalación real serán distintos. El logotipo de la ${CLIENTE} identifica a la institución para la cual se desarrolló el producto.</div>
  </section>`;
}

async function generar(br, m) {
  const mdPath = path.join(root, m.md);
  let cuerpo = execFileSync('pandoc', ['-f', 'gfm', '-t', 'html5', mdPath], {encoding: 'utf8', maxBuffer: 64 * 1024 * 1024});
  cuerpo = cuerpo.replace(/<pre class="mermaid"><code>([\s\S]*?)<\/code><\/pre>/g, '<pre class="mermaid">$1</pre>');
  const html = (marcas, paginas) => `<!doctype html><html lang="es"><head><meta charset="utf-8"><base href="file://${path.dirname(mdPath)}/"><style>${cssBase}${cssCuerpo}</style></head><body>
  ${fichaHtml(m)}
  <section class="pagina-fm"><div class="fm-titulo">Contenido</div><div class="fm-sub">${m.titulo} de ${PRODUCTO} ${VERSION}</div><ul class="indice" id="indice"></ul></section>
  <main id="main">${cuerpo}</main>
  <script>${mermaidJs}</script>
  <script>
  window.__listo=false;
  (async()=>{
   const main=document.getElementById('main');
   main.querySelector('h1')?.remove();
   // Introducción sin título: se agrupa bajo "Acerca de este manual".
   const h=document.createElement('h2');h.textContent='Acerca de este manual';main.prepend(h);
   // Leyendas de tablas y figuras en una sola línea.
   main.querySelectorAll('p').forEach(p=>{
    const t=p.textContent.trim();
    if(p.children.length===1&&p.firstElementChild.tagName==='STRONG'&&/^(Tabla|Figura) \\d+$/.test(t)){
     const n=p.nextElementSibling;const titulo=(n&&n.tagName==='P')?n.innerHTML:'';
     if(n&&n.tagName==='P')n.remove();
     p.className='leyenda';p.innerHTML='<b>'+t+'.</b>'+titulo.replace(/<\\/?em>/g,'');
    } else if(p.querySelector('img')&&p.textContent.trim()===''){p.className='fig';}
    else if(/^Nota\\./.test(t)){p.classList.add('aviso');}
   });
   main.querySelectorAll('table').forEach(t=>{if(![...t.rows].some(r=>r.cells[0]&&r.cells[0].textContent.trim().length>26))t.classList.add('corta');if(t.rows.length>14){t.style.breakInside='auto';[...t.rows].forEach(r=>r.style.breakInside='avoid');}});
   // Numeración de capítulos y secciones, e índice.
   const ind=document.getElementById('indice');const paginas=${JSON.stringify(paginas || {})};
   let c=0,s=0,k=0;
   main.querySelectorAll('h2,h3').forEach(e=>{
    const titulo=e.textContent.trim();let num;
    if(e.tagName==='H2'){c++;s=0;num=String(c);e.innerHTML='<span class="num">Capítulo '+c+'</span>'+e.innerHTML;}
    else{s++;num=c+'.'+s;e.innerHTML='<span class="num">'+num+'</span>'+e.innerHTML;}
    const id='h'+(k++);
    ${marcas ? `e.style.position='relative';e.insertAdjacentHTML('afterbegin','<span class="marca">@@'+id+'@@</span>');` : ''}
    const li=document.createElement('li');li.className=e.tagName==='H2'?'n2':'n3';
    li.innerHTML='<span class="nn">'+num+'</span><span class="tt">'+titulo+'</span><span class="pt"></span><span class="pg">'+(paginas[id]||'00')+'</span>';
    ind.appendChild(li);
   });
   if(window.mermaid){mermaid.initialize({startOnLoad:false,theme:'neutral',fontFamily:'Inter',flowchart:{useMaxWidth:true}});await mermaid.run({querySelector:'pre.mermaid'});}
   window.__listo=true;
  })();
  </script></body></html>`;

  const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'manual-'));
  const pie = `<div style="font-family:Inter,sans-serif;font-size:7pt;color:#6b7a70;width:100%;padding:0 20mm;display:flex;justify-content:space-between;align-items:center"><span>${m.codigo} · Edición 1.0 · ${FECHA}</span><span><span style="color:${VERDE2};font-weight:600">Página <span class="pageNumber"></span></span> de <span class="totalPages"></span></span></div>`;
  const cab = `<div style="font-family:Inter,sans-serif;font-size:7pt;color:#6b7a70;width:100%;margin:0 20mm;padding-bottom:2.5mm;border-bottom:.6pt solid #dbe4dd;display:flex;justify-content:space-between;align-items:center"><span><b style="color:${VERDE};font-weight:700">${PRODUCTO} ${VERSION}</b></span><span>${m.titulo}</span></div>`;
  const imprimir = async (contenido, salida) => {
    const f = path.join(tmp, 'cuerpo.html'); fs.writeFileSync(f, contenido);
    const p = await br.newPage();
    await p.goto('file://' + f, {waitUntil: 'load'});
    await p.waitForFunction(() => window.__listo === true, {timeout: 60000});
    await p.pdf({path: salida, format: 'Letter', printBackground: true, displayHeaderFooter: true, headerTemplate: cab, footerTemplate: pie,
      margin: {top: '24mm', bottom: '22mm', left: '20mm', right: '20mm'}});
    await p.close();
  };
  // Primera pasada: ubica la página de cada título con marcas invisibles.
  const borrador = path.join(tmp, 'borrador.pdf');
  await imprimir(html(true, null), borrador);
  const total = parseInt(execFileSync('pdfinfo', [borrador], {encoding: 'utf8'}).match(/Pages:\s+(\d+)/)[1], 10);
  const paginas = {};
  for (let i = 1; i <= total; i++) {
    const texto = execFileSync('pdftotext', ['-f', String(i), '-l', String(i), borrador, '-'], {encoding: 'utf8'});
    for (const mm of texto.matchAll(/@@(h\d+)@@/g)) if (!paginas[mm[1]]) paginas[mm[1]] = i; // misma numeración del pie de página
  }
  // Segunda pasada: índice con números de página reales y sin marcas.
  const cuerpoPdf = path.join(tmp, 'cuerpo.pdf');
  await imprimir(html(false, paginas), cuerpoPdf);
  // Portada y contraportada sin encabezado ni pie.
  const unaPagina = async (contenido, salida) => {
    const p = await br.newPage(); await p.setContent(contenido, {waitUntil: 'load'});
    await p.pdf({path: salida, format: 'Letter', printBackground: true, margin: {top: 0, bottom: 0, left: 0, right: 0}}); await p.close();
  };
  const tapa = path.join(tmp, 'portada.pdf'), contra = path.join(tmp, 'contra.pdf');
  await unaPagina(portadaHtml(m), tapa);
  await unaPagina(contraportadaHtml(m), contra);
  execFileSync('pdfunite', [tapa, cuerpoPdf, contra, path.join(root, m.pdf)]);
  // Imagen de la portada para la versión en Word.
  const p = await br.newPage({viewport: {width: 816, height: 1056}, deviceScaleFactor: 2.5});
  await p.setContent(portadaHtml(m), {waitUntil: 'load'});
  fs.mkdirSync(path.join(root, 'docs/word/portadas'), {recursive: true});
  await p.screenshot({path: path.join(root, 'docs/word/portadas', m.codigo + '-portada.png'), fullPage: false});
  await p.setContent(contraportadaHtml(m), {waitUntil: 'load'});
  await p.screenshot({path: path.join(root, 'docs/word/portadas', m.codigo + '-contraportada.png'), fullPage: false});
  await p.close();
  fs.rmSync(tmp, {recursive: true, force: true});
  console.log('Manual generado:', m.pdf, '(' + (total + 2) + ' páginas)');
}

(async () => {
  const br = await chromium.launch();
  for (const m of MANUALES) await generar(br, m);
  await br.close();
})().catch(e => { console.error(e); process.exit(1); });
