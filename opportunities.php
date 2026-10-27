<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require_auth();

$u=current_user();
$isStudent=has_role('student');
$isCompany=has_role('company');
$canModerate=has_role('admin','manager');
$canManage=$canModerate||$isCompany;

if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    $action=$_POST['action']??'';
    try{
        if($action==='create'&&$canManage){
            $title=post_str('title',180);
            $type=allowed_value($_POST['type']??'',['practica','empleo','formacion'],'practica');
            $modality=allowed_value($_POST['modality']??'',['presencial','hibrida','remota'],'presencial');
            $closes=valid_date($_POST['closes_on']??null);
            $organization=$isCompany?trim((string)$u['program']):trim((string)($_POST['organization']??''));
            $description=trim((string)($_POST['description']??''));
            $status=(string)($_POST['status']??'draft');
            $allowedStatuses=$isCompany?['draft','review']:['draft','review','published'];
            if(!in_array($status,$allowedStatuses,true)) throw new RuntimeException('El estado seleccionado no está permitido.');
            if($title===''||$organization===''||$description===''||!$closes) throw new RuntimeException('Completa los campos obligatorios.');
            query(
                'INSERT INTO opportunities(title,organization,type,description,requirements,location,modality,closes_on,status,verified_at,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?)',
                [$title,$organization,$type,$description,post_str('requirements'),post_str('location',150),$modality,$closes,$status,$status==='published'?date('Y-m-d H:i:s'):null,$u['id']]
            );
            $id=(int)db()->lastInsertId();
            audit('create','opportunity',$id,$title);
            if($status==='review') notify_roles(['admin','manager'],'Oportunidad por verificar',$organization.' envió «'.$title.'» para revisión.','opportunities.php',$u['id']);
            flash('success',$isCompany?'Oportunidad enviada para revisión.':'Oportunidad creada correctamente.');
        }elseif($action==='apply'&&$isStudent){
            $id=(int)$_POST['opportunity_id'];
            $opportunity=row("SELECT id FROM opportunities WHERE id=? AND status='published' AND closes_on>=CURDATE()",[$id]);
            if(!$opportunity) throw new RuntimeException('La oportunidad ya no recibe postulaciones.');
            $motivation=trim((string)$_POST['motivation']);
            if(mb_strlen($motivation)<20) throw new RuntimeException('Explica tu interés en al menos 20 caracteres.');
            $prev=row('SELECT id,status FROM applications WHERE opportunity_id=? AND user_id=?',[$id,$u['id']]);
            if($prev&&$prev['status']==='withdrawn') query("UPDATE applications SET motivation=?,status='submitted' WHERE id=?",[$motivation,$prev['id']]);
            else query('INSERT INTO applications(opportunity_id,user_id,motivation) VALUES(?,?,?)',[$id,$u['id'],$motivation]);
            audit('apply','opportunity',$id);
            $op=row('SELECT title,created_by FROM opportunities WHERE id=?',[$id]);
            notify((int)$op['created_by'],'Nueva postulación',$u['full_name'].' se postuló a «'.$op['title'].'».','opportunities.php');
            flash('success','Tu postulación quedó registrada.');
        }elseif($action==='status'&&$canManage){
            $id=(int)$_POST['id'];
            $status=(string)$_POST['status'];
            $allowedStatuses=$isCompany?['draft','review','closed']:['draft','review','published','closed'];
            if(!in_array($status,$allowedStatuses,true)) throw new RuntimeException('El estado seleccionado no está permitido.');
            if($isCompany){
                $owned=row('SELECT id FROM opportunities WHERE id=? AND created_by=?',[$id,$u['id']]);
                if(!$owned) throw new RuntimeException('Solo puedes modificar oportunidades de tu empresa.');
            }
            query('UPDATE opportunities SET status=?,verified_at=IF(?="published",NOW(),verified_at) WHERE id=?',[$status,$status,$id]);
            audit('status','opportunity',$id,$status);
            $op=row('SELECT title,created_by FROM opportunities WHERE id=?',[$id]);
            if($op&&(int)$op['created_by']!==$u['id']) notify((int)$op['created_by'],'Tu oportunidad cambió de estado','«'.$op['title'].'» ahora está «'.status_label($status).'».','opportunities.php');
            if($isCompany&&$status==='review') notify_roles(['admin','manager'],'Oportunidad por verificar',$u['program'].' envió «'.$op['title'].'» para revisión.','opportunities.php',$u['id']);
            flash('success','Estado actualizado.');
        }elseif($action==='application_status'&&$canManage){
            $id=(int)$_POST['id'];
            $status=(string)$_POST['status'];
            if(!in_array($status,['submitted','review','accepted','rejected'],true)) throw new RuntimeException('El estado seleccionado no está permitido.');
            if($isCompany){
                $owned=row('SELECT a.id FROM applications a JOIN opportunities o ON o.id=a.opportunity_id WHERE a.id=? AND o.created_by=?',[$id,$u['id']]);
                if(!$owned) throw new RuntimeException('Solo puedes revisar postulaciones recibidas por tu empresa.');
            }
            query('UPDATE applications SET status=? WHERE id=?',[$status,$id]);
            audit('status','application',$id,$status);
            if($status==='accepted') create_internship_from_application($id);
            $ap=row('SELECT a.user_id,o.title FROM applications a JOIN opportunities o ON o.id=a.opportunity_id WHERE a.id=?',[$id]);
            notify((int)$ap['user_id'],'Tu postulación fue actualizada','«'.$ap['title'].'»: '.status_label($status).'.','opportunities.php');
            flash('success','Postulación actualizada.');
        }elseif($action==='withdraw'&&$isStudent){
            $id=(int)$_POST['id'];
            $ap=row("SELECT id FROM applications WHERE id=? AND user_id=? AND status IN ('submitted','review')",[$id,$u['id']]);
            if(!$ap) throw new RuntimeException('Esta postulación ya no se puede retirar.');
            query("UPDATE applications SET status='withdrawn' WHERE id=?",[$id]);
            audit('status','application',$id,'withdrawn');
            flash('success','Retiraste tu postulación.');
        }
    }catch(PDOException $e){
        flash('error',$e->getCode()==='23000'?'Ya existe una postulación para esta oportunidad.':'No fue posible guardar el cambio.');
    }catch(Throwable $e){
        flash('error',$e->getMessage());
    }
    redirect('opportunities.php');
}

$type=$_GET['type']??'';
$params=[];
if($canModerate){
    $where='1=1';
}elseif($isCompany){
    $where='o.created_by=?';
    $params[]=$u['id'];
}else{
    $where="o.status='published' AND o.closes_on>=CURDATE()";
}
if(in_array($type,['practica','empleo','formacion'],true)){
    $where.=' AND o.type=?';
    $params[]=$type;
}
$items=rows("SELECT o.*,u.full_name creator,(SELECT COUNT(*) FROM applications a WHERE a.opportunity_id=o.id) applicants FROM opportunities o JOIN users u ON u.id=o.created_by WHERE $where ORDER BY FIELD(o.status,'published','review','draft','closed'),o.closes_on",$params);

if($canModerate){
    $applications=rows('SELECT a.*,o.title,u.full_name,u.email FROM applications a JOIN opportunities o ON o.id=a.opportunity_id JOIN users u ON u.id=a.user_id ORDER BY a.created_at DESC');
}elseif($isCompany){
    $applications=rows('SELECT a.*,o.title,u.full_name,u.email FROM applications a JOIN opportunities o ON o.id=a.opportunity_id JOIN users u ON u.id=a.user_id WHERE o.created_by=? ORDER BY a.created_at DESC',[$u['id']]);
}elseif($isStudent){
    $applications=rows('SELECT a.*,o.title,o.organization FROM applications a JOIN opportunities o ON o.id=a.opportunity_id WHERE a.user_id=? ORDER BY a.created_at DESC',[$u['id']]);
}else{
    $applications=[];
}

$applied=$isStudent?array_column(rows("SELECT opportunity_id,status FROM applications WHERE user_id=? AND status<>'withdrawn'",[$u['id']]),'status','opportunity_id'):[];
app_header('Oportunidades profesionales','ODS 8 · TRABAJO DECENTE');
?>
<div class="page-actions">
 <div><p><?= $isCompany?'Publica vacantes de práctica y revisa únicamente las postulaciones recibidas por tu empresa.':'Convocatorias verificadas de prácticas, empleo y formación.' ?></p></div>
 <?php if($canManage):?><button class="button button-primary" type="button" data-open="opportunity-form" id="tour-new-opp"><?= icon('plus',16) ?>Nueva oportunidad</button><?php endif;?>
</div>
<nav class="filter-tabs" id="tour-filters" aria-label="Filtrar por tipo"><a class="<?= $type===''?'active':'' ?>" href="<?= e(url('opportunities.php')) ?>"><?= icon('list',15) ?>Todas</a><a class="<?= $type==='practica'?'active':'' ?>" href="<?= e(url('opportunities.php?type=practica')) ?>"><?= icon('briefcase',15) ?>Prácticas</a><a class="<?= $type==='empleo'?'active':'' ?>" href="<?= e(url('opportunities.php?type=empleo')) ?>"><?= icon('building',15) ?>Empleo</a><a class="<?= $type==='formacion'?'active':'' ?>" href="<?= e(url('opportunities.php?type=formacion')) ?>"><?= icon('graduation',15) ?>Formación</a></nav>

<?php if($canManage):?>
<dialog id="opportunity-form"><form method="post" class="modal-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create">
 <div class="modal-head"><div><span class="eyebrow">NUEVO REGISTRO</span><h2>Crear oportunidad</h2></div><button type="button" data-close aria-label="Cerrar">×</button></div>
 <div class="form-grid">
  <label class="span-2">Título<input name="title" maxlength="180" required></label>
  <label>Entidad oferente<input name="organization" maxlength="180" value="<?= $isCompany?e($u['program']):'' ?>" <?= $isCompany?'readonly':'' ?> required></label>
  <label>Tipo<select name="type"><option value="practica">Práctica</option><option value="empleo">Empleo</option><option value="formacion">Formación</option></select></label>
  <label>Modalidad<select name="modality"><option value="presencial">Presencial</option><option value="hibrida">Híbrida</option><option value="remota">Remota</option></select></label>
  <label>Ubicación<input name="location"></label><label>Fecha de cierre<input type="date" name="closes_on" required></label>
  <label>Publicación<select name="status"><option value="draft">Guardar borrador</option><option value="review">Enviar a revisión</option><?php if($canModerate):?><option value="published">Publicar verificada</option><?php endif;?></select></label>
  <label class="span-2">Descripción<textarea name="description" required></textarea></label><label class="span-2">Requisitos<textarea name="requirements"></textarea></label>
 </div>
 <div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary">Guardar oportunidad</button></div>
</form></dialog>
<?php endif;?>

<section class="card-grid opportunity-grid" id="tour-opps">
 <?php if(!$items):?><div class="empty panel"><?= $isCompany?'Tu empresa aún no tiene oportunidades registradas.':'No hay oportunidades disponibles con este filtro.' ?></div><?php endif;?>
 <?php foreach($items as $o):?>
 <article class="opportunity-card">
  <div class="card-top"><span class="type-pill"><?= e(status_label($o['type'])) ?></span><span class="status status-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></div>
  <div class="org-avatar tone-ods8"><?= icon($o['type']==='formacion'?'graduation':($o['type']==='empleo'?'building':'briefcase'),22) ?></div><h2><?= e($o['title']) ?></h2><p class="organization"><?= e($o['organization']) ?></p><p><?= e($o['description']) ?></p>
  <div class="meta-row"><span><?= icon('clock',12) ?><?= e(['presencial'=>'Presencial','hibrida'=>'Híbrida','remota'=>'Remota'][$o['modality']]??$o['modality']) ?></span><span><?= icon('map-pin',12) ?><?= e($o['location']?:'Ubicación por confirmar') ?></span></div><div class="deadline"><span>Cierra</span><strong><?= e(date('d/m/Y',strtotime($o['closes_on']))) ?></strong></div>
  <?php if($isStudent&&isset($applied[$o['id']])):?>
   <div class="applied-note"><?= icon('check-circle',16) ?>Ya te postulaste · <?= e(status_label($applied[$o['id']])) ?></div>
  <?php elseif($isStudent):?>
   <button class="button button-primary button-wide" type="button" data-open="apply-<?= $o['id'] ?>"><?= icon('send',15) ?>Postularme</button>
   <dialog id="apply-<?= $o['id'] ?>"><form method="post" class="modal-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="apply"><input type="hidden" name="opportunity_id" value="<?= $o['id'] ?>"><div class="modal-head"><div><span class="eyebrow">POSTULACIÓN</span><h2><?= e($o['title']) ?></h2></div><button type="button" data-close>×</button></div><label>¿Por qué te interesa esta oportunidad?<textarea name="motivation" minlength="20" required></textarea></label><div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary">Enviar postulación</button></div></form></dialog>
  <?php elseif($canModerate||($isCompany&&(int)$o['created_by']===(int)$u['id'])):?>
   <form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= $o['id'] ?>"><select name="status">
    <option value="draft" <?= $o['status']==='draft'?'selected':'' ?>>Borrador</option><option value="review" <?= $o['status']==='review'?'selected':'' ?>>En revisión</option>
    <?php if($canModerate):?><option value="published" <?= $o['status']==='published'?'selected':'' ?>>Publicada</option><?php elseif($o['status']==='published'):?><option value="published" selected disabled>Publicada (verificada)</option><?php endif;?>
    <option value="closed" <?= $o['status']==='closed'?'selected':'' ?>>Cerrada</option></select><button class="button button-small">Actualizar</button>
   </form><small class="applicant-count"><?= (int)$o['applicants'] ?> postulaciones</small>
  <?php endif;?>
 </article>
 <?php endforeach;?>
</section>

<?php if($isStudent||$canManage):?>
<section class="panel" id="tour-apps"><div class="panel-head"><div><span class="eyebrow">SEGUIMIENTO</span><h2><?= $isStudent?'Mis postulaciones':'Postulaciones recibidas' ?></h2></div></div><div class="table-wrap"><table><thead><tr><th>Oportunidad</th><?php if(!$isStudent):?><th>Postulante</th><?php endif;?><th>Fecha</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
 <?php if(!$applications):?><tr><td colspan="5" class="empty">No hay postulaciones registradas.</td></tr><?php endif;?>
 <?php foreach($applications as $a):?><tr><td><strong><?= e($a['title']) ?></strong><?php if($isStudent):?><small><?= e($a['organization']) ?></small><?php endif;?></td><?php if(!$isStudent):?><td><?= e($a['full_name']) ?><small><?= e($a['email']) ?></small></td><?php endif;?><td><?= e(date('d/m/Y',strtotime($a['created_at']))) ?></td><td><span class="status status-<?= e($a['status']) ?>"><?= e(status_label($a['status'])) ?></span></td><?php if($isStudent):?><td><?php if(in_array($a['status'],['submitted','review'],true)):?><form method="post" class="inline-form" data-confirm="¿Retirar tu postulación a esta oportunidad?"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="withdraw"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="button button-small"><?= icon('undo',13) ?>Retirar</button></form><?php else:?><span class="muted-inline">—</span><?php endif;?></td><?php else:?><td><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="application_status"><input type="hidden" name="id" value="<?= $a['id'] ?>"><select name="status"><option value="submitted" <?= $a['status']==='submitted'?'selected':'' ?>>Enviada</option><option value="review" <?= $a['status']==='review'?'selected':'' ?>>En revisión</option><option value="accepted" <?= $a['status']==='accepted'?'selected':'' ?>>Aceptada</option><option value="rejected" <?= $a['status']==='rejected'?'selected':'' ?>>No seleccionada</option></select><button class="button button-small">Guardar</button></form></td><?php endif;?></tr><?php endforeach;?>
</tbody></table></div></section>
<?php endif;?>
<?php app_footer(); ?>
