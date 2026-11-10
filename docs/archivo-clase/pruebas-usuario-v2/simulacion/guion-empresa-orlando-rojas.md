# Guion de prueba de usuario, sesión simulada · Empresa aliada

| Campo | Valor |
|---|---|
| Participante | Orlando Rojas Narváez |
| Perfil | Empresa aliada |
| Cuenta | La cuenta de empresa que creaste en la tarea A1 (NIT o correo de acceso) |
| Contraseña | La que entregó el administrador al crear la cuenta (en producción no existe `FET2026*`) |
| Fecha | 05 / 11 / 2026 |
| Navegador y equipo | Chromium, pantalla 1366 × 860, servidor de producción en máquina virtual |
| Observador(a) | Sesión simulada (recorrido cognitivo) |

> **Nota.** Este guion corresponde a una sesión simulada, realizada el 5 de noviembre de 2026 sobre el servidor de producción mediante un recorrido cognitivo con apoyo de un asistente de inteligencia artificial (ver el [informe de la sesión simulada](informe-sesion-simulada.md)). El resultado de cada tarea se comprobó en la plataforma; el tiempo es una estimación para una persona que usa la plataforma por primera vez, y la dificultad y las observaciones provienen de la revisión de cada pantalla. No recoge la opinión del participante, por lo que el cuestionario SUS y la firma quedan para la sesión real.

Asumes el papel de una empresa con alianza vigente con la FET, que publica convocatorias de pasantía, selecciona pasantes, avala sus documentos y evalúa su desempeño.

**Convenciones:** en *Resultado* escribe *Completó* (sin ayuda), *Con ayuda* o *No completó*. La *Dificultad* va de 1 (muy fácil) a 5 (muy difícil). El orden general de la sesión está en el [plan de pruebas](../../../pruebas-usuario/README.md#orden-de-la-sesión). La ayuda de cada pantalla está en el [manual de usuario](../../../manual/MANUAL_USUARIO.md).

## E1 · Entrar como empresa y revisar la alianza

**Turno en la sesión:** paso 2 · justo después de crear la alianza en A1.

**Qué debes lograr (sin mirar el manual):**

1. Cierra la sesión de administrador o usa una ventana de incógnito.
2. Entra con el NIT o el correo de la empresa que creaste.
3. Encuentra cuántos cupos tiene tu alianza y hasta cuándo es vigente.

**Resultado esperado:** La franja de la alianza muestra *0 de 2* cupos en uso y la fecha de vigencia.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| Completó | 1 (estimado) | 1 | [`E1-alianza.png`](../../../pruebas-usuario/evidencias/produccion/E1-alianza.png) |

**Observaciones:** Entra con el NIT sin problema. La franja de la alianza (convenio, *Vigente*, cupos en uso y vigencia) está arriba de *Convocatorias y empleo*. La empresa no tiene un menú «Alianzas», así que al principio puede buscarla en el menú.

---

## E2 · Publicar una convocatoria de pasantía

**Turno en la sesión:** paso 2 · 

**Qué debes lograr (sin mirar el manual):**

1. Crea una convocatoria de pasantía con modalidad, ubicación, fecha de cierre, descripción y requisitos.
2. Envíala a verificación de la FET.

**Resultado esperado:** La convocatoria queda *En revisión* y los docentes reciben la notificación.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| Completó | 3 (estimado) | 2 | [`E2-formulario.png`](../../../pruebas-usuario/evidencias/produccion/E2-formulario.png) |

**Observaciones:** El formulario es claro y avisa que cada pasante aceptado ocupa un cupo. Queda *En revisión* y el mensaje explica que la FET debe verificarla.

---

## E3 · Aceptar al estudiante

**Turno en la sesión:** paso 5 · cuando Juan José ya se postuló.

**Qué debes lograr (sin mirar el manual):**

1. Revisa las postulaciones recibidas.
2. Abre la hoja de vida de Juan José.
3. Acepta su postulación.
4. Comprueba que se creó la pasantía y que la franja de la alianza cambió.

**Resultado esperado:** Aparece la pasantía de Juan José en *Pasantías* y la alianza muestra *1 de 2* cupos en uso.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| Completó | 3 (estimado) | 3 | [`E3-postulaciones.png`](../../../pruebas-usuario/evidencias/produccion/E3-postulaciones.png) |

**Observaciones:** La hoja de vida se descarga desde la tabla *Postulaciones recibidas*. Para aceptar había que elegir «Aceptada» en un selector y pulsar «Guardar», cuando lo esperable es un botón «Aceptar». El mensaje «se creó la pasantía del estudiante» y el cupo 1 de 2 confirman el resultado.

---

## E4 · Avalar el plan de práctica

**Turno en la sesión:** paso 8 · cuando Juan José ya subió el documento.

**Qué debes lograr (sin mirar el manual):**

1. Abre la pasantía y descarga el plan de práctica.
2. Avala el documento con una observación.

**Resultado esperado:** El documento queda *Avalado por la empresa*.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| Completó | 2 (estimado) | 2 | [`E4-avalar.png`](../../../pruebas-usuario/evidencias/produccion/E4-avalar.png) |

**Observaciones:** Desde la pasantía, «Revisar» abre el documento con su descarga y la decisión «Avalar el documento». Es claro.

---

## E5 · Evaluar el desempeño del pasante

**Turno en la sesión:** paso 8 · 

**Qué debes lograr (sin mirar el manual):**

1. Registra la evaluación de desempeño con una calificación de 1 a 5 y comentarios.
2. Edítala una vez para corregir algo.

**Resultado esperado:** La evaluación aparece en *Evaluación de la empresa* y se puede editar mientras no haya veredicto final.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| Completó | 2 (estimado) | 1 | [`E5-evaluacion.png`](../../../pruebas-usuario/evidencias/produccion/E5-evaluacion.png) |

**Observaciones:** La evaluación (1 a 5 y comentarios) es simple y se puede editar. Al editarla, el estudiante recibe un segundo aviso igual al primero.

---

## E6 · Publicar una oferta laboral

**Turno en la sesión:** paso 12 · cuando el docente ya emitió el veredicto final. Al terminar, avisa a Hernán para que la verifique.

**Qué debes lograr (sin mirar el manual):**

1. Crea una publicación de tipo *Oferta laboral* y envíala a verificación.

**Resultado esperado:** La oferta queda *En revisión* y no ocupa cupos de la alianza. Cuando Hernán la verifica, queda *Publicada* y Juan José la puede ver.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| Completó | 2 (estimado) | 1 | [`E6-oferta.png`](../../../pruebas-usuario/evidencias/produccion/E6-oferta.png) |

**Observaciones:** Igual que la convocatoria; la plataforma recuerda que las ofertas laborales no usan cupos. El cupo del pasante ya aparece liberado (0 de 2).

---

## Cuestionario de satisfacción (SUS)

No se diligencia en la simulación: el SUS mide la opinión de la persona y solo lo puede responder el participante real.

## Comentarios finales

- ¿Qué fue lo más fácil? Publicar convocatorias y ofertas; los avisos sobre cupos son claros.
- ¿Qué fue lo más difícil o confuso? Aceptar una postulación con un selector y «Guardar» en vez de un botón directo.
- ¿Qué cambiarías o agregarías? Botones «Aceptar» y «Rechazar» en las postulaciones y un acceso a la alianza desde el inicio.

**Firma del participante:** No aplica (sesión simulada).
