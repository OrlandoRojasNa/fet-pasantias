<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require_auth();
$u=current_user();
$first=mb_strtoupper(explode(' ',$u['full_name'])[0]);
$dateCard='<div class="date-card"><span>'.e(fecha_es(null,'day')).'</span><strong>'.date('d').'</strong><small>'.e(fecha_es(null,'month')).'</small></div>';

if(has_role('company')){
    $stats=[
        'total'=>(int)scalar('SELECT COUNT(*) FROM opportunities WHERE created_by=?',[$u['id']]),
        'published'=>(int)scalar("SELECT COUNT(*) FROM opportunities WHERE created_by=? AND status='published'",[$u['id']]),
        'review'=>(int)scalar("SELECT COUNT(*) FROM opportunities WHERE created_by=? AND status='review'",[$u['id']]),
        'applications'=>(int)scalar('SELECT COUNT(*) FROM applications a JOIN opportunities o ON o.id=a.opportunity_id WHERE o.created_by=?',[$u['id']]),
    ];
    $mine=rows('SELECT o.*,(SELECT COUNT(*) FROM applications a WHERE a.opportunity_id=o.id) applicants FROM opportunities o WHERE o.created_by=? ORDER BY o.created_at DESC LIMIT 6',[$u['id']]);
    app_header('Panel de empresa','EMPRESA DE PASANTÍA');
    ?>
    <section class="welcome-banner" id="tour-welcome"><div><span class="eyebrow">HOLA, <?= e($first) ?></span><h2>Gestiona las vacantes de tu empresa.</h2><p>Registra prácticas, envíalas a revisión de la FET y decide sobre las postulaciones que recibas.</p></div><?= $dateCard ?></section>
    <section class="metric-grid" id="tour-metrics">
     <article class="metric"><span class="metric-icon tone-ods8"><?= icon('briefcase',22) ?></span><div><strong><?= $stats['total'] ?></strong><span>Vacantes creadas</span></div><a href="<?= e(url('opportunities.php')) ?>">Gestionar</a></article>
     <article class="metric"><span class="metric-icon tone-teal"><?= icon('check-circle',22) ?></span><div><strong><?= $stats['published'] ?></strong><span>Publicadas</span></div><a href="<?= e(url('opportunities.php')) ?>">Consultar</a></article>
     <article class="metric"><span class="metric-icon tone-gold"><?= icon('clock',22) ?></span><div><strong><?= $stats['review'] ?></strong><span>En revisión de la FET</span></div><a href="<?= e(url('opportunities.php')) ?>">Revisar</a></article>
     <article class="metric"><span class="metric-icon tone-ods17"><?= icon('users',22) ?></span><div><strong><?= $stats['applications'] ?></strong><span>Postulaciones recibidas</span></div><a href="<?= e(url('opportunities.php')) ?>">Ver postulantes</a></article>
    </section>
    <section class="panel" id="tour-my-commitments"><div class="panel-head"><div><span class="eyebrow">MI EMPRESA</span><h2>Vacantes recientes</h2></div><a href="<?= e(url('opportunities.php')) ?>"><?= icon('plus',14) ?>Nueva vacante</a></div>
    <?php if(!$mine):?><div class="empty">Aún no registras vacantes. Crea la primera desde «Oportunidades».</div><?php else:?><div class="compact-list"><?php foreach($mine as $o):?><a href="<?= e(url('opportunities.php')) ?>"><span class="list-icon tone-ods8"><?= icon('briefcase',18) ?></span><span><strong><?= e($o['title']) ?></strong><small><?= e(status_label($o['status'])) ?> · <?= (int)$o['applicants'] ?> postulaciones</small></span><time><?= e(date('d/m/Y',strtotime($o['closes_on']))) ?></time></a><?php endforeach;?></div><?php endif;?></section>
    <?php app_footer(); exit;
}

refresh_overdue_commitments();
$stats=[
    'opportunities'=>(int)scalar("SELECT COUNT(*) FROM opportunities WHERE status='published' AND closes_on>=CURDATE()"),
    'projects'=>(int)scalar("SELECT COUNT(*) FROM projects WHERE status='active'"),
    'agreements'=>(int)scalar("SELECT COUNT(*) FROM agreements WHERE status='active'"),
    'pending'=>(int)scalar("SELECT COUNT(*) FROM commitments WHERE responsible_id=? AND status IN ('pending','in_progress','overdue')",[$u['id']]),
];
$opportunities=rows("SELECT * FROM opportunities WHERE status='published' AND closes_on>=CURDATE() ORDER BY closes_on LIMIT 4");
$commitments=rows("SELECT c.*,m.number FROM commitments c JOIN minutes m ON m.id=c.minute_id WHERE c.responsible_id=? AND c.status IN ('pending','in_progress','overdue') ORDER BY c.due_on LIMIT 5",[$u['id']]);
$recentDone=$commitments?[]:rows("SELECT c.*,m.number FROM commitments c JOIN minutes m ON m.id=c.minute_id WHERE c.responsible_id=? AND c.status='completed' ORDER BY c.completed_at DESC LIMIT 3",[$u['id']]);
$overall=row("SELECT COUNT(*) t,COALESCE(SUM(status='completed'),0) d FROM commitments");
$overallPct=$overall['t']?(int)round($overall['d']/$overall['t']*100):0;
$lastMinute=row('SELECT id,number,title,meeting_on FROM minutes ORDER BY meeting_on DESC,id DESC LIMIT 1');
app_header('Panel principal','RESUMEN INSTITUCIONAL');
?>
<section class="welcome-banner" id="tour-welcome"><div><span class="eyebrow">HOLA, <?= e($first) ?></span><h2>Conecta tu trabajo con los objetivos de la FET.</h2><p>Oportunidades, proyectos y compromisos en un mismo lugar. Si es tu primera vez, el recorrido guiado te muestra cada sección.</p></div><?= $dateCard ?></section>
<section class="metric-grid" id="tour-metrics">
 <article class="metric"><span class="metric-icon tone-ods8"><?= icon('briefcase',22) ?></span><div><strong><?= $stats['opportunities'] ?></strong><span>Oportunidades abiertas</span></div><a href="<?= e(url('opportunities.php')) ?>">Ver todas</a></article>
 <article class="metric"><span class="metric-icon tone-ods9"><?= icon('rocket',22) ?></span><div><strong><?= $stats['projects'] ?></strong><span>Proyectos activos</span></div><a href="<?= e(url('projects.php')) ?>">Seguimiento</a></article>
 <article class="metric"><span class="metric-icon tone-ods17"><?= icon('handshake',22) ?></span><div><strong><?= $stats['agreements'] ?></strong><span>Convenios vigentes</span></div><a href="<?= e(url('alliances.php')) ?>">Consultar</a></article>
 <article class="metric"><span class="metric-icon tone-fet"><?= icon('clipboard',22) ?></span><div><strong><?= $stats['pending'] ?></strong><span>Mis compromisos abiertos</span></div><a href="<?= e(url('minutes.php')) ?>">Gestionar</a></article>
</section>
<div class="two-column">
 <section class="panel"><div class="panel-head"><div><span class="eyebrow">ODS 8</span><h2>Oportunidades destacadas</h2></div><a href="<?= e(url('opportunities.php')) ?>">Explorar</a></div>
 <?php if(!$opportunities):?><div class="empty">Aún no hay oportunidades publicadas.</div><?php else:?><div class="compact-list"><?php foreach($opportunities as $o):?><a href="<?= e(url('opportunities.php?type='.$o['type'])) ?>"><span class="list-icon tone-ods8"><?= icon($o['type']==='formacion'?'graduation':($o['type']==='empleo'?'building':'briefcase'),18) ?></span><span><strong><?= e($o['title']) ?></strong><small><?= e($o['organization']) ?> · <?= e(status_label($o['type'])) ?></small></span><time>Cierra <?= e(date('d/m',strtotime($o['closes_on']))) ?></time></a><?php endforeach;?></div><?php endif;?></section>
 <section class="panel" id="tour-my-commitments"><div class="panel-head"><div><span class="eyebrow">MI TRABAJO</span><h2>Compromisos próximos</h2></div><a href="<?= e(url('minutes.php')) ?>">Ver actas</a></div>
 <?php if($commitments):?><div class="task-list"><?php foreach($commitments as $c):?><a href="<?= e(url('minutes.php?id='.(int)$c['minute_id'])) ?>"><span class="status-dot <?= $c['status']==='overdue'?'danger':'' ?>"></span><div><strong><?= e(mb_strimwidth($c['description'],0,90,'…')) ?></strong><small>Acta <?= e($c['number']) ?> · <?= e(status_label($c['status'])) ?></small></div><time><?= e(date('d/m/Y',strtotime($c['due_on']))) ?></time></a><?php endforeach;?></div>
 <?php else:?><div class="empty-ok"><?= icon('check-circle',26) ?><p>No tienes compromisos abiertos.<?php if($recentDone):?> Los últimos que cerraste:<?php endif;?></p></div><?php if($recentDone):?><div class="task-list"><?php foreach($recentDone as $c):?><a href="<?= e(url('minutes.php?id='.(int)$c['minute_id'])) ?>"><span class="status-dot done"></span><div><strong><?= e(mb_strimwidth($c['description'],0,90,'…')) ?></strong><small>Acta <?= e($c['number']) ?> · Completado</small></div><time><?= e(date('d/m/Y',strtotime($c['due_on']))) ?></time></a><?php endforeach;?></div><?php endif;?><?php endif;?>
 <?php if($lastMinute):?><a class="last-minute" href="<?= e(url('minutes.php?id='.(int)$lastMinute['id'])) ?>"><?= icon('file',16) ?><span>Última acta: <strong><?= e($lastMinute['number']) ?></strong> · <?= e(fecha_es($lastMinute['meeting_on'],'short')) ?></span><b><?= $overallPct ?>% de compromisos cumplidos</b></a><?php endif;?>
 </section>
</div>
<section class="panel ods-panel" id="tour-ods"><div class="panel-head"><div><span class="eyebrow">IMPACTO DEL PILOTO</span><h2>Tres ejes, una sola trazabilidad</h2></div><?php if(has_role('admin','manager')):?><a href="<?= e(url('reports.php')) ?>">Ver indicadores</a><?php endif;?></div>
<div class="ods-cards"><article><b class="ods ods-8">8</b><div><strong>Trabajo decente</strong><span>Oportunidades verificadas y postulaciones con seguimiento.</span></div></article><article><b class="ods ods-9">9</b><div><strong>Innovación</strong><span>Proyectos con responsables, hitos y evidencias.</span></div></article><article><b class="ods ods-17">17</b><div><strong>Alianzas</strong><span>Convenios y compromisos con resultados comprobables.</span></div></article></div></section>
<?php app_footer(); ?>
