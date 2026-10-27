<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
$sent=false; $error=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    $email=mb_strtolower(trim((string)($_POST['email']??'')));
    if(!csrf_valid($_POST['csrf']??null)) $error='El formulario expiró. Actualiza la página.';
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Escribe un correo válido.';
    else{
        $sent=true;
        // Se avisa al administrador solo si la cuenta existe; la respuesta es igual en ambos casos.
        try{ $u=row("SELECT id,full_name FROM users WHERE email=? AND status='active'",[$email]);
            if($u){ notify_roles(['admin'],'Solicitud de recuperación de acceso',$u['full_name'].' ('.$email.') pidió restablecer su contraseña.','users.php'); query('INSERT INTO audit_events(user_id,action,entity_type,entity_id,details,ip_address) VALUES(NULL,?,?,?,?,?)',['password_request','user',$u['id'],$email,substr($_SERVER['REMOTE_ADDR']??'',0,45)]); }
        }catch(Throwable){}
    }
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Recuperar acceso | Intranet FET</title><link rel="icon" type="image/png" href="<?= e(url('assets/img/favicon.png')) ?>"><link rel="stylesheet" href="<?= e(url('assets/css/styles.css')) ?>?v=<?= e(APP_VERSION) ?>"></head>
<body class="auth-page"><main class="recovery-card"><img class="auth-logo" src="<?= e(url('assets/img/logo-fet.png')) ?>" alt="Fundación Escuela Tecnológica de Neiva" width="170" height="93">
<h1>Recupera tu acceso</h1><p>Escribe tu correo. Si la cuenta existe, el administrador técnico recibirá la solicitud y te asignará una contraseña nueva. Por seguridad, la respuesta no confirma si el correo está registrado.</p>
<?php if($sent):?><div class="alert alert-success"><?= icon('check-circle',18) ?><span>Solicitud enviada. Si el correo está registrado, el administrador ya fue avisado.</span></div><?php endif;?><?php if($error):?><div class="alert alert-error"><?= icon('alert',18) ?><span><?= e($error) ?></span></div><?php endif;?>
<form method="post" class="stack-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label>Correo institucional<input type="email" name="email" placeholder="nombre@fet.edu.co" required></label><button class="button button-primary button-wide">Solicitar recuperación</button></form>
<a class="back-link" href="<?= e(url('index.php')) ?>"><?= icon('arrow-left',14) ?>Volver al inicio de sesión</a></main></body></html>
