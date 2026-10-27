<?php
declare(strict_types=1);

const APP_NAME = 'Intranet Privada FET';
const APP_VERSION = '1.2.1';
const SESSION_NAME = 'FETINTRANET';
const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_MINUTES = 10;
const REMEMBER_COOKIE = 'fet_intranet_remember';
const REMEMBER_DAYS = 30;

const ROLE_LABELS = [
    'admin' => 'Administrador técnico',
    'manager' => 'Gestor institucional',
    'leader' => 'Docente / líder',
    'student' => 'Estudiante / egresado',
    'company' => 'Empresa de pasantía',
];

const ODS_LABELS = [
    8 => 'Trabajo decente y crecimiento económico',
    9 => 'Industria, innovación e infraestructura',
    17 => 'Alianzas para lograr los objetivos',
];
