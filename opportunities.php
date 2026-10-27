<?php
declare(strict_types=1);
/**
 * Convocatorias de pasantía y ofertas laborales.
 * Las empresas con alianza vigente publican; la FET (docente líder o administrador) verifica;
 * el estudiante se postula (con hoja de vida en PDF, obligatoria en las ofertas laborales).
 * Aceptar a un estudiante en una pasantía consume un cupo de la alianza y crea la pasantía.
 */
require __DIR__.'/includes/bootstrap.php';
require_auth();

$u=current_user();
$isStudent=has_role('student');
$isCompany=has_role('company');
$canModerate=has_role('admin','leader');
$canManage=$canModerate||$isCompany;
$types=['practica'=>'Convocatoria de pasantía','empleo'=>'Oferta laboral'];
$alliance=$isCompany?company_alliance((int)$u['id']):null;

if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    $action=$_POST['action']??'';
    try{
        if($action==='create'&&$canManage){
            $title=post_str('title',180);
            $type=allowed_value($_POST['type']??'',array_keys($types),'practica');
            $modality=allowed_value($_POST['modality']??'',['presencial','hibrida','remota'],'presencial');
            $closes=valid_date($_POST['closes_on']??null);
            $organization=$isCompany?trim((string)$u['program']):post_str('organization',180);
            $description=post_str('description');
            $status=(string)($_POST['status']??'draft');
            $allowedStatuses=$isCompany?['draft','review']:['draft','review','published'];
            if(!in_array($status,$allowedStatuses,true)) throw new RuntimeException('El estado seleccionado no está permitido.');
            if($title===''||$organization===''||$description===''||!$closes) throw new RuntimeException('Completa los campos obligatorios.');
            if($closes<date('Y-m-d')) throw new RuntimeException('La fecha de cierre no puede estar en el pasado.');
            if($isCompany&&(!$alliance||!$alliance['open'])) throw new RuntimeException('Tu empresa necesita una alianza vigente con la FET para publicar. Comunícate con el administrador.');
            query(
                'INSERT INTO opportunities(title,organization,type,description,requirements,location,modality,closes_on,status,verified_at,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?)',
                [$title,$organization,$type,$description,post_str('requirements'),post_str('location',150),$modality,$closes,$status,$status==='published'?date('Y-m-d H:i:s'):null,$u['id']]
            );
            $id=(int)db()->lastInsertId();
            audit('create','opportunity',$id,$title);
            if($status==='review') notify_roles(['leader','admin'],'Publicación por verificar',$organization.' envió «'.$title.'» para revisión.','opportunities.php',$u['id']);
            flash('success',$isCompany&&$status==='review'?'Publicación enviada a la FET para su verificación.':'Publicación guardada.');
        }elseif($action==='apply'&&$isStudent){
            $id=(int)$_POST['opportunity_id'];
            $op=row("SELECT id,title,type,created_by FROM opportunities WHERE id=? AND status='published' AND closes_on>=CURDATE()",[$id]);
            if(!$op) throw new RuntimeException('La convocatoria ya no recibe postulaciones.');
            $motivation=post_str('motivation');
            if(mb_strlen($motivation)<20) throw new RuntimeException('Explica tu interés en al menos 20 caracteres.');
            $prev=row('SELECT id,status FROM applications WHERE opportunity_id=? AND user_id=?',[$id,$u['id']]);
            if($prev&&$prev['status']!=='withdrawn') throw new RuntimeException('Ya te postulaste a esta convocatoria.');
            $cv=store_evidence_upload('cv',['pdf']);
            if(!$cv&&$op['type']==='empleo') throw new RuntimeException('Adjunta tu hoja de vida en PDF para aplicar a la oferta laboral.');
            if($prev) query("UPDATE applications SET motivation=?,cv_file=?,cv_name=?,status='submitted' WHERE id=?",[$motivation,$cv['stored']??null,$cv['original']??null,$prev['id']]);
            else query('INSERT INTO applications(opportunity_id,user_id,motivation,cv_file,cv_name) VALUES(?,?,?,?,?)',[$id,$u['id'],$motivation,$cv['stored']??null,$cv['original']??null]);
            audit('apply','opportunity',$id,$cv?'con hoja de vida':null);
            notify((int)$op['created_by'],'Nueva postulación',$u['full_name'].' se postuló a «'.$op['title'].'».','opportunities.php');
            flash('success','Tu postulación quedó registrada.');
        }elseif($action==='status'&&$canManage){
            $id=(int)$_POST['id'];
            $status=(string)$_POST['status'];
            $allowedStatuses=$isCompany?['draft','review','closed']:['draft','review','published','closed'];
            if(!in_array($status,$allowedStatuses,true)) throw new RuntimeException('El estado seleccionado no está permitido.');
            $op=row('SELECT id,title,created_by FROM opportunities WHERE id=?',[$id]);
            if(!$op) throw new RuntimeException('La publicación no existe.');
            if($isCompany&&(int)$op['created_by']!==(int)$u['id']) throw new RuntimeException('Solo puedes modificar publicaciones de tu empresa.');
            query('UPDATE opportunities SET status=?,verified_at=IF(?="published",NOW(),verified_at) WHERE id=?',[$status,$status,$id]);
            audit('status','opportunity',$id,$status);
            if((int)$op['created_by']!==(int)$u['id']) notify((int)$op['created_by'],'Tu publicación cambió de estado','«'.$op['title'].'» ahora está «'.status_label($status).'».','opportunities.php');
            if($isCompany&&$status==='review') notify_roles(['leader','admin'],'Publicación por verificar',$u['program'].' envió «'.$op['title'].'» para revisión.','opportunities.php',$u['id']);
            flash('success','Estado actualizado.');
        }elseif($action==='application_status'&&$canManage){
            $id=(int)$_POST['id'];
            $status=(string)$_POST['status'];
            if(!in_array($status,['submitted','review','accepted','rejected'],true)) throw new RuntimeException('El estado seleccionado no está permitido.');
            $ap=row('SELECT a.id,a.user_id,a.status,o.title,o.type,o.created_by,c.role creator_role FROM applications a JOIN opportunities o ON o.id=a.opportunity_id JOIN users c ON c.id=o.created_by WHERE a.id=?',[$id]);
            if(!$ap) throw new RuntimeException('La postulación no existe.');
            if($isCompany&&(int)$ap['created_by']!==(int)$u['id']) throw new RuntimeException('Solo puedes revisar postulaciones recibidas por tu empresa.');
            if($ap['status']==='withdrawn') throw new RuntimeException('El estudiante retiró esta postulación.');
            // Aceptar a un pasante consume un cupo de la alianza: se valida antes de cambiar el estado.
            if($status==='accepted'&&$ap['status']!=='accepted'&&$ap['type']==='practica'&&$ap['creator_role']==='company') require_internship_slot((int)$ap['created_by']);
            query('UPDATE applications SET status=? WHERE id=?',[$status,$id]);
            audit('status','application',$id,$status);
            if($status==='accepted') create_internship_from_application($id);
            notify((int)$ap['user_id'],'Tu postulación fue actualizada','«'.$ap['title'].'»: '.status_label($status).'.','opportunities.php');
            flash('success',$status==='accepted'&&$ap['type']==='practica'?'Postulación aceptada: se creó la pasantía del estudiante.':'Postulación actualizada.');
        }elseif($action==='withdraw'&&$isStudent){
            $id=(int)$_POST['id'];
            $ap=row("SELECT id FROM applications WHERE id=? AND user_id=? AND status IN ('submitted','review')",[$id,$u['id']]);
            if(!$ap) throw new RuntimeException('Esta postulación ya no se puede retirar.');
            query("UPDATE applications SET status='withdrawn' WHERE id=?",[$id]);
            audit('status','application',$id,'withdrawn');
            flash('success','Retiraste tu postulación.');
        }
    }catch(PDOException $e){
        flash('error',$e->getCode()==='23000'?'Ya existe una postulación para esta convocatoria.':'No fue posible guardar el cambio.');
    }catch(Throwable $e){
        flash('error',$e->getMessage());
    }
    redirect('opportunities.php'.(isset($_GET['type'])?'?type='.urlencode((string)$_GET['type']):''));
}

$type=$_GET['type']??'';
$params=[];
if($canModerate){
    $where="o.type IN ('practica','empleo')";
}elseif($isCompany){
    $where='o.created_by=?';
    $params[]=$u['id'];
}else{
    $where="o.type IN ('practica','empleo') AND o.status='published' AND o.closes_on>=CURDATE()";
}
if(isset($types[$type])){
    $where.=' AND o.type=?';
    $params[]=$type;
}
$items=rows("SELECT o.*,u.full_name creator,(SELECT COUNT(*) FROM applications a WHERE a.opportunity_id=o.id AND a.status<>'withdrawn') applicants FROM opportunities o JOIN users u ON u.id=o.created_by WHERE $where ORDER BY FIELD(o.status,'review','published','draft','closed'),o.closes_on",$params);

$select='SELECT a.*,o.title,o.type,o.organization,u.full_name,u.email,u.program FROM applications a JOIN opportunities o ON o.id=a.opportunity_id JOIN users u ON u.id=a.user_id';
if($canModerate) $applications=rows("$select ORDER BY a.created_at DESC LIMIT 200");
elseif($isCompany) $applications=rows("$select WHERE o.created_by=? ORDER BY a.created_at DESC",[$u['id']]);
elseif($isStudent) $applications=rows("$select WHERE a.user_id=? ORDER BY a.created_at DESC",[$u['id']]);
else $applications=[];

$applied=$isStudent?array_column(rows("SELECT opportunity_id,status FROM applications WHERE user_id=? AND status<>'withdrawn'",[$u['id']]),'status','opportunity_id'):[];
$typeIcon=fn(string $t)=>$t==='empleo'?'building':'briefcase';
app_header('Convocatorias y empleo','PASANTÍAS Y OPORTUNIDADES LABORALES');
?>
<div class="page-actions">
 <div><p><?= match(true){
    $isCompany=>'Publica convocatorias de pasantía (usan los cupos de tu alianza) y, si quieres, ofertas laborales. La FET verifica cada publicación antes de mostrarla a los estudiantes.',
    $isStudent=>'Postúlate a una pasantía o aplica a una oferta laboral con tu hoja de vida en PDF.',
    default=>'Verifica las publicaciones de las empresas aliadas y sigue las postulaciones de los estudiantes.',
 } ?></p></div>
 <?php if($canManage&&(!$isCompany||($alliance&&$alliance['open']))):?><button class="button button-primary" type="button" data-open="opportunity-form" id="tour-new-opp"><?= icon('plus',16) ?>Nueva publicación</button><?php endif;?>
</div>

<?php if($isCompany):?>
 <?php if(!$alliance):?><div class="alert alert-error" role="status"><?= icon('alert',18) ?><span>Tu empresa aún no tiene una alianza registrada con la FET. Sin alianza no puedes publicar convocatorias. Comunícate con el administrador.</span></div>
 <?php else:?><section class="alliance-strip" id="tour-alliance"><span class="kpi-icon tone-ods17"><?= icon('handshake',20) ?></span><div><strong>Alianza <?= e($alliance['agreement_code']?:$alliance['name']) ?> · <?= e($alliance['open']?'Vigente':($alliance['status']!=='active'?status_label($alliance['status']):($alliance['ends_on']&&$alliance['ends_on']<date('Y-m-d')?'Vencida':'Aún no inicia'))) ?></strong><small>Cupos de pasantía en uso: <?= (int)$alliance['used'] ?> de <?= (int)$alliance['slots'] ?> · disponibles: <?= (int)$alliance['available'] ?><?= $alliance['ends_on']?' · vigente hasta '.e(date('d/m/Y',strtotime($alliance['ends_on']))):'' ?></small></div><div class="slots-bar" aria-hidden="true"><i style="width:<?= $alliance['slots']?min(100,(int)round($alliance['used']/$alliance['slots']*100)):100 ?>%"></i></div></section><?php endif;?>
<?php endif;?>

<nav class="filter-tabs" id="tour-filters" aria-label="Filtrar por tipo"><a class="<?= $type===''?'active':'' ?>" href="<?= e(url('opportunities.php')) ?>"><?= icon('list',15) ?>Todas</a><a class="<?= $type==='practica'?'active':'' ?>" href="<?= e(url('opportunities.php?type=practica')) ?>"><?= icon('briefcase',15) ?>Pasantías</a><a class="<?= $type==='empleo'?'active':'' ?>" href="<?= e(url('opportunities.php?type=empleo')) ?>"><?= icon('building',15) ?>Ofertas laborales</a></nav>

<?php if($canManage):?>
<dialog id="opportunity-form"><form method="post" class="modal-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create">
 <div class="modal-head"><div><span class="eyebrow">NUEVA PUBLICACIÓN</span><h2>Convocatoria u oferta</h2></div><button type="button" data-close aria-label="Cerrar">×</button></div>
 <div class="form-grid">
  <label class="span-2">Título<input name="title" maxlength="180" required></label>
  <label>Tipo<select name="type"><option value="practica">Convocatoria de pasantía</option><option value="empleo">Oferta laboral (opcional)</option></select></label>
  <label>Empresa<input name="organization" maxlength="180" value="<?= $isCompany?e($u['program']):'' ?>" <?= $isCompany?'readonly':'' ?> required></label>
  <label>Modalidad<select name="modality"><option value="presencial">Presencial</option><option value="hibrida">Híbrida</option><option value="remota">Remota</option></select></label>
  <label>Ubicación<input name="location" maxlength="150"></label>
  <label>Fecha de cierre<input type="date" name="closes_on" min="<?= e(date('Y-m-d')) ?>" required></label>
  <label>Publicación<select name="status"><option value="review">Enviar a verificación de la FET</option><option value="draft">Guardar borrador</option><?php if($canModerate):?><option value="published">Publicar verificada</option><?php endif;?></select></label>
  <label class="span-2">Descripción y funciones<textarea name="description" required></textarea></label><label class="span-2">Requisitos<textarea name="requirements"></textarea></label>
 </div>
 <?php if($isCompany):?><p class="hint"><?= icon('alert',14) ?>Cada estudiante que aceptes en una pasantía ocupa un cupo de tu alianza. Las ofertas laborales no usan cupos.</p><?php endif;?>
 <div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary">Guardar publicación</button></div>
</form></dialog>
<?php endif;?>

<section class="card-grid opportunity-grid" id="tour-opps">
 <?php if(!$items):?><div class="empty panel"><?= $isCompany?'Tu empresa aún no tiene publicaciones.':'No hay publicaciones con este filtro.' ?></div><?php endif;?>
 <?php foreach($items as $o): $oid=(int)$o['id'];?>
 <article class="opportunity-card">
  <div class="card-top"><span class="type-pill"><?= e($types[$o['type']]??status_label($o['type'])) ?></span><span class="status status-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></div>
  <div class="org-avatar tone-ods8"><?= icon($typeIcon($o['type']),22) ?></div><h2><?= e($o['title']) ?></h2><p class="organization"><?= e($o['organization']) ?></p><p><?= e($o['description']) ?></p>
  <?php if($o['requirements']):?><details class="add-inline"><summary>Requisitos</summary><p class="muted-inline"><?= nl2br(e($o['requirements'])) ?></p></details><?php endif;?>
  <div class="meta-row"><span><?= icon('clock',12) ?><?= e(['presencial'=>'Presencial','hibrida'=>'Híbrida','remota'=>'Remota'][$o['modality']]??$o['modality']) ?></span><span><?= icon('map-pin',12) ?><?= e($o['location']?:'Ubicación por confirmar') ?></span></div><div class="deadline"><span>Cierra</span><strong><?= e(date('d/m/Y',strtotime($o['closes_on']))) ?></strong></div>
  <?php if($isStudent&&isset($applied[$oid])):?>
   <div class="applied-note"><?= icon('check-circle',16) ?>Ya te postulaste · <?= e(status_label($applied[$oid])) ?></div>
  <?php elseif($isStudent):?>
   <button class="button button-primary button-wide" type="button" data-open="apply-<?= $oid ?>"><?= icon('send',15) ?><?= $o['type']==='empleo'?'Aplicar con mi hoja de vida':'Postularme' ?></button>
   <dialog id="apply-<?= $oid ?>"><form method="post" enctype="multipart/form-data" class="modal-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="apply"><input type="hidden" name="opportunity_id" value="<?= $oid ?>">
    <div class="modal-head"><div><span class="eyebrow"><?= e(mb_strtoupper($types[$o['type']]??'')) ?></span><h2><?= e($o['title']) ?></h2></div><button type="button" data-close aria-label="Cerrar">×</button></div>
    <div class="form-grid"><label class="span-2">¿Por qué te interesa?<textarea name="motivation" minlength="20" required></textarea></label>
    <label class="span-2">Hoja de vida en PDF (máx. 5 MB)<?= $o['type']==='empleo'?' · obligatoria':' · opcional' ?><input type="file" name="cv" accept=".pdf,application/pdf" <?= $o['type']==='empleo'?'required':'' ?>></label></div>
    <div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary">Enviar postulación</button></div></form></dialog>
  <?php elseif($canModerate||($isCompany&&(int)$o['created_by']===(int)$u['id'])):?>
   <form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= $oid ?>"><select name="status" aria-label="Estado de <?= e($o['title']) ?>">
    <option value="draft" <?= $o['status']==='draft'?'selected':'' ?>>Borrador</option><option value="review" <?= $o['status']==='review'?'selected':'' ?>>En revisión</option>
    <?php if($canModerate):?><option value="published" <?= $o['status']==='published'?'selected':'' ?>>Publicada (verificada)</option><?php elseif($o['status']==='published'):?><option value="published" selected disabled>Publicada (verificada)</option><?php endif;?>
    <option value="closed" <?= $o['status']==='closed'?'selected':'' ?>>Cerrada</option></select><button class="button button-small"><?= $canModerate&&$o['status']==='review'?'Verificar':'Actualizar' ?></button>
   </form><small class="applicant-count"><?= (int)$o['applicants'] ?> postulaciones<?= $canModerate?' · '.e($o['creator']):'' ?></small>
  <?php endif;?>
 </article>
 <?php endforeach;?>
</section>

<?php if($isStudent||$canManage):?>
<section class="panel" id="tour-apps"><div class="panel-head"><div><span class="eyebrow">SEGUIMIENTO</span><h2><?= $isStudent?'Mis postulaciones':'Postulaciones recibidas' ?></h2></div></div><div class="table-wrap"><table><thead><tr><th>Publicación</th><?php if(!$isStudent):?><th>Postulante</th><?php endif;?><th>Hoja de vida</th><th>Fecha</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
 <?php if(!$applications):?><tr><td colspan="6" class="empty">No hay postulaciones registradas.</td></tr><?php endif;?>
 <?php foreach($applications as $a): $aid=(int)$a['id'];?><tr>
  <td><strong><?= e($a['title']) ?></strong><small><?= e(($types[$a['type']]??'').' · '.$a['organization']) ?></small></td>
  <?php if(!$isStudent):?><td><?= e($a['full_name']) ?><small><?= e($a['email']) ?><?= $a['program']?' · '.e($a['program']):'' ?></small><details class="add-inline"><summary>Motivación</summary><p class="muted-inline"><?= nl2br(e($a['motivation'])) ?></p></details></td><?php endif;?>
  <td><?php if($a['cv_file']):?><a class="file-link" href="<?= e(url('evidence.php?type=cv&id='.$aid)) ?>"><?= icon('paperclip',12) ?><?= e($a['cv_name']?:'Hoja de vida.pdf') ?></a><?php else:?><span class="muted-inline">No adjuntó</span><?php endif;?></td>
  <td><?= e(date('d/m/Y',strtotime($a['created_at']))) ?></td><td><span class="status status-<?= e($a['status']) ?>"><?= e(status_label($a['status'])) ?></span></td>
  <?php if($isStudent):?><td><?php if(in_array($a['status'],['submitted','review'],true)):?><form method="post" class="inline-form" data-confirm="¿Retirar tu postulación?"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="withdraw"><input type="hidden" name="id" value="<?= $aid ?>"><button class="button button-small"><?= icon('undo',13) ?>Retirar</button></form><?php else:?><span class="muted-inline">—</span><?php endif;?></td>
  <?php elseif($a['status']==='withdrawn'||($a['status']==='accepted'&&$a['type']==='practica')):?><td><span class="muted-inline"><?= $a['status']==='withdrawn'?'Retirada por el estudiante':'Pasantía creada' ?></span></td>
  <?php else:?><td><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="application_status"><input type="hidden" name="id" value="<?= $aid ?>"><select name="status" aria-label="Estado de la postulación de <?= e($a['full_name']) ?>"><option value="submitted" <?= $a['status']==='submitted'?'selected':'' ?>>Enviada</option><option value="review" <?= $a['status']==='review'?'selected':'' ?>>En revisión</option><option value="accepted" <?= $a['status']==='accepted'?'selected':'' ?>>Aceptada</option><option value="rejected" <?= $a['status']==='rejected'?'selected':'' ?>>No seleccionada</option></select><button class="button button-small">Guardar</button></form></td><?php endif;?>
 </tr><?php endforeach;?>
</tbody></table></div></section>
<?php endif;?>
<?php app_footer(); ?>
