<?php
declare(strict_types=1);

function db(): PDO {
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $c = require __DIR__ . '/../config/database.php';
    $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset={$c['charset']}";
    $pdo = new PDO($dsn, $c['username'], $c['password'], [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
    return $pdo;
}

function query(string $sql, array $params=[]): PDOStatement { $s=db()->prepare($sql); $s->execute($params); return $s; }
function rows(string $sql, array $params=[]): array { return query($sql,$params)->fetchAll(); }
function row(string $sql, array $params=[]): ?array { $r=query($sql,$params)->fetch(); return $r ?: null; }
function scalar(string $sql, array $params=[]): mixed { return query($sql,$params)->fetchColumn(); }

function base_url(): string {
    $configured=getenv('APP_BASE_URL'); if ($configured) return rtrim($configured,'/');
    $script=str_replace('\\','/',$_SERVER['SCRIPT_NAME'] ?? ''); return rtrim(dirname($script),'/.');
}
function url(string $path=''): string { return base_url().'/'.ltrim($path,'/'); }
function e(mixed $v): string { return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function redirect(string $path): never { header('Location: '.url($path)); exit; }
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_valid(?string $token): bool { return is_string($token)&&isset($_SESSION['csrf'])&&hash_equals($_SESSION['csrf'],$token); }
function require_csrf(): void { if (!csrf_valid($_POST['csrf']??null)) { http_response_code(419); exit('La sesión del formulario expiró.'); } }
function flash(string $type,string $message): void { $_SESSION['flash']=['type'=>$type,'message'=>$message]; }
function consume_flash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return is_array($f)?$f:null; }
function current_user(): ?array { return isset($_SESSION['user'])&&is_array($_SESSION['user'])?$_SESSION['user']:null; }
function require_auth(): void { if (!current_user()) { flash('error','Inicia sesión para continuar.'); redirect('index.php'); } }
function has_role(string ...$roles): bool { $u=current_user(); return $u && in_array($u['role'],$roles,true); }
function require_role(string ...$roles): void { require_auth(); if (!has_role(...$roles)) { http_response_code(403); require __DIR__.'/403.php'; exit; } }
function role_label(string $role): string { return ROLE_LABELS[$role]??$role; }
function status_label(string $status): string { return [
    'draft'=>'Borrador','review'=>'En revisión','published'=>'Publicada','closed'=>'Cerrada',
    'submitted'=>'Enviada','accepted'=>'Aceptada','rejected'=>'No seleccionada','withdrawn'=>'Retirada',
    'planned'=>'Planeado','active'=>'Activo','paused'=>'En pausa','completed'=>'Completado',
    'pending'=>'Pendiente','in_progress'=>'En curso','blocked'=>'Bloqueado','overdue'=>'Vencido',
    'prospect'=>'Prospecto','inactive'=>'Inactivo','proposed'=>'Propuesto','expired'=>'Vencido',
    'approved'=>'Aprobada','cancelled'=>'Cancelada','open'=>'Abierto','fixed'=>'Corregido','verified'=>'Verificado',
    'practica'=>'Práctica','empleo'=>'Empleo','formacion'=>'Formación'
][$status]??ucfirst(str_replace('_',' ',$status)); }

function audit(string $action,string $entityType,?int $entityId=null,?string $details=null): void {
    $u=current_user(); query('INSERT INTO audit_events(user_id,action,entity_type,entity_id,details,ip_address) VALUES(?,?,?,?,?,?)',[
        $u['id']??null,$action,$entityType,$entityId,$details,substr($_SERVER['REMOTE_ADDR']??'cli',0,45)
    ]);
}

function authenticate(string $identifier,string $password,bool $remember=false): array {
    $u=row("SELECT * FROM users WHERE (identification=? OR email=?) AND status='active' LIMIT 1",[$identifier,mb_strtolower($identifier)]);
    if ($u && $u['locked_until'] && strtotime($u['locked_until'])>time()) return ['ok'=>false,'message'=>'La cuenta está bloqueada temporalmente.'];
    if (!$u || !password_verify($password,$u['password_hash'])) {
        if ($u) {
            $attempts=(int)$u['failed_attempts']+1; $lock=$attempts>=MAX_LOGIN_ATTEMPTS;
            query('UPDATE users SET failed_attempts=?,locked_until=? WHERE id=?',[$lock?0:$attempts,$lock?date('Y-m-d H:i:s',time()+LOCKOUT_MINUTES*60):null,$u['id']]);
        }
        return ['ok'=>false,'message'=>'Identificación, correo o contraseña incorrectos.'];
    }
    query('UPDATE users SET failed_attempts=0,locked_until=NULL,last_login_at=NOW() WHERE id=?',[$u['id']]);
    session_regenerate_id(true);
    $_SESSION['user']=['id'=>(int)$u['id'],'identification'=>$u['identification'],'full_name'=>$u['full_name'],'email'=>$u['email'],'role'=>$u['role'],'program'=>$u['program']];
    $_SESSION['csrf']=bin2hex(random_bytes(32)); audit('login','session',(int)$u['id']);
    if ($remember) create_remember_token((int)$u['id']);
    return ['ok'=>true,'message'=>'Bienvenido a la Intranet Privada FET.'];
}

function create_remember_token(int $userId): void {
    $selector=bin2hex(random_bytes(9)); $validator=bin2hex(random_bytes(32));
    query('DELETE FROM remember_tokens WHERE user_id=? OR expires_at<NOW()',[$userId]);
    query('INSERT INTO remember_tokens(user_id,selector,validator_hash,expires_at) VALUES(?,?,?,?)',[$userId,$selector,hash('sha256',$validator),date('Y-m-d H:i:s',time()+REMEMBER_DAYS*86400)]);
    setcookie(REMEMBER_COOKIE,"$selector:$validator",['expires'=>time()+REMEMBER_DAYS*86400,'path'=>base_url()?:'/','httponly'=>true,'samesite'=>'Lax']);
}
function restore_remembered_session(): void {
    if (current_user()||empty($_COOKIE[REMEMBER_COOKIE])) return;
    [$selector,$validator]=array_pad(explode(':',(string)$_COOKIE[REMEMBER_COOKIE],2),2,'');
    try { $u=row("SELECT u.* FROM remember_tokens r JOIN users u ON u.id=r.user_id WHERE r.selector=? AND r.expires_at>NOW() AND u.status='active'",[$selector]);
        $token=$u?row('SELECT validator_hash FROM remember_tokens WHERE selector=?',[$selector]):null;
        if ($u&&$token&&hash_equals($token['validator_hash'],hash('sha256',$validator))) {
            $_SESSION['user']=['id'=>(int)$u['id'],'identification'=>$u['identification'],'full_name'=>$u['full_name'],'email'=>$u['email'],'role'=>$u['role'],'program'=>$u['program']];
        }
    } catch(Throwable) {}
}
function destroy_session(): void { if(current_user()) audit('logout','session',(int)current_user()['id']); $_SESSION=[]; if(session_status()===PHP_SESSION_ACTIVE) session_destroy(); setcookie(REMEMBER_COOKIE,'',time()-3600,base_url()?:'/'); }

function nav_items(): array { return [
    ['dashboard.php','Inicio','home',[],'fet'],
    ['opportunities.php','Oportunidades','briefcase',[],'ods8'],
    ['internships.php','Pasantías','file',[],'ods8'],
    ['projects.php','Innovación','rocket',['admin','manager','leader','student'],'ods9'],
    ['alliances.php','Alianzas','handshake',['admin','manager','leader','student'],'ods17'],
    ['minutes.php','Actas y compromisos','clipboard',['admin','manager','leader','student'],'fet'],
    ['reports.php','Indicadores ODS','chart',['admin','manager'],'gold'],
    ['quality.php','Calidad y pruebas','shield',['admin','manager','leader'],'teal'],
    ['users.php','Usuarios','users',['admin'],'slate'],
    ['guide.php','Guía de uso','compass',[],'gold'],
]; }

/** Íconos SVG en línea (trazo de 24 px). Usan currentColor para heredar el color del contenedor. */
function icon(string $name, int $size=20, string $class='icon'): string {
    static $paths=[
        'home'=>'<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h5v-6h4v6h5V9.5"/>',
        'briefcase'=>'<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 13h18"/><path d="M11 13v2h2v-2"/>',
        'rocket'=>'<path d="M5 15c-1.5 1.3-2 5-2 5s3.7-.5 5-2c.7-.8.7-2.1-.1-2.9a2.1 2.1 0 0 0-2.9-.1Z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.9A12.9 12.9 0 0 1 22 2c0 2.7-.8 7.5-6 11a22 22 0 0 1-4 2Z"/><path d="M9 12H4s.6-3 2-4c1.6-1.1 5 0 5 0"/><path d="M12 15v5s3-.6 4-2c1.1-1.6 0-5 0-5"/>',
        'handshake'=>'<path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.9-3.9a3 3 0 0 0-4.2 0l-.9.9a1 1 0 1 1-3-3l2.8-2.8a5.8 5.8 0 0 1 7 .9L21 7.4"/><path d="m21 3 1 11h-2"/><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"/><path d="M3 4h8"/>',
        'clipboard'=>'<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1"/><path d="m9 12 2 2 4-4"/><path d="M9 17h6"/>',
        'chart'=>'<path d="M3 3v18h18"/><path d="M7 16v-4"/><path d="M12 16V8"/><path d="M17 16v-7"/>',
        'shield'=>'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
        'users'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/>',
        'compass'=>'<circle cx="12" cy="12" r="10"/><path d="m16.2 7.8-2.1 6.3-6.3 2.1 2.1-6.3Z"/>',
        'bell'=>'<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/>',
        'help'=>'<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
        'logout'=>'<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'menu'=>'<path d="M4 6h16M4 12h16M4 18h16"/>',
        'plus'=>'<path d="M12 5v14M5 12h14"/>',
        'calendar'=>'<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'clock'=>'<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'check'=>'<path d="M20 6 9 17l-5-5"/>',
        'check-circle'=>'<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'x-circle'=>'<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/>',
        'alert'=>'<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
        'file'=>'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M16 13H8M16 17H8M10 9H8"/>',
        'paperclip'=>'<path d="m21.4 11-9.2 9.2a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5"/>',
        'download'=>'<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
        'printer'=>'<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
        'database'=>'<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.7 4 3 9 3s9-1.3 9-3V5"/><path d="M3 12c0 1.7 4 3 9 3s9-1.3 9-3"/>',
        'eye'=>'<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'arrow-left'=>'<path d="M19 12H5M12 19l-7-7 7-7"/>',
        'target'=>'<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
        'flag'=>'<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1Z"/><path d="M4 22v-7"/>',
        'building'=>'<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/>',
        'map-pin'=>'<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'send'=>'<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
        'filter'=>'<path d="M22 3H2l8 9.5V19l4 2v-8.5Z"/>',
        'key'=>'<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/>',
        'sparkles'=>'<path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/>',
        'bug'=>'<rect x="8" y="6" width="8" height="14" rx="4"/><path d="m19 7-3 2M5 7l3 2M19 19l-3-2M5 19l3-2M20 13h-4M4 13h4M10 4l1 2M14 4l-1 2"/>',
        'list'=>'<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'edit'=>'<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'graduation'=>'<path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>',
        'play'=>'<polygon points="6 3 20 12 6 21 6 3"/>',
        'user-plus'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>',
        'undo'=>'<path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-15-6.7L3 13"/>',
    ];
    $p=$paths[$name]??$paths['sparkles'];
    return '<svg class="'.e($class).'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'.$p.'</svg>';
}

/** Fecha en español sin depender de strftime (obsoleta desde PHP 8.1). */
function fecha_es(?string $date=null, string $format='long'): string {
    $t=$date?strtotime($date):time();
    $days=['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $months=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $m=$months[(int)date('n',$t)-1];
    return match($format){
        'day'=>$days[(int)date('w',$t)],
        'month'=>$m.' de '.date('Y',$t),
        'short'=>date('j',$t).' '.mb_substr($m,0,3).' '.date('Y',$t),
        default=>$days[(int)date('w',$t)].', '.date('j',$t).' de '.$m.' de '.date('Y',$t),
    };
}

/** Avisos internos (módulo «Tablero y avisos» de los requisitos). */
function notify(int $userId,string $title,string $body,?string $link=null): void {
    try { query('INSERT INTO notifications(user_id,title,body,link) VALUES(?,?,?,?)',[$userId,mb_substr($title,0,180),mb_substr($body,0,255),$link]); } catch(Throwable) {}
}
function notify_roles(array $roles,string $title,string $body,?string $link=null,?int $except=null): void {
    $in=implode(',',array_fill(0,count($roles),'?'));
    foreach(rows("SELECT id FROM users WHERE status='active' AND role IN ($in)",$roles) as $r){ if((int)$r['id']!==$except) notify((int)$r['id'],$title,$body,$link); }
}
function unread_notifications(): int {
    $u=current_user(); if(!$u) return 0;
    try { return (int)scalar('SELECT COUNT(*) FROM notifications WHERE user_id=? AND read_at IS NULL',[$u['id']]); } catch(Throwable) { return 0; }
}
function latest_notifications(int $limit=6): array {
    $u=current_user(); if(!$u) return [];
    try { return rows('SELECT * FROM notifications WHERE user_id=? ORDER BY (read_at IS NULL) DESC,created_at DESC LIMIT '.(int)$limit,[$u['id']]); } catch(Throwable) { return []; }
}

/** Marca como vencidos los compromisos cuya fecha ya pasó. Debe ejecutarse antes de consultarlos. */
function refresh_overdue_commitments(): void {
    query("UPDATE commitments SET status='overdue' WHERE due_on<CURDATE() AND status IN ('pending','in_progress')");
}

/**
 * Guarda un archivo de evidencia en storage/documents con un nombre aleatorio
 * (nunca el nombre original, para evitar rutas o extensiones peligrosas).
 */
function store_evidence_upload(string $field): ?array {
    if(empty($_FILES[$field])||($_FILES[$field]['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return null;
    $f=$_FILES[$field];
    if($f['error']!==UPLOAD_ERR_OK) throw new RuntimeException('No fue posible recibir el archivo.');
    if($f['size']>5*1024*1024) throw new RuntimeException('El archivo supera el límite de 5 MB.');
    $ext=strtolower(pathinfo((string)$f['name'],PATHINFO_EXTENSION));
    $allowed=['pdf','png','jpg','jpeg','webp','docx','xlsx','pptx','csv','txt','sql','zip'];
    if(!in_array($ext,$allowed,true)) throw new RuntimeException('Formato no permitido. Usa PDF, imagen, Office, CSV, TXT, SQL o ZIP.');
    $stored=bin2hex(random_bytes(12)).'.'.$ext;
    $dir=__DIR__.'/../storage/documents';
    if(!is_dir($dir)) mkdir($dir,0775,true);
    if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$stored)) throw new RuntimeException('No fue posible guardar el archivo.');
    return ['stored'=>$stored,'original'=>mb_substr(basename((string)$f['name']),0,180)];
}

/** Quién puede ver una pasantía y sus documentos: el estudiante, su empresa evaluadora, su docente y la gestión FET. */
function can_view_internship(array $i): bool {
    $u=current_user(); if(!$u) return false;
    if(has_role('admin','manager')) return true;
    return in_array((int)$u['id'],[(int)$i['student_id'],(int)$i['company_id'],(int)$i['teacher_id']],true);
}

/**
 * Crea la pasantía cuando una empresa acepta una postulación de práctica.
 * La empresa evaluadora es quien publicó la vacante (si tiene perfil de empresa). No duplica.
 */
function create_internship_from_application(int $applicationId): ?int {
    $a=row("SELECT a.id,a.user_id,o.title,o.organization,o.type,o.created_by,u.role creator_role FROM applications a JOIN opportunities o ON o.id=a.opportunity_id JOIN users u ON u.id=o.created_by WHERE a.id=? AND a.status='accepted'",[$applicationId]);
    if(!$a||$a['type']!=='practica') return null;
    $existing=scalar('SELECT id FROM internships WHERE application_id=?',[$applicationId]);
    if($existing) return (int)$existing;
    $company=$a['creator_role']==='company'?(int)$a['created_by']:null;
    query('INSERT INTO internships(application_id,student_id,company_id,title,organization,starts_on) VALUES(?,?,?,?,?,CURDATE())',[$applicationId,$a['user_id'],$company,$a['title'],$a['organization']]);
    $id=(int)db()->lastInsertId();
    audit('create','internship',$id,$a['title']);
    notify((int)$a['user_id'],'Tu pasantía quedó registrada','«'.$a['title'].'»: ya puedes subir tus documentos de pasantía.','internships.php?id='.$id);
    notify_roles(['admin','manager'],'Pasantía sin docente asignado','«'.$a['title'].'» necesita un docente que valide los documentos.','internships.php?id='.$id);
    return $id;
}

/** Nota de 0.0 a 5.0 con un decimal; acepta coma o punto. Devuelve null si no es válida. */
function valid_grade(mixed $v): ?float {
    $v=str_replace(',','.',trim((string)$v));
    if(!preg_match('/^\d(\.\d+)?$/',$v)) return null;
    $g=round((float)$v,1);
    return $g>=0.0&&$g<=5.0?$g:null;
}

function allowed_value(mixed $value,array $allowed,string $default): string { return in_array($value,$allowed,true)?(string)$value:$default; }
function valid_date(mixed $d): ?string { if(!is_string($d)||$d==='') return null; $x=DateTime::createFromFormat('Y-m-d',$d); return $x&&$x->format('Y-m-d')===$d?$d:null; }
function post_str(string $key,int $max=0): string { $v=trim((string)($_POST[$key]??'')); return $max>0?mb_substr($v,0,$max):$v; }

function app_header(string $title,string $eyebrow='INTRANET PRIVADA FET'): void {
    $u=current_user(); $flash=consume_flash(); $current=basename($_SERVER['PHP_SELF']??'');
    require __DIR__.'/layout-header.php';
}
function app_footer(): void { require __DIR__.'/layout-footer.php'; }
