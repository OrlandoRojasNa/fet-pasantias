<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require_role('admin','manager','leader','student');
$u=current_user();
$canWrite=has_role('admin','manager','leader');
$canModerate=has_role('admin','manager');
$statuses=['pending','in_progress','completed'];

/** Un estudiante solo puede abrir actas donde tiene compromisos. */
function can_view_minute(int $minuteId): bool {
    if(!has_role('student')) return true;
    return (bool)scalar('SELECT COUNT(*) FROM commitments WHERE minute_id=? AND responsible_id=?',[$minuteId,current_user()['id']]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    $a=$_POST['action']??'';
    $back='minutes.php';
    try{
        if(($a==='minute'||$a==='minute_update')&&$canWrite){
            $number=post_str('number',30); $title=post_str('title',180); $date=valid_date($_POST['meeting_on']??null);
            $attendees=post_str('attendees'); $summary=post_str('summary');
            if($number===''||$title===''||!$date||$attendees===''||$summary==='') throw new RuntimeException('Completa número, tema, fecha, asistentes y resumen.');
            $fields=[$number,$title,$date,post_str('modality',150)?:null,($_POST['starts_at']??'')?:null,($_POST['ends_at']??'')?:null,$attendees,post_str('objectives')?:null,$summary,post_str('development')?:null,post_str('conclusion')?:null,post_str('work_model')?:null,post_str('next_agenda')?:null,post_str('observations')?:null,allowed_value($_POST['status']??'',['draft','approved'],'draft')];
            if($a==='minute'){
                query('INSERT INTO minutes(number,title,meeting_on,modality,starts_at,ends_at,attendees,objectives,summary,development,conclusion,work_model,next_agenda,observations,status,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[...$fields,$u['id']]);
                $id=(int)db()->lastInsertId(); audit('create','minute',$id,$number); flash('success','Acta '.$number.' registrada. Ahora agrega sus compromisos.');
            }else{
                $id=(int)$_POST['id'];
                query('UPDATE minutes SET number=?,title=?,meeting_on=?,modality=?,starts_at=?,ends_at=?,attendees=?,objectives=?,summary=?,development=?,conclusion=?,work_model=?,next_agenda=?,observations=?,status=? WHERE id=?',[...$fields,$id]);
                audit('update','minute',$id,$number); flash('success','Acta actualizada.');
            }
            $back='minutes.php?id='.$id;
        }elseif($a==='commitment'&&$canWrite){
            $mid=(int)$_POST['minute_id']; $desc=post_str('description'); $due=valid_date($_POST['due_on']??null); $resp=(int)($_POST['responsible_id']??0);
            if($desc===''||!$due||!row("SELECT id FROM users WHERE id=? AND status='active'",[$resp])||!row('SELECT id FROM minutes WHERE id=?',[$mid])) throw new RuntimeException('Escribe el compromiso, el responsable y la fecha de entrega.');
            query('INSERT INTO commitments(minute_id,description,responsible_id,due_on,expected_evidence,status) VALUES(?,?,?,?,?,?)',[$mid,$desc,$resp,$due,post_str('expected_evidence',255)?:null,'pending']);
            $cid=(int)db()->lastInsertId(); audit('create','commitment',$cid);
            $num=(string)scalar('SELECT number FROM minutes WHERE id=?',[$mid]);
            notify($resp,'Nuevo compromiso del acta '.$num,mb_substr($desc,0,160).' · Entrega: '.date('d/m/Y',strtotime($due)),'minutes.php?id='.$mid);
            flash('success','Compromiso asignado. El responsable recibió un aviso.');
            $back='minutes.php?id='.$mid;
        }elseif($a==='commitment_update'){
            $id=(int)$_POST['id'];
            $c=row('SELECT c.*,m.number,m.created_by FROM commitments c JOIN minutes m ON m.id=c.minute_id WHERE c.id=?',[$id]);
            if(!$c||(!$canModerate&&(int)$c['responsible_id']!==$u['id'])){ http_response_code(403); require __DIR__.'/includes/403.php'; exit; }
            $status=allowed_value($_POST['status']??'',$statuses,'pending');
            $evidence=post_str('evidence');
            $file=store_evidence_upload('evidence_file');
            if($status==='completed'&&$evidence===''&&!$file&&!$c['evidence_file']) throw new RuntimeException('Para completar un compromiso describe o adjunta la evidencia.');
            query('UPDATE commitments SET status=?,evidence=?,evidence_file=COALESCE(?,evidence_file),evidence_name=COALESCE(?,evidence_name),completed_at=IF(?="completed",COALESCE(completed_at,NOW()),NULL) WHERE id=?',
                [$status,$evidence?:null,$file['stored']??null,$file['original']??null,$status,$id]);
            if($status!=='completed') refresh_overdue_commitments();
            audit('status','commitment',$id,$status);
            if((int)$c['created_by']!==$u['id']) notify((int)$c['created_by'],'Compromiso actualizado · '.$c['number'],$u['full_name'].' lo marcó como «'.status_label($status).'».','minutes.php?id='.$c['minute_id']);
            flash('success','Compromiso actualizado'.($file?' con el archivo de evidencia.':'.'));
            $back=isset($_POST['return'])&&$_POST['return']==='detail'?'minutes.php?id='.$c['minute_id']:'minutes.php';
        }
    }catch(PDOException $e){
        flash('error',$e->getCode()==='23000'?'Ya existe un acta con ese número.':'No se pudo guardar el registro.');
    }catch(Throwable $e){ flash('error',$e->getMessage()); }
    redirect($back);
}

refresh_overdue_commitments();
$users=rows("SELECT id,full_name,role FROM users WHERE status='active' AND role<>'company' ORDER BY full_name");

/* ---------- Vista de detalle: acta en Formato 1 ---------- */
if(isset($_GET['id'])){
    $id=(int)$_GET['id'];
    $m=row('SELECT m.*,u.full_name creator FROM minutes m JOIN users u ON u.id=m.created_by WHERE m.id=?',[$id]);
    if(!$m||!can_view_minute($id)){ flash('error','El acta no existe o no tienes acceso.'); redirect('minutes.php'); }
    $list=rows('SELECT c.*,u.full_name responsible FROM commitments c JOIN users u ON u.id=c.responsible_id WHERE c.minute_id=? ORDER BY c.due_on,c.id',[$id]);
    $done=count(array_filter($list,fn($c)=>$c['status']==='completed')); $pct=$list?(int)round($done/count($list)*100):0;
    $lines=fn(?string $t)=>array_values(array_filter(array_map('trim',explode("\n",(string)$t))));
    app_header('Acta '.$m['number'],'ACTAS Y COMPROMISOS');
    ?>
    <div class="page-actions no-print"><a class="button button-ghost" href="<?= e(url('minutes.php')) ?>"><?= icon('arrow-left',16) ?>Volver a las actas</a><div class="button-row"><button class="button button-ghost" type="button" data-print><?= icon('printer',16) ?>Imprimir o guardar PDF</button><?php if($canWrite):?><button class="button button-primary" type="button" data-open="minute-edit"><?= icon('edit',16) ?>Editar acta</button><?php endif;?></div></div>
    <article class="acta-sheet" id="tour-acta">
     <header class="acta-head"><img src="<?= e(url('assets/img/logo-fet.png')) ?>" alt="Fundación Escuela Tecnológica de Neiva Jesús Oviedo Pérez" width="190" height="104"><div><h2>Acta de reunión No. <?= e(preg_replace('/\D/','',$m['number'])?:$m['number']) ?></h2><p><?= e($m['title']) ?></p></div><span class="status status-<?= e($m['status']) ?>"><?= e(status_label($m['status'])) ?></span></header>
     <dl class="acta-meta"><div><dt>Fecha</dt><dd><?= e(fecha_es($m['meeting_on'])) ?></dd></div><div><dt>Lugar o modalidad</dt><dd><?= e($m['modality']?:'Por registrar') ?></dd></div><div><dt>Horario</dt><dd><?= $m['starts_at']?e(substr($m['starts_at'],0,5).' a '.substr((string)$m['ends_at'],0,5)):'Por registrar' ?></dd></div><div><dt>Registrada por</dt><dd><?= e($m['creator']) ?></dd></div></dl>
     <section><h3>Participantes</h3><ul class="acta-people"><?php foreach(array_filter(array_map('trim',preg_split('/,(?![^(]*\))/',$m['attendees']))) as $p):?><li><?= e($p) ?></li><?php endforeach;?></ul></section>
     <?php if($m['objectives']):?><section><h3>Objetivos de la reunión</h3><p><?= e($m['objectives']) ?></p></section><?php endif;?>
     <section><h3>Desarrollo</h3><?php $dev=$lines($m['development']); if($dev):?><ol class="acta-list"><?php foreach($dev as $l):?><li><?= e($l) ?></li><?php endforeach;?></ol><?php else:?><p><?= e($m['summary']) ?></p><?php endif;?></section>
     <section id="tour-commitments"><div class="acta-section-head"><h3>Compromisos</h3><div class="mini-progress"><span><?= $done ?> de <?= count($list) ?> cumplidos</span><div class="progress"><i style="width:<?= $pct ?>%"></i></div><strong><?= $pct ?>%</strong></div></div>
      <div class="table-wrap"><table class="commit-table"><thead><tr><th>Responsable</th><th>Compromiso</th><th>Entrega</th><th>Evidencia</th><th>Estado</th></tr></thead><tbody>
      <?php if(!$list):?><tr><td colspan="5" class="empty">Esta acta aún no tiene compromisos.</td></tr><?php endif;?>
      <?php foreach($list as $c): $mine=$canModerate||(int)$c['responsible_id']===$u['id'];?>
       <tr><td><strong><?= e($c['responsible']) ?></strong></td><td><?= e($c['description']) ?><?php if($c['expected_evidence']):?><small>Evidencia esperada: <?= e($c['expected_evidence']) ?></small><?php endif;?></td><td><?= e(date('d/m/Y',strtotime($c['due_on']))) ?></td>
       <td><?= $c['evidence']?e($c['evidence']):'<span class="muted-inline">Sin registrar</span>' ?><?php if($c['evidence_file']):?><a class="file-link" href="<?= e(url('evidence.php?type=commitment&id='.(int)$c['id'])) ?>"><?= icon('paperclip',14) ?><?= e($c['evidence_name']?:'Archivo adjunto') ?></a><?php endif;?></td>
       <td><span class="status status-<?= e($c['status']) ?>"><?= e(status_label($c['status'])) ?></span><?php if($mine):?><button class="link-button no-print" type="button" data-open="commit-<?= (int)$c['id'] ?>">Actualizar</button><?php endif;?></td></tr>
      <?php endforeach;?></tbody></table></div>
      <?php if($canWrite):?><details class="add-inline no-print"><summary><?= icon('plus',15) ?>Agregar compromiso</summary><form method="post" class="inline-create commit-create"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="commitment"><input type="hidden" name="minute_id" value="<?= (int)$m['id'] ?>"><label>Compromiso<textarea name="description" required></textarea></label><label>Responsable<select name="responsible_id"><?php foreach($users as $p):?><option value="<?= (int)$p['id'] ?>"><?= e($p['full_name']) ?></option><?php endforeach;?></select></label><label>Fecha de entrega<input type="date" name="due_on" required></label><label>Evidencia esperada<input name="expected_evidence" maxlength="255" placeholder="Ej.: captura, informe o archivo SQL"></label><button class="button button-primary button-small">Asignar compromiso</button></form></details><?php endif;?>
     </section>
     <?php if($m['conclusion']):?><section><h3>Conclusión</h3><p><?= e($m['conclusion']) ?></p></section><?php endif;?>
     <?php if($m['work_model']):?><section><h3>Modelo de trabajo</h3><p><?= e($m['work_model']) ?></p></section><?php endif;?>
     <?php $ag=$lines($m['next_agenda']); if($ag):?><section><h3>Agenda para futura reunión</h3><ul class="acta-list"><?php foreach($ag as $l):?><li><?= e($l) ?></li><?php endforeach;?></ul></section><?php endif;?>
     <?php if($m['observations']):?><section><h3>Observaciones</h3><p><?= e($m['observations']) ?></p></section><?php endif;?>
     <footer class="acta-signatures"><div><span></span><strong>Miguel Antonio Urbano Silva</strong><small>Docente gestor</small></div><div><span></span><strong>Juan José Sabogal García</strong><small>Seguimiento, pruebas y auditoría</small></div></footer>
    </article>
    <?php foreach($list as $c): if(!($canModerate||(int)$c['responsible_id']===$u['id'])) continue; include __DIR__.'/includes/commitment-dialog.php'; endforeach; ?>
    <?php if($canWrite){ $edit=$m; include __DIR__.'/includes/minute-form.php'; } ?>
    <?php app_footer(); exit;
}

/* ---------- Vista general ---------- */
$isStudent=has_role('student');
$fMinute=(int)($_GET['acta']??0); $fResp=(int)($_GET['responsable']??0); $fStatus=allowed_value($_GET['estado']??'',['pending','in_progress','completed','overdue'],'');
$minutes=$isStudent
 ? rows('SELECT m.*,u.full_name creator,COUNT(c.id) total,SUM(c.status="completed") done FROM minutes m JOIN users u ON u.id=m.created_by JOIN commitments c ON c.minute_id=m.id AND c.responsible_id=? GROUP BY m.id ORDER BY m.meeting_on DESC,m.id DESC',[$u['id']])
 : rows('SELECT m.*,u.full_name creator,COUNT(c.id) total,COALESCE(SUM(c.status="completed"),0) done FROM minutes m JOIN users u ON u.id=m.created_by LEFT JOIN commitments c ON c.minute_id=m.id GROUP BY m.id ORDER BY m.meeting_on DESC,m.id DESC');
$where=['1=1']; $params=[];
if($isStudent){ $where[]='c.responsible_id=?'; $params[]=$u['id']; }
if($fMinute){ $where[]='c.minute_id=?'; $params[]=$fMinute; }
if($fResp&&!$isStudent){ $where[]='c.responsible_id=?'; $params[]=$fResp; }
if($fStatus){ $where[]='c.status=?'; $params[]=$fStatus; }
$commitments=rows('SELECT c.*,m.number,m.title minute_title,u.full_name responsible FROM commitments c JOIN minutes m ON m.id=c.minute_id JOIN users u ON u.id=c.responsible_id WHERE '.implode(' AND ',$where)." ORDER BY FIELD(c.status,'overdue','pending','in_progress','completed'),c.due_on",$params);
$scope=$isStudent?' WHERE responsible_id='.(int)$u['id']:'';
$k=row("SELECT COUNT(*) total,COALESCE(SUM(status='completed'),0) done,COALESCE(SUM(status='overdue'),0) overdue,COALESCE(SUM(status IN ('pending','in_progress')),0) open FROM commitments$scope");
$kPct=$k['total']?(int)round($k['done']/$k['total']*100):0;
app_header('Actas y compromisos','TRAZABILIDAD DE DECISIONES');
?>
<div class="page-actions"><p>Cada acta guarda sus decisiones; cada compromiso, su responsable, su fecha y la evidencia que lo cierra.</p><?php if($canWrite):?><button class="button button-primary" type="button" data-open="minute-form" id="tour-new-minute"><?= icon('plus',16) ?>Nueva acta</button><?php endif;?></div>
<section class="kpi-strip" id="tour-kpis">
 <article><span class="kpi-icon tone-fet"><?= icon('clipboard',22) ?></span><div><strong><?= count($minutes) ?></strong><span>Actas registradas</span></div></article>
 <article><span class="kpi-icon tone-teal"><?= icon('check-circle',22) ?></span><div><strong><?= (int)$k['done'] ?>/<?= (int)$k['total'] ?></strong><span>Compromisos cumplidos</span></div></article>
 <article><span class="kpi-icon tone-gold"><?= icon('clock',22) ?></span><div><strong><?= (int)$k['open'] ?></strong><span>En curso o pendientes</span></div></article>
 <article><span class="kpi-icon tone-ods8"><?= icon('alert',22) ?></span><div><strong><?= (int)$k['overdue'] ?></strong><span>Vencidos</span></div></article>
 <article class="kpi-big"><div class="ring small" style="--value:<?= $kPct ?>;--ring:var(--fet)"><strong><?= $kPct ?>%</strong></div><span>Cumplimiento general</span></article>
</section>
<?php if($canWrite){ $edit=null; include __DIR__.'/includes/minute-form.php'; } ?>
<section class="minutes-grid" id="tour-minutes">
<?php if(!$minutes):?><div class="empty panel"><?= $isStudent?'Todavía no tienes compromisos asignados en ninguna acta.':'Aún no hay actas. Registra la primera con «Nueva acta».' ?></div><?php endif;?>
<?php foreach($minutes as $m): $p=$m['total']?(int)round($m['done']/$m['total']*100):0;?>
 <a class="minute-card" href="<?= e(url('minutes.php?id='.(int)$m['id'])) ?>">
  <div class="minute-number"><small>Acta</small><?= e(preg_replace('/\D/','',$m['number'])?:$m['number']) ?></div>
  <div><div class="card-top"><time><?= icon('calendar',13) ?><?= e(fecha_es($m['meeting_on'],'short')) ?></time><span class="status status-<?= e($m['status']) ?>"><?= e(status_label($m['status'])) ?></span></div>
  <h2><?= e($m['title']) ?></h2><p><?= e($m['summary']) ?></p>
  <div class="mini-progress"><span><?= (int)$m['done'] ?> de <?= (int)$m['total'] ?> compromisos</span><div class="progress"><i style="width:<?= $p ?>%"></i></div><strong><?= $p ?>%</strong></div></div>
 </a>
<?php endforeach;?>
</section>
<section class="panel" id="tour-matrix"><div class="panel-head"><div><span class="eyebrow">SEGUIMIENTO</span><h2>Matriz de compromisos</h2></div>
 <form method="get" class="filter-form" aria-label="Filtrar compromisos"><select name="acta" aria-label="Acta"><option value="">Todas las actas</option><?php foreach($minutes as $m):?><option value="<?= (int)$m['id'] ?>" <?= $fMinute===(int)$m['id']?'selected':'' ?>><?= e($m['number']) ?></option><?php endforeach;?></select><?php if(!$isStudent):?><select name="responsable" aria-label="Responsable"><option value="">Todos los responsables</option><?php foreach($users as $p):?><option value="<?= (int)$p['id'] ?>" <?= $fResp===(int)$p['id']?'selected':'' ?>><?= e($p['full_name']) ?></option><?php endforeach;?></select><?php endif;?><select name="estado" aria-label="Estado"><option value="">Todos los estados</option><?php foreach(['pending','in_progress','completed','overdue'] as $s):?><option value="<?= $s ?>" <?= $fStatus===$s?'selected':'' ?>><?= e(status_label($s)) ?></option><?php endforeach;?></select><button class="button button-small"><?= icon('filter',14) ?>Filtrar</button></form></div>
<div class="table-wrap"><table><thead><tr><th>Compromiso</th><th>Acta</th><th>Responsable</th><th>Entrega</th><th>Evidencia</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
<?php if(!$commitments):?><tr><td colspan="7" class="empty">No hay compromisos con estos filtros.</td></tr><?php endif;?>
<?php foreach($commitments as $c): $mine=$canModerate||(int)$c['responsible_id']===$u['id'];?>
<tr><td><strong><?= e($c['description']) ?></strong></td><td><a class="text-link" href="<?= e(url('minutes.php?id='.(int)$c['minute_id'])) ?>"><?= e($c['number']) ?></a></td><td><?= e($c['responsible']) ?></td><td><?= e(date('d/m/Y',strtotime($c['due_on']))) ?></td>
<td><?php if($c['evidence']||$c['evidence_file']):?><span class="evidence-ok"><?= icon('check',14) ?><?= e(mb_strimwidth((string)($c['evidence']?:$c['evidence_name']),0,60,'…')) ?></span><?php else:?><span class="muted-inline"><?= e($c['expected_evidence']?:'Sin registrar') ?></span><?php endif;?></td>
<td><span class="status status-<?= e($c['status']) ?>"><?= e(status_label($c['status'])) ?></span></td>
<td><?php if($mine):?><button class="button button-small" type="button" data-open="commit-<?= (int)$c['id'] ?>"><?= icon('edit',13) ?>Actualizar</button><?php else:?><span class="muted-inline">Solo lectura</span><?php endif;?></td></tr>
<?php endforeach;?></tbody></table></div></section>
<?php foreach($commitments as $c): if(!($canModerate||(int)$c['responsible_id']===$u['id'])) continue; $returnTo='list'; include __DIR__.'/includes/commitment-dialog.php'; endforeach; ?>
<?php app_footer(); ?>
