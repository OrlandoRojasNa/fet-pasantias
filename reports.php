<?php
declare(strict_types=1);
/**
 * Reportes y auditoría del proceso de pasantías (docente líder y administrador).
 * Resumen por periodo, ocupación de cupos por alianza, listado exportable a CSV y bitácora de auditoría.
 */
require __DIR__.'/includes/bootstrap.php';
require_role('admin','leader');

$from=valid_date($_GET['desde']??null)??date('Y-m-d',strtotime('-180 days'));
$to=valid_date($_GET['hasta']??null)??date('Y-m-d');
if($from>$to) [$from,$to]=[$to,$from];
$range=[$from.' 00:00:00',$to.' 23:59:59'];
$n=fn(string $sql,array $p=[])=>(int)scalar($sql,$p);

// Pasantías que estuvieron vigentes en algún momento del periodo.
$inPeriod="COALESCE(i.starts_on,DATE(i.created_at))<=? AND (i.ends_on IS NULL OR i.ends_on>=?)";
$periodArgs=[$to,$from];
$data=[
 'total'=>$n("SELECT COUNT(*) FROM internships i WHERE $inPeriod",$periodArgs),
 'active'=>$n("SELECT COUNT(*) FROM internships i WHERE i.status='active' AND $inPeriod",$periodArgs),
 'approved'=>$n("SELECT COUNT(*) FROM internships i WHERE i.final_result='aprobada' AND i.final_at BETWEEN ? AND ?",$range),
 'failed'=>$n("SELECT COUNT(*) FROM internships i WHERE i.final_result='reprobada' AND i.final_at BETWEEN ? AND ?",$range),
 'docs'=>$n('SELECT COUNT(*) FROM internship_documents WHERE created_at BETWEEN ? AND ?',$range),
 'pending'=>$n("SELECT COUNT(*) FROM internship_documents d JOIN internships i ON i.id=d.internship_id WHERE i.status='active' AND d.status IN ('submitted','endorsed')"),
 'avg'=>scalar('SELECT ROUND(AVG(grade),1) FROM internship_documents WHERE graded_at BETWEEN ? AND ?',$range),
 'applications'=>$n('SELECT COUNT(*) FROM applications WHERE created_at BETWEEN ? AND ?',$range),
];
$byAlliance=rows("SELECT a.name,a.slots,a.status,a.ends_on,
    (SELECT COUNT(*) FROM internships i WHERE i.company_id=a.company_id AND i.status='active') used,
    (SELECT COUNT(*) FROM internships i WHERE i.company_id=a.company_id AND i.final_result='aprobada') approved,
    (SELECT COUNT(*) FROM internships i WHERE i.company_id=a.company_id AND i.final_result='reprobada') failed,
    (SELECT COUNT(*) FROM opportunities o WHERE o.created_by=a.company_id AND o.type='empleo') jobs
    FROM alliances a ORDER BY a.name");
$list=rows("SELECT i.*,s.full_name student,s.identification student_id_number,c.full_name company,t.full_name teacher,
    (SELECT COUNT(*) FROM internship_documents d WHERE d.internship_id=i.id) docs,
    (SELECT COUNT(*) FROM internship_documents d WHERE d.internship_id=i.id AND d.status='approved') approved_docs,
    (SELECT ROUND(AVG(d.grade),1) FROM internship_documents d WHERE d.internship_id=i.id AND d.grade IS NOT NULL) avg_grade
    FROM internships i JOIN users s ON s.id=i.student_id LEFT JOIN users c ON c.id=i.company_id LEFT JOIN users t ON t.id=i.teacher_id
    WHERE $inPeriod ORDER BY i.created_at DESC",$periodArgs);

if(($_GET['formato']??'')==='csv'){
    audit('export','report',null,"$from a $to");
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="pasantias_fet_'.$from.'_'.$to.'.csv"');
    $out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF");
    fputcsv($out,['Pasantías FET - Reporte del periodo',$from,$to],';');
    fputcsv($out,[],';');
    fputcsv($out,['Pasantía','Organización','Estudiante','Identificación','Empresa evaluadora','Docente','Inicio','Fin','Estado','Documentos','Aprobados','Promedio','Evaluación empresa','Resultado final'],';');
    foreach($list as $i) fputcsv($out,[$i['title'],$i['organization'],$i['student'],$i['student_id_number'],$i['company']??'',$i['teacher']??'',$i['starts_on'],$i['ends_on'],status_label($i['status']),$i['docs'],$i['approved_docs'],$i['avg_grade']??'',$i['company_rating']?$i['company_rating'].'/5':'',$i['final_result']??''],';');
    fputcsv($out,[],';'); fputcsv($out,['Alianza','Cupos','En uso','Aprobadas','Reprobadas','Ofertas laborales'],';');
    foreach($byAlliance as $a) fputcsv($out,[$a['name'],$a['slots'],$a['used'],$a['approved'],$a['failed'],$a['jobs']],';');
    fclose($out); exit;
}

$who=(int)($_GET['usuario']??0);
$auditWhere='a.created_at BETWEEN ? AND ?'; $auditArgs=$range;
if($who){ $auditWhere.=' AND a.user_id=?'; $auditArgs[]=$who; }
$recent=rows("SELECT a.*,u.full_name,u.role FROM audit_events a LEFT JOIN users u ON u.id=a.user_id WHERE $auditWhere AND a.action NOT IN ('login','logout') ORDER BY a.created_at DESC,a.id DESC LIMIT 60",$auditArgs);
$people=rows("SELECT id,full_name FROM users ORDER BY full_name");
$actionLabels=['apply'=>'Postulación','status'=>'Cambio de estado','create'=>'Creación','update'=>'Edición','assign'=>'Asignación','upload'=>'Documento subido','resubmit'=>'Corrección subida','review'=>'Revisión de documento','evaluate'=>'Evaluación de empresa','verdict'=>'Veredicto final','backup'=>'Respaldo SQL','export'=>'Exportación','download'=>'Descarga','password'=>'Cambio de contraseña','role'=>'Cambio de perfil','unlock'=>'Desbloqueo'];
$entityLabels=['opportunity'=>'Publicación','application'=>'Postulación','internship'=>'Pasantía','internship_document'=>'Documento','alliance'=>'Alianza','user'=>'Usuario','database'=>'Base de datos','report'=>'Reporte','evidence'=>'Archivo'];
$qs=fn(array $extra=[])=>e(url('reports.php?'.http_build_query(array_merge(['desde'=>$from,'hasta'=>$to],$extra))));
$pct=static fn(int $a,int $b): string=>$b>0?(string)round($a/$b*100).'%':'—';
app_header('Reportes y auditoría','PASANTÍAS FET');
?>
<div class="page-actions"><p>Cifras del proceso de pasantías en el periodo elegido y registro de quién hizo qué y cuándo.</p><button class="button button-ghost" type="button" data-print><?= icon('printer',16) ?>Imprimir reporte</button></div>
<form method="get" class="period-bar panel" id="tour-period"><span class="kpi-icon tone-gold"><?= icon('calendar',20) ?></span><label>Desde<input type="date" name="desde" value="<?= e($from) ?>"></label><label>Hasta<input type="date" name="hasta" value="<?= e($to) ?>"></label><button class="button button-primary button-small"><?= icon('filter',14) ?>Aplicar</button><a class="button button-ghost button-small" href="<?= $qs(['formato'=>'csv']) ?>"><?= icon('download',14) ?>Exportar CSV</a></form>

<section class="metric-grid" id="tour-indicators">
 <article class="metric"><span class="metric-icon tone-ods8"><?= icon('file',22) ?></span><div><strong><?= $data['total'] ?></strong><span>Pasantías en el periodo (<?= $data['active'] ?> en curso)</span></div></article>
 <article class="metric"><span class="metric-icon tone-teal"><?= icon('check-circle',22) ?></span><div><strong><?= $data['approved'] ?> / <?= $data['failed'] ?></strong><span>Aprobadas / reprobadas (<?= $pct($data['approved'],$data['approved']+$data['failed']) ?> de aprobación)</span></div></article>
 <article class="metric"><span class="metric-icon tone-gold"><?= icon('edit',22) ?></span><div><strong><?= $data['avg']!==null?e(number_format((float)$data['avg'],1)):'—' ?></strong><span>Nota promedio de documentos · <?= $data['docs'] ?> subidos</span></div></article>
 <article class="metric"><span class="metric-icon tone-ods17"><?= icon('clock',22) ?></span><div><strong><?= $data['pending'] ?></strong><span>Documentos pendientes hoy · <?= $data['applications'] ?> postulaciones en el periodo</span></div></article>
</section>

<section class="panel" id="tour-compliance"><div class="panel-head"><div><span class="eyebrow">ALIANZAS</span><h2>Cupos y resultados por empresa</h2></div></div>
<div class="table-wrap"><table><thead><tr><th>Empresa</th><th>Cupos en uso</th><th>Ocupación</th><th>Aprobadas</th><th>Reprobadas</th><th>Ofertas laborales</th><th>Estado</th></tr></thead><tbody>
<?php if(!$byAlliance):?><tr><td colspan="7" class="empty">Sin alianzas registradas.</td></tr><?php endif;?>
<?php foreach($byAlliance as $a):?><tr><td><strong><?= e($a['name']) ?></strong></td><td><?= (int)$a['used'] ?> / <?= (int)$a['slots'] ?></td><td><?= $pct((int)$a['used'],(int)$a['slots']) ?></td><td><?= (int)$a['approved'] ?></td><td><?= (int)$a['failed'] ?></td><td><?= (int)$a['jobs'] ?></td><td><span class="status status-<?= e($a['status']) ?>"><?= e(status_label($a['status'])) ?></span></td></tr><?php endforeach;?>
</tbody></table></div></section>

<section class="panel" id="tour-report-list"><div class="panel-head"><div><span class="eyebrow">DETALLE</span><h2>Pasantías del periodo</h2></div></div>
<div class="table-wrap"><table><thead><tr><th>Pasantía</th><th>Estudiante</th><th>Empresa</th><th>Docente</th><th>Documentos</th><th>Promedio</th><th>Resultado</th></tr></thead><tbody>
<?php if(!$list):?><tr><td colspan="7" class="empty">No hay pasantías en este periodo.</td></tr><?php endif;?>
<?php foreach($list as $i):?><tr><td><a href="<?= e(url('internships.php?id='.(int)$i['id'])) ?>"><strong><?= e($i['title']) ?></strong></a><small><?= e($i['organization']) ?></small></td><td><?= e($i['student']) ?></td><td><?= e($i['company']?:'Sin asignar') ?></td><td><?= e($i['teacher']?:'Sin asignar') ?></td><td><?= (int)$i['approved_docs'] ?> de <?= (int)$i['docs'] ?> aprobados</td><td><?= $i['avg_grade']!==null?e(number_format((float)$i['avg_grade'],1)):'—' ?></td><td><span class="status status-<?= $i['final_result']==='aprobada'?'approved':($i['final_result']==='reprobada'?'rejected':e($i['status'])) ?>"><?= e($i['final_result']?ucfirst($i['final_result']):status_label($i['status'])) ?></span></td></tr><?php endforeach;?>
</tbody></table></div></section>

<section class="panel" id="tour-audit"><div class="panel-head"><div><span class="eyebrow">TRAZABILIDAD</span><h2>Bitácora de auditoría</h2></div>
<form method="get" class="inline-form"><input type="hidden" name="desde" value="<?= e($from) ?>"><input type="hidden" name="hasta" value="<?= e($to) ?>"><select name="usuario" aria-label="Filtrar por usuario"><option value="">Todos los usuarios</option><?php foreach($people as $p):?><option value="<?= (int)$p['id'] ?>" <?= $who===(int)$p['id']?'selected':'' ?>><?= e($p['full_name']) ?></option><?php endforeach;?></select><button class="button button-small">Filtrar</button></form></div>
<div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Elemento</th><th>Detalle</th></tr></thead><tbody>
<?php if(!$recent):?><tr><td colspan="5" class="empty">Sin actividad en el periodo.</td></tr><?php endif;?>
<?php foreach($recent as $r):?><tr><td><?= e(date('d/m/Y H:i',strtotime($r['created_at']))) ?></td><td><?= e($r['full_name']?:'Sistema') ?><small><?= e($r['role']?role_label($r['role']):'') ?></small></td><td><?= e($actionLabels[$r['action']]??$r['action']) ?></td><td><?= e(($entityLabels[$r['entity_type']]??$r['entity_type']).($r['entity_id']?' #'.$r['entity_id']:'')) ?></td><td><?= e($r['details']?:'—') ?></td></tr><?php endforeach;?>
</tbody></table></div></section>
<?php app_footer(); ?>
