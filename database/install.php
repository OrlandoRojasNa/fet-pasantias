<?php
declare(strict_types=1);
/**
 * Instalador y actualizador de la Intranet Privada FET.
 * Se puede ejecutar varias veces: crea lo que falta, migra columnas nuevas y
 * carga las Actas 1 a 6 sin duplicar registros.
 *   C:\xampp\php\php.exe C:\xampp\htdocs\PruebaFet\database\install.php
 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$config=require __DIR__.'/../config/database.php';
try{
 $pdo=new PDO("mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}",$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $pdo->exec(file_get_contents(__DIR__.'/schema.sql'));
 $pdo=new PDO("mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);

 // Migraciones idempotentes para bases creadas con versiones anteriores.
 $migrations=[
  "ALTER TABLE users MODIFY role ENUM('admin','manager','leader','student','company') NOT NULL DEFAULT 'student'",
  "ALTER TABLE minutes ADD COLUMN IF NOT EXISTS modality VARCHAR(150) NULL AFTER meeting_on",
  "ALTER TABLE minutes ADD COLUMN IF NOT EXISTS starts_at TIME NULL AFTER modality",
  "ALTER TABLE minutes ADD COLUMN IF NOT EXISTS ends_at TIME NULL AFTER starts_at",
  "ALTER TABLE minutes ADD COLUMN IF NOT EXISTS objectives TEXT NULL AFTER attendees",
  "ALTER TABLE minutes ADD COLUMN IF NOT EXISTS development TEXT NULL AFTER summary",
  "ALTER TABLE minutes ADD COLUMN IF NOT EXISTS conclusion TEXT NULL AFTER development",
  "ALTER TABLE minutes ADD COLUMN IF NOT EXISTS work_model TEXT NULL AFTER conclusion",
  "ALTER TABLE minutes ADD COLUMN IF NOT EXISTS next_agenda TEXT NULL AFTER work_model",
  "ALTER TABLE minutes ADD COLUMN IF NOT EXISTS observations TEXT NULL AFTER next_agenda",
  "ALTER TABLE commitments MODIFY description TEXT NOT NULL",
  "ALTER TABLE commitments ADD COLUMN IF NOT EXISTS expected_evidence VARCHAR(255) NULL AFTER due_on",
  "ALTER TABLE commitments ADD COLUMN IF NOT EXISTS evidence TEXT NULL AFTER status",
  "ALTER TABLE commitments ADD COLUMN IF NOT EXISTS evidence_file VARCHAR(60) NULL AFTER evidence",
  "ALTER TABLE commitments ADD COLUMN IF NOT EXISTS evidence_name VARCHAR(180) NULL AFTER evidence_file",
  "ALTER TABLE commitments ADD COLUMN IF NOT EXISTS completed_at DATETIME NULL AFTER evidence_name",
  "ALTER TABLE milestones ADD COLUMN IF NOT EXISTS evidence_file VARCHAR(60) NULL AFTER evidence",
  "ALTER TABLE milestones ADD COLUMN IF NOT EXISTS evidence_name VARCHAR(180) NULL AFTER evidence_file",
  "ALTER TABLE internships ADD COLUMN IF NOT EXISTS company_rating TINYINT UNSIGNED NULL AFTER status",
  "ALTER TABLE internships ADD COLUMN IF NOT EXISTS company_evaluation TEXT NULL AFTER company_rating",
  "ALTER TABLE internships ADD COLUMN IF NOT EXISTS company_evaluated_at DATETIME NULL AFTER company_evaluation",
  "ALTER TABLE internships ADD COLUMN IF NOT EXISTS final_result ENUM('aprobada','reprobada') NULL AFTER company_evaluated_at",
  "ALTER TABLE internships ADD COLUMN IF NOT EXISTS final_justification TEXT NULL AFTER final_result",
  "ALTER TABLE internships ADD COLUMN IF NOT EXISTS final_by INT UNSIGNED NULL AFTER final_justification",
  "ALTER TABLE internships ADD COLUMN IF NOT EXISTS final_at DATETIME NULL AFTER final_by",
  "ALTER TABLE internship_documents ADD COLUMN IF NOT EXISTS grade DECIMAL(2,1) NULL AFTER status",
  "ALTER TABLE internship_documents ADD COLUMN IF NOT EXISTS graded_by INT UNSIGNED NULL AFTER grade",
  "ALTER TABLE internship_documents ADD COLUMN IF NOT EXISTS graded_at DATETIME NULL AFTER graded_by",
  "ALTER TABLE document_comments ADD COLUMN IF NOT EXISTS grade DECIMAL(2,1) NULL AFTER decision",
 ];
 foreach($migrations as $sql) $pdo->exec($sql);

 $hash=password_hash('FET2026*',PASSWORD_DEFAULT);
 if((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()===0){
  $users=[
   ['1000000001','Administrador FET','admin@fet.edu.co','admin','Tecnología'],
   ['1000000002','Gestor Institucional FET','gestor@fet.edu.co','manager','Gestión institucional'],
   ['1000000003','Miguel Antonio Urbano Silva','docente@fet.edu.co','leader','Ingeniería de Software'],
   ['1076502055','Estudiante Demostración','estudiante@fet.edu.co','student','Ingeniería de Software'],
   ['1000000004','Hernán Felipe Patiño Rico','hernan.patino@fet.edu.co','leader','Ingeniería de Software'],
   ['1000000005','Orlando Rojas Narváez','orlando.rojas@fet.edu.co','student','Ingeniería de Software'],
   ['1000000006','Juan José Sabogal García','juan.sabogal@fet.edu.co','student','Ingeniería de Software'],
  ];
  $s=$pdo->prepare('INSERT INTO users(identification,full_name,email,password_hash,role,program) VALUES(?,?,?,?,?,?)');
  foreach($users as [$i,$n,$m,$r,$p]) $s->execute([$i,$n,$m,$hash,$r,$p]);
  $pdo->exec("INSERT INTO opportunities(title,organization,type,description,requirements,location,modality,closes_on,status,verified_at,created_by) VALUES
  ('Práctica en desarrollo web','Laboratorio Digital Huila','practica','Apoyo al desarrollo de soluciones web para proyectos regionales.','Conocimientos de PHP, bases de datos y trabajo en equipo.','Neiva','hibrida',DATE_ADD(CURDATE(),INTERVAL 30 DAY),'published',NOW(),2),
  ('Semillero de innovación aplicada','FET · Programa de Ingeniería de Software','formacion','Convocatoria para fortalecer proyectos con impacto académico y regional.','Disponibilidad de cuatro horas semanales.','Campus FET, Rivera (Huila)','presencial',DATE_ADD(CURDATE(),INTERVAL 45 DAY),'published',NOW(),2),
  ('Desarrollador junior','Empresa aliada Demo','empleo','Vacante de entrada para egresados con interés en aplicaciones empresariales.','Portafolio y fundamentos de desarrollo web.','Neiva','hibrida',DATE_ADD(CURDATE(),INTERVAL 20 DAY),'published',NOW(),2)");
  $pdo->exec("INSERT INTO projects(name,summary,ods,leader_id,status,starts_on,ends_on) VALUES ('Intranet Privada FET','Plataforma para oportunidades, innovación y alianzas institucionales.',9,3,'active','2026-09-07','2026-11-30')");
  $pdo->exec("INSERT INTO project_members(project_id,user_id,responsibility) VALUES (1,3,'Docente gestor'),(1,5,'Backend'),(1,6,'Frontend'),(1,7,'Pruebas y auditoría')");
  $pdo->exec("INSERT INTO milestones(project_id,title,due_on,status,evidence) VALUES (1,'Autenticación y permisos','2026-09-28','completed','Módulo funcional'),(1,'Módulos ODS 8, 9 y 17','2026-10-12','completed','Versión integrada'),(1,'Pruebas de aceptación','2026-11-09','in_progress','Calidad y pruebas')");
  $pdo->exec("INSERT INTO partners(name,sector,contact_name,contact_email,status) VALUES ('Cámara de Comercio del Huila','Desarrollo empresarial','Contacto demostración','alianzas@example.test','active'),('Centro de Innovación Regional','Tecnología e innovación','Contacto demostración','innovacion@example.test','active')");
  $pdo->exec("INSERT INTO agreements(partner_id,title,objective,responsible_id,starts_on,ends_on,status,evidence_reference) VALUES (1,'Cooperación para oportunidades profesionales','Fortalecer la conexión entre formación y sector productivo.',2,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 365 DAY),'active','CONV-DEMO-001')");
 }

 // Cuentas de demostración de los tres accesos principales (siempre activas y con la clave conocida).
 $demo=[
  ['1000000001','Administrador FET','admin@fet.edu.co','admin','Tecnología'],
  ['1076502055','Estudiante Demostración','estudiante@fet.edu.co','student','Ingeniería de Software'],
  ['900123456','Empresa de Pasantías Demo','empresa@fet.edu.co','company','Empresa Aliada de Pasantías'],
 ];
 $find=$pdo->prepare('SELECT id FROM users WHERE identification=? OR email=? LIMIT 1');
 $upd=$pdo->prepare('UPDATE users SET identification=?,full_name=?,email=?,password_hash=?,role=?,program=?,status="active",failed_attempts=0,locked_until=NULL WHERE id=?');
 $ins=$pdo->prepare('INSERT INTO users(identification,full_name,email,password_hash,role,program,status) VALUES(?,?,?,?,?,?,"active")');
 foreach($demo as [$i,$n,$m,$r,$p]){ $find->execute([$i,$m]); $id=$find->fetchColumn(); $id?$upd->execute([$i,$n,$m,$hash,$r,$p,$id]):$ins->execute([$i,$n,$m,$hash,$r,$p]); }

 // Las convocatorias de demostración se mantienen abiertas para que el piloto siempre tenga datos vigentes.
 $pdo->exec("UPDATE opportunities SET closes_on=DATE_ADD(CURDATE(),INTERVAL 30 DAY) WHERE closes_on<CURDATE() AND status='published' AND title IN ('Práctica en desarrollo web','Semillero de innovación aplicada','Desarrollador junior')");

 // Vacantes de la empresa de demostración para que su panel muestre el flujo completo.
 $companyId=(int)$pdo->query("SELECT id FROM users WHERE email='empresa@fet.edu.co'")->fetchColumn();
 $studentId=(int)$pdo->query("SELECT id FROM users WHERE email='estudiante@fet.edu.co'")->fetchColumn();
 if($companyId&&!(int)$pdo->query("SELECT COUNT(*) FROM opportunities WHERE created_by=$companyId")->fetchColumn()){
  $o=$pdo->prepare("INSERT INTO opportunities(title,organization,type,description,requirements,location,modality,closes_on,status,verified_at,created_by) VALUES(?,?,?,?,?,?,?,DATE_ADD(CURDATE(),INTERVAL ? DAY),?,?,?)");
  $o->execute(['Pasantía en soporte de sistemas','Empresa Aliada de Pasantías','practica','Acompañamiento en soporte técnico, inventario de equipos y mesa de ayuda.','Estudiante de últimos semestres con manejo básico de redes.','Neiva','presencial',40,'published',date('Y-m-d H:i:s'),$companyId]);
  $pub=(int)$pdo->lastInsertId();
  $o->execute(['Pasantía en análisis de datos','Empresa Aliada de Pasantías','practica','Apoyo en tableros e informes con datos de ventas y producción.','Conocimientos de Excel y SQL básico.','Rivera, Huila','hibrida',50,'review',null,$companyId]);
  if($studentId) $pdo->prepare("INSERT IGNORE INTO applications(opportunity_id,user_id,motivation,status) VALUES(?,?,?,'review')")->execute([$pub,$studentId,'Quiero fortalecer mi experiencia en soporte y redes mientras termino la carrera.']);
 }

 // Pasantía de demostración: estudiante, empresa evaluadora y docente que valida.
 $teacherId=(int)$pdo->query("SELECT id FROM users WHERE email='docente@fet.edu.co'")->fetchColumn();
 if($studentId&&$companyId&&!(int)$pdo->query('SELECT COUNT(*) FROM internships')->fetchColumn()){
  $pdo->prepare("INSERT INTO internships(student_id,company_id,teacher_id,title,organization,starts_on,ends_on,status) VALUES(?,?,?,?,?,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 120 DAY),'active')")->execute([$studentId,$companyId,$teacherId?:null,'Pasantía en soporte de sistemas','Empresa Aliada de Pasantías']);
 }

 // Responsables de los compromisos de las actas.
 $byEmail=fn(string $e)=>(int)($pdo->query('SELECT id FROM users WHERE email='.$pdo->quote($e))->fetchColumn());
 $people=['juan'=>$byEmail('juan.sabogal@fet.edu.co'),'hernan'=>$byEmail('hernan.patino@fet.edu.co'),'orlando'=>$byEmail('orlando.rojas@fet.edu.co'),'docente'=>$byEmail('docente@fet.edu.co')];
 $creator=$people['docente']?:1;

 // Actas 1 a 6: se insertan si faltan, o se completan si fueron creadas sin la estructura del Formato 1.
 // Solo la primera ejecución completa actas antiguas; después nunca modifica ni borra actas existentes.
 $actas=require __DIR__.'/seed_actas.php';
 $loaded=0;
 $seeded=(bool)$pdo->query("SELECT COUNT(*) FROM audit_events WHERE action='seed' AND entity_type='minutes'")->fetchColumn();
 foreach($actas as $a){
  $row=$pdo->query('SELECT id,development FROM minutes WHERE number='.$pdo->quote($a['number']))->fetch();
  if($row&&($seeded||!empty($row['development']))) continue;
  $fields=[$a['title'],$a['meeting_on'],$a['modality'],$a['starts_at'],$a['ends_at'],$a['attendees'],$a['objectives'],$a['summary'],$a['development'],$a['conclusion'],$a['work_model'],$a['next_agenda'],$a['observations'],$a['status']];
  if($row){
   $pdo->prepare('UPDATE minutes SET title=?,meeting_on=?,modality=?,starts_at=?,ends_at=?,attendees=?,objectives=?,summary=?,development=?,conclusion=?,work_model=?,next_agenda=?,observations=?,status=? WHERE id=?')->execute([...$fields,$row['id']]);
   $mid=(int)$row['id'];
   $pdo->prepare('DELETE FROM commitments WHERE minute_id=?')->execute([$mid]);
  }else{
   $pdo->prepare('INSERT INTO minutes(title,meeting_on,modality,starts_at,ends_at,attendees,objectives,summary,development,conclusion,work_model,next_agenda,observations,status,number,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([...$fields,$a['number'],$creator]);
   $mid=(int)$pdo->lastInsertId();
  }
  $c=$pdo->prepare('INSERT INTO commitments(minute_id,description,responsible_id,due_on,expected_evidence,status,evidence,completed_at) VALUES(?,?,?,?,?,?,?,?)');
  foreach($a['commitments'] as [$who,$desc,$due,$expected,$status,$evidence]){
   $c->execute([$mid,$desc,$people[$who]?:$creator,$due,$expected,$status,$evidence,$status==='completed'?$due.' 18:00:00':null]);
  }
  $loaded++;
 }
 if(!$seeded) $pdo->exec("INSERT INTO audit_events(action,entity_type,details,ip_address) VALUES('seed','minutes','Actas 1 a 6 cargadas','cli')");

 // Hallazgos registrados durante las pruebas (Acta 6: «lista de hallazgos con prioridad y estado»).
 if((int)$pdo->query('SELECT COUNT(*) FROM findings')->fetchColumn()===0){
  $f=$pdo->prepare('INSERT INTO findings(title,module,description,priority,status,reported_by,assigned_to,resolution) VALUES(?,?,?,?,?,?,?,?)');
  $list=[
   ['La empresa podía ver postulaciones de otras empresas','Oportunidades','Las consultas no filtraban por la empresa propietaria.','alta','verified','juan','hernan','Las consultas y cambios de estado validan created_by en el servidor.'],
   ['El estado del compromiso no mostraba el valor actual','Actas y compromisos','El selector siempre aparecía en «Pendiente».','media','verified','juan','orlando','El selector marca el estado guardado y solo acepta valores permitidos.'],
   ['Los vencidos se calculaban después de mostrar la lista','Actas y compromisos','El estado «Vencido» aparecía solo al recargar la página.','media','verified','juan','hernan','La actualización de vencidos se ejecuta antes de consultar.'],
   ['La fecha del tablero aparecía en inglés','Tablero','strftime() está obsoleta en PHP 8.2 y no tenía configuración regional.','baja','verified','juan','hernan','Se reemplazó por una función propia con días y meses en español.'],
   ['Tablas y formularios desbordaban en celular','Interfaz','Algunas vistas superaban el ancho de la pantalla.','media','verified','juan','orlando','Tablas con desplazamiento horizontal y formularios en una columna.'],
   ['Faltaba respaldo verificable de la base de datos','Administración','No existía una forma de descargar el respaldo desde la aplicación.','alta','verified','juan','hernan','Respaldo SQL descargable por el administrador en Calidad y pruebas.'],
  ];
  foreach($list as [$t,$m,$d,$p,$s,$r,$as,$res]) $f->execute([$t,$m,$d,$p,$s,$people[$r]?:null,$people[$as]?:null,$res]);
 }

 // Avisos de bienvenida para que el tablero de avisos no arranque vacío.
 if((int)$pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn()===0){
  $n=$pdo->prepare('INSERT INTO notifications(user_id,title,body,link) SELECT id,?,?,? FROM users WHERE status="active"');
  $n->execute(['Bienvenido a la Intranet Privada FET','Pulsa el botón de ayuda (?) para recorrer la plataforma paso a paso.','guide.php']);
 }

 echo "Intranet Privada FET instalada y actualizada correctamente.\n";
 echo "Actas cargadas o completadas en esta ejecución: $loaded\n";
 echo "Cuentas: admin@fet.edu.co, gestor@fet.edu.co, docente@fet.edu.co, estudiante@fet.edu.co, empresa@fet.edu.co | Clave: FET2026*\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
