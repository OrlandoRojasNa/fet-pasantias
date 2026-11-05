<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require_auth();
$u=current_user(); $role=$u['role'];

$modules=[
 ['dashboard.php','home','fet','Inicio','Tu resumen: lo que tienes pendiente en el proceso de pasantías según tu perfil.',[]],
 ['internships.php','file','ods8','Pasantías','Gestión documental: el estudiante sube sus documentos, la empresa los avala y evalúa el desempeño, y el docente líder los califica (0.0 a 5.0), audita el historial y emite el veredicto final.',[]],
 ['opportunities.php','briefcase','ods9','Convocatorias y empleo','Las empresas aliadas publican convocatorias de pasantía (con los cupos de su alianza) y ofertas laborales opcionales. La FET las verifica y el estudiante se postula con su hoja de vida en PDF.',[]],
 ['alliances.php','handshake','ods17','Alianzas','El administrador registra cada empresa aliada, su cuenta de acceso, el convenio y los cupos de pasantía. Ampliar los cupos abre más plazas.',['admin','leader']],
 ['reports.php','chart','gold','Reportes y auditoría','Resumen por periodo, cupos y resultados por empresa, exportación a CSV y bitácora de quién hizo qué y cuándo.',['admin','leader']],
 ['users.php','users','slate','Usuarios','Crear cuentas, cambiar perfil, desbloquear, restablecer contraseñas y descargar el respaldo SQL.',['admin']],
 ['notifications.php','bell','fet','Avisos','La campana te avisa cuando hay un documento por revisar, cambia tu postulación o se emite un resultado.',[]],
];
$flows=[
 'student'=>['Estudiante',[['briefcase','Busca una convocatoria','Pasantías y ofertas laborales verificadas por la FET.'],['send','Postúlate','Cuenta por qué te interesa y adjunta tu hoja de vida en PDF (obligatoria en ofertas laborales).'],['file','Sube tus documentos','Cuando la empresa te acepta se crea tu pasantía: carga plan, informes y certificado.'],['check-circle','Conoce tu resultado','Verás notas, observaciones, la evaluación de la empresa y el veredicto del docente.']]],
 'company'=>['Empresa aliada',[['plus','Publica la convocatoria','Usa los cupos de tu alianza; la FET la verifica antes de publicarla.'],['users','Elige a tus pasantes','Revisa la hoja de vida y acepta la postulación: así se crea la pasantía.'],['file','Avala los documentos','Revisa lo que sube el pasante o devuélvelo con observaciones.'],['edit','Evalúa el desempeño','Califica de 1 a 5 y comenta; el docente lo tiene en cuenta.']]],
 'leader'=>['Docente líder de pasantías',[['briefcase','Verifica publicaciones','Publica las convocatorias y ofertas que envían las empresas.'],['file','Asigna y revisa','Asigna empresa y docente; califica cada documento de 0.0 a 5.0 (3.0 aprueba).'],['eye','Audita','Consulta el historial de cada pasantía y la bitácora en Reportes.'],['check-circle','Emite el veredicto','Promedio de 3.0 o más: aprobada; menos de 3.0: reprobada.']]],
 'admin'=>['Administrador',[['handshake','Crea alianzas','Registra la empresa, su cuenta y sus cupos de pasantía.'],['plus','Amplía cupos','Edita la alianza para abrir más plazas o renovar la vigencia.'],['users','Gestiona cuentas','Crea docentes y estudiantes, desbloquea y restablece contraseñas.'],['database','Respalda y audita','Descarga el respaldo SQL y revisa la bitácora.']]],
];
[$roleName,$steps]=$flows[$role]??$flows['student'];
app_header('Guía de uso','TUTORIAL DE LA PLATAFORMA');
?>
<section class="guide-hero" id="tour-guide-hero">
 <div><h2>Aprende a usar la plataforma en dos minutos.</h2><p>El recorrido guiado resalta cada parte de la pantalla y explica para qué sirve. Puedes repetirlo cuando quieras con el botón <b class="inline-help"><?= icon('help',14) ?></b> que está abajo a la derecha en todas las páginas.</p>
 <div class="button-row"><button class="button button-light" type="button" data-tour-start><?= icon('play',16) ?>Iniciar recorrido guiado</button><a class="button button-outline-light" href="<?= e(url('dashboard.php?tour=1')) ?>"><?= icon('home',16) ?>Recorrer desde el inicio</a></div></div>
 <img src="<?= e(url('assets/img/logo-fet-white.png')) ?>" alt="" width="220" height="120">
</section>

<section class="panel"><div class="panel-head"><div><span class="eyebrow">TU RECORRIDO COMO <?= e(mb_strtoupper($roleName)) ?></span><h2>Así fluye tu trabajo</h2></div></div>
<ol class="flow-steps"><?php foreach($steps as $i=>[$ic,$t,$d]):?><li><span class="step-icon"><?= icon($ic,22) ?></span><b><?= $i+1 ?></b><strong><?= e($t) ?></strong><p><?= e($d) ?></p></li><?php endforeach;?></ol></section>

<section class="panel"><div class="panel-head"><div><span class="eyebrow">MÓDULOS DISPONIBLES PARA TU PERFIL</span><h2>Qué hay en cada sección</h2></div></div>
<div class="module-grid"><?php foreach($modules as [$href,$ic,$tone,$name,$desc,$roles]): if($roles&&!in_array($role,$roles,true)) continue;?><a class="module-card" href="<?= e(url($href)) ?>"><span class="kpi-icon tone-<?= e($tone) ?>"><?= icon($ic,22) ?></span><strong><?= e($name) ?></strong><p><?= e($desc) ?></p></a><?php endforeach;?></div></section>

<section class="panel"><div class="panel-head"><div><span class="eyebrow">PREGUNTAS FRECUENTES</span><h2>Dudas comunes</h2></div></div>
<div class="faq">
 <details><summary>¿Cómo se crea una pasantía?</summary><p>Cuando una empresa aliada acepta la postulación de un estudiante a su convocatoria de pasantía, la plataforma crea la pasantía y ocupa un cupo de la alianza. El docente líder también puede registrarla a mano.</p></details>
 <details><summary>¿Qué pasa si la empresa no tiene cupos?</summary><p>No podrá aceptar más pasantes hasta que termine una pasantía en curso o el administrador amplíe los cupos de su alianza.</p></details>
 <details><summary>¿Cómo se aprueba la pasantía?</summary><p>El docente califica cada documento de 0.0 a 5.0; con 3.0 o más se aprueba y con menos se devuelve al estudiante para corregirlo. El veredicto final se sugiere con el promedio: 3.0 o más, aprobada; menos de 3.0, reprobada. No se puede cambiar después.</p></details>
 <details><summary>¿Qué formato debe tener la hoja de vida?</summary><p>Un PDF de máximo 5 MB. Es obligatoria en las ofertas laborales y opcional en las convocatorias de pasantía. Solo la ven la empresa que publicó, los docentes líderes y el administrador.</p></details>
 <details><summary>¿Qué pasa si me equivoco de contraseña?</summary><p>Después de cinco intentos fallidos la cuenta se bloquea diez minutos. El administrador puede desbloquearla o asignarte una contraseña nueva.</p></details>
</div></section>
<?php app_footer(); ?>
