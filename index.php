<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
if(current_user()) redirect('dashboard.php');
$error=null; $identifier=''; $remember=false; $flash=consume_flash();
if($_SERVER['REQUEST_METHOD']==='POST'){
    $identifier=trim((string)($_POST['identifier']??'')); $password=(string)($_POST['password']??''); $remember=isset($_POST['remember']);
    if(!csrf_valid($_POST['csrf']??null)) $error='El formulario expiró. Actualiza la página e inténtalo de nuevo.';
    elseif($identifier===''||$password==='') $error='Escribe tu identificación o correo y la contraseña.';
    else try{ $result=authenticate($identifier,$password,$remember); if($result['ok']){ flash('success',$result['message']); redirect('dashboard.php'); } $error=$result['message']; }
    catch(Throwable){ $error='No fue posible conectar con la base de datos. Ejecuta database/install.php una vez.'; }
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Acceso a la plataforma de pasantías de la Fundación Escuela Tecnológica de Neiva · Campus Rivera, Huila"><title>Acceso | Pasantías FET</title><link rel="icon" type="image/png" href="<?= e(url('assets/img/favicon.png')) ?>"><link rel="stylesheet" href="<?= e(url('assets/css/styles.css')) ?>?v=<?= e(APP_VERSION) ?>"></head>
<body class="login-page"><main class="login-shell">
<section class="login-story">
 <img class="login-logo" src="<?= e(url('assets/img/logo-fet-white.png')) ?>" alt="Fundación Escuela Tecnológica de Neiva Jesús Oviedo Pérez" width="260" height="142">
 <div class="story-copy"><span class="overline">PASANTÍAS FET</span><h1>Tu pasantía, de la convocatoria al resultado final.</h1><p>Convocatorias de empresas aliadas, documentos revisados por tu docente líder y un historial claro de cada paso.</p>
  <div class="ods-row"><article><b class="ods ods-8"><?= icon('file',20) ?></b><span><small>GESTIÓN</small>Documental</span></article><article><b class="ods ods-9"><?= icon('briefcase',20) ?></b><span><small>CONVOCATORIAS</small>y empleo</span></article><article><b class="ods ods-17"><?= icon('handshake',20) ?></b><span><small>ALIANZAS</small>con cupos</span></article></div></div>
 <p class="story-foot"><?= icon('map-pin',14) ?> Campus Rivera, Huila · Acceso exclusivo para la comunidad autorizada FET.</p>
</section>
<section class="login-form-panel"><div class="login-form-wrap">
 <img class="mobile-logo" src="<?= e(url('assets/img/logo-fet.png')) ?>" alt="Fundación Escuela Tecnológica de Neiva" width="180" height="98">
 <h2>Inicia sesión</h2><p class="muted">Ingresa con tu identificación o tu correo institucional.</p>
 <?php if($flash):?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif;?><?php if($error):?><div class="alert alert-error" role="alert"><?= icon('alert',18) ?><span><?= e($error) ?></span></div><?php endif;?>
 <form method="post" class="stack-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label>Identificación o correo<span class="input-icon"><?= icon('users',18) ?><input name="identifier" value="<?= e($identifier) ?>" autocomplete="username" maxlength="150" required autofocus></span></label>
  <label>Contraseña<span class="password-wrap input-icon"><?= icon('key',18) ?><input id="password" name="password" type="password" autocomplete="current-password" required><button type="button" data-password-toggle aria-pressed="false">Ver</button></span></label>
  <label class="check"><input type="checkbox" name="remember" value="1" <?= $remember?'checked':'' ?>> Mantener mi sesión en este equipo</label>
  <button class="button button-primary button-wide" type="submit">Ingresar</button></form>
 <div class="login-help"><a href="<?= e(url('forgot-password.php')) ?>">¿Olvidaste tu contraseña?</a><a href="<?= e(url('help.php')) ?>"><?= icon('help',15) ?>Ayuda de acceso</a></div>
 <details class="demo-note"><summary><?= icon('sparkles',16) ?>Cuentas de demostración</summary><table><tr><td>Administrador</td><td>admin@fet.edu.co</td></tr><tr><td>Gestor</td><td>gestor@fet.edu.co</td></tr><tr><td>Docente</td><td>docente@fet.edu.co</td></tr><tr><td>Estudiante</td><td>estudiante@fet.edu.co</td></tr><tr><td>Empresa</td><td>empresa@fet.edu.co</td></tr></table><span>Contraseña para todas: <b>FET2026*</b></span></details>
</div></section></main><script src="<?= e(url('assets/js/app.js')) ?>?v=<?= e(APP_VERSION) ?>" defer></script></body></html>
