<?php
declare(strict_types=1);

const APP_NAME = 'Pasantías FET';
const APP_VERSION = '2.0.0';
const SESSION_NAME = 'FETINTRANET';
const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_MINUTES = 10;
const REMEMBER_COOKIE = 'fet_intranet_remember';
const REMEMBER_DAYS = 30;

const ROLE_LABELS = [
    'admin' => 'Administrador',
    'leader' => 'Docente líder de pasantías',
    'student' => 'Estudiante',
    'company' => 'Empresa aliada',
];
