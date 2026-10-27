<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name(SESSION_NAME);
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$https,'httponly'=>true,'samesite'=>'Lax']);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self'; img-src 'self' data:; form-action 'self'; base-uri 'self'; frame-ancestors 'none'");
}

require_once __DIR__ . '/functions.php';
restore_remembered_session();
