<?php
declare(strict_types=1);
/**
 * Instalador y actualizador de Pasantías FET.
 * Se puede ejecutar varias veces: crea lo que falta, migra columnas nuevas y
 * carga datos de demostración sin duplicar registros.
 *   C:\xampp\php\php.exe C:\xampp\htdocs\PruebaFet\database\install.php
 * Las tablas de módulos retirados (actas, proyectos, hallazgos, convenios) no se borran
 * en instalaciones anteriores: la aplicación ya no las usa.
 */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$config=require __DIR__.'/../config/database.php';
try{
 $pdo=new PDO("mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}",$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $pdo->exec(file_get_contents(__DIR__.'/schema.sql'));
 $pdo=new PDO("mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);

 // Migraciones idempotentes para bases creadas con versiones anteriores.
 $migrations=[
  // El perfil «Gestor institucional» se retiró: sus cuentas pasan a administrador.
  "ALTER TABLE users MODIFY role ENUM('admin','manager','leader','student','company') NOT NULL DEFAULT 'student'",
  "UPDATE users SET role='admin' WHERE role='manager'",
  "ALTER TABLE users MODIFY role ENUM('admin','leader','student','company') NOT NULL DEFAULT 'student'",
  "ALTER TABLE applications ADD COLUMN IF NOT EXISTS cv_file VARCHAR(60) NULL AFTER motivation",
  "ALTER TABLE applications ADD COLUMN IF NOT EXISTS cv_name VARCHAR(180) NULL AFTER cv_file",
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
  // Las convocatorias de «formación» ya no forman parte de la plataforma.
  "UPDATE opportunities SET status='closed' WHERE type='formacion' AND status<>'closed'",
 ];
 foreach($migrations as $sql) $pdo->exec($sql);

 $hash=password_hash('FET2026*',PASSWORD_DEFAULT);
 // Cuentas de demostración (siempre activas y con la clave conocida): una por perfil, más el equipo del proyecto.
 $demo=[
  ['1000000001','Administrador FET','admin@fet.edu.co','admin','Tecnología'],
  ['1000000003','Miguel Antonio Urbano Silva','docente@fet.edu.co','leader','Ingeniería de Software'],
  ['1076502055','Estudiante Demostración','estudiante@fet.edu.co','student','Ingeniería de Software'],
  ['900123456','Empresa de Pasantías Demo','empresa@fet.edu.co','company','Empresa Aliada de Pasantías'],
 ];
 $team=[
  ['1000000004','Hernán Felipe Patiño Rico','hernan.patino@fet.edu.co','leader','Ingeniería de Software'],
  ['1000000005','Orlando Rojas Narváez','orlando.rojas@fet.edu.co','student','Ingeniería de Software'],
  ['1000000006','Juan José Sabogal García','juan.sabogal@fet.edu.co','student','Ingeniería de Software'],
 ];
 $find=$pdo->prepare('SELECT id FROM users WHERE identification=? OR email=? LIMIT 1');
 $upd=$pdo->prepare('UPDATE users SET identification=?,full_name=?,email=?,password_hash=?,role=?,program=?,status="active",failed_attempts=0,locked_until=NULL WHERE id=?');
 $ins=$pdo->prepare('INSERT INTO users(identification,full_name,email,password_hash,role,program,status) VALUES(?,?,?,?,?,?,"active")');
 foreach($demo as [$i,$n,$m,$r,$p]){ $find->execute([$i,$m]); $id=$find->fetchColumn(); $id?$upd->execute([$i,$n,$m,$hash,$r,$p,$id]):$ins->execute([$i,$n,$m,$hash,$r,$p]); }
 foreach($team as [$i,$n,$m,$r,$p]){ $find->execute([$i,$m]); if(!$find->fetchColumn()) $ins->execute([$i,$n,$m,$hash,$r,$p]); }
 $byEmail=fn(string $e)=>(int)$pdo->query('SELECT id FROM users WHERE email='.$pdo->quote($e))->fetchColumn();
 $adminId=$byEmail('admin@fet.edu.co'); $companyId=$byEmail('empresa@fet.edu.co'); $studentId=$byEmail('estudiante@fet.edu.co'); $teacherId=$byEmail('docente@fet.edu.co');

 // Alianza de la empresa de demostración con 3 cupos de pasantía.
 if(!$pdo->query("SELECT COUNT(*) FROM alliances WHERE company_id=$companyId")->fetchColumn()){
  $pdo->prepare("INSERT INTO alliances(company_id,name,sector,contact_name,contact_email,agreement_code,slots,starts_on,ends_on,status,notes,created_by) VALUES(?,?,?,?,?,?,?,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 1 YEAR),'active',?,?)")
   ->execute([$companyId,'Empresa Aliada de Pasantías','Tecnología y servicios','Empresa de Pasantías Demo','empresa@fet.edu.co','CONV-DEMO-001',3,'Alianza de demostración creada por el instalador.',$adminId]);
 }

 // Publicaciones de la empresa de demostración: una pasantía publicada, otra en verificación y una oferta laboral.
 if(!(int)$pdo->query("SELECT COUNT(*) FROM opportunities WHERE created_by=$companyId")->fetchColumn()){
  $o=$pdo->prepare("INSERT INTO opportunities(title,organization,type,description,requirements,location,modality,closes_on,status,verified_at,created_by) VALUES(?,?,?,?,?,?,?,DATE_ADD(CURDATE(),INTERVAL ? DAY),?,?,?)");
  $o->execute(['Pasantía en soporte de sistemas','Empresa Aliada de Pasantías','practica','Acompañamiento en soporte técnico, inventario de equipos y mesa de ayuda.','Estudiante de últimos semestres con manejo básico de redes.','Neiva','presencial',40,'published',date('Y-m-d H:i:s'),$companyId]);
  $pub=(int)$pdo->lastInsertId();
  $o->execute(['Pasantía en análisis de datos','Empresa Aliada de Pasantías','practica','Apoyo en tableros e informes con datos de ventas y producción.','Conocimientos de Excel y SQL básico.','Rivera, Huila','hibrida',50,'review',null,$companyId]);
  if($studentId) $pdo->prepare("INSERT IGNORE INTO applications(opportunity_id,user_id,motivation,status) VALUES(?,?,?,'review')")->execute([$pub,$studentId,'Quiero fortalecer mi experiencia en soporte y redes mientras termino la carrera.']);
 }
 if(!(int)$pdo->query("SELECT COUNT(*) FROM opportunities WHERE created_by=$companyId AND type='empleo'")->fetchColumn()){
  $pdo->prepare("INSERT INTO opportunities(title,organization,type,description,requirements,location,modality,closes_on,status,verified_at,created_by) VALUES(?,?,'empleo',?,?,?,?,DATE_ADD(CURDATE(),INTERVAL 30 DAY),'published',NOW(),?)")
   ->execute(['Desarrollador web junior','Empresa Aliada de Pasantías','Vacante para egresados o estudiantes de último semestre: mantenimiento de aplicaciones PHP y MySQL.','Portafolio y fundamentos de desarrollo web. Adjuntar hoja de vida en PDF.','Neiva','hibrida',$companyId]);
 }
 // Las publicaciones de demostración se mantienen abiertas para que siempre haya datos vigentes.
 $pdo->exec("UPDATE opportunities SET closes_on=DATE_ADD(CURDATE(),INTERVAL 30 DAY) WHERE closes_on<CURDATE() AND status='published' AND created_by=$companyId");

 // Pasantía de demostración: estudiante, empresa evaluadora y docente líder.
 if($studentId&&$companyId&&!(int)$pdo->query('SELECT COUNT(*) FROM internships')->fetchColumn()){
  $pdo->prepare("INSERT INTO internships(student_id,company_id,teacher_id,title,organization,starts_on,ends_on,status) VALUES(?,?,?,?,?,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 120 DAY),'active')")->execute([$studentId,$companyId,$teacherId?:null,'Pasantía en soporte de sistemas','Empresa Aliada de Pasantías']);
 }

 // Aviso de bienvenida para que el tablero de avisos no arranque vacío.
 if((int)$pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn()===0){
  $n=$pdo->prepare('INSERT INTO notifications(user_id,title,body,link) SELECT id,?,?,? FROM users WHERE status="active"');
  $n->execute(['Bienvenido a Pasantías FET','Pulsa el botón de ayuda (?) para recorrer la plataforma paso a paso.','guide.php']);
 }

 echo "Pasantías FET instalada y actualizada correctamente.\n";
 echo "Cuentas: admin@fet.edu.co, docente@fet.edu.co, estudiante@fet.edu.co, empresa@fet.edu.co | Clave: FET2026*\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
