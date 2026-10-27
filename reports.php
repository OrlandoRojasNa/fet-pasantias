<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require_role('admin','manager');

refresh_overdue_commitments();
$from=valid_date($_GET['desde']??null)??date('Y-m-d',strtotime('-90 days'));
$to=valid_date($_GET['hasta']??null)??date('Y-m-d');
if($from>$to) [$from,$to]=[$to,$from];
$range=[$from.' 00:00:00',$to.' 23:59:59'];
$n=fn(string $sql,array $p=[])=>(int)scalar($sql,$p);

$data=[
 'published'=>$n("SELECT COUNT(*) FROM opportunities WHERE status='published' AND created_at BETWEEN ? AND ?",$range),
 'verified'=>$n("SELECT COUNT(*) FROM opportunities WHERE status='published' AND verified_at IS NOT NULL AND created_at BETWEEN ? AND ?",$range),
 'applications'=>$n('SELECT COUNT(*) FROM applications WHERE created_at BETWEEN ? AND ?',$range),
 'followed'=>$n("SELECT COUNT(*) FROM applications WHERE status NOT IN ('submitted','withdrawn') AND created_at BETWEEN ? AND ?",$range),
 'active_projects'=>$n("SELECT COUNT(*) FROM projects WHERE status IN ('active','completed') AND (starts_on IS NULL OR starts_on<=?) AND (ends_on IS NULL OR ends_on>=?)",[$to,$from]),
 'traceable_projects'=>$n("SELECT COUNT(DISTINCT p.id) FROM projects p JOIN milestones m ON m.project_id=p.id WHERE p.status IN ('active','completed') AND (p.starts_on IS NULL OR p.starts_on<=?) AND (p.ends_on IS NULL OR p.ends_on>=?) AND ((m.evidence IS NOT NULL AND m.evidence<>'') OR m.evidence_file IS NOT NULL)",[$to,$from]),
 'commitments'=>$n('SELECT COUNT(*) FROM commitments WHERE due_on BETWEEN ? AND ?',[$from,$to]),
 'done_commitments'=>$n("SELECT COUNT(*) FROM commitments WHERE status='completed' AND due_on BETWEEN ? AND ?",[$from,$to]),
 'agreements'=>$n("SELECT COUNT(*) FROM agreements WHERE status='active'"),
 'agreements_with_activity'=>$n("SELECT COUNT(DISTINCT a.id) FROM agreements a JOIN activities x ON x.agreement_id=a.id WHERE a.status='active' AND x.occurs_on BETWEEN ? AND ?",[$from,$to]),
];
$pct=static fn(int $a,int $b): ?int=>$b>0?(int)round($a/$b*100):null;
$indicators=[
 [8,'Oportunidades verificadas',$data['verified'],$data['published'],100],
 [8,'Postulaciones con respuesta',$data['followed'],$data['applications'],80],
 [9,'Proyectos con evidencia de avance',$data['traceable_projects'],$data['active_projects'],90],
 [17,'Compromisos cumplidos',$data['done_commitments'],$data['commitments'],90],
 [17,'Convenios vigentes con actividad',$data['agreements_with_activity'],$data['agreements'],75],
];
$byMinute=rows("SELECT m.id,m.number,m.title,m.meeting_on,COUNT(c.id) total,COALESCE(SUM(c.status='completed'),0) done,COALESCE(SUM(c.status='overdue'),0) overdue FROM minutes m LEFT JOIN commitments c ON c.minute_id=m.id WHERE m.meeting_on BETWEEN ? AND ? GROUP BY m.id ORDER BY m.meeting_on",[$from,$to]);
$byPerson=rows("SELECT u.full_name,COUNT(c.id) total,COALESCE(SUM(c.status='completed'),0) done FROM commitments c JOIN users u ON u.id=c.responsible_id WHERE c.due_on BETWEEN ? AND ? GROUP BY u.id ORDER BY total DESC",[$from,$to]);

if(($_GET['formato']??'')==='csv'){
    audit('export','report',null,"$from a $to");
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="indicadores_fet_'.$from.'_'.$to.'.csv"');
    $out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF");
    fputcsv($out,['Intranet Privada FET - Indicadores del periodo',$from,$to],';');
    fputcsv($out,[],';'); fputcsv($out,['ODS','Indicador','Cumplen','Evaluados','Porcentaje','Meta'],';');
    foreach($indicators as [$ods,$name,$a,$b,$t]) fputcsv($out,[$ods,$name,$a,$b,$pct($a,$b)===null?'N/A':$pct($a,$b).'%',$t.'%'],';');
    fputcsv($out,[],';'); fputcsv($out,['Acta','Tema','Fecha','Compromisos','Cumplidos','Vencidos','Cumplimiento'],';');
    foreach($byMinute as $m) fputcsv($out,[$m['number'],$m['title'],$m['meeting_on'],$m['total'],$m['done'],$m['overdue'],$pct((int)$m['done'],(int)$m['total'])===null?'N/A':$pct((int)$m['done'],(int)$m['total']).'%'],';');
    fputcsv($out,[],';'); fputcsv($out,['Responsable','Compromisos','Cumplidos'],';');
    foreach($byPerson as $p) fputcsv($out,[$p['full_name'],$p['total'],$p['done']],';');
    fclose($out); exit;
}

$recent=rows('SELECT a.*,u.full_name FROM audit_events a LEFT JOIN users u ON u.id=a.user_id WHERE a.created_at BETWEEN ? AND ? ORDER BY a.created_at DESC LIMIT 25',$range);
$actionLabels=['login'=>'Inicio de sesión','logout'=>'Cierre de sesión','apply'=>'Postulación','status'=>'Cambio de estado','create'=>'Creación','update'=>'Edición','backup'=>'Respaldo SQL','export'=>'Exportación','download'=>'Descarga de evidencia','password'=>'Cambio de contraseña','role'=>'Cambio de perfil'];
$entityLabels=['session'=>'Sesión','commitment'=>'Compromiso','opportunity'=>'Oportunidad','application'=>'Postulación','project'=>'Proyecto','project_member'=>'Equipo','milestone'=>'Hito','partner'=>'Aliado','agreement'=>'Convenio','activity'=>'Actividad','minute'=>'Acta','user'=>'Usuario','finding'=>'Hallazgo','database'=>'Base de datos','report'=>'Reporte','evidence'=>'Evidencia'];
$qs=fn(array $extra=[])=>e(url('reports.php?'.http_build_query(array_merge(['desde'=>$from,'hasta'=>$to],$extra))));
app_header('Indicadores y auditoría','ODS 8 · 9 · 17');
?>
<div class="page-actions"><p>Indicadores operativos del piloto. Los porcentajes salen de los registros internos y no certifican el cumplimiento oficial de los ODS.</p><button class="button button-ghost" type="button" data-print><?= icon('printer',16) ?>Imprimir reporte</button></div>
<form method="get" class="period-bar panel" id="tour-period"><span class="kpi-icon tone-gold"><?= icon('calendar',20) ?></span><label>Desde<input type="date" name="desde" value="<?= e($from) ?>"></label><label>Hasta<input type="date" name="hasta" value="<?= e($to) ?>"></label><button class="button button-primary button-small"><?= icon('filter',14) ?>Aplicar</button><a class="button button-ghost button-small" href="<?= $qs(['formato'=>'csv']) ?>"><?= icon('download',14) ?>Exportar CSV</a></form>
<section class="impact-hero"><div><span class="eyebrow">PERIODO <?= e(date('d/m/Y',strtotime($from))) ?> AL <?= e(date('d/m/Y',strtotime($to))) ?></span><h2>Avance verificable del piloto</h2><p>Cada indicador conserva el total usado en el cálculo para evitar interpretaciones sin evidencia.</p></div><div class="impact-count"><strong><?= $data['published']+$data['active_projects']+$data['agreements'] ?></strong><span>registros activos</span></div></section>
<section class="indicator-grid" id="tour-indicators">
<?php foreach($indicators as [$ods,$name,$a,$b,$target]): $v=$pct($a,$b);?>
 <article class="indicator-card"><div class="indicator-head"><b class="ods ods-<?= $ods ?>"><?= $ods ?></b><span>Meta <?= $target ?>%</span></div><h2><?= e($name) ?></h2>
 <div class="ring" style="--value:<?= $v??0 ?>;--ring:var(--ods-<?= $ods ?>)"><strong><?= $v===null?'N/A':$v.'%' ?></strong></div>
 <p><?= $a ?> de <?= $b ?> registros evaluados<?= $v!==null?($v>=$target?' · <b class="ok-text">meta alcanzada</b>':' · por debajo de la meta'):'' ?></p></article>
<?php endforeach;?>
</section>
<div class="two-column">
 <section class="panel" id="tour-compliance"><div class="panel-head"><div><span class="eyebrow">ACTAS DEL PERIODO</span><h2>Cumplimiento por acta</h2></div></div>
 <?php if(!$byMinute):?><div class="empty">No hay actas en el periodo seleccionado.</div><?php endif;?>
 <div class="bar-list"><?php foreach($byMinute as $m): $v=$pct((int)$m['done'],(int)$m['total'])??0;?><a href="<?= e(url('minutes.php?id='.(int)$m['id'])) ?>" class="bar-row"><span><strong><?= e($m['number']) ?></strong><small><?= e(date('d/m/Y',strtotime($m['meeting_on']))) ?> · <?= (int)$m['done'] ?>/<?= (int)$m['total'] ?><?= $m['overdue']?' · '.(int)$m['overdue'].' vencidos':'' ?></small></span><div class="bar"><i style="width:<?= $v ?>%"></i></div><b><?= $v ?>%</b></a><?php endforeach;?></div></section>
 <section class="panel"><div class="panel-head"><div><span class="eyebrow">EQUIPO</span><h2>Compromisos por responsable</h2></div></div>
 <?php if(!$byPerson):?><div class="empty">Sin compromisos con entrega en el periodo.</div><?php endif;?>
 <div class="bar-list"><?php foreach($byPerson as $p): $v=$pct((int)$p['done'],(int)$p['total'])??0;?><div class="bar-row"><span><strong><?= e($p['full_name']) ?></strong><small><?= (int)$p['done'] ?> de <?= (int)$p['total'] ?> cumplidos</small></span><div class="bar"><i style="width:<?= $v ?>%"></i></div><b><?= $v ?>%</b></div><?php endforeach;?></div></section>
</div>
<section class="panel" id="tour-audit"><div class="panel-head"><div><span class="eyebrow">TRAZABILIDAD</span><h2>Actividad del sistema en el periodo</h2></div></div>
<div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Registro</th><th>Detalle</th></tr></thead><tbody>
<?php if(!$recent):?><tr><td colspan="5" class="empty">Sin eventos en el periodo.</td></tr><?php endif;?>
<?php foreach($recent as $ev): $detail=(string)($ev['details']?:'—');?><tr><td><?= e(date('d/m/Y H:i',strtotime($ev['created_at']))) ?></td><td><?= e($ev['full_name']?:'Sistema') ?></td><td><?= e($actionLabels[$ev['action']]??$ev['action']) ?></td><td><?= e($entityLabels[$ev['entity_type']]??$ev['entity_type']) ?> #<?= e($ev['entity_id']?:'—') ?></td><td><?= e(preg_match('/^[a-z_]+$/',$detail)?status_label($detail):$detail) ?></td></tr><?php endforeach;?>
</tbody></table></div></section>
<?php app_footer(); ?>
