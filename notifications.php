<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require_auth();
$u=current_user();
if(isset($_GET['open'])){
    $n=row('SELECT * FROM notifications WHERE id=? AND user_id=?',[(int)$_GET['open'],$u['id']]);
    if($n){ query('UPDATE notifications SET read_at=COALESCE(read_at,NOW()) WHERE id=?',[$n['id']]);
        $link=(string)$n['link']; if($link!==''&&preg_match('/^[a-z\-]+\.php(\?[\w=&%\-.]*)?(#[\w\-]+)?$/',$link)) redirect($link); }
    redirect('notifications.php');
}
if($_SERVER['REQUEST_METHOD']==='POST'){ require_csrf(); query('UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL',[$u['id']]); flash('success','Todos los avisos quedaron como leídos.'); redirect('notifications.php'); }
$items=rows('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC,id DESC LIMIT 100',[$u['id']]);
app_header('Avisos','TABLERO Y AVISOS');
?>
<div class="page-actions"><p>Aquí llegan los compromisos asignados, los cambios en tus postulaciones y las solicitudes que requieren tu revisión.</p><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="button button-ghost"><?= icon('check',15) ?>Marcar todos como leídos</button></form></div>
<section class="panel notice-list">
<?php if(!$items):?><div class="empty"><?= icon('bell',28) ?><p>No tienes avisos. Cuando alguien te asigne un compromiso o revise tu postulación, aparecerá aquí.</p></div><?php endif;?>
<?php foreach($items as $n):?><a class="notice<?= $n['read_at']?'':' unread' ?>" href="<?= e(url('notifications.php?open='.(int)$n['id'])) ?>"><span class="kpi-icon tone-<?= $n['read_at']?'slate':'fet' ?>"><?= icon('bell',18) ?></span><div><strong><?= e($n['title']) ?></strong><p><?= e($n['body']) ?></p></div><time><?= e(fecha_es($n['created_at'],'short')) ?> · <?= e(date('H:i',strtotime($n['created_at']))) ?></time></a><?php endforeach;?>
</section>
<?php app_footer(); ?>
