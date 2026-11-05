<?php
declare(strict_types=1);
/** Panel de inicio: cada perfil ve lo que tiene pendiente en el proceso de pasantías. */
require __DIR__.'/includes/bootstrap.php';
require_auth();
$u=current_user();
$uid=(int)$u['id'];
$first=mb_strtoupper(explode(' ',$u['full_name'])[0]);
$dateCard='<div class="date-card"><span>'.e(fecha_es(null,'day')).'</span><strong>'.date('d').'</strong><small>'.e(fecha_es(null,'month')).'</small></div>';
$metric=fn(string $icon,string $tone,int|string $value,string $label,string $href,string $link)=>'<article class="metric"><span class="metric-icon tone-'.e($tone).'">'.icon($icon,22).'</span><div><strong>'.e($value).'</strong><span>'.e($label).'</span></div><a href="'.e(url($href)).'">'.e($link).'</a></article>';
$internshipList=function(array $items,string $empty): string {
    if(!$items) return '<div class="empty">'.e($empty).'</div>';
    $h='<div class="compact-list">';
    foreach($items as $i) $h.='<a href="'.e(url('internships.php?id='.(int)$i['id'])).'"><span class="list-icon tone-ods8">'.icon('file',18).'</span><span><strong>'.e($i['title']).'</strong><small>'.e($i['line']).'</small></span><time>'.e($i['badge']).'</time></a>';
    return $h.'</div>';
};

if(has_role('student')){
    $mine=rows("SELECT i.*,(SELECT COUNT(*) FROM internship_documents d WHERE d.internship_id=i.id AND d.status='returned') returned,(SELECT COUNT(*) FROM internship_documents d WHERE d.internship_id=i.id AND d.status='approved') approved FROM internships i WHERE i.student_id=? ORDER BY FIELD(i.status,'active','completed','cancelled'),i.created_at DESC",[$uid]);
    $active=array_values(array_filter($mine,fn($i)=>$i['status']==='active'))[0]??null;
    $stats=[
        'calls'=>(int)scalar("SELECT COUNT(*) FROM opportunities WHERE type='practica' AND status='published' AND closes_on>=CURDATE()"),
        'jobs'=>(int)scalar("SELECT COUNT(*) FROM opportunities WHERE type='empleo' AND status='published' AND closes_on>=CURDATE()"),
        'apps'=>(int)scalar("SELECT COUNT(*) FROM applications WHERE user_id=? AND status IN ('submitted','review')",[$uid]),
        'returned'=>$active?(int)$active['returned']:0,
    ];
    $items=array_map(fn($i)=>$i+['line'=>$i['organization'].' · '.(int)$i['approved'].' documento(s) aprobado(s)'.((int)$i['returned']?' · '.(int)$i['returned'].' por corregir':''),'badge'=>$i['final_result']?'Pasantía '.$i['final_result']:status_label($i['status'])],$mine);
    $calls=rows("SELECT * FROM opportunities WHERE type IN ('practica','empleo') AND status='published' AND closes_on>=CURDATE() ORDER BY closes_on LIMIT 5");
    app_header('Mi pasantía','ESTUDIANTE');
    ?>
    <section class="welcome-banner" id="tour-welcome"><div><span class="eyebrow">HOLA, <?= e($first) ?></span><h2><?= $active?'Tu pasantía está en curso.':'Encuentra tu pasantía.' ?></h2><p><?= $active?'Sube tus documentos, corrige lo que te devuelvan y consulta tus notas y el resultado final.':'Postúlate a una convocatoria de pasantía. Cuando una empresa te acepte, aquí verás tu pasantía.' ?></p></div><?= $dateCard ?></section>
    <section class="metric-grid" id="tour-metrics">
     <?= $metric('briefcase','ods8',$stats['calls'],'Convocatorias de pasantía abiertas','opportunities.php?type=practica','Ver convocatorias') ?>
     <?= $metric('building','ods9',$stats['jobs'],'Ofertas laborales abiertas','opportunities.php?type=empleo','Ver ofertas') ?>
     <?= $metric('send','gold',$stats['apps'],'Postulaciones en trámite','opportunities.php','Mis postulaciones') ?>
     <?= $metric('undo','ods17',$stats['returned'],'Documentos por corregir',$active?'internships.php?id='.(int)$active['id']:'internships.php','Ir a mi pasantía') ?>
    </section>
    <div class="two-column">
     <section class="panel" id="tour-my-work"><div class="panel-head"><div><span class="eyebrow">PASANTÍAS</span><h2>Mis pasantías</h2></div><a href="<?= e(url('internships.php')) ?>">Abrir</a></div><?= $internshipList($items,'Aún no tienes pasantía. Postúlate a una convocatoria.') ?></section>
     <section class="panel"><div class="panel-head"><div><span class="eyebrow">CONVOCATORIAS</span><h2>Abiertas ahora</h2></div><a href="<?= e(url('opportunities.php')) ?>">Explorar</a></div>
     <?php if(!$calls):?><div class="empty">No hay convocatorias abiertas.</div><?php else:?><div class="compact-list"><?php foreach($calls as $o):?><a href="<?= e(url('opportunities.php?type='.$o['type'])) ?>"><span class="list-icon tone-ods8"><?= icon($o['type']==='empleo'?'building':'briefcase',18) ?></span><span><strong><?= e($o['title']) ?></strong><small><?= e($o['organization']) ?> · <?= e(status_label($o['type'])) ?></small></span><time>Cierra <?= e(date('d/m',strtotime($o['closes_on']))) ?></time></a><?php endforeach;?></div><?php endif;?></section>
    </div>
    <?php app_footer(); exit;
}

if(has_role('company')){
    $alliance=company_alliance($uid);
    $stats=[
        'published'=>(int)scalar("SELECT COUNT(*) FROM opportunities WHERE created_by=? AND status='published' AND closes_on>=CURDATE()",[$uid]),
        'apps'=>(int)scalar("SELECT COUNT(*) FROM applications a JOIN opportunities o ON o.id=a.opportunity_id WHERE o.created_by=? AND a.status IN ('submitted','review')",[$uid]),
        'toEndorse'=>(int)scalar("SELECT COUNT(*) FROM internship_documents d JOIN internships i ON i.id=d.internship_id WHERE i.company_id=? AND d.status='submitted'",[$uid]),
    ];
    $interns=rows("SELECT i.*,s.full_name student,(SELECT COUNT(*) FROM internship_documents d WHERE d.internship_id=i.id AND d.status='submitted') pending FROM internships i JOIN users s ON s.id=i.student_id WHERE i.company_id=? ORDER BY FIELD(i.status,'active','completed','cancelled'),i.created_at DESC LIMIT 8",[$uid]);
    $items=array_map(fn($i)=>$i+['line'=>$i['student'].((int)$i['pending']?' · '.(int)$i['pending'].' documento(s) por avalar':'').(!$i['company_evaluated_at']&&$i['status']==='active'?' · evaluación pendiente':''),'badge'=>$i['final_result']?'Pasantía '.$i['final_result']:status_label($i['status'])],$interns);
    app_header('Panel de empresa','EMPRESA ALIADA');
    ?>
    <section class="welcome-banner" id="tour-welcome"><div><span class="eyebrow">HOLA, <?= e($first) ?></span><h2>Tus pasantes y convocatorias.</h2><p>Publica convocatorias con los cupos de tu alianza, elige a tus pasantes, avala sus documentos y evalúa su desempeño.</p></div><?= $dateCard ?></section>
    <section class="metric-grid" id="tour-metrics">
     <?= $metric('handshake','ods17',$alliance?((int)$alliance['available'].' de '.(int)$alliance['slots']):'Sin alianza','Cupos de pasantía disponibles','opportunities.php','Ver mi alianza') ?>
     <?= $metric('briefcase','ods8',$stats['published'],'Publicaciones abiertas','opportunities.php','Gestionar') ?>
     <?= $metric('users','gold',$stats['apps'],'Postulaciones por decidir','opportunities.php','Ver postulantes') ?>
     <?= $metric('file','teal',$stats['toEndorse'],'Documentos por avalar','internships.php','Revisar') ?>
    </section>
    <section class="panel" id="tour-my-work"><div class="panel-head"><div><span class="eyebrow">MIS PASANTES</span><h2>Pasantías de tu empresa</h2></div><a href="<?= e(url('internships.php')) ?>">Ver todas</a></div><?= $internshipList($items,'Aún no tienes pasantes. Acepta una postulación a tu convocatoria para crear la pasantía.') ?></section>
    <?php app_footer(); exit;
}

// Docente líder y administrador: visión global del proceso.
$stats=[
    'active'=>(int)scalar("SELECT COUNT(*) FROM internships WHERE status='active'"),
    'toReview'=>(int)scalar("SELECT COUNT(*) FROM internship_documents d JOIN internships i ON i.id=d.internship_id WHERE i.status='active' AND d.status IN ('submitted','endorsed')"),
    'toVerify'=>(int)scalar("SELECT COUNT(*) FROM opportunities WHERE status='review'"),
    'slots'=>(int)scalar("SELECT COALESCE(SUM(slots),0) FROM alliances WHERE status='active' AND (starts_on IS NULL OR starts_on<=CURDATE()) AND (ends_on IS NULL OR ends_on>=CURDATE())"),
];
$stats['free']=max(0,$stats['slots']-(int)scalar("SELECT COUNT(*) FROM internships i JOIN alliances a ON a.company_id=i.company_id WHERE i.status='active' AND a.status='active' AND (a.starts_on IS NULL OR a.starts_on<=CURDATE()) AND (a.ends_on IS NULL OR a.ends_on>=CURDATE())"));
$queue=rows("SELECT i.id,i.title,s.full_name student,COUNT(d.id) n,MIN(d.updated_at) since FROM internship_documents d JOIN internships i ON i.id=d.internship_id JOIN users s ON s.id=i.student_id WHERE i.status='active' AND d.status IN ('submitted','endorsed') GROUP BY i.id,i.title,s.full_name ORDER BY since LIMIT 8");
$items=array_map(fn($q)=>$q+['line'=>$q['student'].' · '.(int)$q['n'].' documento(s) por calificar','badge'=>'Desde '.date('d/m',strtotime($q['since']))],$queue);
$attention=rows("SELECT i.id,i.title,s.full_name student,i.teacher_id,i.company_id FROM internships i JOIN users s ON s.id=i.student_id WHERE i.status='active' AND (i.teacher_id IS NULL OR i.company_id IS NULL) ORDER BY i.created_at LIMIT 6");
$attentionItems=array_map(fn($a)=>$a+['line'=>$a['student'].' · '.(!$a['teacher_id']?'sin docente':'').(!$a['teacher_id']&&!$a['company_id']?' y ':'').(!$a['company_id']?'sin empresa evaluadora':''),'badge'=>'Asignar'],$attention);
app_header('Panel de pasantías',has_role('admin')?'ADMINISTRACIÓN':'DOCENTE LÍDER DE PASANTÍAS');
?>
<section class="welcome-banner" id="tour-welcome"><div><span class="eyebrow">HOLA, <?= e($first) ?></span><h2>Gestión documental de pasantías.</h2><p><?= has_role('admin')?'Registra alianzas y cupos, administra cuentas y supervisa el proceso de pasantías.':'Revisa y califica documentos, asigna responsables, audita cada pasantía y emite el veredicto final.' ?></p></div><?= $dateCard ?></section>
<section class="metric-grid" id="tour-metrics">
 <?= $metric('file','ods8',$stats['active'],'Pasantías en curso','internships.php','Ver pasantías') ?>
 <?= $metric('edit','gold',$stats['toReview'],'Documentos por calificar','internships.php','Revisar') ?>
 <?= $metric('briefcase','ods9',$stats['toVerify'],'Publicaciones por verificar','opportunities.php','Verificar') ?>
 <?= $metric('handshake','ods17',$stats['free'].' de '.$stats['slots'],'Cupos disponibles en alianzas','alliances.php',has_role('admin')?'Gestionar alianzas':'Ver alianzas') ?>
</section>
<div class="two-column">
 <section class="panel" id="tour-my-work"><div class="panel-head"><div><span class="eyebrow">COLA DE REVISIÓN</span><h2>Documentos esperando calificación</h2></div><a href="<?= e(url('internships.php')) ?>">Ver todas</a></div><?= $internshipList($items,'No hay documentos pendientes de calificación.') ?></section>
 <section class="panel"><div class="panel-head"><div><span class="eyebrow">POR ASIGNAR</span><h2>Pasantías sin responsables</h2></div></div><?= $internshipList($attentionItems,'Todas las pasantías en curso tienen docente y empresa evaluadora.') ?></section>
</div>
<?php app_footer(); ?>
