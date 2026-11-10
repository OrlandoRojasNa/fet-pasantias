<?php
declare(strict_types=1);
/**
 * Datos de ejemplo para la demostración de Pasantías FET.
 *
 * Simula la información que en producción llegaría de la base de datos de la universidad
 * (estudiantes y docentes) y registra alianzas con empresas de la región, cada una con su
 * cuenta de acceso y sus publicaciones. Todos los nombres, documentos y empresas son ficticios.
 *
 * Se puede ejecutar varias veces: no duplica registros. Ejecutar después de install.php:
 *   C:\xampp\php\php.exe database\seed_universidad.php
 * Clave de todas las cuentas creadas: FET2026*
 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$config=require __DIR__.'/../config/database.php';
if(strtolower((string)getenv('APP_ENV'))==='production'){ fwrite(STDERR,"No se cargan datos de ejemplo en producción.\n"); exit(1); }

$pdo=new PDO("mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
    $config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$hash=password_hash('FET2026*',PASSWORD_DEFAULT);

$find=$pdo->prepare('SELECT id FROM users WHERE identification=? OR email=? LIMIT 1');
$ins=$pdo->prepare('INSERT INTO users(identification,full_name,email,password_hash,role,program,status) VALUES(?,?,?,?,?,?,"active")');
$cuenta=function(string $id,string $nombre,string $correo,string $rol,string $programa) use($find,$ins,$hash,$pdo): array {
    $find->execute([$id,$correo]);
    if($u=$find->fetchColumn()) return [(int)$u,false];
    $ins->execute([$id,$nombre,$correo,$hash,$rol,$programa]);
    return [(int)$pdo->lastInsertId(),true];
};
$nuevos=['leader'=>0,'student'=>0,'company'=>0,'alliance'=>0,'opportunity'=>0];

// Docentes líderes de pasantías: la plataforma admite como máximo tres.
// docente@fet.edu.co y hernan.patino@fet.edu.co los crea install.php; aquí se completa el tercero.
$docentes=[
    ['1075234871','Claudia Patricia Cuéllar Ramírez','claudia.cuellar@fet.edu.co','Coordinación de Prácticas Empresariales'],
];
$lideres=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='leader' AND status='active'")->fetchColumn();
foreach($docentes as [$i,$n,$m,$p]){
    $find->execute([$i,$m]);
    if($find->fetchColumn()) continue;
    if($lideres>=3){ echo "Ya hay 3 docentes líderes activos: no se crea $m.\n"; continue; }
    $cuenta($i,$n,$m,'leader',$p); $lideres++; $nuevos['leader']++;
}

// Estudiantes, como llegarían del sistema académico de la universidad.
$estudiantes=[
    ['1075301122','Valentina Andrea Perdomo Charry','valentina.perdomo@fet.edu.co','Ingeniería de Software'],
    ['1075302458','Santiago José Trujillo Vargas','santiago.trujillo@fet.edu.co','Ingeniería de Software'],
    ['1075303791','Laura Camila Rojas Medina','laura.rojas@fet.edu.co','Ingeniería de Software'],
    ['1075304216','Andrés Felipe Cabrera Losada','andres.cabrera@fet.edu.co','Ingeniería de Software'],
    ['1075305633','María José Polanía Cortés','maria.polania@fet.edu.co','Tecnología en Desarrollo de Software'],
    ['1075306947','Juan David Ospina Quintero','juan.ospina@fet.edu.co','Tecnología en Desarrollo de Software'],
    ['1075307382','Daniela Sofía Artunduaga Paredes','daniela.artunduaga@fet.edu.co','Tecnología en Desarrollo de Software'],
    ['1075308814','Cristian Camilo Méndez Ramos','cristian.mendez@fet.edu.co','Ingeniería Eléctrica'],
    ['1075309265','Natalia Fernanda Guzmán Rivera','natalia.guzman@fet.edu.co','Ingeniería Eléctrica'],
    ['1075310579','Sebastián Alejandro Calderón Díaz','sebastian.calderon@fet.edu.co','Ingeniería Eléctrica'],
    ['1075311903','Paula Alejandra Sánchez Bonilla','paula.sanchez@fet.edu.co','Ingeniería Ambiental'],
    ['1075312346','Diego Armando Plazas Ortiz','diego.plazas@fet.edu.co','Ingeniería Ambiental'],
    ['1075313688','Carolina Isabel Motta Castro','carolina.motta@fet.edu.co','Ingeniería Ambiental'],
    ['1075314027','Julián Esteban Vargas Penagos','julian.vargas@fet.edu.co','Administración de Empresas'],
    ['1075315461','Manuela Ximena Lozada Tovar','manuela.lozada@fet.edu.co','Administración de Empresas'],
    ['1075316892','Kevin Alexis Muñoz Silva','kevin.munoz@fet.edu.co','Administración de Empresas'],
    ['1075317234','Angie Lorena Quintero Falla','angie.quintero@fet.edu.co','Contaduría Pública'],
    ['1075318675','Brayan Stiven Cerquera Polo','brayan.cerquera@fet.edu.co','Contaduría Pública'],
    ['1075319018','Sara Lucía Andrade Montealegre','sara.andrade@fet.edu.co','Contaduría Pública'],
    ['1075320451','Felipe Andrés Collazos Rengifo','felipe.collazos@fet.edu.co','Ingeniería de Software'],
];
foreach($estudiantes as [$i,$n,$m,$p]){ [, $nuevo]=$cuenta($i,$n,$m,'student',$p); if($nuevo) $nuevos['student']++; }

// Alianzas con empresas de la región. La cuenta de la empresa entra con su NIT.
$adminId=(int)$pdo->query("SELECT id FROM users WHERE role='admin' AND status='active' ORDER BY id LIMIT 1")->fetchColumn();
$alianzas=[
    // NIT, razón social, correo, sector, contacto, teléfono, código, cupos, meses de vigencia, estado
    ['901450112','Huila Digital Soluciones S.A.S.','talento@huiladigital.co','Desarrollo de software','Ricardo Andrés Losada','3104567812','FET-CONV-2026-011',4,12,'active'],
    ['901450233','Redes y Energía del Opita S.A.S.','practicas@redesopita.co','Energía y mantenimiento eléctrico','Gloria Inés Perdomo','3157892340','FET-CONV-2026-012',3,18,'active'],
    ['901450354','AgroSur Café del Huila S.A.S.','gestionhumana@agrosurcafe.co','Agroindustria','Fabián Ernesto Charry','3209875461','FET-CONV-2026-013',3,12,'active'],
    ['901450475','Contadores Asociados del Sur Ltda.','seleccion@contasur.co','Servicios contables y tributarios','Martha Lucía Vargas','3112348790','FET-CONV-2026-014',2,12,'active'],
    ['901450596','EcoAmbiental Neiva S.A.S.','proyectos@ecoambientalneiva.co','Consultoría ambiental','Hernando José Tovar','3186543210','FET-CONV-2026-015',2,24,'active'],
    ['901450617','Centro Médico Surandino S.A.S.','sistemas@surandino.co','Salud, área de tecnología','Diana Marcela Rivera','3014567098','FET-CONV-2026-016',2,12,'active'],
    ['901450738','Comercializadora Surcolombiana S.A.S.','rrhh@comersur.co','Comercio y logística','Óscar Mauricio Cortés','3123456781','FET-CONV-2025-009',1,12,'suspended'],
];
$insAli=$pdo->prepare("INSERT INTO alliances(company_id,name,sector,contact_name,contact_email,contact_phone,agreement_code,slots,starts_on,ends_on,status,notes,created_by)
    VALUES(?,?,?,?,?,?,?,?,DATE_SUB(CURDATE(),INTERVAL 1 MONTH),DATE_ADD(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),INTERVAL ? MONTH),?,?,?)");
$empresas=[];
foreach($alianzas as [$nit,$razon,$correo,$sector,$contacto,$tel,$codigo,$cupos,$meses,$estado]){
    [$cid,$nuevo]=$cuenta($nit,$razon,$correo,'company',$razon);
    if($nuevo) $nuevos['company']++;
    $empresas[$nit]=['id'=>$cid,'nombre'=>$razon,'estado'=>$estado];
    if(!$pdo->query("SELECT COUNT(*) FROM alliances WHERE company_id=$cid")->fetchColumn()){
        $nota=$estado==='suspended'?'Convenio suspendido mientras se renueva la póliza de la empresa.':'Convenio marco de prácticas y pasantías.';
        $insAli->execute([$cid,$razon,$sector,$contacto,$correo,$tel,$codigo,$cupos,$meses,$estado,$nota,$adminId?:null]);
        $nuevos['alliance']++;
    }
}

// Publicaciones verificadas de las empresas con alianza vigente.
$publicaciones=[
    ['901450112','Pasantía en desarrollo web con PHP y MySQL','practica','hibrida','Neiva','Apoyo al equipo de desarrollo en el mantenimiento de aplicaciones web para clientes del sector público y privado.','Estudiante de Ingeniería de Software o Tecnología en Desarrollo de Software, conocimientos de PHP, SQL y Git.',25],
    ['901450112','Desarrollador junior (oferta laboral)','empleo','remota','Neiva','Vacante para egresados o estudiantes de último semestre para el área de soporte y desarrollo.','Hoja de vida en PDF. Experiencia con bases de datos relacionales.',30],
    ['901450233','Pasantía en mantenimiento de redes eléctricas','practica','presencial','Neiva','Acompañamiento a cuadrillas técnicas en inspección y mantenimiento preventivo de redes de media y baja tensión.','Estudiante de Ingeniería Eléctrica, curso de trabajo seguro en alturas vigente.',20],
    ['901450354','Pasantía en gestión ambiental de beneficio de café','practica','presencial','Pitalito','Apoyo al programa de manejo de aguas residuales y residuos del beneficio de café.','Estudiante de Ingeniería Ambiental.',28],
    ['901450475','Pasantía en contabilidad e impuestos','practica','presencial','Neiva','Apoyo en conciliaciones bancarias, causación y preparación de declaraciones tributarias.','Estudiante de Contaduría Pública con manejo de hojas de cálculo.',22],
    ['901450596','Pasantía en estudios de impacto ambiental','practica','hibrida','Neiva','Participación en la elaboración de estudios de impacto ambiental y planes de manejo.','Estudiante de Ingeniería Ambiental, manejo básico de SIG.',35],
    ['901450617','Pasantía en soporte de sistemas de información','practica','presencial','Neiva','Soporte a usuarios y mantenimiento de equipos y del sistema de historias clínicas.','Estudiante de Ingeniería de Software o Tecnología en Desarrollo de Software.',18],
];
$insOpp=$pdo->prepare("INSERT INTO opportunities(title,organization,type,description,requirements,location,modality,closes_on,status,verified_at,created_by)
    VALUES(?,?,?,?,?,?,?,DATE_ADD(CURDATE(),INTERVAL ? DAY),'published',NOW(),?)");
$existe=$pdo->prepare('SELECT COUNT(*) FROM opportunities WHERE title=? AND created_by=?');
foreach($publicaciones as [$nit,$titulo,$tipo,$modalidad,$lugar,$desc,$req,$dias]){
    $e=$empresas[$nit]??null;
    if(!$e||$e['estado']!=='active') continue;
    $existe->execute([$titulo,$e['id']]);
    if($existe->fetchColumn()) continue;
    $insOpp->execute([$titulo,$e['nombre'],$tipo,$desc,$req,$lugar,$modalidad,$dias,$e['id']]);
    $nuevos['opportunity']++;
}

$total=fn(string $sql)=>(int)$pdo->query($sql)->fetchColumn();
echo "Datos de ejemplo cargados.\n";
printf("Nuevos: %d docentes líderes, %d estudiantes, %d empresas, %d alianzas, %d publicaciones.\n",
    $nuevos['leader'],$nuevos['student'],$nuevos['company'],$nuevos['alliance'],$nuevos['opportunity']);
printf("En la base: %d docentes líderes, %d estudiantes, %d empresas, %d alianzas vigentes, %d publicaciones activas.\n",
    $total("SELECT COUNT(*) FROM users WHERE role='leader' AND status='active'"),
    $total("SELECT COUNT(*) FROM users WHERE role='student'"),
    $total("SELECT COUNT(*) FROM users WHERE role='company'"),
    $total("SELECT COUNT(*) FROM alliances WHERE status='active'"),
    $total("SELECT COUNT(*) FROM opportunities WHERE status='published'"));
echo "Clave de las cuentas nuevas: FET2026* (las empresas ingresan con su NIT o su correo).\n";
