<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require_role('admin');
$me=current_user();
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf(); $a=$_POST['action']??'';
    try{
        $id=(int)($_POST['id']??0);
        if($a==='create'){
            $email=mb_strtolower(post_str('email',150));
            if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Escribe un correo válido.');
            if(post_str('full_name')===''||post_str('identification')==='') throw new RuntimeException('Escribe el nombre y la identificación.');
            if(mb_strlen((string)($_POST['password']??''))<8) throw new RuntimeException('La contraseña inicial debe tener al menos 8 caracteres.');
            query('INSERT INTO users(identification,full_name,email,password_hash,role,program,status) VALUES(?,?,?,?,?,?,?)',[post_str('identification',30),post_str('full_name',150),$email,password_hash((string)$_POST['password'],PASSWORD_DEFAULT),allowed_value($_POST['role']??'',array_keys(ROLE_LABELS),'student'),post_str('program',150)?:null,allowed_value($_POST['status']??'',['active','inactive'],'active')]);
            $nid=(int)db()->lastInsertId(); audit('create','user',$nid,$email);
            notify($nid,'Bienvenido a Pasantías FET','Pulsa el botón de ayuda (?) para recorrer la plataforma paso a paso.','guide.php');
            flash('success','Usuario creado.');
        }elseif($id===$me['id']){
            throw new RuntimeException('Para proteger el acceso, no puedes modificar tu propia cuenta desde aquí.');
        }elseif($a==='status'){
            $st=allowed_value($_POST['status']??'',['active','inactive'],'active');
            query('UPDATE users SET status=? WHERE id=?',[$st,$id]); if($st==='inactive') query('DELETE FROM remember_tokens WHERE user_id=?',[$id]);
            audit('status','user',$id,$st); flash('success','Estado actualizado.');
        }elseif($a==='role'){
            $role=allowed_value($_POST['role']??'',array_keys(ROLE_LABELS),'student');
            query('UPDATE users SET role=? WHERE id=?',[$role,$id]); audit('role','user',$id,$role); flash('success','Perfil actualizado a «'.role_label($role).'».');
        }elseif($a==='unlock'){
            // Desbloquea una cuenta bloqueada por intentos fallidos sin cambiar su contraseña.
            $u=row('SELECT full_name FROM users WHERE id=?',[$id]);
            if(!$u) throw new RuntimeException('La cuenta no existe.');
            query('UPDATE users SET failed_attempts=0,locked_until=NULL WHERE id=?',[$id]);
            audit('unlock','user',$id); notify($id,'Tu cuenta fue desbloqueada','El administrador desbloqueó tu cuenta. Ya puedes iniciar sesión con tu contraseña.',null);
            flash('success','Cuenta de '.$u['full_name'].' desbloqueada. Ya puede iniciar sesión.');
        }elseif($a==='password'){
            $pw=(string)($_POST['password']??'');
            if(mb_strlen($pw)<8) throw new RuntimeException('La nueva contraseña debe tener al menos 8 caracteres.');
            query('UPDATE users SET password_hash=?,failed_attempts=0,locked_until=NULL WHERE id=?',[password_hash($pw,PASSWORD_DEFAULT),$id]);
            query('DELETE FROM remember_tokens WHERE user_id=?',[$id]);
            audit('password','user',$id); notify($id,'Tu contraseña fue restablecida','El administrador asignó una contraseña nueva a tu cuenta.',null);
            flash('success','Contraseña restablecida y cuenta desbloqueada.');
        }
    }catch(PDOException){ flash('error','La identificación o el correo ya están registrados.'); }
    catch(Throwable $e){ flash('error',$e->getMessage()); }
    redirect('users.php');
}
$users=rows('SELECT * FROM users ORDER BY FIELD(role,"admin","leader","company","student"),full_name');
$counts=array_count_values(array_column($users,'role'));
$isLocked=fn(array $p)=>$p['locked_until']&&strtotime($p['locked_until'])>time();
$lockedCount=count(array_filter($users,$isLocked));
$roleIcon=['admin'=>['key','slate'],'leader'=>['graduation','ods9'],'student'=>['users','fet'],'company'=>['briefcase','ods8']];
app_header('Usuarios y permisos','ADMINISTRACIÓN TÉCNICA');
?>
<div class="page-actions"><p>Administra cuentas y perfiles. Las empresas aliadas se crean desde «Alianzas» junto con sus cupos. Los permisos se validan en el servidor en cada solicitud.<?php if($lockedCount):?> <strong><?= $lockedCount ?> cuenta(s) bloqueada(s) por intentos fallidos</strong>: usa «Desbloquear».<?php endif;?></p><div class="button-row"><form method="post" action="<?= e(url('backup.php')) ?>" id="tour-backup"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="button button-ghost"><?= icon('database',16) ?>Descargar respaldo SQL</button></form><button class="button button-primary" type="button" data-open="user-form" id="tour-new-user"><?= icon('user-plus',16) ?>Nuevo usuario</button></div></div>
<section class="kpi-strip four"><?php foreach(ROLE_LABELS as $k=>$label):?><article><span class="kpi-icon tone-<?= $roleIcon[$k][1] ?>"><?= icon($roleIcon[$k][0],20) ?></span><div><strong><?= (int)($counts[$k]??0) ?></strong><span><?= e($label) ?></span></div></article><?php endforeach;?></section>
<dialog id="user-form"><form method="post" class="modal-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create"><div class="modal-head"><div><span class="eyebrow">CUENTA NUEVA</span><h2>Crear usuario</h2></div><button type="button" data-close aria-label="Cerrar">×</button></div><div class="form-grid"><label>Nombre completo<input name="full_name" maxlength="150" required></label><label>Identificación<input name="identification" maxlength="30" required></label><label>Correo<input type="email" name="email" maxlength="150" required></label><label>Contraseña inicial<input type="password" name="password" minlength="8" autocomplete="new-password" required></label><label>Perfil<select name="role"><?php foreach(ROLE_LABELS as $key=>$label):?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach;?></select></label><label>Programa, dependencia o empresa<input name="program" maxlength="150"></label><label>Estado<select name="status"><option value="active">Activo</option><option value="inactive">Inactivo</option></select></label></div><div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary">Crear cuenta</button></div></form></dialog>
<section class="panel" id="tour-users-table"><div class="table-wrap"><table><thead><tr><th>Usuario</th><th>Identificación</th><th>Perfil</th><th>Programa / dependencia</th><th>Último acceso</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($users as $p): $self=(int)$p['id']===$me['id'];?><tr>
 <td><div class="user-cell"><span class="list-icon tone-<?= $roleIcon[$p['role']][1] ?>"><?= icon($roleIcon[$p['role']][0],16) ?></span><span><strong><?= e($p['full_name']) ?></strong><small><?= e($p['email']) ?></small></span></div></td><td><?= e($p['identification']) ?></td>
 <td><?php if($self):?><?= e(role_label($p['role'])) ?><?php else:?><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><select name="role" aria-label="Perfil de <?= e($p['full_name']) ?>"><?php foreach(ROLE_LABELS as $k=>$l):?><option value="<?= e($k) ?>" <?= $p['role']===$k?'selected':'' ?>><?= e($l) ?></option><?php endforeach;?></select><button class="button button-small">Guardar</button></form><?php endif;?></td>
 <td><?= e($p['program']?:'—') ?></td><td><?= $p['last_login_at']?e(date('d/m/Y H:i',strtotime($p['last_login_at']))):'Sin ingreso' ?></td>
 <td><span class="status status-<?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span><?php if($isLocked($p)):?> <span class="status status-overdue">Bloqueada</span><small>Hasta las <?= e(date('H:i',strtotime($p['locked_until']))) ?> por intentos fallidos</small><?php elseif((int)$p['failed_attempts']>0):?><small><?= (int)$p['failed_attempts'] ?> intento(s) fallido(s)</small><?php endif;?></td>
 <td><?php if($self):?><span class="muted-inline">Cuenta actual</span><?php else:?><div class="button-row" style="flex-wrap:wrap;gap:6px"><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="status" value="<?= $p['status']==='active'?'inactive':'active' ?>"><button class="button button-small"><?= $p['status']==='active'?'Desactivar':'Activar' ?></button></form><?php if($isLocked($p)||(int)$p['failed_attempts']>0):?><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="unlock"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="button button-small button-primary"><?= icon('undo',13) ?>Desbloquear</button></form><?php endif;?><button class="button button-small" type="button" data-open="pw-<?= (int)$p['id'] ?>"><?= icon('key',13) ?>Contraseña</button></div>
  <dialog id="pw-<?= (int)$p['id'] ?>"><form method="post" class="modal-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="password"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><div class="modal-head"><div><span class="eyebrow">SEGURIDAD</span><h2>Restablecer contraseña</h2></div><button type="button" data-close aria-label="Cerrar">×</button></div><p>Asigna una contraseña nueva para <strong><?= e($p['full_name']) ?></strong>. La cuenta se desbloquea y se cierran sus sesiones recordadas.</p><label>Nueva contraseña<input type="password" name="password" minlength="8" autocomplete="new-password" required></label><div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary">Restablecer</button></div></form></dialog><?php endif;?></td>
</tr><?php endforeach;?></tbody></table></div></section>
<?php app_footer(); ?>
