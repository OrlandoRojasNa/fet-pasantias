<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require_role('admin','manager','leader','student');
$u=current_user();
$canWrite=has_role('admin','manager','leader');
$mStatuses=['pending','in_progress','completed','blocked'];

if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf(); $action=$_POST['action']??'';
    try{
        if($action==='create'&&$canWrite){
            $name=post_str('name',180); $summary=post_str('summary'); $leader=(int)($_POST['leader_id']??0);
            if($name===''||$summary===''||!row("SELECT id FROM users WHERE id=? AND role IN ('admin','manager','leader')",[$leader])) throw new RuntimeException('Completa el nombre, el resumen y el líder.');
            query('INSERT INTO projects(name,summary,ods,leader_id,status,starts_on,ends_on) VALUES(?,?,?,?,?,?,?)',[$name,$summary,(int)allowed_value($_POST['ods']??'',['8','9','17'],'9'),$leader,allowed_value($_POST['status']??'',['planned','active','paused'],'planned'),valid_date($_POST['starts_on']??null),valid_date($_POST['ends_on']??null)]);
            $id=(int)db()->lastInsertId();
            query('INSERT IGNORE INTO project_members(project_id,user_id,responsibility) VALUES(?,?,?)',[$id,$leader,'Liderazgo del proyecto']);
            audit('create','project',$id,$name); flash('success','Proyecto creado.');
        }elseif($action==='project_status'&&$canWrite){
            $id=(int)$_POST['id']; $st=allowed_value($_POST['status']??'',['planned','active','paused','completed'],'active');
            query('UPDATE projects SET status=? WHERE id=?',[$st,$id]); audit('status','project',$id,$st); flash('success','Estado del proyecto actualizado.');
        }elseif($action==='member'&&$canWrite){
            $pid=(int)$_POST['project_id']; $uid=(int)$_POST['user_id'];
            if(!row("SELECT id FROM users WHERE id=? AND status='active' AND role<>'company'",[$uid])) throw new RuntimeException('Selecciona un integrante válido.');
            query('INSERT INTO project_members(project_id,user_id,responsibility) VALUES(?,?,?) ON DUPLICATE KEY UPDATE responsibility=VALUES(responsibility)',[$pid,$uid,post_str('responsibility',180)?:null]);
            $pname=(string)scalar('SELECT name FROM projects WHERE id=?',[$pid]);
            notify($uid,'Te agregaron a un proyecto','Ahora haces parte de «'.$pname.'».','projects.php');
            audit('create','project_member',$pid,(string)$uid); flash('success','Integrante agregado al equipo.');
        }elseif($action==='milestone'&&$canWrite){
            $pid=(int)$_POST['project_id']; $title=post_str('title',180); $due=valid_date($_POST['due_on']??null);
            if($title===''||!$due) throw new RuntimeException('Escribe el nombre del hito y su fecha.');
            query('INSERT INTO milestones(project_id,title,due_on,status,evidence) VALUES(?,?,?,?,?)',[$pid,$title,$due,allowed_value($_POST['status']??'',['pending','in_progress'],'pending'),post_str('evidence')?:null]);
            audit('create','milestone',(int)db()->lastInsertId()); flash('success','Hito agregado.');
        }elseif($action==='milestone_status'&&$canWrite){
            $id=(int)$_POST['id']; $status=allowed_value($_POST['status']??'',$mStatuses,'pending');
            $file=store_evidence_upload('evidence_file');
            query('UPDATE milestones SET status=?,evidence=?,evidence_file=COALESCE(?,evidence_file),evidence_name=COALESCE(?,evidence_name),completed_at=IF(?="completed",COALESCE(completed_at,NOW()),NULL) WHERE id=?',[$status,post_str('evidence')?:null,$file['stored']??null,$file['original']??null,$status,$id]);
            audit('status','milestone',$id,$status); flash('success','Hito actualizado.');
        }
    }catch(Throwable $e){ flash('error',$e instanceof RuntimeException?$e->getMessage():'Revisa los datos del formulario.'); }
    redirect('projects.php');
}

$leaders=rows("SELECT id,full_name FROM users WHERE role IN ('admin','manager','leader') AND status='active' ORDER BY full_name");
$people=rows("SELECT id,full_name,role FROM users WHERE status='active' AND role<>'company' ORDER BY full_name");
$where='1=1'; $params=[];
if(has_role('student')){ $where='p.id IN (SELECT project_id FROM project_members WHERE user_id=?)'; $params[]=$u['id']; }
$projects=rows("SELECT p.*,u.full_name leader,(SELECT COUNT(*) FROM milestones m WHERE m.project_id=p.id) milestones,(SELECT COUNT(*) FROM milestones m WHERE m.project_id=p.id AND m.status='completed') completed FROM projects p JOIN users u ON u.id=p.leader_id WHERE $where ORDER BY FIELD(p.status,'active','planned','paused','completed'),p.updated_at DESC",$params);
$byProject=[]; foreach(rows('SELECT * FROM milestones ORDER BY due_on') as $m) $byProject[$m['project_id']][]=$m;
$team=[]; foreach(rows('SELECT pm.*,u.full_name FROM project_members pm JOIN users u ON u.id=pm.user_id ORDER BY u.full_name') as $t) $team[$t['project_id']][]=$t;
app_header('Proyectos de innovación','ODS 9 · INNOVACIÓN E INFRAESTRUCTURA');
?>
<div class="page-actions"><p>Responsables, equipo, hitos y evidencias para conservar la continuidad de cada iniciativa.</p><?php if($canWrite):?><button class="button button-primary" type="button" data-open="project-form" id="tour-new-project"><?= icon('plus',16) ?>Nuevo proyecto</button><?php endif;?></div>
<?php if($canWrite):?><dialog id="project-form"><form method="post" class="modal-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create"><div class="modal-head"><div><span class="eyebrow">ODS 9</span><h2>Crear proyecto</h2></div><button type="button" data-close aria-label="Cerrar">×</button></div><div class="form-grid"><label class="span-2">Nombre<input name="name" maxlength="180" required></label><label>Objetivo relacionado<select name="ods"><option value="9">ODS 9 · Innovación</option><option value="8">ODS 8 · Trabajo decente</option><option value="17">ODS 17 · Alianzas</option></select></label><label>Líder<select name="leader_id"><?php foreach($leaders as $l):?><option value="<?= (int)$l['id'] ?>"><?= e($l['full_name']) ?></option><?php endforeach;?></select></label><label>Inicio<input type="date" name="starts_on"></label><label>Cierre previsto<input type="date" name="ends_on"></label><label>Estado<select name="status"><option value="planned">Planeado</option><option value="active">Activo</option><option value="paused">En pausa</option></select></label><label class="span-2">Resumen<textarea name="summary" required></textarea></label></div><div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary">Crear proyecto</button></div></form></dialog><?php endif;?>
<section class="project-list" id="tour-projects">
<?php if(!$projects):?><div class="empty panel"><?= has_role('student')?'Aún no participas en ningún proyecto. Cuando un docente te agregue, aparecerá aquí.':'No hay proyectos. Crea el primero con «Nuevo proyecto».' ?></div><?php endif;?>
<?php foreach($projects as $p): $progress=$p['milestones']?(int)round($p['completed']/$p['milestones']*100):0;?>
<article class="project-card">
 <div class="project-head"><div class="ods-badge ods-<?= (int)$p['ods'] ?>"><?= icon((int)$p['ods']===8?'briefcase':((int)$p['ods']===17?'handshake':'rocket'),24) ?><span>ODS <?= (int)$p['ods'] ?></span></div><div><span class="status status-<?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span><h2><?= e($p['name']) ?></h2><p><?= e($p['summary']) ?></p></div><div class="project-score"><strong><?= $progress ?>%</strong><span>avance</span></div></div>
 <div class="project-meta"><span><small>Líder</small><?= e($p['leader']) ?></span><span><small>Periodo</small><?= $p['starts_on']?e(date('d/m/Y',strtotime($p['starts_on']))):'Por definir' ?> — <?= $p['ends_on']?e(date('d/m/Y',strtotime($p['ends_on']))):'Abierto' ?></span><span><small>Hitos</small><?= (int)$p['completed'] ?> de <?= (int)$p['milestones'] ?> completados</span>
 <?php if($canWrite):?><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="project_status"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><select name="status" aria-label="Estado del proyecto"><?php foreach(['planned','active','paused','completed'] as $s):?><option value="<?= $s ?>" <?= $p['status']===$s?'selected':'' ?>><?= e(status_label($s)) ?></option><?php endforeach;?></select><button class="button button-small">Guardar</button></form><?php endif;?></div>
 <div class="progress"><i style="width:<?= $progress ?>%"></i></div>
 <div class="team-row"><span class="team-label"><?= icon('users',15) ?>Equipo</span><?php foreach($team[$p['id']]??[] as $t):?><span class="chip"><b><?= e(mb_strtoupper(mb_substr($t['full_name'],0,1))) ?></b><?= e($t['full_name']) ?><?php if($t['responsibility']):?><small><?= e($t['responsibility']) ?></small><?php endif;?></span><?php endforeach;?>
 <?php if($canWrite):?><details class="chip-add"><summary><?= icon('user-plus',14) ?>Agregar</summary><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="member"><input type="hidden" name="project_id" value="<?= (int)$p['id'] ?>"><select name="user_id" aria-label="Integrante"><?php foreach($people as $pe):?><option value="<?= (int)$pe['id'] ?>"><?= e($pe['full_name']) ?></option><?php endforeach;?></select><input name="responsibility" placeholder="Responsabilidad" maxlength="180" aria-label="Responsabilidad"><button class="button button-small">Agregar</button></form></details><?php endif;?></div>
 <div class="milestone-list"><?php foreach($byProject[$p['id']]??[] as $m):?>
  <form method="post" enctype="multipart/form-data" class="milestone"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="milestone_status"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><span class="status-dot <?= $m['status']==='completed'?'done':($m['status']==='blocked'?'danger':'') ?>"></span><div><strong><?= e($m['title']) ?></strong><small>Entrega: <?= e(date('d/m/Y',strtotime($m['due_on']))) ?><?php if($m['evidence_file']):?> · <a class="file-link" href="<?= e(url('evidence.php?type=milestone&id='.(int)$m['id'])) ?>"><?= icon('paperclip',12) ?><?= e($m['evidence_name']) ?></a><?php endif;?></small></div>
  <?php if($canWrite):?><input name="evidence" value="<?= e($m['evidence']) ?>" placeholder="Evidencia o enlace" aria-label="Evidencia"><label class="file-mini" title="Adjuntar archivo"><?= icon('paperclip',15) ?><input type="file" name="evidence_file" aria-label="Archivo de evidencia"></label><select name="status" aria-label="Estado del hito"><?php foreach($mStatuses as $s):?><option value="<?= $s ?>" <?= $m['status']===$s?'selected':'' ?>><?= e(status_label($s)) ?></option><?php endforeach;?></select><button class="button button-small">Guardar</button>
  <?php else:?><span class="muted-inline"><?= e($m['evidence']?:'Sin evidencia') ?></span><span class="status status-<?= e($m['status']) ?>"><?= e(status_label($m['status'])) ?></span><?php endif;?></form>
 <?php endforeach;?></div>
 <?php if($canWrite):?><details class="add-inline"><summary><?= icon('flag',15) ?>Agregar hito</summary><form method="post" class="inline-create"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="milestone"><input type="hidden" name="project_id" value="<?= (int)$p['id'] ?>"><input name="title" placeholder="Nombre del hito" maxlength="180" required aria-label="Nombre del hito"><input type="date" name="due_on" required aria-label="Fecha"><select name="status" aria-label="Estado"><option value="pending">Pendiente</option><option value="in_progress">En curso</option></select><input name="evidence" placeholder="Evidencia inicial (opcional)" aria-label="Evidencia inicial"><button class="button button-primary button-small">Agregar</button></form></details><?php endif;?>
</article>
<?php endforeach;?>
</section>
<?php app_footer(); ?>
