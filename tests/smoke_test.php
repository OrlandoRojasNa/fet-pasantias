<?php
declare(strict_types=1);
/**
 * Prueba automática de los flujos principales de Pasantías FET.
 * Inicia sesión con cada perfil por HTTP, recorre las pantallas y verifica permisos.
 * Los registros que crea llevan el prefijo «[Prueba automática]» y se eliminan al final.
 *
 *   C:\xampp\php\php.exe C:\xampp\htdocs\PruebaFet\tests\smoke_test.php [http://localhost/PruebaFet]
 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$base=rtrim($argv[1]??'http://localhost/PruebaFet','/');
$cfg=require __DIR__.'/../config/database.php';
$pdo=new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset=utf8mb4",$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
const PASS='FET2026*';
const TAG='[Prueba automática]';

final class Client {
    private string $jar;
    public function __construct(private string $base){ $this->jar=tempnam(sys_get_temp_dir(),'fet'); }
    public function __destruct(){ @unlink($this->jar); }
    public function req(string $path,?array $post=null): array {
        $ch=curl_init($this->base.'/'.ltrim($path,'/'));
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEJAR=>$this->jar,CURLOPT_COOKIEFILE=>$this->jar,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>20]);
        if($post!==null){ curl_setopt($ch,CURLOPT_POST,true); curl_setopt($ch,CURLOPT_POSTFIELDS,$post); }
        $body=(string)curl_exec($ch); $code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE); $url=(string)curl_getinfo($ch,CURLINFO_EFFECTIVE_URL); curl_close($ch);
        return ['code'=>$code,'body'=>$body,'url'=>$url];
    }
    public function csrf(string $path='index.php'): string { preg_match('/name="csrf" value="([a-f0-9]{64})"/',$this->req($path)['body'],$m); return $m[1]??''; }
    public function login(string $email,string $pass=PASS): array { $t=$this->csrf('index.php'); return $this->req('index.php',['csrf'=>$t,'identifier'=>$email,'password'=>$pass]); }
}
function clean(string $body): bool { return !preg_match('/(Warning|Notice|Deprecated|Fatal error|Uncaught)\b.*(on line|in [A-Z]:)/i',$body); }

$flows=[]; $t0=microtime(true);
$run=function(string $id,string $name,string $profile,callable $fn) use(&$flows){
    $steps=[]; $ok=true;
    try{ foreach($fn() as [$desc,$pass]){ $steps[]=['paso'=>$desc,'ok'=>(bool)$pass]; $ok=$ok&&$pass; } }
    catch(Throwable $e){ $steps[]=['paso'=>'Error: '.$e->getMessage(),'ok'=>false]; $ok=false; }
    $flows[]=['id'=>$id,'flujo'=>$name,'perfil'=>$profile,'resultado'=>$ok?'Pasa':'Falla','pasos'=>$steps];
    echo ($ok?'[PASA] ':'[FALLA] ')."$id $name\n"; foreach($steps as $s) echo '   '.($s['ok']?'ok ':'X  ').$s['paso']."\n";
};

$pages=[
 'admin'=>['allow'=>['dashboard.php','internships.php','opportunities.php','alliances.php','reports.php','users.php','guide.php','notifications.php'],'deny'=>[]],
 'docente'=>['allow'=>['dashboard.php','internships.php','opportunities.php','alliances.php','reports.php','guide.php','notifications.php'],'deny'=>['users.php']],
 'estudiante'=>['allow'=>['dashboard.php','internships.php','opportunities.php','guide.php','notifications.php'],'deny'=>['users.php','reports.php','alliances.php']],
 'empresa'=>['allow'=>['dashboard.php','internships.php','opportunities.php','guide.php','notifications.php'],'deny'=>['users.php','reports.php','alliances.php']],
];
const NIT='999000111';
const CO_EMAIL='prueba.automatica@empresa.test';
function pdf(string $name): CURLFile { $f=tempnam(sys_get_temp_dir(),'fet').'.pdf'; file_put_contents($f,"%PDF-1.4\n% ".TAG."\n"); return new CURLFile($f,'application/pdf',$name); }

// F1. Acceso seguro
$run('F1','Inicio de sesión y rechazo de credenciales inválidas','Todos',function() use($base){
    $s=[];
    foreach(['admin','docente','estudiante','empresa'] as $r){ $c=new Client($base); $res=$c->login("$r@fet.edu.co"); $s[]=["Ingreso de $r@fet.edu.co llega al panel",str_contains($res['url'],'dashboard.php')&&$res['code']===200]; }
    $c=new Client($base); $res=$c->login('estudiante@fet.edu.co','claveIncorrecta'); $s[]=['Contraseña incorrecta muestra error y no abre sesión',str_contains($res['body'],'incorrectos')&&!str_contains($res['url'],'dashboard')];
    $res=$c->req('index.php',['csrf'=>'falso','identifier'=>'admin@fet.edu.co','password'=>PASS]); $s[]=['Formulario sin token CSRF válido es rechazado',str_contains($res['body'],'expiró')];
    return $s;
});

// F2. Permisos por rol
$run('F2','Cada perfil ve solo sus módulos (matriz de permisos)','Todos',function() use($base,$pages){
    $s=[];
    foreach($pages as $r=>$p){ $c=new Client($base); $c->login("$r@fet.edu.co");
        $okAllow=true; foreach($p['allow'] as $pg){ $res=$c->req($pg); if($res['code']!==200||!clean($res['body'])||str_contains($res['url'],'index.php')){ $okAllow=false; $s[]=["$r: $pg respondió {$res['code']}",false]; } }
        $s[]=["$r abre sus ".count($p['allow']).' pantallas sin errores',$okAllow];
        if($p['deny']){ $okDeny=true; foreach($p['deny'] as $pg){ if($c->req($pg)['code']!==403) $okDeny=false; } $s[]=["$r recibe 403 en ".implode(', ',$p['deny']),$okDeny]; }
    }
    $c=new Client($base); $c->login('admin@fet.edu.co'); $gone=true;
    foreach(['projects.php','minutes.php','quality.php'] as $pg) if($c->req($pg)['code']!==404) $gone=false;
    $s[]=['Los módulos retirados (Innovación, Actas, Calidad) ya no existen',$gone];
    return $s;
});

// F3. Convocatoria: empresa publica → docente líder verifica → estudiante se postula → empresa acepta (crea la pasantía)
$run('F3','Convocatoria de pasantía, verificación y postulación','Empresa · Docente · Estudiante',function() use($base,$pdo){
    $s=[]; $title=TAG.' Práctica de soporte TI';
    $emp=new Client($base); $emp->login('empresa@fet.edu.co');
    $emp->req('opportunities.php',['csrf'=>$emp->csrf('opportunities.php'),'action'=>'create','title'=>$title,'type'=>'practica','modality'=>'hibrida','location'=>'Neiva','closes_on'=>date('Y-m-d',strtotime('+20 days')),'status'=>'review','description'=>'Registro creado por la prueba automática.','requirements'=>'Ninguno']);
    $op=$pdo->query('SELECT * FROM opportunities WHERE title='.$pdo->quote($title))->fetch();
    $s[]=['La empresa crea la convocatoria y queda «En revisión»',$op&&$op['status']==='review'];
    $doc=$pdo->query('SELECT id FROM users WHERE email="docente@fet.edu.co"')->fetchColumn();
    $s[]=['El docente líder recibe un aviso para verificarla',(bool)$pdo->query('SELECT COUNT(*) FROM notifications WHERE user_id='.(int)$doc.' AND body LIKE '.$pdo->quote('%'.$title.'%'))->fetchColumn()];
    $a=new Client($base); $a->login('docente@fet.edu.co');
    $a->req('opportunities.php',['csrf'=>$a->csrf('opportunities.php'),'action'=>'status','id'=>$op['id'],'status'=>'published']);
    $s[]=['El docente líder la publica como verificada',$pdo->query('SELECT status FROM opportunities WHERE id='.(int)$op['id'])->fetchColumn()==='published'];
    $st=new Client($base); $st->login('estudiante@fet.edu.co');
    $st->req('opportunities.php',['csrf'=>$st->csrf('opportunities.php'),'action'=>'apply','opportunity_id'=>$op['id'],'motivation'=>'Me interesa aplicar lo aprendido en Ingeniería de Software.']);
    $app=$pdo->query('SELECT * FROM applications WHERE opportunity_id='.(int)$op['id'])->fetch();
    $s[]=['El estudiante se postula',(bool)$app];
    $emp->req('opportunities.php',['csrf'=>$emp->csrf('opportunities.php'),'action'=>'application_status','id'=>$app['id'],'status'=>'accepted']);
    $s[]=['La empresa acepta la postulación de su convocatoria',$pdo->query('SELECT status FROM applications WHERE id='.(int)$app['id'])->fetchColumn()==='accepted'];
    $stId=(int)$app['user_id'];
    $s[]=['El estudiante recibe el aviso del resultado',(bool)$pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id=$stId AND title LIKE '%postulación%'")->fetchColumn()];
    return $s;
});

// F7. Pasantías: la postulación aceptada crea la pasantía; estudiante sube, empresa avala, docente aprueba
$run('F7','Pasantía: documentos, evaluación de la empresa y veredicto del docente','Estudiante · Empresa · Docente',function() use($base,$pdo){
    $s=[];
    $i=$pdo->query("SELECT * FROM internships WHERE title LIKE '".TAG."%' LIMIT 1")->fetch();
    $s[]=['Al aceptar la postulación a una práctica se crea la pasantía',(bool)$i];
    $docente=(int)$pdo->query("SELECT id FROM users WHERE email='docente@fet.edu.co'")->fetchColumn();
    $g=new Client($base); $g->login('docente@fet.edu.co');
    $g->req('internships.php',['csrf'=>$g->csrf('internships.php'),'action'=>'assign','id'=>$i['id'],'company_id'=>$i['company_id'],'teacher_id'=>$docente,'status'=>'active']);
    $s[]=['El docente líder asigna el docente de la pasantía',(int)$pdo->query('SELECT teacher_id FROM internships WHERE id='.(int)$i['id'])->fetchColumn()===$docente];
    $tmp=tempnam(sys_get_temp_dir(),'fet').'.pdf'; file_put_contents($tmp,"%PDF-1.4\n% ".TAG."\n");
    $st=new Client($base); $st->login('estudiante@fet.edu.co');
    $st->req('internships.php',['csrf'=>$st->csrf('internships.php'),'action'=>'upload','id'=>$i['id'],'doc_type'=>'plan','title'=>TAG.' Plan de práctica','document'=>new CURLFile($tmp,'application/pdf','plan.pdf')]);
    @unlink($tmp);
    $d=$pdo->query('SELECT * FROM internship_documents WHERE internship_id='.(int)$i['id'])->fetch();
    $s[]=['El estudiante sube el plan de práctica',$d&&$d['status']==='submitted'];
    $s[]=['La empresa y el docente reciben el aviso',(int)$pdo->query('SELECT COUNT(*) FROM notifications WHERE user_id IN ('.(int)$i['company_id'].','.$docente.') AND body LIKE '.$pdo->quote('%'.TAG.' Plan%'))->fetchColumn()===2];
    $emp=new Client($base); $emp->login('empresa@fet.edu.co'); $doc=new Client($base); $doc->login('docente@fet.edu.co');
    $s[]=['La empresa evaluadora y el docente pueden descargar el documento',str_contains($emp->req('evidence.php?type=internship&id='.$d['id'])['body'],'%PDF')&&str_contains($doc->req('evidence.php?type=internship&id='.$d['id'])['body'],'%PDF')];
    $j=new Client($base); $j->login('juan.sabogal@fet.edu.co');
    $s[]=['Otro estudiante no puede ver la pasantía ni descargar el documento',$j->req('internships.php?id='.$i['id'])['code']===403&&$j->req('evidence.php?type=internship&id='.$d['id'])['code']===404];
    $doc->req('internships.php',['csrf'=>$doc->csrf('internships.php'),'action'=>'verdict','id'=>$i['id'],'result'=>'aprobada','justification'=>'Intento de aprobar sin notas registradas.']);
    $s[]=['La pasantía no se puede aprobar sin notas (regla del promedio 3.0)',!$pdo->query('SELECT final_result FROM internships WHERE id='.(int)$i['id'])->fetchColumn()];
    $doc->req('internships.php',['csrf'=>$doc->csrf('internships.php'),'action'=>'review','document_id'=>$d['id'],'decision'=>'approve','grade'=>'2.5','comment'=>'']);
    $s[]=['Con nota menor a 3.0 el docente no puede aprobar el documento',$pdo->query('SELECT status FROM internship_documents WHERE id='.(int)$d['id'])->fetchColumn()==='submitted'];
    $emp->req('internships.php',['csrf'=>$emp->csrf('internships.php'),'action'=>'review','document_id'=>$d['id'],'decision'=>'endorse','comment'=>'Plan coherente con las funciones.']);
    $s[]=['La empresa avala el documento',$pdo->query('SELECT status FROM internship_documents WHERE id='.(int)$d['id'])->fetchColumn()==='endorsed'];
    $doc->req('internships.php',['csrf'=>$doc->csrf('internships.php'),'action'=>'review','document_id'=>$d['id'],'decision'=>'approve','grade'=>'5.5','comment'=>'Aprobado.']);
    $s[]=['El docente no puede calificar por encima de 5.0',$pdo->query('SELECT status FROM internship_documents WHERE id='.(int)$d['id'])->fetchColumn()==='endorsed'];
    $doc->req('internships.php',['csrf'=>$doc->csrf('internships.php'),'action'=>'review','document_id'=>$d['id'],'decision'=>'approve','comment'=>'Aprobado.']);
    $s[]=['El docente no puede aprobar sin calificar',$pdo->query('SELECT status FROM internship_documents WHERE id='.(int)$d['id'])->fetchColumn()==='endorsed'];
    $doc->req('internships.php',['csrf'=>$doc->csrf('internships.php'),'action'=>'review','document_id'=>$d['id'],'decision'=>'approve','grade'=>'4,5','comment'=>'Aprobado.']);
    $s[]=['El docente califica con decimales (4,5) y queda guardado 4.5',$pdo->query('SELECT grade FROM internship_documents WHERE id='.(int)$d['id'])->fetchColumn()==='4.5'];
    $s[]=['El docente lo aprueba y el estudiante recibe el aviso',$pdo->query('SELECT status FROM internship_documents WHERE id='.(int)$d['id'])->fetchColumn()==='approved'&&(bool)$pdo->query('SELECT COUNT(*) FROM notifications WHERE user_id='.(int)$i['student_id']." AND title='Tu documento fue aprobado'")->fetchColumn()];
    $page=$st->req('internships.php?id='.$i['id']);
    $s[]=['El estudiante ve el estado, la nota y las observaciones',$page['code']===200&&clean($page['body'])&&str_contains($page['body'],'Plan coherente')&&str_contains($page['body'],'4.5</strong> / 5.0')];
    $verdict=['csrf'=>'','action'=>'verdict','id'=>$i['id'],'result'=>'aprobada','justification'=>'Cumplió el plan y la empresa reporta buen desempeño.'];
    $emp->req('internships.php',['csrf'=>$emp->csrf('internships.php'),'action'=>'evaluate','id'=>$i['id'],'rating'=>4,'evaluation'=>'Responsable y puntual; buen manejo de soporte técnico.']);
    $s[]=['La empresa registra la evaluación de desempeño',(int)$pdo->query('SELECT company_rating FROM internships WHERE id='.(int)$i['id'])->fetchColumn()===4];
    $st->req('internships.php',['csrf'=>$st->csrf('internships.php')]+$verdict);
    $s[]=['El estudiante no puede emitir el veredicto',!$pdo->query('SELECT final_result FROM internships WHERE id='.(int)$i['id'])->fetchColumn()];
    $doc->req('internships.php',['csrf'=>$doc->csrf('internships.php')]+$verdict);
    $f=$pdo->query('SELECT final_result,status FROM internships WHERE id='.(int)$i['id'])->fetch();
    $s[]=['El docente aprueba la pasantía con justificación',$f['final_result']==='aprobada'&&$f['status']==='completed'];
    $page=$st->req('internships.php?id='.$i['id'])['body'];
    $s[]=['El estudiante ve el resultado, la justificación y la evaluación de la empresa',str_contains($page,'Cumplió el plan')&&str_contains($page,'4/5')];
    $s[]=['El estudiante no ve el historial de auditoría',!str_contains($page,'Historial de la pasantía')];
    $h=$doc->req('internships.php?id='.$i['id'])['body'];
    $s[]=['El docente líder ve el historial con la carga, la revisión, la descarga y el veredicto',str_contains($h,'Historial de la pasantía')&&str_contains($h,'Subió un documento')&&str_contains($h,'Revisó un documento')&&str_contains($h,'Descargó un documento')&&str_contains($h,'Emitió el veredicto final')];
    return $s;
});


// F8. Alianzas y cupos: el administrador crea la alianza; los cupos limitan cuántos pasantes acepta la empresa
$run('F8','Alianza empresarial con cupos que limitan los pasantes','Administrador · Empresa · Docente · Estudiantes',function() use($base,$pdo){
    $s=[]; $name=TAG.' Empresa Aliada de Prueba';
    $doc=new Client($base); $doc->login('docente@fet.edu.co');
    $doc->req('alliances.php',['csrf'=>$doc->csrf('alliances.php'),'action'=>'create','name'=>$name,'slots'=>1,'nit'=>NIT,'email'=>CO_EMAIL,'password'=>PASS]);
    $s[]=['El docente líder no puede crear alianzas',!$pdo->query('SELECT COUNT(*) FROM alliances WHERE name='.$pdo->quote($name))->fetchColumn()];
    $a=new Client($base); $a->login('admin@fet.edu.co');
    $a->req('alliances.php',['csrf'=>$a->csrf('alliances.php'),'action'=>'create','name'=>$name,'sector'=>'Pruebas','agreement_code'=>'CONV-PRUEBA','slots'=>1,'starts_on'=>date('Y-m-d'),'ends_on'=>date('Y-m-d',strtotime('+1 year')),'nit'=>NIT,'email'=>CO_EMAIL,'password'=>PASS]);
    $al=$pdo->query('SELECT * FROM alliances WHERE name='.$pdo->quote($name))->fetch();
    $co=(int)$pdo->query('SELECT id FROM users WHERE email='.$pdo->quote(CO_EMAIL)." AND role='company'")->fetchColumn();
    $s[]=['El administrador registra la alianza con 1 cupo y crea la cuenta de la empresa',$al&&(int)$al['slots']===1&&$co&&(int)$al['company_id']===$co];
    $emp=new Client($base); $res=$emp->login(CO_EMAIL);
    $s[]=['La empresa nueva inicia sesión y ve sus cupos',str_contains($res['url'],'dashboard.php')&&str_contains($emp->req('opportunities.php')['body'],'Cupos de pasantía en uso: 0 de 1')];
    $title=TAG.' Pasantía con cupo limitado';
    $emp->req('opportunities.php',['csrf'=>$emp->csrf('opportunities.php'),'action'=>'create','title'=>$title,'type'=>'practica','modality'=>'remota','closes_on'=>date('Y-m-d',strtotime('+15 days')),'status'=>'review','description'=>'Convocatoria creada por la prueba automática.']);
    $op=(int)$pdo->query('SELECT id FROM opportunities WHERE title='.$pdo->quote($title))->fetchColumn();
    $doc->req('opportunities.php',['csrf'=>$doc->csrf('opportunities.php'),'action'=>'status','id'=>$op,'status'=>'published']);
    $apps=[];
    foreach(['juan.sabogal@fet.edu.co','orlando.rojas@fet.edu.co'] as $mail){ $c=new Client($base); $c->login($mail); $c->req('opportunities.php',['csrf'=>$c->csrf('opportunities.php'),'action'=>'apply','opportunity_id'=>$op,'motivation'=>'Postulación de la prueba automática para validar los cupos.']); $apps[]=(int)$pdo->query("SELECT a.id FROM applications a JOIN users u ON u.id=a.user_id WHERE a.opportunity_id=$op AND u.email=".$pdo->quote($mail))->fetchColumn(); }
    $s[]=['Dos estudiantes se postulan a la convocatoria verificada',$apps[0]&&$apps[1]];
    $emp->req('opportunities.php',['csrf'=>$emp->csrf('opportunities.php'),'action'=>'application_status','id'=>$apps[0],'status'=>'accepted']);
    $s[]=['La empresa acepta al primero: se crea la pasantía y ocupa el cupo',(int)$pdo->query("SELECT COUNT(*) FROM internships WHERE company_id=$co AND status='active'")->fetchColumn()===1];
    $res=$emp->req('opportunities.php',['csrf'=>$emp->csrf('opportunities.php'),'action'=>'application_status','id'=>$apps[1],'status'=>'accepted']);
    $s[]=['Sin cupos, no puede aceptar al segundo y ve el motivo',$pdo->query('SELECT status FROM applications WHERE id='.$apps[1])->fetchColumn()!=='accepted'&&str_contains($res['body'],'no tiene cupos disponibles')];
    $a->req('alliances.php',['csrf'=>$a->csrf('alliances.php'),'action'=>'update','id'=>$al['id'],'slots'=>0,'status'=>'active']);
    $s[]=['Los cupos no pueden quedar por debajo de los pasantes en curso',(int)$pdo->query('SELECT slots FROM alliances WHERE id='.(int)$al['id'])->fetchColumn()===1];
    $a->req('alliances.php',['csrf'=>$a->csrf('alliances.php'),'action'=>'update','id'=>$al['id'],'slots'=>2,'status'=>'active','starts_on'=>$al['starts_on'],'ends_on'=>$al['ends_on']]);
    $s[]=['El administrador amplía la alianza a 2 cupos y la empresa recibe el aviso',(int)$pdo->query('SELECT slots FROM alliances WHERE id='.(int)$al['id'])->fetchColumn()===2&&(bool)$pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id=$co AND title='Se ampliaron tus cupos de pasantía'")->fetchColumn()];
    $emp->req('opportunities.php',['csrf'=>$emp->csrf('opportunities.php'),'action'=>'application_status','id'=>$apps[1],'status'=>'accepted']);
    $s[]=['Con el cupo nuevo acepta al segundo estudiante',(int)$pdo->query("SELECT COUNT(*) FROM internships WHERE company_id=$co AND status='active'")->fetchColumn()===2];
    $demoOp=(int)$pdo->query("SELECT id FROM opportunities WHERE created_by=(SELECT id FROM users WHERE email='empresa@fet.edu.co') LIMIT 1")->fetchColumn();
    $before=$pdo->query("SELECT status FROM opportunities WHERE id=$demoOp")->fetchColumn();
    $emp->req('opportunities.php',['csrf'=>$emp->csrf('opportunities.php'),'action'=>'status','id'=>$demoOp,'status'=>'closed']);
    $s[]=['Una empresa no puede cerrar publicaciones de otra',$demoOp&&$pdo->query("SELECT status FROM opportunities WHERE id=$demoOp")->fetchColumn()===$before];
    $a->req('alliances.php',['csrf'=>$a->csrf('alliances.php'),'action'=>'update','id'=>$al['id'],'slots'=>2,'status'=>'suspended']);
    $emp->req('opportunities.php',['csrf'=>$emp->csrf('opportunities.php'),'action'=>'create','title'=>TAG.' Publicación sin alianza','type'=>'practica','modality'=>'remota','closes_on'=>date('Y-m-d',strtotime('+15 days')),'status'=>'review','description'=>'No debería crearse.']);
    $s[]=['Con la alianza suspendida la empresa no puede publicar',!$pdo->query('SELECT COUNT(*) FROM opportunities WHERE title='.$pdo->quote(TAG.' Publicación sin alianza'))->fetchColumn()];
    $s[]=['La página de alianzas muestra la ocupación',str_contains($a->req('alliances.php')['body'],'2 / 2')];
    return $s;
});

// F4. Oferta laboral opcional: el estudiante aplica con su hoja de vida en PDF
$run('F4','Oferta laboral con hoja de vida','Empresa · Docente · Estudiante',function() use($base,$pdo){
    $s=[]; $title=TAG.' Desarrollador junior';
    $emp=new Client($base); $emp->login('empresa@fet.edu.co');
    $emp->req('opportunities.php',['csrf'=>$emp->csrf('opportunities.php'),'action'=>'create','title'=>$title,'type'=>'empleo','modality'=>'hibrida','closes_on'=>date('Y-m-d',strtotime('+20 days')),'status'=>'review','description'=>'Oferta laboral creada por la prueba automática.']);
    $op=(int)$pdo->query('SELECT id FROM opportunities WHERE type="empleo" AND title='.$pdo->quote($title))->fetchColumn();
    $s[]=['La empresa crea la oferta laboral',(bool)$op];
    $doc=new Client($base); $doc->login('docente@fet.edu.co');
    $doc->req('opportunities.php',['csrf'=>$doc->csrf('opportunities.php'),'action'=>'status','id'=>$op,'status'=>'published']);
    $st=new Client($base); $st->login('estudiante@fet.edu.co');
    $s[]=['El estudiante la ve publicada con el botón para aplicar con hoja de vida',str_contains($st->req('opportunities.php?type=empleo')['body'],'Aplicar con mi hoja de vida')];
    $st->req('opportunities.php',['csrf'=>$st->csrf('opportunities.php'),'action'=>'apply','opportunity_id'=>$op,'motivation'=>'Quiero trabajar en desarrollo web al graduarme.']);
    $s[]=['Sin hoja de vida no puede aplicar',!$pdo->query("SELECT COUNT(*) FROM applications WHERE opportunity_id=$op")->fetchColumn()];
    $txt=tempnam(sys_get_temp_dir(),'fet').'.txt'; file_put_contents($txt,'no es pdf');
    $st->req('opportunities.php',['csrf'=>$st->csrf('opportunities.php'),'action'=>'apply','opportunity_id'=>$op,'motivation'=>'Quiero trabajar en desarrollo web al graduarme.','cv'=>new CURLFile($txt,'text/plain','hoja.txt')]);
    @unlink($txt);
    $s[]=['Solo acepta la hoja de vida en PDF',!$pdo->query("SELECT COUNT(*) FROM applications WHERE opportunity_id=$op")->fetchColumn()];
    $st->req('opportunities.php',['csrf'=>$st->csrf('opportunities.php'),'action'=>'apply','opportunity_id'=>$op,'motivation'=>'Quiero trabajar en desarrollo web al graduarme.','cv'=>pdf('hoja_de_vida.pdf')]);
    $app=$pdo->query("SELECT * FROM applications WHERE opportunity_id=$op")->fetch();
    $s[]=['Con el PDF la postulación queda registrada con la hoja de vida',$app&&$app['cv_file']&&$app['cv_name']==='hoja_de_vida.pdf'];
    $s[]=['La empresa y el docente descargan la hoja de vida',str_contains($emp->req('evidence.php?type=cv&id='.$app['id'])['body'],'%PDF')&&str_contains($doc->req('evidence.php?type=cv&id='.$app['id'])['body'],'%PDF')];
    $j=new Client($base); $j->login('juan.sabogal@fet.edu.co'); $other=new Client($base); $other->login(CO_EMAIL);
    $s[]=['Otro estudiante y otra empresa no pueden descargarla',$j->req('evidence.php?type=cv&id='.$app['id'])['code']===404&&$other->req('evidence.php?type=cv&id='.$app['id'])['code']===404];
    $emp->req('opportunities.php',['csrf'=>$emp->csrf('opportunities.php'),'action'=>'application_status','id'=>$app['id'],'status'=>'accepted']);
    $s[]=['Aceptar una oferta laboral no crea pasantía ni usa cupos',$pdo->query('SELECT status FROM applications WHERE id='.(int)$app['id'])->fetchColumn()==='accepted'&&!$pdo->query('SELECT COUNT(*) FROM internships WHERE application_id='.(int)$app['id'])->fetchColumn()];
    return $s;
});

// F5. Reportes y auditoría
$run('F5','Reporte de pasantías, exportación CSV y bitácora','Docente líder',function() use($base){
    $s=[]; $g=new Client($base); $g->login('docente@fet.edu.co');
    $r=$g->req('reports.php?desde=2026-01-01&hasta='.date('Y-m-d')); $s[]=['El reporte carga el resumen, las alianzas y la bitácora',$r['code']===200&&clean($r['body'])&&str_contains($r['body'],'Cupos y resultados por empresa')&&str_contains($r['body'],'Bitácora de auditoría')];
    $csv=$g->req('reports.php?desde=2026-01-01&hasta='.date('Y-m-d').'&formato=csv'); $s[]=['La exportación CSV incluye pasantías y alianzas',str_contains($csv['body'],'Empresa evaluadora')&&str_contains($csv['body'],'Empresa Aliada de Pasantías')];
    $st=new Client($base); $st->login('estudiante@fet.edu.co'); $s[]=['Un estudiante no puede ver los reportes',$st->req('reports.php')['code']===403];
    return $s;
});

// F6. Respaldo y auditoría
$run('F6','Respaldo SQL, desbloqueo de cuentas y auditoría','Administrador',function() use($base,$pdo){
    $s=[]; $a=new Client($base); $a->login('admin@fet.edu.co');
    $b=$a->req('backup.php',['csrf'=>$a->csrf('users.php')])['body'];
    $s[]=['El respaldo incluye las tablas y su hash SHA-256',str_contains($b,'CREATE TABLE `alliances`')&&str_contains($b,'SHA-256')];
    preg_match('/-- SHA-256 del contenido anterior: ([a-f0-9]{64})/',$b,$m); $content=substr($b,0,(int)strpos($b,"-- Registros:"));
    $s[]=['El hash coincide con el contenido descargado',isset($m[1])&&hash('sha256',$content)===$m[1]];
    $st=new Client($base); $st->login('estudiante@fet.edu.co');
    $s[]=['Un estudiante no puede descargar el respaldo',$st->req('backup.php',['csrf'=>$st->csrf('dashboard.php')])['code']===403];
    $s[]=['La auditoría registró el respaldo',(bool)$pdo->query("SELECT COUNT(*) FROM audit_events WHERE action='backup' AND created_at>=NOW()-INTERVAL 5 MINUTE")->fetchColumn()];
    // Desbloqueo: cinco contraseñas incorrectas bloquean la cuenta y el administrador la desbloquea.
    $j=new Client($base); for($k=0;$k<5;$k++) $j->login('juan.sabogal@fet.edu.co','claveIncorrecta');
    $s[]=['Cinco intentos fallidos bloquean la cuenta',str_contains($j->login('juan.sabogal@fet.edu.co')['body'],'bloqueada')];
    $jid=(int)$pdo->query("SELECT id FROM users WHERE email='juan.sabogal@fet.edu.co'")->fetchColumn();
    $s[]=['Usuarios muestra la cuenta como bloqueada con «Desbloquear»',str_contains($a->req('users.php')['body'],'value="unlock"')];
    $a->req('users.php',['csrf'=>$a->csrf('users.php'),'action'=>'unlock','id'=>$jid]);
    $res=$j->login('juan.sabogal@fet.edu.co');
    $s[]=['El administrador la desbloquea y el usuario vuelve a entrar',str_contains($res['url'],'dashboard.php')];
    $s[]=['La auditoría registró el desbloqueo',(bool)$pdo->query("SELECT COUNT(*) FROM audit_events WHERE action='unlock' AND entity_id=$jid AND created_at>=NOW()-INTERVAL 5 MINUTE")->fetchColumn()];
    $pdo->exec("DELETE FROM notifications WHERE user_id=$jid AND title='Tu cuenta fue desbloqueada' AND created_at>=NOW()-INTERVAL 10 MINUTE");
    return $s;
});

// Limpieza de los datos de prueba
$files=array_merge(
    $pdo->query("SELECT d.file FROM internship_documents d JOIN internships i ON i.id=d.internship_id WHERE i.title LIKE '".TAG."%'")->fetchAll(PDO::FETCH_COLUMN),
    $pdo->query("SELECT a.cv_file FROM applications a JOIN opportunities o ON o.id=a.opportunity_id WHERE o.title LIKE '".TAG."%' AND a.cv_file IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN));
foreach($files as $f){ if(preg_match('/^[a-f0-9]{24}\.[a-z0-9]{2,4}$/',(string)$f)) @unlink(__DIR__.'/../storage/documents/'.$f); }
$pdo->exec("DELETE FROM notifications WHERE link IN (SELECT CONCAT('internships.php?id=',id) FROM internships WHERE title LIKE '".TAG."%')");
$pdo->exec("DELETE FROM internships WHERE title LIKE '".TAG."%'");
$pdo->exec("DELETE FROM notifications WHERE body LIKE '%".TAG."%' OR title LIKE '%".TAG."%'");
$ids=$pdo->query("SELECT id FROM opportunities WHERE title LIKE '".TAG."%'")->fetchAll(PDO::FETCH_COLUMN);
if($ids){ $in=implode(',',array_map('intval',$ids)); $pdo->exec("DELETE FROM notifications WHERE title LIKE '%postulación%' AND created_at>=NOW()-INTERVAL 10 MINUTE"); $pdo->exec("DELETE FROM opportunities WHERE id IN ($in)"); }
$pdo->exec("DELETE FROM notifications WHERE title IN ('Nueva alianza empresarial','Más cupos de pasantía','Nueva pasantía por asignar') AND created_at>=NOW()-INTERVAL 10 MINUTE");
$pdo->exec("DELETE FROM users WHERE email='".CO_EMAIL."' AND role='company'");

$passed=count(array_filter($flows,fn($f)=>$f['resultado']==='Pasa'));
$result=['ejecutado'=>date('Y-m-d H:i:s'),'duracion_s'=>round(microtime(true)-$t0,1),'base'=>$base,'pasan'=>$passed,'total'=>count($flows),'flujos'=>$flows];
@mkdir(__DIR__.'/../storage/logs',0775,true);
file_put_contents(__DIR__.'/../storage/logs/smoke-results.json',json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo "\nResultado: $passed de ".count($flows)." flujos pasan.\n";
exit($passed===count($flows)?0:1);
