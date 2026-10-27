<?php $unread=unread_notifications(); $notes=latest_notifications(6); ?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="Intranet Privada de la Fundación Escuela Tecnológica de Neiva Jesús Oviedo Pérez · Campus Rivera, Huila">
<title><?= e($title) ?> | <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/png" href="<?= e(url('assets/img/favicon.png')) ?>"><link rel="apple-touch-icon" href="<?= e(url('assets/img/apple-touch-icon.png')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/styles.css')) ?>?v=<?= e(APP_VERSION) ?>"></head>
<body class="app-body" data-user="<?= (int)$u['id'] ?>" data-role="<?= e($u['role']) ?>" data-page="<?= e(basename($current,'.php')) ?>"><a class="skip-link" href="#contenido">Saltar al contenido</a><div class="app-frame">
<aside class="sidebar" data-sidebar id="tour-sidebar">
 <a class="side-brand" href="<?= e(url('dashboard.php')) ?>" aria-label="Inicio de la Intranet Privada FET"><img src="<?= e(url('assets/img/logo-fet-white.png')) ?>" alt="Fundación Escuela Tecnológica de Neiva Jesús Oviedo Pérez" width="178" height="97"></a>
 <span class="side-caption">Intranet privada</span>
 <nav class="side-nav" aria-label="Navegación principal">
 <?php foreach(nav_items() as [$href,$label,$ic,$roles,$tone]): if($roles&&!has_role(...$roles)) continue; ?><a class="<?= $current===$href?'active':'' ?>" href="<?= e(url($href)) ?>" data-tour-nav="<?= e(basename($href,'.php')) ?>"<?= $current===$href?' aria-current="page"':'' ?>><span class="nav-icon tone-<?= e($tone) ?>"><?= icon($ic,18) ?></span><?= e($label) ?></a><?php endforeach; ?>
 </nav>
 <div class="ods-mini"><span>ODS priorizados</span><div><b class="ods ods-8" title="Trabajo decente y crecimiento económico">8</b><b class="ods ods-9" title="Industria, innovación e infraestructura">9</b><b class="ods ods-17" title="Alianzas para lograr los objetivos">17</b></div></div>
</aside>
<div class="app-main"><header class="topbar"><button class="menu-toggle" type="button" data-menu aria-label="Abrir menú" aria-expanded="false"><?= icon('menu',20) ?></button><div class="topbar-title"><span class="eyebrow"><?= e($eyebrow) ?></span><h1><?= e($title) ?></h1></div>
 <div class="top-actions">
  <button class="icon-button tour-launch" type="button" data-tour-start title="Ver el tutorial de esta pantalla" aria-label="Ver el tutorial de esta pantalla" id="tour-help"><?= icon('help',20) ?><span>Tutorial</span></button>
  <div class="bell-wrap" id="tour-bell"><button class="icon-button" type="button" data-bell aria-haspopup="true" aria-expanded="false" aria-label="Avisos<?= $unread?': '.$unread.' sin leer':'' ?>"><?= icon('bell',20) ?><?php if($unread):?><b class="badge"><?= $unread>9?'9+':$unread ?></b><?php endif;?></button>
   <div class="bell-panel" data-bell-panel hidden><div class="bell-head"><strong>Avisos</strong><a href="<?= e(url('notifications.php')) ?>">Ver todos</a></div>
   <?php if(!$notes):?><p class="bell-empty">No tienes avisos todavía.</p><?php else: foreach($notes as $n):?><a class="bell-item<?= $n['read_at']?'':' unread' ?>" href="<?= e(url('notifications.php?open='.(int)$n['id'])) ?>"><strong><?= e($n['title']) ?></strong><small><?= e($n['body']) ?></small><time><?= e(fecha_es($n['created_at'],'short')) ?></time></a><?php endforeach; endif;?>
   </div></div>
  <div class="user-menu" id="tour-user"><span class="avatar"><?= e(mb_strtoupper(mb_substr($u['full_name'],0,1))) ?></span><span class="user-text"><strong><?= e($u['full_name']) ?></strong><small><?= e(role_label($u['role'])) ?></small></span><form method="post" action="<?= e(url('logout.php')) ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="icon-button" type="submit" title="Cerrar sesión" aria-label="Cerrar sesión"><?= icon('logout',19) ?></button></form></div>
 </div></header>
<main class="content" id="contenido" tabindex="-1">
<?php if($flash): ?><div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= icon($flash['type']==='error'?'alert':'check-circle',18) ?><span><?= e($flash['message']) ?></span></div><?php endif; ?>
