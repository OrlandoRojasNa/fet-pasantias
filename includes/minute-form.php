<?php /** Formulario de acta con la estructura del Formato 1. $edit = fila del acta o null. */ $isEdit=!empty($edit); $v=fn(string $k)=>e($edit[$k]??''); ?>
<dialog id="<?= $isEdit?'minute-edit':'minute-form' ?>" class="wide"><form method="post" class="modal-form">
 <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="<?= $isEdit?'minute_update':'minute' ?>"><?php if($isEdit):?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif;?>
 <div class="modal-head"><div><span class="eyebrow">FORMATO 1 · ACTA DE REUNIÓN</span><h2><?= $isEdit?'Editar acta':'Registrar acta' ?></h2></div><button type="button" data-close aria-label="Cerrar">×</button></div>
 <div class="form-grid three">
  <label>Número<input name="number" value="<?= $isEdit?$v('number'):'ACT-'.str_pad((string)((int)scalar('SELECT COUNT(*) FROM minutes')+1),3,'0',STR_PAD_LEFT) ?>" maxlength="30" required></label>
  <label>Fecha<input type="date" name="meeting_on" value="<?= $isEdit?$v('meeting_on'):date('Y-m-d') ?>" required></label>
  <label>Estado<select name="status"><option value="draft">Borrador</option><option value="approved" <?= ($edit['status']??'')==='approved'?'selected':'' ?>>Aprobada</option></select></label>
  <label class="span-3">Tema para tratar<input name="title" value="<?= $v('title') ?>" maxlength="180" required></label>
  <label>Lugar o modalidad<input name="modality" value="<?= $v('modality') ?>" placeholder="Presencial · Campus FET"></label>
  <label>Hora de inicio<input type="time" name="starts_at" value="<?= e(substr((string)($edit['starts_at']??''),0,5)) ?>"></label>
  <label>Hora de cierre<input type="time" name="ends_at" value="<?= e(substr((string)($edit['ends_at']??''),0,5)) ?>"></label>
  <label class="span-3">Participantes<textarea name="attendees" placeholder="Nombre (rol), separados por coma" required><?= $v('attendees') ?></textarea></label>
  <label class="span-3">Objetivos de la reunión<textarea name="objectives"><?= $v('objectives') ?></textarea></label>
  <label class="span-3">Resumen (se muestra en la tarjeta)<textarea name="summary" required><?= $v('summary') ?></textarea></label>
  <label class="span-3">Desarrollo (un punto por línea)<textarea name="development" rows="5"><?= $v('development') ?></textarea></label>
  <label class="span-3">Conclusión<textarea name="conclusion"><?= $v('conclusion') ?></textarea></label>
  <label class="span-3">Modelo de trabajo<textarea name="work_model"><?= $v('work_model') ?></textarea></label>
  <label class="span-3">Agenda para futura reunión (un punto por línea)<textarea name="next_agenda"><?= $v('next_agenda') ?></textarea></label>
  <label class="span-3">Observaciones<textarea name="observations"><?= $v('observations') ?></textarea></label>
 </div>
 <div class="modal-actions"><button type="button" class="button button-ghost" data-close>Cancelar</button><button class="button button-primary"><?= icon('check',15) ?><?= $isEdit?'Guardar cambios':'Guardar acta' ?></button></div>
</form></dialog>
