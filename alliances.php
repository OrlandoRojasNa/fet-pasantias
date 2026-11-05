<?php
declare(strict_types=1);
/**
 * Alianzas empresariales: el administrador registra la empresa aliada, su cuenta de acceso,
 * el convenio y los cupos de pasantía. Ampliar los cupos abre más plazas para pasantes.
 * Los docentes líderes consultan las alianzas y su ocupación.
 */
require __DIR__.'/includes/bootstrap.php';
require_role('admin','leader');
$u=current_user();
$isAdmin=has_role('admin');

if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf(); $a=$_POST['action']??'';
    try{
        if(!$isAdmin) throw new RuntimeException('Solo el administrador puede registrar o modificar alianzas.');
        $slots=(int)($_POST['slots']??0);
        $starts=valid_date($_POST['starts_on']??null); $ends=valid_date($_POST['ends_on']??null);
        if($slots<1||$slots>500) throw new RuntimeException('Los cupos deben estar entre 1 y 500.');
        if($starts&&$ends&&$ends<$starts) throw new RuntimeException('La fecha de fin no puede ser anterior a la de inicio.');
        $contactEmail=mb_strtolower(post_str('contact_email',150));
        if($contactEmail!==''&&!filter_var($contactEmail,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('El correo del contacto no es válido.');
        $fields=[post_str('sector',120)?:null,post_str('contact_name',150)?:null,$contactEmail?:null,post_str('contact_phone',40)?:null,post_str('agreement_code',60)?:null,$slots,$starts,$ends,post_str('notes')?:null];

        if($a==='create'){
            $name=post_str('name',180);
            if($name==='') throw new RuntimeException('Escribe la razón social de la empresa.');
            $companyId=(int)($_POST['company_id']??0);
            db()->beginTransaction();
            if($companyId){
                if(!row("SELECT id FROM users WHERE id=? AND role='company'",[$companyId])) throw new RuntimeException('La cuenta de empresa elegida no es válida.');
            }else{
                // Cuenta nueva de la empresa: inicia sesión con el NIT o el correo y la contraseña inicial.
                $email=mb_strtolower(post_str('email',150)); $nit=post_str('nit',30); $pw=(string)($_POST['password']??'');
                if($nit===''||!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Escribe el NIT y un correo válido para la cuenta de la empresa.');
                if(mb_strlen($pw)<8) throw new RuntimeException('La contraseña inicial debe tener al menos 8 caracteres.');
                if(row('SELECT id FROM users WHERE identification=? OR email=?',[$nit,$email])) throw new RuntimeException('Ya existe una cuenta con ese NIT o correo. Elígela en «Cuenta de la empresa».');
                query("INSERT INTO users(identification,full_name,email,password_hash,role,program,status) VALUES(?,?,?,?,'company',?,'active')",[$nit,post_str('contact_name',150)?:$name,$email,password_hash($pw,PASSWORD_DEFAULT),$name]);
                $companyId=(int)db()->lastInsertId();
                audit('create','user',$companyId,$email);
            }
            if(row('SELECT id FROM alliances WHERE company_id=?',[$companyId])) throw new RuntimeException('Esa empresa ya tiene una alianza. Edítala para ampliar los cupos o la vigencia.');
            query('INSERT INTO alliances(company_id,name,sector,contact_name,contact_email,contact_phone,agreement_code,slots,starts_on,ends_on,notes,status,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)',[$companyId,$name,...$fields,'active',$u['id']]);
            $id=(int)db()->lastInsertId();
            db()->commit();
            audit('create','alliance',$id,$name.' · '.$slots.' cupos');
            notify($companyId,'Alianza registrada con la FET','Tu empresa tiene '.$slots.' cupo(s) de pasantía. Ya puedes publicar convocatorias.','opportunities.php');
            notify_roles(['leader'],'Nueva alianza empresarial',$name.' abrió '.$slots.' cupo(s) de pasantía.','alliances.php');
            flash('success','Alianza registrada con '.$slots.' cupo(s).');
        }elseif($a==='update'){
            $id=(int)($_POST['id']??0);
            $al=row('SELECT * FROM alliances WHERE id=?',[$id]);
            if(!$al) throw new RuntimeException('La alianza no existe.');
            $used=(int)scalar("SELECT COUNT(*) FROM internships WHERE company_id=? AND status='active'",[$al['company_id']]);
            if($slots<$used) throw new RuntimeException('La empresa tiene '.$used.' pasante(s) en curso: los cupos no pueden ser menos que eso.');
            $status=allowed_value($_POST['status']??'',['active','suspended','expired'],$al['status']);
            query('UPDATE alliances SET sector=?,contact_name=?,contact_email=?,contact_phone=?,agreement_code=?,slots=?,starts_on=?,ends_on=?,notes=?,status=? WHERE id=?',[...$fields,$status,$id]);
            $changes=[];
            if($slots!==(int)$al['slots']) $changes[]='cupos '.$al['slots'].' → '.$slots;
            if($status!==$al['status']) $changes[]='estado '.status_label($al['status']).' → '.status_label($status);
            if($ends!==$al['ends_on']) $changes[]='vigencia hasta '.($ends?date('d/m/Y',strtotime($ends)):'sin fecha');
            audit('update','alliance',$id,$changes?implode(' · ',$changes):'datos de contacto');
            if($slots>(int)$al['slots']){
                notify((int)$al['company_id'],'Se ampliaron tus cupos de pasantía','Tu alianza con la FET ahora tiene '.$slots.' cupo(s).','opportunities.php');
                notify_roles(['leader'],'Más cupos de pasantía',$al['name'].' amplió sus cupos a '.$slots.'.','alliances.php');
            }elseif($changes) notify((int)$al['company_id'],'Tu alianza con la FET fue actualizada',implode(' · ',$changes),'opportunities.php');
            flash('success','Alianza actualizada'.($changes?': '.implode(' · ',$changes):'').'.');
        }
    }catch(Throwable $e){
        if(db()->inTransaction()) db()->rollBack();
        flash('error',$e instanceof PDOException?'No fue posible guardar la alianza (¿NIT o correo repetido?).':$e->getMessage());
    }
    redirect('alliances.php');
}

$alliances=rows("SELECT a.*,u.email login_email,u.identification nit,u.status account_status,
    (SELECT COUNT(*) FROM internships i WHERE i.company_id=a.company_id AND i.status='active') used,
    (SELECT COUNT(*) FROM internships i WHERE i.company_id=a.company_id AND i.status='completed') finished,
    (SELECT COUNT(*) FROM opportunities o WHERE o.created_by=a.company_id AND o.status='published' AND o.closes_on>=CURDATE()) open_calls
    FROM alliances a JOIN users u ON u.id=a.company_id ORDER BY FIELD(a.status,'active','suspended','expired'),a.name");
$today=date('Y-m-d');
$isOpen=fn(array $a)=>$a['status']==='active'&&(!$a['starts_on']||$a['starts_on']<=$today)&&(!$a['ends_on']||$a['ends_on']>=$today);
$totals=['alliances'=>0,'slots'=>0,'used'=>0];
foreach($alliances as $a){ if($isOpen($a)){ $totals['alliances']++; $totals['slots']+=(int)$a['slots']; $totals['used']+=(int)$a['used']; } }
$freeCompanies=$isAdmin?rows("SELECT id,full_name,program,email FROM users WHERE role='company' AND id NOT IN (SELECT company_id FROM alliances) ORDER BY program,full_name"):[];
app_header('Alianzas empresariales','CUPOS DE PASANTÍA');
?>
<div class="page-actions"><div><p><?= $isAdmin?'Registra las empresas aliadas y sus cupos. Cada cupo permite tener un pasante en curso; amplía los cupos para abrir más plazas.':'Empresas aliadas de la FET y ocupación de sus cupos de pasantía.' ?></p></div><?php if($isAdmin):?><button class="button button-primary" type="button" data-open="alliance-form" id="tour-new-alliance"><?= icon('plus',16) ?>Nueva alianza</button><?php endif;?></div>

<section class="kpi-strip four" id="tour-alliance-kpis">
 <article><span class="kpi-icon tone-ods17"><?= icon('handshake',20) ?></span><div><strong><?= $totals['alliances'] ?></strong><span>Alianzas vigentes</span></div></article>
 <article><span class="kpi-icon tone-fet"><?= icon('briefcase',20) ?></span><div><strong><?= $totals['slots'] ?></strong><span>Cupos totales</span></div></article>
 <article><span class="kpi-icon tone-gold"><?= icon('users',20) ?></span><div><strong><?= $totals['used'] ?></strong><span>Pasantes en curso</span></div></article>
 <article><span class="kpi-icon tone-teal"><?= icon('check-circle',20) ?></span><div><strong><?= max(0,$totals['slots']-$totals['used']) ?></strong><span>Cupos disponibles</span></div></article>
</section>

<section class="panel" id="tour-alliances"><div class="panel-head"><div><span class="eyebrow">CONVENIOS</span><h2>Empresas aliadas</h2></div></div>
<div class="table-wrap"><table><thead><tr><th>Empresa</th><th>Convenio</th><th>Vigencia</th><th>Cupos</th><th>Convocatorias abiertas</th><th>Estado</th><?php if($isAdmin):?><th>Acción</th><?php endif;?></tr></thead><tbody>
<?php if(!$alliances):?><tr><td colspan="7" class="empty">Aún no hay alianzas registradas.</td></tr><?php endif;?>
<?php foreach($alliances as $a): $aid=(int)$a['id']; $open=$isOpen($a); $pct=$a['slots']?min(100,(int)round($a['used']/$a['slots']*100)):100;?>
 <tr>
  <td><strong><?= e($a['name']) ?></strong><small><?= e($a['sector']?:'Sector sin registrar') ?> · NIT <?= e($a['nit']) ?></small><small><?= e($a['contact_name']?:'') ?><?= $a['contact_email']?' · '.e($a['contact_email']):'' ?><?= $a['contact_phone']?' · '.e($a['contact_phone']):'' ?></small><small>Acceso: <?= e($a['login_email']) ?><?= $a['account_status']!=='active'?' (cuenta inactiva)':'' ?></small></td>
  <td><?= e($a['agreement_code']?:'—') ?></td>
  <td><?= $a['starts_on']?e(date('d/m/Y',strtotime($a['starts_on']))):'—' ?> a <?= $a['ends_on']?e(date('d/m/Y',strtotime($a['ends_on']))):'sin fecha' ?></td>
  <td><strong><?= (int)$a['used'] ?> / <?= (int)$a['slots'] ?></strong><div class="slots-bar"><i style="width:<?= $pct ?>%"></i></div><small><?= max(0,(int)$a['slots']-(int)$a['used']) ?> disponibles · <?= (int)$a['finished'] ?> finalizadas</small></td>
  <td><?= (int)$a['open_calls'] ?></td>
  <td><span class="status status-<?= $open?'active':($a['status']==='active'?'expired':e($a['status'])) ?>"><?= e($open?'Vigente':($a['status']==='active'?($a['ends_on']&&$a['ends_on']<$today?'Vencida':'Aún no inicia'):status_label($a['status']))) ?></span></td>
  <?php if($isAdmin):?><td><button class="button button-small" type="button" data-open="edit-<?= $aid ?>"><?= icon('edit',13) ?>Editar o ampliar</button>
   <dialog id="edit-<?= $aid ?>"><form method="post" class="modal-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= $aid ?>">
    <div class="modal-head"><div><span class="eyebrow">ALIANZA</span><h2><?= e($a['name']) ?></h2></div><button type="button" data-close aria-label="Cerrar">×</button></div>
    <div class="form-grid">
     <label>Cupos de pasantía<input type="number" name="slots" min="<?= max(1,(int)$a['used']) ?>" max="500" value="<?= (int)$a['slots'] ?>" required></label>
     <label>Estado<select name="status"><?php foreach(['active'=>'Activa','suspended'=>'Suspendida','expired'=>'Vencida'] as $k=>$l):?><option value="<?= $k ?>" <?= $a['status']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach;?></select></label>
     <label>Inicio<input type="date" name="starts_on" value="<?= e($a['starts_on']) ?>"></label><label>Fin<input type="date" name="ends_on" value="<?= e($a['ends_on']) ?>"></label>
     <label>Código del convenio<input name="agreement_code" maxlength="60" value="<?= e($a['agreement_code']) ?>"></label><label>Sector<input name="sector" maxlength="120" value="<?= e($a['sector']) ?>"></label>
     <label>Contacto<input name="contact_name" maxlength="150" value="<?= e($a['contact_name']) ?>"></label><label>Correo del contacto<input type="email" name="contact_email" maxlength="150" value="<?= e($a['contact_email']) ?>"></label>
     <label>Teléfono<input name="contact_phone" maxlength="40" value="<?= e($a['contact_phone']) ?>"></label>
     <label class="span-2">Notas<textarea name="notes"><?= e($a['notes']) ?></textarea></label>
    </div>
    <div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary">Guardar cambios</button></div>
   </form></dialog></td><?php endif;?>
 </tr>
<?php endforeach;?>
</tbody></table></div></section>

<?php if($isAdmin):?>
<dialog id="alliance-form"><form method="post" class="modal-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create">
 <div class="modal-head"><div><span class="eyebrow">NUEVA ALIANZA</span><h2>Registrar empresa aliada</h2></div><button type="button" data-close aria-label="Cerrar">×</button></div>
 <div class="form-grid">
  <label class="span-2">Razón social<input name="name" maxlength="180" required></label>
  <label>Sector<input name="sector" maxlength="120"></label><label>Código del convenio<input name="agreement_code" maxlength="60" placeholder="Ej. CONV-2026-001"></label>
  <label>Cupos de pasantía<input type="number" name="slots" min="1" max="500" value="2" required></label><label>Inicio<input type="date" name="starts_on" value="<?= e($today) ?>"></label>
  <label>Fin<input type="date" name="ends_on"></label><label>Contacto en la empresa<input name="contact_name" maxlength="150"></label>
  <label>Correo del contacto<input type="email" name="contact_email" maxlength="150"></label><label>Teléfono<input name="contact_phone" maxlength="40"></label>
  <label class="span-2">Cuenta de la empresa<select name="company_id"><option value="">Crear una cuenta nueva con los datos de abajo</option><?php foreach($freeCompanies as $c):?><option value="<?= (int)$c['id'] ?>"><?= e(($c['program']?:$c['full_name']).' · '.$c['email']) ?></option><?php endforeach;?></select></label>
  <label>NIT (usuario de acceso)<input name="nit" maxlength="30"></label><label>Correo de acceso<input type="email" name="email" maxlength="150"></label>
  <label class="span-2">Contraseña inicial (mín. 8 caracteres)<input type="password" name="password" minlength="8" autocomplete="new-password"></label>
  <label class="span-2">Notas<textarea name="notes"></textarea></label>
 </div>
 <p class="hint"><?= icon('alert',14) ?>Si eliges una cuenta existente, no se usan el NIT, el correo ni la contraseña.</p>
 <div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary">Registrar alianza</button></div>
</form></dialog>
<?php endif;?>
<?php app_footer(); ?>
