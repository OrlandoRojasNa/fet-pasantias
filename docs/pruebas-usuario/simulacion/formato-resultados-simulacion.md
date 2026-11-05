# Formato de resultados · Pruebas de usuario (sesión simulada)

**Fecha de la sesión:** 05 / 11 / 2026 · **Versión probada:** 2.0.0 (rama `docs-manuales-pruebas`) · **Lugar:** servidor de producción en máquina virtual (Ubuntu Server 24.04, HTTPS)

> **Sesión simulada.** El asistente de IA (Claude) asumió los cuatro perfiles y siguió el orden de la sesión del [plan de pruebas](../README.md). El resultado de cada tarea se comprobó en la plataforma. Los tiempos son **estimaciones** para una persona que usa la plataforma por primera vez, y la dificultad y las observaciones salen de la revisión de cada pantalla. No reemplaza la sesión con Juan José, Hernán y Orlando, que se registra en el [formato original](../formato-resultados.md). Los guiones diligenciados están en esta misma carpeta.

## 1. Resumen por tarea

Copia aquí el resultado de cada guion: ✅ completó sin ayuda · 🟡 completó con ayuda · ❌ no completó.

| Código | Participante | Tarea | Resultado | Tiempo estimado (min) | Dificultad (1-5) | Observación principal |
|---|---|---|---|---|---|---|
| A1 | Orlando (Administrador) | Registrar alianza con empresa nueva | ✅ | 4 | 3 | Formulario largo; la opción de crear la cuenta de la empresa no se nota (H-08). |
| A2 | Orlando (Administrador) | Ampliar cupos | ✅ | 1 | 1 | Directo; no acepta 0 cupos. |
| A3 | Orlando (Administrador) | Crear usuario y cambiar contraseña | ✅ | 3 | 2 | El «Guardar» de cada fila para cambiar el perfil es fácil de pasar por alto. |
| A4 | Orlando (Administrador) | Desbloquear una cuenta | ✅ | 2 | 1 | La cuenta bloqueada se ve enseguida con su botón «Desbloquear». |
| A5 | Orlando (Administrador) | Descargar respaldo | ✅ | 1 | 1 | A la vista; falta explicar para qué sirve la huella SHA-256. |
| E1 | Orlando (Empresa) | Entrar y revisar alianza | ✅ | 1 | 1 | La alianza está en *Convocatorias y empleo*; no hay menú «Alianzas» (H-09). |
| E2 | Orlando (Empresa) | Publicar convocatoria | ✅ | 3 | 2 | Claro; avisa que cada pasante ocupa un cupo. |
| E3 | Orlando (Empresa) | Aceptar al estudiante | ✅ | 3 | 3 | Se acepta con un selector y «Guardar», no con un botón «Aceptar» (H-03). |
| E4 | Orlando (Empresa) | Avalar el plan de práctica | ✅ | 2 | 2 | Claro: «Revisar» trae la descarga y la decisión. |
| E5 | Orlando (Empresa) | Evaluar desempeño | ✅ | 2 | 1 | Simple; al editar llega un aviso repetido al estudiante (H-07). |
| E6 | Orlando (Empresa) | Publicar oferta laboral | ✅ | 2 | 1 | Igual que la convocatoria; no usa cupos. |
| D1 | Hernán (Docente) | Revisar el panel | ✅ | 1 | 1 | El panel lleva directo a lo pendiente. |
| D2 | Hernán (Docente) | Verificar convocatoria | ✅ | 1 | 2 | El selector ya dice «Publicada» antes de verificar (H-04). |
| D3 | Hernán (Docente) | Asignarse a la pasantía | ✅ | 2 | 2 | Se encuentra en *Pasantías sin responsables*. |
| D4 | Hernán (Docente) | Devolver documento con nota baja | ✅ | 3 | 2 | Bloqueo claro con 2.5; antes de la corrección H-02 sacaba al listado. |
| D5 | Hernán (Docente) | Aprobar la corrección | ✅ | 1 | 1 | Mismo diálogo de D4, ya conocido. |
| D6 | Hernán (Docente) | Emitir veredicto final | ✅ | 2 | 1 | El diálogo sugiere el resultado con el promedio. |
| D7 | Hernán (Docente) | Reportes y auditoría | ✅ | 3 | 2 | La bitácora muestra números en vez de nombres (H-06). |
| S1 | Juan José (Estudiante) | Entrar y ubicarse | ✅ | 2 | 1 | Tutorial de 11 pasos; antes de la corrección H-01 no abría. |
| S2 | Juan José (Estudiante) | Postularse con hoja de vida | ✅ | 2 | 1 | Se encuentra desde el inicio; la hoja de vida es opcional en pasantías. |
| S3 | Juan José (Estudiante) | Subir plan de práctica | ✅ | 2 | 1 | Directo. |
| S4 | Juan José (Estudiante) | Corregir documento devuelto | ✅ | 2 | 2 | La observación del docente está en texto pequeño dentro de la tabla. |
| S5 | Juan José (Estudiante) | Consultar resultado final | ✅ | 1 | 1 | Resultado claro; el texto de *Avisos* habla de «compromisos» (H-05). |
| S6 | Juan José (Estudiante) | Aplicar a oferta laboral | ✅ | 2 | 1 | Sin hoja de vida no deja enviar, como se espera. |

**Tasa de éxito:** tareas ✅ 24 de 24 (100 %) · con ayuda 🟡 0 · no completadas ❌ 0 · **Tiempo total estimado:** 48 minutos · **Dificultad promedio:** 1,5 de 5

| Perfil | Tareas | Tiempo estimado (min) | Dificultad promedio |
|---|---|---|---|
| Administrador | 5 | 11 | 1,6 |
| Empresa | 6 | 13 | 1,7 |
| Docente | 7 | 13 | 1,6 |
| Estudiante | 6 | 11 | 1,2 |

## 2. Satisfacción (SUS)

No se calcula en la simulación. El SUS mide la opinión de cada persona y solo tiene validez con las respuestas de los participantes reales; la tabla queda para la sesión con el equipo en el [formato original](../formato-resultados.md#2-satisfacción-sus).

## 3. Hallazgos

Severidad: **Alta** (impide completar la tarea) · **Media** (la tarea se completa con dificultad o con ayuda) · **Baja** (detalle visual o de redacción).

| ID | Tarea | Descripción del hallazgo | Pasos para reproducirlo | Severidad | Evidencia | Acción propuesta | Estado |
|---|---|---|---|---|---|---|---|
| H-01 | S1 | El tutorial no abría en ninguna pantalla (error de JavaScript `pages is not defined`). | Entrar con cualquier perfil y pulsar «Tutorial». | Media | [S1-tutorial.png](../evidencias/produccion/S1-tutorial.png) | Reconstruir los pasos del recorrido en `assets/js/tour.js`. | Cerrado |
| H-02 | D4 | Si una acción falla dentro de una pasantía (por ejemplo, aprobar con 2.5), la plataforma manda al listado de pasantías. | Como docente, intentar aprobar un documento con nota menor a 3.0. | Media | [D4-bloqueo-aprobar.png](../evidencias/produccion/D4-bloqueo-aprobar.png) | Volver al detalle de la pasantía cuando hay error. | Cerrado |
| H-03 | E3 | Para aceptar una postulación hay que elegir «Aceptada» en un selector y pulsar «Guardar»; no hay un botón «Aceptar». | Como empresa, abrir *Postulaciones recibidas*. | Baja | [E3-postulaciones.png](../evidencias/produccion/E3-postulaciones.png) | Agregar botones «Aceptar» y «Rechazar». | Abierto |
| H-04 | D2 | El selector de estado de una publicación *En revisión* ya muestra «Publicada (verificada)» antes de verificar. | Como docente, abrir *Convocatorias y empleo* con una publicación por verificar. | Baja | [D2-verificar.png](../evidencias/produccion/D2-verificar.png) | Mostrar el estado actual en el selector o usar un botón «Verificar» solo. | Abierto |
| H-05 | S5 | La pantalla *Avisos* dice «Aquí llegan los compromisos asignados…», texto del módulo de actas que ya no existe. | Abrir *Avisos* con cualquier perfil. | Baja | [S5-notificaciones.png](../evidencias/produccion/S5-notificaciones.png) | Cambiar el texto por uno sobre pasantías y postulaciones. | Abierto |
| H-06 | D7 | En la bitácora, la columna *Elemento* muestra «Documento #1» o «Publicación #2» en lugar del nombre, y la corrección subida no tiene detalle. | *Reportes y auditoría* → Bitácora. | Baja | [D7-reportes.png](../evidencias/produccion/D7-reportes.png) | Mostrar el título del documento o la publicación. | Abierto |
| H-07 | E5 | Al editar la evaluación de desempeño, el estudiante recibe un segundo aviso idéntico al primero. | Como empresa, guardar la evaluación y luego editarla. | Baja | [S5-notificaciones.png](../evidencias/produccion/S5-notificaciones.png) | Usar un texto distinto («actualizó tu evaluación») al editar. | Abierto |
| H-08 | A1 | El formulario de nueva alianza es largo y la opción de crear la cuenta de la empresa no se nota a primera vista. | Como administrador, *Alianzas* → «Nueva alianza». | Baja | [A1-formulario.png](../evidencias/produccion/A1-formulario.png) | Separar el formulario en dos secciones: empresa y cuenta de acceso. | Abierto |
| H-09 | E1 | La empresa no tiene un menú «Alianzas»; la información de su alianza está arriba de *Convocatorias y empleo*. | Entrar como empresa y buscar la alianza. | Baja | [E1-alianza.png](../evidencias/produccion/E1-alianza.png) | Mostrar la franja de la alianza también en el inicio. | Abierto |

## 4. Conclusiones

- **Lo que funcionó bien:** las 24 tareas de los cuatro perfiles se completaron en producción. Las reglas del proceso (cupos, nota mínima de 3.0, hoja de vida obligatoria en ofertas, veredicto irreversible, bloqueo por intentos fallidos) se cumplen y sus mensajes son claros. El panel de cada perfil lleva directo a lo pendiente.
- **Lo que hay que mejorar:** los dos errores de severidad media (H-01 y H-02) ya se corrigieron. Quedan siete detalles de severidad baja (H-03 a H-09), ninguno impide completar una tarea.
- **Decisión:** ☐ Lista para entrega · ☑ Lista con ajustes menores · ☐ Requiere correcciones antes de la entrega

**Responsables:** Juan José Sabogal García · Hernán Felipe Patiño Rico · Orlando Rojas Narváez (sesión simulada; pendiente la sesión con el equipo)
