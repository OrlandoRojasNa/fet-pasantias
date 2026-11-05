// Recorre el proceso completo de pasantías con los cuatro perfiles y guarda las capturas del manual.
// Uso: node docs/manual/generar_capturas.js docs/manual/img http://localhost/PruebaFet
// Requiere Node.js con Playwright y una base recién instalada (php database/install.php).
const fs=require('fs'),os=require('os'),path=require('path');
const PDF='%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[]/Count 0>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n';
const CV=path.join(os.tmpdir(),'hoja_de_vida.pdf'), PLAN=path.join(os.tmpdir(),'plan_de_practica.pdf');
fs.writeFileSync(CV,PDF); fs.writeFileSync(PLAN,PDF);
const {chromium}=require('playwright');
let B='http://127.0.0.1:8081/pruebafet';
const OUT=process.argv[2]||'img';
const B_=process.argv[3]; if(B_) B=B_;
const CSS='.app-frame{position:relative}.sidebar{position:absolute !important}.tour-fab{display:none!important}';
fs.mkdirSync(OUT,{recursive:true}); let n=0; const log=[];
(async()=>{
 const br=await chromium.launch();
 async function as(mail){
  const ctx=await br.newContext({viewport:{width:1366,height:860},deviceScaleFactor:1,locale:'es-CO'});
  await ctx.addInitScript(()=>{try{localStorage.setItem('fet-tour-seen','1')}catch(e){}});
  const p=await ctx.newPage(); p.on('dialog',d=>d.accept());
  await p.goto(B+'/index.php'); await p.fill('input[name=identifier]',mail); await p.fill('#password','FET2026*');
  await Promise.all([p.waitForNavigation(),p.click('form.stack-form button[type=submit]')]);
  return p;
 }
 async function shot(p,name,opt={}){ await p.addStyleTag({content:CSS}); await p.waitForTimeout(250);
   const f=`${OUT}/${String(++n).padStart(2,'0')}-${name}.png`;
   if(opt.el){ await (await p.$(opt.el)).screenshot({path:f}); } else await p.screenshot({path:f,fullPage:!opt.view}); log.push(f); console.log(f); }
 async function go(p,path){ await p.goto(B+'/'+path); }
 async function submit(p,sel){ await Promise.all([p.waitForNavigation(),p.click(sel)]); }
 const flash=async p=>console.log('   flash:',(await p.$eval('.flash, .alert',e=>e.innerText).catch(()=>'-')).replace(/\s+/g,' ').slice(0,140));

 // 0. login
 { const ctx=await br.newContext({viewport:{width:1366,height:860},locale:'es-CO'}); const p=await ctx.newPage(); await go(p,'index.php'); await shot(p,'login',{view:true}); await ctx.close(); }

 // ADMIN
 const ad=await as('admin@fet.edu.co');
 await shot(ad,'admin-inicio');
 await go(ad,'alliances.php'); await shot(ad,'admin-alianzas');
 await ad.click('[data-open="alliance-form"]');
 await ad.fill('#alliance-form [name=name]','Soluciones TIC del Huila S.A.S.');
 await ad.fill('#alliance-form [name=sector]','Desarrollo de software');
 await ad.fill('#alliance-form [name=agreement_code]','CONV-2026-014');
 await ad.fill('#alliance-form [name=slots]','2');
 await ad.fill('#alliance-form [name=ends_on]','2027-12-31');
 await ad.fill('#alliance-form [name=contact_name]','Laura Méndez');
 await ad.fill('#alliance-form [name=contact_email]','talento@solucionestic.test');
 await ad.fill('#alliance-form [name=contact_phone]','3101234567');
 await ad.fill('#alliance-form [name=nit]','901555222');
 await ad.fill('#alliance-form [name=email]','contacto@solucionestic.test');
 await ad.fill('#alliance-form [name=password]','FET2026*');
 await shot(ad,'admin-alianza-formulario',{view:true});
 await submit(ad,'#alliance-form button.button-primary'); await flash(ad);
 await shot(ad,'admin-alianza-creada');
 const editBtn=await ad.$('[data-open^="edit-"]'); await editBtn.click(); await shot(ad,'admin-alianza-editar',{view:true}); await ad.keyboard.press('Escape');
 await go(ad,'users.php'); await shot(ad,'admin-usuarios');
 await ad.click('[data-open="user-form"]'); await shot(ad,'admin-usuario-formulario',{view:true}); await ad.keyboard.press('Escape');
 await go(ad,'reports.php'); await shot(ad,'admin-reportes');

 // EMPRESA: nueva publicación
 const em=await as('empresa@fet.edu.co');
 await shot(em,'empresa-inicio');
 await go(em,'opportunities.php'); await shot(em,'empresa-convocatorias');
 await em.click('[data-open="opportunity-form"]');
 await em.fill('#opportunity-form [name=title]','Pasantía en desarrollo de aplicaciones web');
 await em.selectOption('#opportunity-form [name=type]','practica');
 await em.selectOption('#opportunity-form [name=modality]','hibrida');
 await em.fill('#opportunity-form [name=location]','Neiva, Huila');
 await em.fill('#opportunity-form [name=closes_on]','2026-12-20');
 await em.fill('#opportunity-form [name=description]','Apoyo al equipo de desarrollo en el mantenimiento de aplicaciones PHP y MySQL.');
 await em.fill('#opportunity-form [name=requirements]','Estudiante de Ingeniería de Software de séptimo semestre en adelante.');
 await em.selectOption('#opportunity-form [name=status]','review');
 await shot(em,'empresa-publicacion-formulario',{view:true});
 await submit(em,'#opportunity-form button.button-primary'); await flash(em);

 // DOCENTE verifica
 const dc=await as('docente@fet.edu.co');
 await shot(dc,'docente-inicio');
 await go(dc,'opportunities.php');
 const card=dc.locator('article',{hasText:'Pasantía en desarrollo de aplicaciones web'}).first();
 await card.locator('select[name=status]').selectOption('published');
 await shot(dc,'docente-verificar-publicacion');
 await Promise.all([dc.waitForNavigation(),card.locator('form.inline-form button').click()]); await flash(dc);

 // ESTUDIANTE se postula
 const es=await as('estudiante@fet.edu.co');
 await shot(es,'estudiante-inicio');
 await go(es,'opportunities.php'); await shot(es,'estudiante-convocatorias');
 const sc=es.locator('article',{hasText:'Pasantía en desarrollo de aplicaciones web'}).first();
 await sc.locator('[data-open^="apply-"]').click();
 const dlg=es.locator('dialog[open]');
 await dlg.locator('[name=motivation]').fill('Me interesa el desarrollo web y quiero aplicar lo aprendido en PHP y bases de datos.');
 await dlg.locator('[name=cv]').setInputFiles(CV);
 await shot(es,'estudiante-postulacion-formulario',{view:true});
 await Promise.all([es.waitForNavigation(),dlg.locator('button.button-primary').click()]); await flash(es);
 await shot(es,'estudiante-postulado');

 // EMPRESA acepta
 await go(em,'opportunities.php');
 const row=em.locator('tr',{hasText:'Pasantía en desarrollo de aplicaciones web'}).first();
 await row.locator('select[name=status]').selectOption('accepted');
 await row.scrollIntoViewIfNeeded(); await shot(em,'empresa-postulaciones');
 await Promise.all([em.waitForNavigation(),row.locator('button').last().click()]); await flash(em);
 await go(em,'internships.php'); await shot(em,'empresa-pasantias');

 // ESTUDIANTE sube documento
 await go(es,'internships.php'); await shot(es,'estudiante-pasantias');
 await es.locator('tr',{hasText:'Pasantía en desarrollo de aplicaciones web'}).first().locator('a').last().click(); await es.waitForLoadState();
 const intUrl=es.url(); console.log('   internship',intUrl);
 await shot(es,'estudiante-pasantia-detalle');
 await es.click('[data-open="doc-upload"]');
 await es.selectOption('#doc-upload [name=doc_type]','plan');
 await es.fill('#doc-upload [name=title]','Plan de práctica - primer periodo');
 await es.setInputFiles('#doc-upload [name=document]',PLAN);
 await shot(es,'estudiante-subir-documento',{view:true});
 await submit(es,'#doc-upload button.button-primary'); await flash(es);
 await shot(es,'estudiante-documento-enviado');

 // EMPRESA avala y evalúa
 await em.goto(intUrl);
 await em.click('[data-open^="review-"]'); await em.selectOption('dialog[open] [name=decision]','endorse');
 await em.fill('dialog[open] [name=comment]','El plan coincide con las actividades acordadas.');
 await shot(em,'empresa-avalar-documento',{view:true});
 await Promise.all([em.waitForNavigation(),em.click('dialog[open] button.button-primary')]); await flash(em);
 await em.click('[data-open="company-eval"]'); await em.selectOption('#company-eval [name=rating]','5');
 await em.fill('#company-eval [name=evaluation]','Muy responsable y puntual. Buen manejo de PHP y trabajo en equipo.');
 await shot(em,'empresa-evaluacion',{view:true});
 await submit(em,'#company-eval button.button-primary'); await flash(em);

 // DOCENTE califica y da veredicto
 await go(dc,'internships.php'); await shot(dc,'docente-pasantias');
 await dc.goto(intUrl); await shot(dc,'docente-pasantia-detalle');
 await dc.click('[data-open="internship-assign"]');
 await dc.selectOption('#internship-assign [name=teacher_id]',{label:'Miguel Antonio Urbano Silva'});
 await dc.fill('#internship-assign [name=ends_on]','2027-05-31');
 await shot(dc,'docente-asignar',{view:true});
 await submit(dc,'#internship-assign button.button-primary'); await flash(dc);
 await dc.click('[data-open^="review-"]'); await dc.selectOption('dialog[open] [name=decision]','approve');
 await dc.fill('dialog[open] [name=grade]','4.5'); await dc.fill('dialog[open] [name=comment]','Plan completo y bien estructurado.');
 await shot(dc,'docente-calificar-documento',{view:true});
 await Promise.all([dc.waitForNavigation(),dc.click('dialog[open] button.button-primary')]); await flash(dc);
 await dc.click('[data-open="final-verdict"]');
 await dc.fill('#final-verdict [name=justification]','Cumplió el plan de práctica con buena calificación y evaluación positiva de la empresa.');
 await shot(dc,'docente-veredicto',{view:true});
 await submit(dc,'#final-verdict button.button-primary'); await flash(dc);
 await shot(dc,'docente-pasantia-aprobada');
 await go(dc,'reports.php'); await shot(dc,'docente-reportes');
 await go(dc,'alliances.php'); await shot(dc,'docente-alianzas');

 // ESTUDIANTE ve resultado
 await es.goto(intUrl); await shot(es,'estudiante-resultado');
 await go(es,'notifications.php'); await shot(es,'estudiante-notificaciones');
 await go(es,'guide.php'); await shot(es,'estudiante-guia');
 await br.close();
})().catch(e=>{console.error(e);process.exit(1)});
