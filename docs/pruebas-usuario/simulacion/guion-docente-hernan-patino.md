# Guion de prueba de usuario, sesión simulada · Docente líder de pasantías

| Campo | Valor |
|---|---|
| Participante | Hernán Felipe Patiño Rico |
| Perfil | Docente líder de pasantías |
| Cuenta | hernan.patino@fet.edu.co (o identificación 1000000004) |
| Contraseña | La que entregó el administrador al crear la cuenta (en producción no existe `FET2026*`) |
| Fecha | 05 / 11 / 2026 |
| Navegador y equipo | Chromium, pantalla 1366 × 860, servidor de producción en máquina virtual |
| Observador(a) | Sesión simulada (recorrido cognitivo) |

> **Nota.** Este guion corresponde a una sesión simulada, realizada el 5 de noviembre de 2026 sobre el servidor de producción mediante un recorrido cognitivo con apoyo de un asistente de inteligencia artificial (ver el [informe de la sesión simulada](informe-sesion-simulada.md)). El resultado de cada tarea se comprobó en la plataforma; el tiempo es una estimación para una persona que usa la plataforma por primera vez, y la dificultad y las observaciones provienen de la revisión de cada pantalla. No recoge la opinión del participante, por lo que el cuestionario SUS y la firma quedan para la sesión real.

Asumes el papel del docente que revisa, audita, gestiona y aprueba las pasantías de los estudiantes.

**Convenciones:** en *Resultado* escribe *Completó* (sin ayuda), *Con ayuda* o *No completó*. La *Dificultad* va de 1 (muy fácil) a 5 (muy difícil). El orden general de la sesión está en el [plan de pruebas](../README.md#orden-de-la-sesión). La ayuda de cada pantalla está en el [manual de usuario](../../manual/MANUAL_USUARIO.md).

## D1 · Entrar y revisar el panel

**Turno en la sesión:** paso 3 · cuando la empresa ya envió su convocatoria a verificación.

**Qué debes lograr (sin mirar el manual):**

1. Entra con tu cuenta.
2. Identifica cuántas publicaciones hay por verificar y cuántos documentos esperan calificación.

**Resultado esperado:** Ves el panel con la cola de revisión, las publicaciones por verificar y las pasantías sin responsables.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| Completó | 1 (estimado) | 1 | [`D1-panel.png`](../evidencias/produccion/D1-panel.png) |

**Observaciones:** El panel resume lo importante: «1 Publicaciones por verificar» con su enlace «Verificar», la cola de revisión y las pasantías sin responsables.

---

## D2 · Verificar la convocatoria de la empresa

**Turno en la sesión:** paso 3 · 

**Qué debes lograr (sin mirar el manual):**

1. Busca la convocatoria que la empresa de Orlando envió a verificación.
2. Revísala y publícala como verificada.

**Resultado esperado:** La convocatoria queda *Publicada* y la empresa recibe una notificación.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| Completó | 1 (estimado) | 2 | [`D2-verificar.png`](../evidencias/produccion/D2-verificar.png) |

**Observaciones:** Para verificar había que elegir «Publicada (verificada)» en el selector y luego pulsar «Verificar». Con el valor que traía el selector por defecto, el botón no publicaba la convocatoria, y eso puede confundir la primera vez.

---

## D3 · Asignarte como docente de la pasantía

**Turno en la sesión:** paso 6 · cuando la empresa aceptó a Juan José.

**Qué debes lograr (sin mirar el manual):**

1. Encuentra la pasantía nueva de Juan José (aparece en *Pasantías sin responsables*).
2. Asígnate como docente líder y define la fecha de fin de la pasantía.

**Resultado esperado:** En el detalle de la pasantía apareces como *Docente líder asignado*.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| Completó | 2 (estimado) | 2 | [`D3-asignar.png`](../evidencias/produccion/D3-asignar.png) |

**Observaciones:** El panel la muestra en *Pasantías sin responsables* con el enlace «Asignar». El diálogo pide empresa, docente, fechas y estado; los valores sugeridos son correctos.

---

## D4 · Devolver un documento con nota baja

**Turno en la sesión:** paso 9 · cuando Juan José ya subió el plan y la empresa lo avaló.

**Qué debes lograr (sin mirar el manual):**

1. Abre el plan de práctica de Juan José.
2. Intenta **aprobarlo** con nota 2.5 y observa qué pasa.
3. Devuélvelo con nota 2.5 y una observación de qué debe corregir.

**Resultado esperado:** La plataforma no permite aprobar con menos de 3.0. El documento queda *Devuelto* con tu nota y observación, y Juan José recibe una notificación.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| Completó | 3 (estimado) | 2 | [`D4-bloqueo-aprobar.png`](../evidencias/produccion/D4-bloqueo-aprobar.png) |

**Observaciones:** Al intentar aprobar con 2.5 la plataforma lo impide con un mensaje claro y deja al docente en la misma pasantía (corrección F-02). Luego elige «Devolver», con la nota y la observación, sin problema. La ayuda del campo de nota («con 3.0 o más se aprueba») orienta bien.

---

## D5 · Aprobar la corrección

**Turno en la sesión:** paso 11 · cuando Juan José subió la versión corregida.

**Qué debes lograr (sin mirar el manual):**

1. Revisa el documento corregido.
2. Apruébalo con una nota de 3.0 o más.

**Resultado esperado:** El documento queda *Aprobado por el docente* y el promedio de la pasantía se actualiza.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| Completó | 1 (estimado) | 1 | [`D5-aprobar.png`](../evidencias/produccion/D5-aprobar.png) |

**Observaciones:** Mismo diálogo que en D4, ya conocido. El promedio 4.2 / 5.0 se actualiza en el encabezado de la pasantía.

---

## D6 · Emitir el veredicto final

**Turno en la sesión:** paso 11 · 

**Qué debes lograr (sin mirar el manual):**

1. Revisa la evaluación de desempeño que dejó la empresa.
2. Emite el veredicto final con su justificación.
3. Intenta modificarlo después.

**Resultado esperado:** La pasantía queda *Finalizada* y *Aprobada*. El veredicto ya no se puede cambiar y el *Historial de la pasantía* muestra todos los pasos.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| Completó | 2 (estimado) | 1 | [`D6-veredicto.png`](../evidencias/produccion/D6-veredicto.png) |

**Observaciones:** El diálogo resume la evaluación de la empresa, los documentos aprobados y el promedio, y sugiere el resultado. Después el botón de veredicto desaparece y el historial muestra todo el recorrido.

---

## D7 · Reportes y auditoría

**Turno en la sesión:** paso 14 · 

**Qué debes lograr (sin mirar el manual):**

1. Abre *Reportes y auditoría* y consulta las cifras del periodo.
2. Revisa la ocupación de cupos por empresa.
3. Filtra la bitácora por el usuario de Juan José.
4. Exporta el CSV y ábrelo en Excel.

**Resultado esperado:** Ves la pasantía aprobada en las cifras, la bitácora filtrada y un CSV con las pasantías del periodo.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| Completó | 3 (estimado) | 2 | [`D7-reportes.png`](../evidencias/produccion/D7-reportes.png) |

**Observaciones:** Las cifras y la ocupación por empresa son claras. En la bitácora, la columna *Elemento* muestra «Documento #1» o «Publicación #2» en lugar del nombre, y la corrección subida aparece sin detalle («—»). El CSV abre bien en Excel.

---

## Cuestionario de satisfacción (SUS)

No se diligencia en la simulación: el SUS mide la opinión de la persona y solo lo puede responder el participante real.

## Comentarios finales

- ¿Qué fue lo más fácil? El panel con la cola de revisión y el diálogo del veredicto, que sugiere el resultado.
- ¿Qué fue lo más difícil o confuso? El selector de verificación, que ya aparece en «Publicada» antes de verificar.
- ¿Qué cambiarías o agregarías? Mostrar el nombre del documento o la publicación en la bitácora en lugar del número.

**Firma del participante:** No aplica (sesión simulada).
