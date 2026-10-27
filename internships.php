<?php
declare(strict_types=1);
/**
 * Pasantías: el estudiante sube su documentación, la empresa evaluadora la avala
 * y el docente asignado la aprueba. Cada perfil ve solo las pasantías en las que participa.
 */
require __DIR__.'/includes/bootstrap.php';
require_auth();

$u=current_user();
$uid=(int)$u['id'];
$isStudent=has_role('student');
$canModerate=has_role('admin','manager');
$docTypes=['plan'=>'Plan de práctica','parcial'=>'Informe parcial','final'=>'Informe final','evaluacion'=>'Evaluación de la empresa','certificado'=>'Certificado','otro'=>'Otro documento'];
$docStatus=['submitted'=>['Enviado','submitted'],'endorsed'=>['Avalado por la empresa','in_progress'],'approved'=>['Aprobado por el docente','approved'],'returned'=>['Devuelto con observaciones','rejected']];
$intStatus=['active'=>['En curso','active'],'completed'=>['Finalizada','completed'],'cancelled'=>['Anulada','closed']];
$resultBadge=['aprobada'=>['Aprobada','approved'],'reprobada'=>['Reprobada','rejected']];
const PASS_GRADE=3.0; // Nota mínima para aprobar un documento y la pasantía (promedio).

/** Promedio de las notas del docente en los documentos de la pasantía (null si no hay notas). */
function internship_average(int $id): ?float {
    $avg=scalar('SELECT AVG(grade) FROM internship_documents WHERE internship_id=? AND grade IS NOT NULL',[$id]);
    return $avg===null?null:round((float)$avg,1);
}

/** Carga una pasantía y corta con 403 si el usuario no participa en ella. */
function load_internship(int $id): array {
    $i=row('SELECT i.*,s.full_name student,s.email student_email,s.program student_program,c.full_name company,c.program company_org,t.full_name teacher,f.full_name final_by_name FROM internships i JOIN users s ON s.id=i.student_id LEFT JOIN users c ON c.id=i.company_id LEFT JOIN users t ON t.id=i.teacher_id LEFT JOIN users f ON f.id=i.final_by WHERE i.id=?',[$id]);
    if(!$i||!can_view_internship($i)){ http_response_code(403); require __DIR__.'/includes/403.php'; exit; }
    return $i;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    $action=$_POST['action']??'';
    $back='internships.php';
    try{
        if($action==='create'&&$canModerate){
            $student=(int)($_POST['student_id']??0); $company=(int)($_POST['company_id']??0)?:null; $teacher=(int)($_POST['teacher_id']??0)?:null;
            $title=post_str('title',180); $organization=post_str('organization',180);
            if(!row("SELECT id FROM users WHERE id=? AND role='student' AND status='active'",[$student])||$title==='') throw new RuntimeException('Elige el estudiante y escribe el nombre de la pasantía.');
            if($company&&!row("SELECT id FROM users WHERE id=? AND role='company'",[$company])) throw new RuntimeException('La empresa evaluadora no es válida.');
            if($teacher&&!row("SELECT id FROM users WHERE id=? AND role='leader'",[$teacher])) throw new RuntimeException('El docente no es válido.');
            if($organization==='') $organization=$company?(string)scalar('SELECT COALESCE(program,full_name) FROM users WHERE id=?',[$company]):'Por definir';
            query('INSERT INTO internships(student_id,company_id,teacher_id,title,organization,starts_on,ends_on) VALUES(?,?,?,?,?,?,?)',[$student,$company,$teacher,$title,$organization,valid_date($_POST['starts_on']??null),valid_date($_POST['ends_on']??null)]);
            $id=(int)db()->lastInsertId(); audit('create','internship',$id,$title);
            notify($student,'Tu pasantía quedó registrada','«'.$title.'»: ya puedes subir tus documentos de pasantía.','internships.php?id='.$id);
            if($company) notify($company,'Nuevo pasante asignado','Serás la empresa evaluadora de «'.$title.'».','internships.php?id='.$id);
            if($teacher) notify($teacher,'Pasantía asignada para validar','Validarás los documentos de «'.$title.'».','internships.php?id='.$id);
            flash('success','Pasantía registrada.');
            $back='internships.php?id='.$id;
        }elseif($action==='assign'&&$canModerate){
            $i=load_internship((int)$_POST['id']);
            $company=(int)($_POST['company_id']??0)?:null; $teacher=(int)($_POST['teacher_id']??0)?:null;
            if($company&&!row("SELECT id FROM users WHERE id=? AND role='company'",[$company])) throw new RuntimeException('La empresa evaluadora no es válida.');
            if($teacher&&!row("SELECT id FROM users WHERE id=? AND role='leader'",[$teacher])) throw new RuntimeException('El docente no es válido.');
            query('UPDATE internships SET company_id=?,teacher_id=?,starts_on=?,ends_on=?,status=? WHERE id=?',[$company,$teacher,valid_date($_POST['starts_on']??null),valid_date($_POST['ends_on']??null),allowed_value($_POST['status']??'',array_keys($intStatus),$i['status']),$i['id']]);
            audit('assign','internship',(int)$i['id'],'empresa '.($company??'-').' · docente '.($teacher??'-'));
            if($teacher&&$teacher!==(int)$i['teacher_id']) notify($teacher,'Pasantía asignada para validar','Validarás los documentos de «'.$i['title'].'» ('.$i['student'].').','internships.php?id='.$i['id']);
            if($company&&$company!==(int)$i['company_id']) notify($company,'Nuevo pasante asignado','Serás la empresa evaluadora de «'.$i['title'].'» ('.$i['student'].').','internships.php?id='.$i['id']);
            flash('success','Pasantía actualizada.');
            $back='internships.php?id='.$i['id'];
        }elseif($action==='evaluate'){
            // Evaluación del desempeño del pasante por parte de la empresa (insumo del veredicto del docente).
            $i=load_internship((int)$_POST['id']);
            if((int)$i['company_id']!==$uid) throw new RuntimeException('Solo la empresa evaluadora puede calificar el desempeño.');
            if($i['final_result']) throw new RuntimeException('El docente ya emitió el veredicto final.');
            $rating=(int)($_POST['rating']??0); $text=post_str('evaluation');
            if($rating<1||$rating>5||mb_strlen($text)<20) throw new RuntimeException('Elige una calificación de 1 a 5 y describe el desempeño en al menos 20 caracteres.');
            query('UPDATE internships SET company_rating=?,company_evaluation=?,company_evaluated_at=NOW() WHERE id=?',[$rating,$text,$i['id']]);
            audit('evaluate','internship',(int)$i['id'],'calificación '.$rating);
            if($i['teacher_id']) notify((int)$i['teacher_id'],'Evaluación de desempeño recibida','La empresa calificó a '.$i['student'].' con '.$rating.'/5 en «'.$i['title'].'».','internships.php?id='.$i['id']);
            notify((int)$i['student_id'],'La empresa evaluó tu desempeño','Ya puedes ver la evaluación de «'.$i['title'].'».','internships.php?id='.$i['id']);
            flash('success','Evaluación de desempeño guardada. El docente ya puede verla.');
            $back='internships.php?id='.$i['id'];
        }elseif($action==='verdict'){
            // Veredicto final del docente: aprueba o reprueba la pasantía con justificación.
            $i=load_internship((int)$_POST['id']);
            if(!$canModerate&&(int)$i['teacher_id']!==$uid) throw new RuntimeException('Solo el docente asignado puede emitir el veredicto.');
            if($i['final_result']) throw new RuntimeException('La pasantía ya tiene veredicto final.');
            $result=allowed_value($_POST['result']??'',['aprobada','reprobada'],'');
            $why=post_str('justification');
            if($result===''||mb_strlen($why)<20) throw new RuntimeException('Elige aprobada o reprobada y justifica la decisión en al menos 20 caracteres.');
            // Regla: la pasantía se aprueba con promedio de notas de 3.0 o más; con menos de 3.0 queda reprobada.
            $avg=internship_average((int)$i['id']);
            if($result==='aprobada'&&($avg===null||$avg<PASS_GRADE)) throw new RuntimeException($avg===null?'Para aprobar la pasantía, califica primero al menos un documento.':'El promedio es '.number_format($avg,1).': con menos de 3.0 la pasantía queda reprobada.');
            query("UPDATE internships SET final_result=?,final_justification=?,final_by=?,final_at=NOW(),status='completed',ends_on=COALESCE(ends_on,CURDATE()) WHERE id=?",[$result,$why,$uid,$i['id']]);
            audit('verdict','internship',(int)$i['id'],$result);
            notify((int)$i['student_id'],'Resultado final de tu pasantía','«'.$i['title'].'»: '.($result==='aprobada'?'APROBADA':'REPROBADA').'. Revisa la justificación del docente.','internships.php?id='.$i['id']);
            if($i['company_id']) notify((int)$i['company_id'],'Resultado final de la pasantía','«'.$i['title'].'» de '.$i['student'].': '.$result.'.','internships.php?id='.$i['id']);
            flash('success','Veredicto registrado: pasantía '.$result.'.');
            $back='internships.php?id='.$i['id'];
        }elseif($action==='upload'){
            $i=load_internship((int)$_POST['id']);
            if((int)$i['student_id']!==$uid) throw new RuntimeException('Solo el estudiante de la pasantía puede subir documentos.');
            if($i['status']!=='active') throw new RuntimeException('La pasantía ya no recibe documentos.');
            $type=allowed_value($_POST['doc_type']??'',array_keys($docTypes),'otro');
            $title=post_str('title',180)?:$docTypes[$type];
            $file=store_evidence_upload('document');
            if(!$file) throw new RuntimeException('Adjunta el archivo del documento.');
            query('INSERT INTO internship_documents(internship_id,doc_type,title,file,file_name,uploaded_by) VALUES(?,?,?,?,?,?)',[$i['id'],$type,$title,$file['stored'],$file['original'],$uid]);
            $did=(int)db()->lastInsertId(); audit('upload','internship_document',$did,$title);
            $msg=$u['full_name'].' subió «'.$title.'» de la pasantía «'.$i['title'].'».';
            if($i['company_id']) notify((int)$i['company_id'],'Documento de pasantía por avalar',$msg,'internships.php?id='.$i['id']);
            if($i['teacher_id']) notify((int)$i['teacher_id'],'Documento de pasantía recibido',$msg,'internships.php?id='.$i['id']);
            flash('success','Documento enviado. Tu empresa evaluadora y tu docente ya pueden verlo.');
            $back='internships.php?id='.$i['id'];
        }elseif($action==='resubmit'){
            $d=row('SELECT * FROM internship_documents WHERE id=?',[(int)$_POST['document_id']]);
            if(!$d) throw new RuntimeException('El documento no existe.');
            $i=load_internship((int)$d['internship_id']);
            if((int)$i['student_id']!==$uid||$d['status']!=='returned') throw new RuntimeException('Solo puedes corregir documentos devueltos.');
            $file=store_evidence_upload('document');
            if(!$file) throw new RuntimeException('Adjunta la versión corregida.');
            query("UPDATE internship_documents SET file=?,file_name=?,status='submitted',endorsed_at=NULL,approved_at=NULL,grade=NULL,graded_by=NULL,graded_at=NULL WHERE id=?",[$file['stored'],$file['original'],$d['id']]);
            $note=post_str('comment');
            if($note!=='') query('INSERT INTO document_comments(document_id,user_id,decision,body) VALUES(?,?,?,?)',[$d['id'],$uid,'resubmit',$note]);
            audit('resubmit','internship_document',(int)$d['id']);
            $msg=$u['full_name'].' corrigió «'.$d['title'].'».';
            if($i['company_id']) notify((int)$i['company_id'],'Documento corregido por avalar',$msg,'internships.php?id='.$i['id']);
            if($i['teacher_id']) notify((int)$i['teacher_id'],'Documento de pasantía corregido',$msg,'internships.php?id='.$i['id']);
            flash('success','Enviaste la versión corregida.');
            $back='internships.php?id='.$i['id'];
        }elseif($action==='review'){
            $d=row('SELECT * FROM internship_documents WHERE id=?',[(int)$_POST['document_id']]);
            if(!$d) throw new RuntimeException('El documento no existe.');
            $i=load_internship((int)$d['internship_id']);
            $decision=allowed_value($_POST['decision']??'',['endorse','approve','return'],'');
            $comment=post_str('comment');
            $isCompanyReviewer=(int)$i['company_id']===$uid;
            $isTeacherReviewer=(int)$i['teacher_id']===$uid||$canModerate;
            if($decision==='return'&&$comment==='') throw new RuntimeException('Escribe la observación para que el estudiante sepa qué corregir.');
            // El docente califica el documento (0.0 a 5.0) al aprobarlo o devolverlo; la empresa solo avala o devuelve.
            $gradedByTeacher=$decision==='approve'||($decision==='return'&&$isTeacherReviewer&&!($isCompanyReviewer&&$d['status']==='submitted'));
            $grade=null;
            if($gradedByTeacher){
                $grade=valid_grade($_POST['grade']??'');
                if($grade===null) throw new RuntimeException('Escribe la calificación del documento entre 0.0 y 5.0 (por ejemplo 4.5).');
            }
            if($decision==='endorse'){
                if(!$isCompanyReviewer||$d['status']!=='submitted') throw new RuntimeException('Solo la empresa evaluadora puede avalar documentos enviados.');
                query("UPDATE internship_documents SET status='endorsed',endorsed_at=NOW() WHERE id=?",[$d['id']]);
                if($i['teacher_id']) notify((int)$i['teacher_id'],'Documento avalado por la empresa','«'.$d['title'].'» de '.$i['student'].' está listo para tu aprobación.','internships.php?id='.$i['id']);
                notify((int)$i['student_id'],'Tu documento fue avalado','La empresa avaló «'.$d['title'].'».','internships.php?id='.$i['id']);
            }elseif($decision==='approve'){
                if(!$isTeacherReviewer||!in_array($d['status'],['submitted','endorsed'],true)) throw new RuntimeException('Solo el docente asignado puede aprobar documentos pendientes.');
                if($grade<PASS_GRADE) throw new RuntimeException('Con nota menor a 3.0 el documento no se aprueba: devuélvelo con observaciones.');
                query("UPDATE internship_documents SET status='approved',approved_at=NOW() WHERE id=?",[$d['id']]);
                notify((int)$i['student_id'],'Tu documento fue aprobado','El docente aprobó «'.$d['title'].'» con '.number_format((float)$grade,1).'.','internships.php?id='.$i['id']);
                if($i['company_id']) notify((int)$i['company_id'],'Documento aprobado por el docente','«'.$d['title'].'» de '.$i['student'].' quedó aprobado.','internships.php?id='.$i['id']);
            }elseif($decision==='return'){
                $canReturn=($isCompanyReviewer&&$d['status']==='submitted')||($isTeacherReviewer&&in_array($d['status'],['submitted','endorsed'],true));
                if(!$canReturn) throw new RuntimeException('No puedes devolver este documento.');
                query("UPDATE internship_documents SET status='returned' WHERE id=?",[$d['id']]);
                notify((int)$i['student_id'],'Documento devuelto con observaciones','«'.$d['title'].'»: '.mb_substr($comment,0,150),'internships.php?id='.$i['id']);
            }else throw new RuntimeException('Elige una decisión.');
            if($grade!==null) query('UPDATE internship_documents SET grade=?,graded_by=?,graded_at=NOW() WHERE id=?',[$grade,$uid,$d['id']]);
            if($comment!==''||$grade!==null) query('INSERT INTO document_comments(document_id,user_id,decision,grade,body) VALUES(?,?,?,?,?)',[$d['id'],$uid,$decision,$grade,$comment!==''?$comment:'Sin observaciones.']);
            audit('review','internship_document',(int)$d['id'],$decision);
            flash('success',['endorse'=>'Documento avalado.','approve'=>'Documento aprobado.','return'=>'Documento devuelto al estudiante.'][$decision].($grade!==null?' Calificación: '.number_format($grade,1).'.':''));
            $back='internships.php?id='.$i['id'];
        }
    }catch(Throwable $e){ flash('error',$e->getMessage()); }
    redirect($back);
}

$companies=$canModerate?rows("SELECT id,full_name,program FROM users WHERE role='company' AND status='active' ORDER BY program,full_name"):[];
$teachers=$canModerate?rows("SELECT id,full_name FROM users WHERE role='leader' AND status='active' ORDER BY full_name"):[];
$badge=fn(array $map,string $k)=>'<span class="status status-'.e($map[$k][1]??$k).'">'.e($map[$k][0]??$k).'</span>';

/* ---------- Detalle de una pasantía ---------- */
if(isset($_GET['id'])){
    $i=load_internship((int)$_GET['id']);
    $docs=rows('SELECT * FROM internship_documents WHERE internship_id=? ORDER BY created_at,id',[$i['id']]);
    $comments=[];
    if($docs){
        $in=implode(',',array_map(fn($d)=>(int)$d['id'],$docs));
        foreach(rows("SELECT c.*,u.full_name,u.role FROM document_comments c JOIN users u ON u.id=c.user_id WHERE c.document_id IN ($in) ORDER BY c.created_at,c.id") as $c) $comments[(int)$c['document_id']][]=$c;
    }
    $approved=count(array_filter($docs,fn($d)=>$d['status']==='approved'));
    $graded=array_filter(array_column($docs,'grade'),fn($g)=>$g!==null);
    $average=$graded?number_format(array_sum(array_map('floatval',$graded))/count($graded),1):null;
    $isOwner=(int)$i['student_id']===$uid;
    $isCompanyReviewer=(int)$i['company_id']===$uid;
    $isTeacherReviewer=(int)$i['teacher_id']===$uid||$canModerate;
    $decisionLabel=['endorse'=>'Avaló','approve'=>'Aprobó','return'=>'Devolvió','resubmit'=>'Corrigió'];
    app_header($i['title'],'PASANTÍAS · DOCUMENTACIÓN');
    ?>
    <div class="page-actions"><a class="button button-ghost" href="<?= e(url('internships.php')) ?>"><?= icon('arrow-left',16) ?>Volver a las pasantías</a>
     <div class="button-row"><?php if($canModerate):?><button class="button button-ghost" type="button" data-open="internship-assign" id="tour-int-assign"><?= icon('edit',16) ?>Asignar empresa y docente</button><?php endif;?>
     <?php if($isCompanyReviewer&&!$i['final_result']):?><button class="button button-ghost" type="button" data-open="company-eval" id="tour-int-eval"><?= icon('edit',16) ?><?= $i['company_evaluated_at']?'Editar evaluación':'Evaluar desempeño' ?></button><?php endif;?>
     <?php if(!$i['final_result']&&$i['status']==='active'&&($canModerate||(int)$i['teacher_id']===$uid)):?><button class="button button-primary" type="button" data-open="final-verdict" id="tour-int-verdict"><?= icon('check-circle',16) ?>Emitir veredicto final</button><?php endif;?>
     <?php if($isOwner&&$i['status']==='active'):?><button class="button button-primary" type="button" data-open="doc-upload" id="tour-int-upload"><?= icon('plus',16) ?>Subir documento</button><?php endif;?></div>
    </div>

    <section class="panel" id="tour-int-info"><div class="panel-head"><div><span class="eyebrow"><?= e($i['organization']) ?></span><h2><?= e($i['title']) ?></h2></div><?= $badge($intStatus,$i['status']) ?></div>
     <div class="project-meta">
      <span><small>Estudiante</small><?= e($i['student']) ?></span>
      <span><small>Empresa evaluadora</small><?= e($i['company']?:'Sin asignar') ?></span>
      <span><small>Docente que valida</small><?= e($i['teacher']?:'Sin asignar') ?></span>
      <span><small>Periodo</small><?= $i['starts_on']?e(date('d/m/Y',strtotime($i['starts_on']))):'Por definir' ?> — <?= $i['ends_on']?e(date('d/m/Y',strtotime($i['ends_on']))):'Abierto' ?></span>
      <span><small>Documentos aprobados</small><?= $approved ?> de <?= count($docs) ?></span>
      <span><small>Promedio de notas</small><?= $average!==null?e($average).' / 5.0':'Sin notas' ?></span>
     </div>
    </section>

    <section class="panel" id="tour-int-final"><div class="panel-head"><div><span class="eyebrow">EVALUACIÓN FINAL</span><h2>Desempeño y resultado de la pasantía</h2></div><?= $i['final_result']?$badge($resultBadge,$i['final_result']):'<span class="status status-pending">Sin veredicto</span>' ?></div>
     <div class="two-column">
      <div><h3>Evaluación de la empresa</h3>
       <?php if($i['company_evaluated_at']):?><p><strong>Calificación: <?= (int)$i['company_rating'] ?>/5</strong> · <?= e($i['company']) ?> · <?= e(date('d/m/Y',strtotime($i['company_evaluated_at']))) ?></p><p><?= nl2br(e($i['company_evaluation'])) ?></p>
       <?php else:?><p class="muted-inline"><?= $i['company_id']?'La empresa aún no registra la evaluación de desempeño del pasante.':'Esta pasantía no tiene empresa evaluadora asignada.' ?></p><?php endif;?></div>
      <div><h3>Veredicto del docente</h3>
       <?php if($i['final_result']):?><p><strong>Pasantía <?= e($i['final_result']) ?></strong> · <?= e($i['final_by_name']?:'') ?> · <?= e(date('d/m/Y',strtotime($i['final_at']))) ?></p><p><?= nl2br(e($i['final_justification'])) ?></p>
       <?php else:?><p class="muted-inline">El docente decide si la pasantía se aprueba o se reprueba con base en la evaluación de la empresa y los documentos presentados por el estudiante.</p><?php endif;?></div>
     </div>
    </section>

    <section class="panel" id="tour-int-docs"><div class="panel-head"><div><span class="eyebrow">DOCUMENTACIÓN</span><h2>Documentos de la pasantía</h2></div></div>
    <p class="muted-inline">El estudiante sube cada documento, la empresa evaluadora lo avala y el docente lo aprueba. Si alguno lo devuelve, el estudiante sube la versión corregida.</p>
    <div class="table-wrap"><table><thead><tr><th>Documento</th><th>Enviado</th><th>Estado</th><th>Nota</th><th>Observaciones</th><th>Acción</th></tr></thead><tbody>
    <?php if(!$docs):?><tr><td colspan="6" class="empty"><?= $isOwner?'Aún no subes documentos. Empieza por el plan de práctica con «Subir documento».':'El estudiante aún no ha subido documentos.' ?></td></tr><?php endif;?>
    <?php foreach($docs as $d): $did=(int)$d['id'];
        $canEndorse=$isCompanyReviewer&&$d['status']==='submitted';
        $canApprove=$isTeacherReviewer&&in_array($d['status'],['submitted','endorsed'],true);
        $canReturn=$canEndorse||($isTeacherReviewer&&in_array($d['status'],['submitted','endorsed'],true));
        $canFix=$isOwner&&$d['status']==='returned'&&$i['status']==='active'; ?>
     <tr>
      <td><strong><?= e($d['title']) ?></strong><small><?= e($docTypes[$d['doc_type']]??$d['doc_type']) ?> · <a class="file-link" href="<?= e(url('evidence.php?type=internship&id='.$did)) ?>"><?= icon('paperclip',12) ?><?= e($d['file_name']) ?></a></small></td>
      <td><?= e(date('d/m/Y',strtotime($d['updated_at']))) ?></td>
      <td><?= $badge($docStatus,$d['status']) ?></td>
      <td><?= $d['grade']!==null?'<span style="white-space:nowrap"><strong>'.e(number_format((float)$d['grade'],1)).'</strong> / 5.0</span>':'<span class="muted-inline">—</span>' ?></td>
      <td><?php if(empty($comments[$did])):?><span class="muted-inline">—</span><?php else: foreach($comments[$did] as $c):?><small><strong><?= e($decisionLabel[$c['decision']]??'Comentó') ?> · <?= e($c['full_name']) ?><?= $c['grade']!==null?' · Nota '.e(number_format((float)$c['grade'],1)):'' ?></strong> (<?= e(date('d/m/Y',strtotime($c['created_at']))) ?>): <?= e($c['body']) ?></small><?php endforeach; endif;?></td>
      <td><?php if($canEndorse||$canApprove||$canReturn):?><button class="button button-small" type="button" data-open="review-<?= $did ?>"><?= icon('eye',13) ?>Revisar</button>
       <dialog id="review-<?= $did ?>"><form method="post" class="modal-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="review"><input type="hidden" name="document_id" value="<?= $did ?>">
        <div class="modal-head"><div><span class="eyebrow"><?= $isCompanyReviewer?'EMPRESA EVALUADORA':'DOCENTE' ?></span><h2><?= e($d['title']) ?></h2></div><button type="button" data-close aria-label="Cerrar">×</button></div>
        <p><a class="file-link" href="<?= e(url('evidence.php?type=internship&id='.$did)) ?>"><?= icon('download',14) ?>Descargar <?= e($d['file_name']) ?></a></p>
        <div class="form-grid"><label class="span-2">Decisión<select name="decision"><?php if($canEndorse):?><option value="endorse">Avalar el documento</option><?php endif;?><?php if($canApprove):?><option value="approve">Aprobar el documento</option><?php endif;?><?php if($canReturn):?><option value="return">Devolver con observaciones</option><?php endif;?></select></label>
        <?php if($isTeacherReviewer&&!$canEndorse):?><label class="span-2">Calificación del documento (0.0 a 5.0 · con 3.0 o más se aprueba; con menos se devuelve)<input type="number" name="grade" min="0" max="5" step="0.1" inputmode="decimal" placeholder="Ej. 4.5" required value="<?= $d['grade']!==null?e(number_format((float)$d['grade'],1)):'' ?>"></label><?php endif;?>
        <label class="span-2">Observaciones (obligatorias si lo devuelves)<textarea name="comment"></textarea></label></div>
        <div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary">Guardar decisión</button></div>
       </form></dialog>
      <?php elseif($canFix):?><button class="button button-small" type="button" data-open="fix-<?= $did ?>"><?= icon('undo',13) ?>Corregir</button>
       <dialog id="fix-<?= $did ?>"><form method="post" enctype="multipart/form-data" class="modal-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="resubmit"><input type="hidden" name="document_id" value="<?= $did ?>">
        <div class="modal-head"><div><span class="eyebrow">VERSIÓN CORREGIDA</span><h2><?= e($d['title']) ?></h2></div><button type="button" data-close aria-label="Cerrar">×</button></div>
        <div class="form-grid"><label class="span-2">Archivo corregido (máx. 5 MB)<input type="file" name="document" required accept=".pdf,.png,.jpg,.jpeg,.webp,.docx,.xlsx,.pptx,.csv,.txt,.zip"></label><label class="span-2">Qué corregiste (opcional)<textarea name="comment"></textarea></label></div>
        <div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary">Enviar corrección</button></div>
       </form></dialog>
      <?php else:?><span class="muted-inline">—</span><?php endif;?></td>
     </tr>
    <?php endforeach;?>
    </tbody></table></div></section>

    <?php if($isOwner&&$i['status']==='active'):?>
    <dialog id="doc-upload"><form method="post" enctype="multipart/form-data" class="modal-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="upload"><input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
     <div class="modal-head"><div><span class="eyebrow">NUEVO DOCUMENTO</span><h2>Subir documento de pasantía</h2></div><button type="button" data-close aria-label="Cerrar">×</button></div>
     <div class="form-grid"><label>Tipo<select name="doc_type"><?php foreach($docTypes as $k=>$label):?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach;?></select></label><label>Título (opcional)<input name="title" maxlength="180"></label>
     <label class="span-2">Archivo (PDF, imagen u Office, máx. 5 MB)<input type="file" name="document" required accept=".pdf,.png,.jpg,.jpeg,.webp,.docx,.xlsx,.pptx,.csv,.txt,.zip"></label></div>
     <div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary">Enviar documento</button></div>
    </form></dialog>
    <?php endif;?>

    <?php if($isCompanyReviewer&&!$i['final_result']):?>
    <dialog id="company-eval"><form method="post" class="modal-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="evaluate"><input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
     <div class="modal-head"><div><span class="eyebrow">EMPRESA EVALUADORA</span><h2>Evaluación de desempeño</h2></div><button type="button" data-close aria-label="Cerrar">×</button></div>
     <div class="form-grid"><label class="span-2">Calificación general<select name="rating" required><?php foreach([5=>'5 · Excelente',4=>'4 · Bueno',3=>'3 · Aceptable',2=>'2 · Deficiente',1=>'1 · Insuficiente'] as $k=>$label):?><option value="<?= $k ?>" <?= (int)$i['company_rating']===$k?'selected':'' ?>><?= e($label) ?></option><?php endforeach;?></select></label>
     <label class="span-2">Comentarios sobre el desempeño (responsabilidad, conocimientos, trabajo en equipo, cumplimiento)<textarea name="evaluation" minlength="20" required><?= e($i['company_evaluation']) ?></textarea></label></div>
     <div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary">Guardar evaluación</button></div>
    </form></dialog>
    <?php endif;?>

    <?php if(!$i['final_result']&&$i['status']==='active'&&($canModerate||(int)$i['teacher_id']===$uid)):?>
    <dialog id="final-verdict"><form method="post" class="modal-form" data-confirm="El veredicto final no se puede cambiar después. ¿Continuar?"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="verdict"><input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
     <div class="modal-head"><div><span class="eyebrow">DOCENTE LÍDER DE PASANTÍAS</span><h2>Veredicto final</h2></div><button type="button" data-close aria-label="Cerrar">×</button></div>
     <p class="muted-inline">Evaluación de la empresa: <?= $i['company_evaluated_at']?(int)$i['company_rating'].'/5':'pendiente' ?> · Documentos aprobados: <?= $approved ?> de <?= count($docs) ?> · Promedio de notas: <?= $average!==null?e($average):'sin notas' ?></p>
     <?php $suggest=$average!==null&&(float)$average>=PASS_GRADE?'aprobada':'reprobada'; ?>
     <p class="muted-inline"><strong>Regla:</strong> con promedio de 3.0 o más la pasantía se aprueba; con menos de 3.0 se reprueba. Resultado sugerido: <strong><?= $suggest==='aprobada'?'Aprobada':'Reprobada' ?></strong>.</p>
     <div class="form-grid"><label class="span-2">Resultado<select name="result" required><option value="aprobada" <?= $suggest==='aprobada'?'selected':'' ?>>Aprobada: el estudiante pasa la práctica</option><option value="reprobada" <?= $suggest==='reprobada'?'selected':'' ?>>Reprobada: el estudiante no pasa la práctica</option></select></label>
     <label class="span-2">Justificación (se le muestra al estudiante)<textarea name="justification" minlength="20" required></textarea></label></div>
     <div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary">Registrar veredicto</button></div>
    </form></dialog>
    <?php endif;?>

    <?php if($canModerate):?>
    <dialog id="internship-assign"><form method="post" class="modal-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="assign"><input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
     <div class="modal-head"><div><span class="eyebrow">GESTIÓN FET</span><h2>Asignar empresa y docente</h2></div><button type="button" data-close aria-label="Cerrar">×</button></div>
     <div class="form-grid">
      <label>Empresa evaluadora<select name="company_id"><option value="">Sin asignar</option><?php foreach($companies as $c):?><option value="<?= (int)$c['id'] ?>" <?= (int)$i['company_id']===(int)$c['id']?'selected':'' ?>><?= e(($c['program']?:$c['full_name']).' · '.$c['full_name']) ?></option><?php endforeach;?></select></label>
      <label>Docente que valida<select name="teacher_id"><option value="">Sin asignar</option><?php foreach($teachers as $t):?><option value="<?= (int)$t['id'] ?>" <?= (int)$i['teacher_id']===(int)$t['id']?'selected':'' ?>><?= e($t['full_name']) ?></option><?php endforeach;?></select></label>
      <label>Inicio<input type="date" name="starts_on" value="<?= e($i['starts_on']) ?>"></label><label>Fin<input type="date" name="ends_on" value="<?= e($i['ends_on']) ?>"></label>
      <label>Estado<select name="status"><?php foreach($intStatus as $k=>[$label]):?><option value="<?= e($k) ?>" <?= $i['status']===$k?'selected':'' ?>><?= e($label) ?></option><?php endforeach;?></select></label>
     </div>
     <div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary">Guardar</button></div>
    </form></dialog>
    <?php endif;?>
    <?php app_footer(); exit;
}

/* ---------- Lista de pasantías según el perfil ---------- */
$where='1=1'; $params=[];
if(!$canModerate){ $where='(i.student_id=? OR i.company_id=? OR i.teacher_id=?)'; $params=[$uid,$uid,$uid]; }
$items=rows("SELECT i.*,s.full_name student,c.full_name company,t.full_name teacher,
    (SELECT COUNT(*) FROM internship_documents d WHERE d.internship_id=i.id) docs,
    (SELECT COUNT(*) FROM internship_documents d WHERE d.internship_id=i.id AND d.status='approved') approved,
    (SELECT COUNT(*) FROM internship_documents d WHERE d.internship_id=i.id AND d.status IN ('submitted','endorsed')) pending
    FROM internships i JOIN users s ON s.id=i.student_id LEFT JOIN users c ON c.id=i.company_id LEFT JOIN users t ON t.id=i.teacher_id
    WHERE $where ORDER BY FIELD(i.status,'active','completed','cancelled'),i.created_at DESC",$params);
$students=$canModerate?rows("SELECT id,full_name,program FROM users WHERE role='student' AND status='active' ORDER BY full_name"):[];
$intro=match(true){
    $isStudent=>'Sube aquí la documentación de tu pasantía. Tu empresa evaluadora y tu docente la revisan y te avisan.',
    has_role('company')=>'Documentos de tus pasantes: revísalos y avala los que estén correctos.',
    has_role('leader')=>'Pasantías que te asignaron para validar. Aprueba los documentos después del aval de la empresa.',
    default=>'Seguimiento de todas las pasantías: asigna la empresa evaluadora y el docente que valida.',
};
app_header('Pasantías','ODS 8 · DOCUMENTACIÓN DE PASANTÍAS');
?>
<div class="page-actions" id="tour-int-intro"><div><p><?= e($intro) ?></p></div><?php if($canModerate):?><button class="button button-primary" type="button" data-open="internship-form" id="tour-int-new"><?= icon('plus',16) ?>Registrar pasantía</button><?php endif;?></div>

<section class="panel" id="tour-int-list"><div class="panel-head"><div><span class="eyebrow">SEGUIMIENTO</span><h2><?= $isStudent?'Mis pasantías':'Pasantías' ?></h2></div></div>
<div class="table-wrap"><table><thead><tr><th>Pasantía</th><th>Estudiante</th><th>Empresa evaluadora</th><th>Docente</th><th>Documentos</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
<?php if(!$items):?><tr><td colspan="7" class="empty"><?= $isStudent?'Aún no tienes una pasantía registrada. Se crea cuando una empresa acepta tu postulación a una práctica.':'No hay pasantías registradas.' ?></td></tr><?php endif;?>
<?php foreach($items as $p):?><tr>
 <td><strong><?= e($p['title']) ?></strong><small><?= e($p['organization']) ?></small></td>
 <td><?= e($p['student']) ?></td><td><?= e($p['company']?:'Sin asignar') ?></td><td><?= e($p['teacher']?:'Sin asignar') ?></td>
 <td><?= (int)$p['approved'] ?> de <?= (int)$p['docs'] ?> aprobados<?php if((int)$p['pending']):?><small><?= (int)$p['pending'] ?> por revisar</small><?php endif;?></td>
 <td><?= $p['final_result']?$badge($resultBadge,$p['final_result']):$badge($intStatus,$p['status']) ?></td>
 <td><a class="button button-small" href="<?= e(url('internships.php?id='.(int)$p['id'])) ?>"><?= icon('eye',13) ?>Abrir</a></td>
</tr><?php endforeach;?>
</tbody></table></div></section>

<?php if($canModerate):?>
<dialog id="internship-form"><form method="post" class="modal-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create">
 <div class="modal-head"><div><span class="eyebrow">NUEVO REGISTRO</span><h2>Registrar pasantía</h2></div><button type="button" data-close aria-label="Cerrar">×</button></div>
 <div class="form-grid">
  <label class="span-2">Nombre de la pasantía<input name="title" maxlength="180" required></label>
  <label>Estudiante<select name="student_id" required><?php foreach($students as $s):?><option value="<?= (int)$s['id'] ?>"><?= e($s['full_name']) ?></option><?php endforeach;?></select></label>
  <label>Entidad donde se realiza<input name="organization" maxlength="180" placeholder="Si se deja vacío, se usa la empresa"></label>
  <label>Empresa evaluadora<select name="company_id"><option value="">Sin asignar</option><?php foreach($companies as $c):?><option value="<?= (int)$c['id'] ?>"><?= e(($c['program']?:$c['full_name']).' · '.$c['full_name']) ?></option><?php endforeach;?></select></label>
  <label>Docente que valida<select name="teacher_id"><option value="">Sin asignar</option><?php foreach($teachers as $t):?><option value="<?= (int)$t['id'] ?>"><?= e($t['full_name']) ?></option><?php endforeach;?></select></label>
  <label>Inicio<input type="date" name="starts_on"></label><label>Fin<input type="date" name="ends_on"></label>
 </div>
 <div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary">Registrar</button></div>
</form></dialog>
<?php endif;?>
<?php app_footer(); ?>
