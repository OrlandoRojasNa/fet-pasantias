<?php declare(strict_types=1); require __DIR__.'/includes/bootstrap.php'; ?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ayuda | Intranet Privada FET</title><link rel="icon" type="image/png" href="<?= e(url('assets/img/favicon.png')) ?>"><link rel="stylesheet" href="<?= e(url('assets/css/styles.css')) ?>?v=<?= e(APP_VERSION) ?>"></head>
<body class="auth-page"><main class="help-card"><img class="auth-logo" src="<?= e(url('assets/img/logo-fet.png')) ?>" alt="Fundación Escuela Tecnológica de Neiva" width="170" height="93">
<h1>¿Cómo podemos ayudarte?</h1>
<div class="help-grid">
 <article><span class="kpi-icon tone-fet"><?= icon('users',20) ?></span><h2>Datos de acceso</h2><p>Usa la identificación o el correo que te asignó el administrador de la intranet.</p></article>
 <article><span class="kpi-icon tone-gold"><?= icon('key',20) ?></span><h2>Contraseña</h2><p>Respeta mayúsculas y símbolos. Tras cinco intentos fallidos la cuenta se bloquea diez minutos.</p></article>
 <article><span class="kpi-icon tone-ods17"><?= icon('shield',20) ?></span><h2>Permisos</h2><p>Cada perfil ve solo los módulos y registros autorizados para sus funciones.</p></article>
 <article><span class="kpi-icon tone-ods9"><?= icon('compass',20) ?></span><h2>Tutorial</h2><p>Al entrar por primera vez se abre un recorrido guiado. Repítelo con el botón (?) de cualquier pantalla.</p></article>
</div>
<div class="button-row"><a class="button button-primary" href="<?= e(url('index.php')) ?>"><?= icon('arrow-left',15) ?>Volver al acceso</a><a class="button button-ghost" href="<?= e(url('forgot-password.php')) ?>">Recuperar contraseña</a></div></main></body></html>
