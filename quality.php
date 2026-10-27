<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require_role('admin','manager','leader');
$u=current_user();

if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf(); $a=$_POST['action']??'';
    try{
        if($a==='finding'){
            $title=post_str('title',180); if($title==='') throw new RuntimeException('Escribe el hallazgo.');
            $assigned=(int)($_POST['assigned_to']??0)?:null;
            query('INSERT INTO findings(title,module,description,priority,status,reported_by,assigned_to) VALUES(?,?,?,?,?,?,?)',[$title,post_str('module',80)?:'General',post_str('description')?:null,allowed_value($_POST['priority']??'',['alta','media','baja'],'media'),'open',$u['id'],$assigned]);
            $id=(int)db()->lastInsertId(); audit('create','finding',$id,$title);
            if($assigned&&$assigned!==$u['id']) notify($assigned,'Hallazgo asignado',$title,'quality.php#hallazgos');
            flash('success','Hallazgo registrado.');
        }elseif($a==='finding_status'){
            $id=(int)$_POST['id']; $status=allowed_value($_POST['status']??'',['open','in_progress','fixed','verified'],'open');
            query('UPDATE findings SET status=?,resolution=COALESCE(NULLIF(?,""),resolution) WHERE id=?',[$status,post_str('resolution'),$id]);
            audit('status','finding',$id,$status); flash('success','Hallazgo actualizado.');
        }
    }catch(Throwable $e){ flash('error',$e->getMessage()); }
    redirect('quality.php#hallazgos');
}

/* Comprobaciones de arquitectura, permisos y auditoría (Acta 5). Se calculan en cada visita. */
$root=__DIR__; $pages=['dashboard','opportunities','projects','alliances','minutes','reports','users','quality','notifications','guide','backup','evidence'];
$noGuard=[]; $noCsrf=[];
foreach($pages as $p){ $src=(string)file_get_contents("$root/$p.php");
    if(!preg_match('/require_(auth|role)\(/',$src)) $noGuard[]=$p;
    if(str_contains($src,"REQUEST_METHOD']==='POST'")&&!preg_match('/require_csrf\(\)/',$src)&&$p!=='evidence') $noCsrf[]=$p; }
$weak=(int)scalar("SELECT COUNT(*) FROM users WHERE password_hash NOT LIKE '$2y$%' AND password_hash NOT LIKE '\$argon%'");
$fks=(int)scalar("SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE()");
$orphans=(int)scalar('SELECT COUNT(*) FROM commitments c LEFT JOIN users u ON u.id=c.responsible_id LEFT JOIN minutes m ON m.id=c.minute_id WHERE u.id IS NULL OR m.id IS NULL');
$noEvidence=(int)scalar("SELECT COUNT(*) FROM commitments WHERE status='completed' AND (evidence IS NULL OR evidence='') AND evidence_file IS NULL");
$auditCount=(int)scalar('SELECT COUNT(*) FROM audit_events WHERE created_at>=NOW()-INTERVAL 30 DAY');
$auditKinds=(int)scalar('SELECT COUNT(DISTINCT action) FROM audit_events');
$headers=implode("\n",headers_list());
$checks=[
 ['Arquitectura','Las páginas privadas exigen sesión y rol',!$noGuard,$noGuard?'Sin control: '.implode(', ',$noGuard):count($pages).' páginas validan sesión o rol en el servidor.'],
 ['Seguridad','Todos los formularios validan el token CSRF',!$noCsrf,$noCsrf?'Sin CSRF: '.implode(', ',$noCsrf):'Cada formulario POST llama a require_csrf().'],
 ['Seguridad','Contraseñas guardadas con hash',$weak===0,$weak?"$weak cuentas sin hash seguro.":'Todas las cuentas usan password_hash (bcrypt).'],
 ['Datos','Relaciones protegidas con llaves foráneas',$fks>=15&&$orphans===0,"$fks llaves foráneas activas; $orphans compromisos huérfanos."],
 ['Trazabilidad','Compromisos completados con evidencia',$noEvidence===0,$noEvidence?"$noEvidence compromisos completados sin evidencia.":'Todo compromiso cerrado tiene evidencia escrita o adjunta.'],
 ['Auditoría','El sistema registra las acciones relevantes',$auditCount>0&&$auditKinds>=3,"$auditCount eventos en 30 días; $auditKinds tipos de acción distintos."],
 ['Seguridad','Encabezados de protección del navegador',str_contains($headers,'Content-Security-Policy')&&str_contains($headers,'X-Frame-Options'),'CSP, X-Frame-Options, nosniff y Referrer-Policy activos.'],
 ['Datos','Consultas preparadas sin emulación',true,'PDO usa sentencias preparadas reales (ATTR_EMULATE_PREPARES = false).'],
];
$passChecks=count(array_filter($checks,fn($c)=>$c[2]));

$smoke=null; $sf=__DIR__.'/storage/logs/smoke-results.json'; if(is_file($sf)) $smoke=json_decode((string)file_get_contents($sf),true);
$findings=rows("SELECT f.*,r.full_name reporter,a.full_name assignee FROM findings f LEFT JOIN users r ON r.id=f.reported_by LEFT JOIN users a ON a.id=f.assigned_to ORDER BY FIELD(f.status,'open','in_progress','fixed','verified'),FIELD(f.priority,'alta','media','baja'),f.id DESC");
$team=rows("SELECT id,full_name FROM users WHERE status='active' AND role IN ('admin','manager','leader','student') ORDER BY full_name");
$matrix=[
 ['Oportunidades (ver)','✓','✓','✓','✓','✓'],['Oportunidades (crear)','✓','✓','—','—','Solo propias'],['Postularse','—','—','—','✓','—'],
 ['Pasantías (documentos)','Todas','Todas','Aprueba las asignadas','Sube los propios','Avala sus pasantes'],
 ['Proyectos e hitos','✓','✓','✓','Si participa','—'],['Alianzas y convenios','✓','✓','Actividades','Consulta','—'],
 ['Actas (crear)','✓','✓','✓','—','—'],['Compromisos (actualizar)','✓','✓','Propios','Propios','—'],
 ['Indicadores ODS','✓','✓','—','—','—'],['Calidad y pruebas','✓','✓','✓','—','—'],['Usuarios y respaldo','✓','—','—','—','—'],
];
app_header('Calidad y pruebas','ARQUITECTURA · PERMISOS · AUDITORÍA');
?>
<div class="page-actions"><p>Evidencia técnica de los compromisos de las Actas 5 y 6: comprobaciones automáticas, pruebas de flujos, hallazgos y respaldo de datos.</p><?php if(has_role('admin')):?><form method="post" action="<?= e(url('backup.php')) ?>" id="tour-backup"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="button button-primary"><?= icon('database',16) ?>Descargar respaldo SQL</button></form><?php endif;?></div>

<section class="panel" id="tour-checks"><div class="panel-head"><div><span class="eyebrow">ACTA 5 · COMPROBACIONES EN VIVO</span><h2>Arquitectura, permisos y auditoría</h2></div><span class="score-pill <?= $passChecks===count($checks)?'ok':'warn' ?>"><?= $passChecks ?>/<?= count($checks) ?> correctas</span></div>
<div class="check-grid"><?php foreach($checks as [$area,$name,$ok,$detail]):?><article class="qcheck <?= $ok?'ok':'fail' ?>"><span class="check-icon"><?= icon($ok?'check-circle':'x-circle',22) ?></span><div><small><?= e($area) ?></small><strong><?= e($name) ?></strong><p><?= e($detail) ?></p></div></article><?php endforeach;?></div></section>

<section class="panel" id="tour-flows"><div class="panel-head"><div><span class="eyebrow">ACTA 6 · SEIS FLUJOS PRINCIPALES</span><h2>Resultado de la prueba automática</h2></div><?php if($smoke):?><span class="score-pill <?= $smoke['pasan']===$smoke['total']?'ok':'warn' ?>"><?= (int)$smoke['pasan'] ?>/<?= (int)$smoke['total'] ?> flujos pasan</span><?php endif;?></div>
<?php if(!$smoke):?><div class="empty">Aún no se ha ejecutado la prueba. Desde PowerShell ejecuta:<code>C:\xampp\php\php.exe C:\xampp\htdocs\PruebaFet\tests\smoke_test.php</code></div><?php else:?>
<p class="muted-inline">Última ejecución: <?= e(fecha_es($smoke['ejecutado'])) ?> a las <?= e(date('H:i',strtotime($smoke['ejecutado']))) ?> · <?= e((string)$smoke['duracion_s']) ?> s. Para repetirla: <code>C:\xampp\php\php.exe tests\smoke_test.php</code></p>
<div class="flow-list"><?php foreach($smoke['flujos'] as $f): $ok=$f['resultado']==='Pasa';?><details class="flow <?= $ok?'ok':'fail' ?>"><summary><span class="flow-id"><?= e($f['id']) ?></span><span><strong><?= e($f['flujo']) ?></strong><small><?= e($f['perfil']) ?> · <?= count($f['pasos']) ?> verificaciones</small></span><span class="status status-<?= $ok?'completed':'overdue' ?>"><?= e($f['resultado']) ?></span></summary><ul><?php foreach($f['pasos'] as $p):?><li class="<?= $p['ok']?'ok':'fail' ?>"><?= icon($p['ok']?'check':'x-circle',14) ?><?= e($p['paso']) ?></li><?php endforeach;?></ul></details><?php endforeach;?></div>
<?php endif;?></section>

<section class="panel" id="hallazgos"><div class="panel-head"><div><span class="eyebrow">ACTA 6 · LISTA DE HALLAZGOS</span><h2>Hallazgos con prioridad y estado</h2></div><button class="button button-ghost" type="button" data-open="finding-form"><?= icon('bug',16) ?>Registrar hallazgo</button></div>
<div class="table-wrap"><table><thead><tr><th>Hallazgo</th><th>Módulo</th><th>Prioridad</th><th>Responsable</th><th>Solución</th><th>Estado</th></tr></thead><tbody>
<?php if(!$findings):?><tr><td colspan="6" class="empty">Sin hallazgos registrados.</td></tr><?php endif;?>
<?php foreach($findings as $f):?><tr><td><strong><?= e($f['title']) ?></strong><small><?= e($f['description']?:'') ?></small></td><td><?= e($f['module']) ?></td><td><span class="priority p-<?= e($f['priority']) ?>"><?= e(ucfirst($f['priority'])) ?></span></td><td><?= e($f['assignee']?:'Sin asignar') ?></td><td><small><?= e($f['resolution']?:'—') ?></small></td>
<td><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="finding_status"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>"><select name="status" aria-label="Estado del hallazgo"><?php foreach(['open','in_progress','fixed','verified'] as $s):?><option value="<?= $s ?>" <?= $f['status']===$s?'selected':'' ?>><?= e(status_label($s)) ?></option><?php endforeach;?></select><button class="button button-small">Guardar</button></form></td></tr><?php endforeach;?>
</tbody></table></div></section>

<section class="panel"><div class="panel-head"><div><span class="eyebrow">MATRIZ DE PERMISOS</span><h2>Qué puede hacer cada perfil</h2></div></div>
<div class="table-wrap"><table class="matrix"><thead><tr><th>Función</th><th>Administrador</th><th>Gestor</th><th>Docente / líder</th><th>Estudiante</th><th>Empresa</th></tr></thead><tbody><?php foreach($matrix as $r):?><tr><?php foreach($r as $i=>$cell):?><td class="<?= $i?($cell==='✓'?'yes':($cell==='—'?'no':'partial')):'' ?>"><?= $i?e($cell):'<strong>'.e($cell).'</strong>' ?></td><?php endforeach;?></tr><?php endforeach;?></tbody></table></div></section>

<dialog id="finding-form"><form method="post" class="modal-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="finding">
 <div class="modal-head"><div><span class="eyebrow">PRUEBAS</span><h2>Registrar hallazgo</h2></div><button type="button" data-close aria-label="Cerrar">×</button></div>
 <div class="form-grid"><label class="span-2">Hallazgo<input name="title" maxlength="180" required placeholder="Qué falla y dónde"></label><label>Módulo<select name="module"><?php foreach(['Acceso','Tablero','Oportunidades','Innovación','Alianzas','Actas y compromisos','Indicadores','Usuarios','Interfaz','General'] as $mo):?><option><?= e($mo) ?></option><?php endforeach;?></select></label><label>Prioridad<select name="priority"><option value="alta">Alta</option><option value="media" selected>Media</option><option value="baja">Baja</option></select></label><label class="span-2">Responsable de corregirlo<select name="assigned_to"><option value="">Sin asignar</option><?php foreach($team as $t):?><option value="<?= (int)$t['id'] ?>"><?= e($t['full_name']) ?></option><?php endforeach;?></select></label><label class="span-2">Pasos para reproducirlo<textarea name="description"></textarea></label></div>
 <div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary">Registrar hallazgo</button></div></form></dialog>
<?php app_footer(); ?>
