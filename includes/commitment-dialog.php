<?php /** Diálogo para actualizar estado y evidencia de un compromiso. Espera $c y opcionalmente $returnTo. */ ?>
<dialog id="commit-<?= (int)$c['id'] ?>"><form method="post" enctype="multipart/form-data" class="modal-form">
 <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="commitment_update"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="return" value="<?= e(($returnTo??'detail')) ?>">
 <div class="modal-head"><div><span class="eyebrow">COMPROMISO · <?= e($c['number']??'') ?></span><h2>Actualizar avance</h2></div><button type="button" data-close aria-label="Cerrar">×</button></div>
 <p class="modal-quote"><?= e($c['description']) ?></p>
 <div class="form-grid">
  <label>Estado<select name="status"><?php foreach(['pending','in_progress','completed'] as $s):?><option value="<?= $s ?>" <?= $c['status']===$s||($c['status']==='overdue'&&$s==='pending')?'selected':'' ?>><?= e(status_label($s)) ?></option><?php endforeach;?></select></label>
  <label>Fecha de entrega<input value="<?= e(date('d/m/Y',strtotime($c['due_on']))) ?>" readonly></label>
  <label class="span-2">Evidencia<textarea name="evidence" placeholder="<?= e($c['expected_evidence']?'Esperada: '.$c['expected_evidence']:'Describe qué se entregó y dónde está') ?>"><?= e($c['evidence']) ?></textarea></label>
  <label class="span-2">Archivo de evidencia (opcional, máx. 5 MB)<input type="file" name="evidence_file" accept=".pdf,.png,.jpg,.jpeg,.webp,.docx,.xlsx,.pptx,.csv,.txt,.sql,.zip"><?php if($c['evidence_file']):?><small>Archivo actual: <?= e($c['evidence_name']) ?>. Si subes otro, lo reemplaza.</small><?php endif;?></label>
 </div>
 <p class="hint"><?= icon('alert',14) ?>Para marcarlo como «Completado» debes describir o adjuntar la evidencia.</p>
 <div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary"><?= icon('check',15) ?>Guardar avance</button></div>
</form></dialog>
