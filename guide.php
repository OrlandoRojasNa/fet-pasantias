<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require_auth();
$u=current_user(); $role=$u['role'];

$modules=[
 ['dashboard.php','home','fet','Inicio','Tu resumen del día: oportunidades abiertas, proyectos activos, convenios vigentes y tus compromisos próximos.',[]],
 ['opportunities.php','briefcase','ods8','Oportunidades · ODS 8','Prácticas, empleos y formación verificados. El estudiante se postula; la empresa publica sus vacantes y revisa a sus postulantes; el gestor verifica antes de publicar.',[]],
 ['internships.php','file','ods8','Pasantías','El estudiante sube los documentos de su pasantía, la empresa los avala y evalúa el desempeño, y el docente aprueba los documentos y emite el veredicto final: aprobada o reprobada.',[]],
 ['projects.php','rocket','ods9','Innovación · ODS 9','Proyectos con líder, equipo, hitos y evidencias. El avance se calcula con los hitos completados.',['admin','manager','leader','student']],
 ['alliances.php','handshake','ods17','Alianzas · ODS 17','Aliados, convenios con vigencia y responsable, y actividades con resultados comprobables.',['admin','manager','leader','student']],
 ['minutes.php','clipboard','fet','Actas y compromisos','Las actas siguen el Formato 1 de la FET. Cada compromiso tiene responsable, fecha y evidencia; al vencer se marca solo como «Vencido».',['admin','manager','leader','student']],
 ['reports.php','chart','gold','Indicadores ODS','Indicadores por periodo, cumplimiento por acta y exportación a CSV para Excel.',['admin','manager']],
 ['quality.php','shield','teal','Calidad y pruebas','Comprobaciones automáticas, resultado de los flujos de prueba, hallazgos y respaldo SQL.',['admin','manager','leader']],
 ['users.php','users','slate','Usuarios','Crear cuentas, cambiar perfil, restablecer contraseñas y activar o desactivar accesos.',['admin']],
 ['notifications.php','bell','fet','Avisos','La campana te avisa cuando te asignan un compromiso, cambia tu postulación o hay algo por revisar.',[]],
];
$flows=[
 'student'=>['Estudiante',[['briefcase','Busca una oportunidad','Filtra por práctica, empleo o formación.'],['send','Postúlate','Explica en dos líneas por qué te interesa.'],['file','Sube tus documentos','Si te aceptan en una práctica, carga en «Pasantías» tu plan, informes y certificado.'],['check-circle','Conoce tu resultado','Verás la evaluación de la empresa y el veredicto del docente.']]],
 'company'=>['Empresa de pasantía',[['plus','Crea la vacante','La FET la verifica antes de publicarla.'],['users','Elige a tus pasantes','Acepta postulaciones; así se crea la pasantía.'],['file','Avala los documentos','Revisa lo que sube el pasante o devuélvelo con observaciones.'],['edit','Evalúa el desempeño','Califica de 1 a 5 y comenta; el docente decide con base en eso.']]],
 'admin'=>['Administrador',[['users','Gestiona cuentas','Crea usuarios y asigna perfiles.'],['briefcase','Verifica oportunidades','Publica las que envían las empresas.'],['clipboard','Registra actas','Asigna compromisos con fecha y evidencia esperada.'],['database','Respalda y audita','Descarga el respaldo SQL y revisa la auditoría.']]],
 'manager'=>['Gestor institucional',[['briefcase','Verifica oportunidades','Publica las vacantes revisadas.'],['file','Asigna las pasantías','Define la empresa evaluadora y el docente que valida.'],['clipboard','Sigue los compromisos','Filtra por acta, responsable o estado.'],['chart','Reporta por periodo','Exporta los indicadores ODS a CSV.']]],
 'leader'=>['Docente o líder',[['file','Califica los documentos','Nota de 0.0 a 5.0: con 3.0 o más se aprueba, con menos se devuelve.'],['check-circle','Emite el veredicto','Promedio de 3.0 o más: aprobada; menos de 3.0: reprobada.'],['clipboard','Registra el acta','Con la estructura del Formato 1.'],['shield','Revisa la calidad','Hallazgos y pruebas del equipo.']]],
];
[$roleName,$steps]=$flows[$role]??$flows['student'];
app_header('Guía de uso','TUTORIAL DE LA INTRANET');
?>
<section class="guide-hero" id="tour-guide-hero">
 <div><h2>Aprende a usar la intranet en dos minutos.</h2><p>El recorrido guiado resalta cada parte de la pantalla y explica para qué sirve. Puedes repetirlo cuando quieras con el botón <b class="inline-help"><?= icon('help',14) ?></b> que está abajo a la derecha en todas las páginas.</p>
 <div class="button-row"><button class="button button-light" type="button" data-tour-start><?= icon('play',16) ?>Iniciar recorrido guiado</button><a class="button button-outline-light" href="<?= e(url('dashboard.php?tour=1')) ?>"><?= icon('home',16) ?>Recorrer desde el inicio</a></div></div>
 <img src="<?= e(url('assets/img/logo-fet-white.png')) ?>" alt="" width="220" height="120">
</section>

<section class="panel"><div class="panel-head"><div><span class="eyebrow">TU RECORRIDO COMO <?= e(mb_strtoupper($roleName)) ?></span><h2>Así fluye tu trabajo</h2></div></div>
<ol class="flow-steps"><?php foreach($steps as $i=>[$ic,$t,$d]):?><li><span class="step-icon"><?= icon($ic,22) ?></span><b><?= $i+1 ?></b><strong><?= e($t) ?></strong><p><?= e($d) ?></p></li><?php endforeach;?></ol></section>

<section class="panel"><div class="panel-head"><div><span class="eyebrow">MÓDULOS DISPONIBLES PARA TU PERFIL</span><h2>Qué hay en cada sección</h2></div></div>
<div class="module-grid"><?php foreach($modules as [$href,$ic,$tone,$name,$desc,$roles]): if($roles&&!in_array($role,$roles,true)) continue;?><a class="module-card" href="<?= e(url($href)) ?>"><span class="kpi-icon tone-<?= e($tone) ?>"><?= icon($ic,22) ?></span><strong><?= e($name) ?></strong><p><?= e($desc) ?></p></a><?php endforeach;?></div></section>

<section class="panel"><div class="panel-head"><div><span class="eyebrow">PREGUNTAS FRECUENTES</span><h2>Dudas comunes</h2></div></div>
<div class="faq">
 <details><summary>¿Cómo cierro un compromiso?</summary><p>En «Actas y compromisos» pulsa «Actualizar» en tu compromiso, describe o adjunta la evidencia (PDF, imagen, Office, SQL o ZIP hasta 5 MB) y elige «Completado». Sin evidencia la intranet no permite completarlo.</p></details>
 <details><summary>¿Por qué un compromiso aparece como «Vencido»?</summary><p>Porque su fecha de entrega ya pasó y no se ha completado. Al registrar la evidencia y marcarlo como completado cambia de estado.</p></details>
 <details><summary>¿Cómo imprimo un acta?</summary><p>Abre el acta y pulsa «Imprimir o guardar PDF». Se imprime con el logo de la FET, los participantes, el desarrollo, los compromisos y las firmas.</p></details>
 <details><summary>¿Qué pasa si me equivoco de contraseña?</summary><p>Después de cinco intentos fallidos la cuenta se bloquea diez minutos. Si la olvidaste, usa «¿Olvidaste tu contraseña?» y el administrador recibirá la solicitud.</p></details>
 <details><summary>¿Los indicadores certifican el cumplimiento de los ODS?</summary><p>No. Son indicadores internos del piloto calculados con los registros de la intranet; cada porcentaje muestra cuántos registros se usaron.</p></details>
</div></section>
<?php app_footer(); ?>
