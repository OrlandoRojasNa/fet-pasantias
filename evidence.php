<?php
declare(strict_types=1);
/** Descarga de archivos de evidencia con control de permisos (los archivos no son públicos). */
require __DIR__.'/includes/bootstrap.php';
require_auth();
$u=current_user();
$type=$_GET['type']??''; $id=(int)($_GET['id']??0); $rec=null;
if($type==='cv'){
    // Hoja de vida: la ve el estudiante que la subió, la empresa dueña de la publicación, los docentes líderes y el administrador.
    $rec=row('SELECT a.cv_file evidence_file,a.cv_name evidence_name,a.user_id,o.created_by FROM applications a JOIN opportunities o ON o.id=a.opportunity_id WHERE a.id=?',[$id]);
    if($rec&&!has_role('admin','leader')&&!in_array((int)$u['id'],[(int)$rec['user_id'],(int)$rec['created_by']],true)) $rec=null;
}elseif($type==='internship'){
    $rec=row('SELECT d.file evidence_file,d.file_name evidence_name,i.student_id,i.company_id,i.teacher_id FROM internship_documents d JOIN internships i ON i.id=d.internship_id WHERE d.id=?',[$id]);
    if($rec&&!can_view_internship($rec)) $rec=null;
}
$path=$rec&&$rec['evidence_file']&&preg_match('/^[a-f0-9]{24}\.[a-z0-9]{2,4}$/',$rec['evidence_file'])?__DIR__.'/storage/documents/'.$rec['evidence_file']:null;
if(!$path||!is_file($path)){ http_response_code(404); exit('El archivo no existe o no tienes acceso.'); }
audit('download','evidence',$id,$type);
$name=preg_replace('/[^\w.\- ]+/u','_',(string)($rec['evidence_name']?:basename($path)));
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.$name.'"');
header('Content-Length: '.filesize($path));
readfile($path);
