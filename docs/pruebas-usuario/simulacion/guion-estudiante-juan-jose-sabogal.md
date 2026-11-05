# Guion de prueba de usuario (sesión simulada) · Estudiante

| Campo | Valor |
|---|---|
| Participante | Juan José Sabogal García |
| Perfil | Estudiante |
| Cuenta | juan.sabogal@fet.edu.co (o identificación 1000000006) |
| Contraseña | La que entregó el administrador al crear la cuenta (en producción no existe `FET2026*`) |
| Fecha | 05 / 11 / 2026 |
| Navegador y equipo | Chromium, pantalla 1366 × 860, servidor de producción en máquina virtual |
| Observador(a) | Sesión simulada (asistente de IA) |

> **Sesión simulada.** Este guion fue diligenciado por el asistente de IA (Claude), asumiendo el papel del participante, sobre el servidor de producción recién instalado (Ubuntu 24.04, HTTPS) el 05/11/2026. El *resultado* de cada tarea se comprobó en la plataforma ([resultados de la ejecución](../resultados-ejecucion-produccion.md)). El *tiempo* es una **estimación** de lo que tardaría una persona que usa la plataforma por primera vez, y la *dificultad* y las *observaciones* salen de una revisión de cada pantalla. **No son la opinión del participante real**: el cuestionario SUS y la firma quedan para la sesión con el equipo, en los guiones originales de la carpeta superior.

Asumes el papel de un estudiante de Ingeniería de Software que busca pasantía, sube sus documentos y consulta su resultado final.

**Convenciones:** en *Resultado* marca ✅ (completó sin ayuda), 🟡 (completó con ayuda) o ❌ (no completó). La *Dificultad* va de 1 (muy fácil) a 5 (muy difícil). El orden general de la sesión está en el [plan de pruebas](../README.md#orden-de-la-sesión). La ayuda de cada pantalla está en el [manual de usuario](../../manual/MANUAL_USUARIO.md).

## S1 · Entrar y ubicarse

**Turno en la sesión:** paso 4 · cuando el docente ya verificó la convocatoria.

**Qué debes lograr (sin mirar el manual):**

1. Entra a la plataforma con tu cuenta.
2. Identifica en el inicio cuántas convocatorias de pasantía y ofertas laborales hay abiertas.
3. Abre el **Tutorial** y recórrelo.

**Resultado esperado:** Entras a *Mi pasantía* y ves los indicadores de convocatorias, ofertas, postulaciones y documentos por corregir.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| ✅ | 2 (estimado) | 1 | [`S1-tutorial.png`](../evidencias/produccion/S1-tutorial.png) |

**Observaciones:** El tutorial se abre solo la primera vez (11 pasos) y vuelve a abrirse con el botón «Tutorial». Los indicadores del inicio muestran 1 convocatoria y 0 ofertas.

---

## S2 · Postularse a una pasantía con hoja de vida

**Turno en la sesión:** paso 4 · 

**Qué debes lograr (sin mirar el manual):**

1. Busca la convocatoria de pasantía que publicó la empresa de Orlando.
2. Postúlate explicando por qué te interesa y adjunta tu hoja de vida en PDF.
3. Comprueba el estado de tu postulación.

**Resultado esperado:** La tarjeta dice *Ya te postulaste* y la postulación aparece en *Mis postulaciones* con estado *Enviada*.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| ✅ | 2 (estimado) | 1 | [`S2-postulacion.png`](../evidencias/produccion/S2-postulacion.png) |

**Observaciones:** La convocatoria se encuentra desde el inicio («Abiertas ahora») o desde el menú. El diálogo pide la motivación y la hoja de vida (opcional en pasantías). La tarjeta queda con «Ya te postulaste».

---

## S3 · Subir el plan de práctica

**Turno en la sesión:** paso 7 · cuando la empresa ya te aceptó.

**Qué debes lograr (sin mirar el manual):**

1. Revisa tus notificaciones: debe haber una que diga que tu pasantía quedó registrada.
2. Abre tu pasantía.
3. Sube un documento de tipo *Plan de práctica* en PDF.

**Resultado esperado:** El documento aparece en la tabla con estado *Enviado* y tu empresa y tu docente reciben una notificación.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| ✅ | 2 (estimado) | 1 | [`S3-subir.png`](../evidencias/produccion/S3-subir.png) |

**Observaciones:** «Subir documento» está arriba en la pasantía. El tipo *Plan de práctica* y el archivo bastan; el título es opcional.

---

## S4 · Corregir un documento devuelto

**Turno en la sesión:** paso 10 · cuando el docente devolvió el plan con una nota menor a 3.0.

**Qué debes lograr (sin mirar el manual):**

1. Encuentra la observación y la nota que dejó el docente.
2. Sube la versión corregida del documento explicando qué cambiaste.

**Resultado esperado:** El documento vuelve a estado de revisión y la nota anterior desaparece.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| ✅ | 2 (estimado) | 2 | [`S4-corregir.png`](../evidencias/produccion/S4-corregir.png) |

**Observaciones:** La fila del documento muestra la observación del docente y la nota 2.5, y el botón «Corregir». Hay que leer la columna de observaciones para saber qué cambiar; la notificación «Documento devuelto con observaciones» ayuda a llegar.

---

## S5 · Consultar el resultado final

**Turno en la sesión:** paso 13 · cuando el docente ya emitió el veredicto.

**Qué debes lograr (sin mirar el manual):**

1. Abre tu pasantía.
2. Lee la evaluación de la empresa, el veredicto del docente y la nota de tu documento.

**Resultado esperado:** La pasantía aparece *Finalizada* y *Aprobada*, con la justificación del docente.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| ✅ | 1 (estimado) | 1 | [`S5-resultado.png`](../evidencias/produccion/S5-resultado.png) |

**Observaciones:** La pasantía muestra *Finalizada* y *Aprobada* con la justificación del docente y la evaluación de la empresa. En *Avisos*, el texto de la parte superior todavía habla de «compromisos asignados», que ya no existen en la plataforma.

---

## S6 · Aplicar a una oferta laboral

**Turno en la sesión:** paso 13 · 

**Qué debes lograr (sin mirar el manual):**

1. Filtra las **Ofertas laborales**.
2. Aplica a la oferta que publicó la empresa en la tarea E6 (en el ambiente de pruebas también aparece *Desarrollador web junior*). Intenta primero enviarla **sin** hoja de vida y luego con ella.

**Resultado esperado:** Sin hoja de vida la plataforma no deja aplicar. Con la hoja de vida en PDF, la postulación queda registrada.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| ✅ | 2 (estimado) | 1 | [`S6-postulado.png`](../evidencias/produccion/S6-postulado.png) |

**Observaciones:** El filtro *Ofertas laborales* funciona. Sin hoja de vida el formulario no deja enviar, porque es obligatoria en las ofertas. Con ella la tarjeta queda en «Ya te postulaste · Enviada».

---

## Cuestionario de satisfacción (SUS)

No se diligencia en la simulación: el SUS mide la opinión de la persona y solo lo puede responder el participante real.

## Comentarios finales

- ¿Qué fue lo más fácil? Ver el resultado final y las observaciones del docente en la misma pantalla de la pasantía.
- ¿Qué fue lo más difícil o confuso? Corregir el documento devuelto: la observación está en una columna de texto pequeño dentro de la tabla.
- ¿Qué cambiarías o agregarías? Destacar la observación del docente cuando el documento está devuelto y actualizar el texto de la pantalla *Avisos*.

**Firma del participante:** No aplica (sesión simulada).
