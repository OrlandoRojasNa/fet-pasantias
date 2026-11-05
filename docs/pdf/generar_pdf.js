// Genera los PDF de los manuales a partir de los .md (requiere pandoc, Node.js, Playwright y el paquete mermaid).
// Uso (desde la raíz del repositorio): node docs/pdf/generar_pdf.js
const {chromium}=require('playwright');
const {execFileSync}=require('child_process');
const fs=require('fs'),path=require('path');
const root=path.resolve(__dirname,'..','..');
let mermaidJs='';
try{ mermaidJs=fs.readFileSync(require.resolve('mermaid/dist/mermaid.min.js'),'utf8'); }catch(e){ console.warn('Sin el paquete mermaid: los diagramas saldrán como texto.'); }
const logo='data:image/png;base64,'+fs.readFileSync(path.join(root,'assets/img/logo-fet.png')).toString('base64');
const docs=[
 {md:'docs/manual/MANUAL_USUARIO.md',pdf:'docs/pdf/Manual_de_Usuario_Pasantias_FET_v2.0.0.pdf',title:'Manual de usuario',sub:'Guía de uso por perfil: estudiante, empresa aliada, docente líder de pasantías y administrador'},
 {md:'docs/pruebas-despliegue/INFORME_DESPLIEGUE.md',pdf:'docs/pdf/Informe_Prueba_Despliegue_Pasantias_FET_v2.0.0.pdf',title:'Informe de prueba de despliegue',sub:'Instalación en máquinas virtuales Ubuntu Server 24.04 siguiendo el Manual de despliegue'},
 {md:'docs/pruebas-usuario/resultados-ejecucion-produccion.md',pdf:'docs/pdf/Resultados_Pruebas_por_Rol_Pasantias_FET_v2.0.0.pdf',title:'Resultados de las pruebas por rol',sub:'Ejecución de los guiones de estudiante, empresa, docente y administrador en el servidor de producción'},
 {md:'docs/MANUAL_DESPLIEGUE.md',pdf:'docs/pdf/Manual_de_Despliegue_Pasantias_FET_v2.0.0.pdf',title:'Manual de despliegue',sub:'Instalación, configuración, puesta en marcha, operación y actualización'},
];
const css=`
@page{size:Letter;margin:22mm 18mm 20mm 18mm}
body{font-family:"Segoe UI",Arial,Helvetica,sans-serif;font-size:10.5pt;line-height:1.5;color:#1d2b24}
h1{display:none}
h2{font-size:17pt;color:#1f5133;border-bottom:2px solid #2e7d4f;padding-bottom:4px;margin-top:26px;break-after:avoid}
h3{font-size:12.5pt;color:#1f5133;margin-top:18px;break-after:avoid}
h2:not(:first-of-type){break-before:page}
table{border-collapse:collapse;width:100%;margin:10px 0;font-size:9.5pt;break-inside:avoid}
th,td{border:1px solid #cfdcd3;padding:5px 7px;vertical-align:top;text-align:left}
th{background:#e8f2ec;color:#1f5133}
img{max-width:100%;max-height:190mm;width:auto;display:block;margin-left:auto;margin-right:auto;border:1px solid #d5dfd8;border-radius:6px;margin:8px 0;break-inside:avoid}
p:has(img){break-inside:avoid}
pre{background:#f4f7f5;border:1px solid #dbe5de;border-radius:6px;padding:9px 11px;font-size:8.8pt;white-space:pre-wrap;word-break:break-word;break-inside:avoid}
code{font-family:Consolas,"Courier New",monospace;font-size:9pt;background:#f1f5f2;padding:1px 3px;border-radius:3px}
pre code{background:none;padding:0}
blockquote{border-left:4px solid #e0a526;background:#fff8e6;margin:10px 0;padding:6px 12px;color:#4a3b12}
a{color:#1f6b3f;text-decoration:none}
pre.mermaid{background:none;border:0;text-align:center}
.cover{height:235mm;display:flex;flex-direction:column;justify-content:space-between;break-after:page}
.cover .top img{height:70px}
.cover .mid .kicker{letter-spacing:.18em;font-size:10pt;color:#2e7d4f;font-weight:700;text-transform:uppercase}
.cover .mid .title{font-size:34pt;font-weight:800;color:#163a25;margin:8px 0 6px}
.cover .mid .sub{font-size:13pt;color:#40584a;max-width:150mm}
.cover .mid .band{height:6px;width:90px;background:#2e7d4f;margin:22px 0}
.cover .bottom{font-size:10pt;color:#40584a;border-top:1px solid #cfdcd3;padding-top:10px}
.cover .bottom b{color:#163a25}
`;
(async()=>{
 const br=await chromium.launch();
 for(const d of docs){
  const mdPath=path.join(root,d.md);
  let body=execFileSync('pandoc',['-f','gfm','-t','html5',mdPath],{encoding:'utf8',maxBuffer:64*1024*1024});
  body=body.replace(/<pre class="mermaid"><code>([\s\S]*?)<\/code><\/pre>/g,'<pre class="mermaid">$1</pre>');
  const cover=`<section class="cover"><div class="top"><img src="${logo}" alt="FET"></div>
   <div class="mid"><div class="kicker">Pasantías FET · Versión 2.0.0</div><div class="title">${d.title}</div><div class="sub">${d.sub}</div><div class="band"></div></div>
   <div class="bottom"><b>Fundación Escuela Tecnológica de Neiva «Jesús Oviedo Pérez»</b> · Campus Rivera, Huila<br>Elaborado por: Orlando Rojas Narváez · Hernán Felipe Patiño Rico · Juan José Sabogal García<br>Noviembre de 2026 · Documento versión 1.0</div></section>`;
  const html=`<!doctype html><html lang="es"><head><meta charset="utf-8"><title>${d.title} · Pasantías FET</title><style>${css}</style></head><body>${cover}${body}
   <script>${mermaidJs}</script><script>window.__done=false;(async()=>{try{if(window.mermaid){mermaid.initialize({startOnLoad:false,theme:'neutral',flowchart:{useMaxWidth:true}});await mermaid.run({querySelector:'pre.mermaid'});}}finally{window.__done=true}})();</script></body></html>`;
  const tmp=path.join(path.dirname(mdPath),'.__print.html');
  fs.writeFileSync(tmp,html);
  const p=await br.newPage();
  await p.goto('file://'+tmp,{waitUntil:'load'});
  await p.waitForFunction(()=>window.__done===true,{timeout:30000});
  // Las tablas largas pueden partirse entre páginas (sin cortar filas) para no dejar páginas en blanco.
  await p.evaluate(()=>document.querySelectorAll('table').forEach(t=>{if(t.rows.length>14){t.style.breakInside='auto';[...t.rows].forEach(r=>r.style.breakInside='avoid');}}));
  await p.pdf({path:path.join(root,d.pdf),format:'Letter',printBackground:true,displayHeaderFooter:true,
   headerTemplate:`<div style="font-size:7.5px;color:#7a8a80;width:100%;padding:0 18mm;display:flex;justify-content:space-between"><span>Pasantías FET 2.0.0</span><span>${d.title}</span></div>`,
   footerTemplate:`<div style="font-size:7.5px;color:#7a8a80;width:100%;padding:0 18mm;display:flex;justify-content:space-between"><span>Fundación Escuela Tecnológica de Neiva</span><span>Página <span class="pageNumber"></span> de <span class="totalPages"></span></span></div>`,
   margin:{top:'22mm',bottom:'20mm',left:'18mm',right:'18mm'}});
  await p.close(); fs.unlinkSync(tmp);
  console.log('PDF generado:',d.pdf);
 }
 await br.close();
})().catch(e=>{console.error(e);process.exit(1)});
