# Informe de pruebas de usuario de Pasantías FET 2.0.0

Este informe reúne las pruebas de usuario de Pasantías FET 2.0.0 para sus cuatro perfiles: estudiante, empresa aliada, docente líder de pasantías y administrador. Presenta dos ejercicios complementarios realizados sobre el plan de pruebas, que define veinticuatro tareas distribuidas entre esos perfiles.

El primer ejercicio fue la ejecución de los guiones de cada perfil en un servidor de producción, para comprobar que cada tarea puede completarse y que la plataforma responde como lo describe el *Manual de usuario*. El segundo fue una sesión simulada de uso, en la que cada tarea se recorrió desde la perspectiva de una persona que usa la plataforma por primera vez, para estimar tiempos, dificultad y puntos de confusión.

En los dos ejercicios se completaron todas las tareas. Se registraron diez hallazgos, dos de severidad media y ocho de severidad baja, y todos quedaron corregidos en la versión que se entrega.

## Plan de pruebas y participantes

El plan asigna a cada integrante del equipo un perfil de la plataforma, como se muestra en la Tabla 1. Las tareas se ejecutan en un orden de sesión que sigue el proceso real de una pasantía: la alianza se registra, la empresa publica, el docente verifica, el estudiante se postula, la empresa acepta, se entregan y califican los documentos y, al final, el docente emite el veredicto.

**Tabla 1**

*Participantes y perfiles del plan de pruebas*

| Participante | Perfil | Tareas |
|---|---|---|
| Juan José Sabogal García | Estudiante | S1 a S6 |
| Hernán Felipe Patiño Rico | Docente líder de pasantías | D1 a D7 |
| Orlando Rojas Narváez | Empresa aliada | E1 a E6 |
| Orlando Rojas Narváez | Administrador | P0 y A1 a A5 |

Como la base de producción no trae cuentas de demostración, la ejecución empezó con un paso de preparación, identificado como P0, en el que el administrador creó las cuentas del docente y del estudiante. Las cuentas usadas se resumen en la Tabla 2.

**Tabla 2**

*Cuentas utilizadas en las pruebas*

| Perfil | Cuenta | Origen |
|---|---|---|
| Administrador | orlando.rojas@fet.edu.co | Administrador inicial creado por el instalador |
| Docente líder | hernan.patino@fet.edu.co | Creada por el administrador en *Usuarios* (P0) |
| Estudiante | juan.sabogal@fet.edu.co | Creada por el administrador en *Usuarios* (P0) |
| Empresa | NIT 901222333, InnovaSoft Huila S.A.S. | Creada por el administrador al registrar la alianza (A1) |

## Ambiente y método

### Ambiente

Las pruebas se realizaron el 5 de noviembre de 2026 en un servidor Ubuntu Server 24.04.5 LTS instalado con el instalador automático del producto, con Apache 2.4.58, PHP 8.3.6, MariaDB 10.11.14 y HTTPS. La base de datos se instaló en modo de producción, sin datos de demostración y con un único administrador inicial. Las contraseñas usadas fueron de prueba y la base se borró al terminar.

### Ejecución de los guiones por perfil

Las tareas de los cuatro guiones se automatizaron con Playwright, una herramienta que controla un navegador Chromium. Cada perfil trabajó en una sesión independiente del navegador y las tareas se ejecutaron en el orden de la sesión. En cada tarea se verificaron los mensajes de la plataforma, los estados de los registros y el contenido de las pantallas, y se tomó una captura como evidencia.

### Sesión simulada de uso

La sesión simulada siguió el método del recorrido cognitivo: un evaluador recorre cada tarea como lo haría un usuario nuevo y se pregunta, en cada paso, si esa persona sabría qué hacer y si entendería la respuesta del sistema. Cada guion se recorrió en el papel del participante asignado. El recorrido se hizo con apoyo de un asistente de inteligencia artificial, que ejecutó las tareas en la plataforma y revisó cada pantalla; por esa razón, sus resultados no representan la opinión de los integrantes del equipo.

Para cada tarea se registraron cuatro datos. El resultado indica si la tarea se completó. El tiempo es una estimación, en minutos, de lo que tardaría una persona que usa la plataforma por primera vez. La dificultad se calificó de 1 (muy fácil) a 5 (muy difícil). Las observaciones describen lo que podría confundir a un usuario nuevo. Los hallazgos se clasificaron por severidad: alta cuando impiden completar la tarea, media cuando la tarea se completa con dificultad y baja cuando se trata de un detalle visual o de redacción.

La escala de usabilidad del sistema (SUS) no se aplicó en la simulación, porque mide la percepción de cada persona y su puntaje solo tiene sentido con las respuestas de los participantes. En la sesión con el equipo, un puntaje superior a 68 indica una usabilidad por encima del promedio.

## Resultados de la ejecución por perfil

Las veinticinco tareas ejecutadas, que incluyen las veinticuatro del plan y la preparación P0, se completaron en la corrida final, lo que equivale a una tasa de éxito del 100 %. No se registraron errores de JavaScript en ninguna pantalla. La Tabla 3 presenta el resultado de cada tarea en el orden de la sesión.

**Tabla 3**

*Resultado de las tareas ejecutadas en el servidor de producción*

| Paso | Código | Perfil | Tarea | Comprobación | Resultado |
|---|---|---|---|---|---|
| 0 | P0 | Administrador | Crear las cuentas del docente y del estudiante | Las dos cuentas quedan activas | Completada |
| 1 | A1 | Administrador | Registrar una alianza con una empresa nueva | La alianza aparece vigente con 0 de 2 cupos en uso | Completada |
| 2 | E1 | Empresa | Entrar y revisar la alianza | Ingresa con el NIT y ve los cupos y la vigencia | Completada |
| 2 | E2 | Empresa | Publicar una convocatoria de pasantía | La convocatoria queda en revisión | Completada |
| 3 | D1 | Docente | Revisar el panel | Ve la publicación pendiente y su notificación | Completada |
| 3 | D2 | Docente | Verificar la convocatoria | La convocatoria queda publicada | Completada |
| 4 | S1 | Estudiante | Entrar y ubicarse | Ve la convocatoria abierta y el tutorial se abre | Completada tras corregir H-01 |
| 4 | S2 | Estudiante | Postularse con hoja de vida | La postulación queda registrada | Completada |
| 5 | E3 | Empresa | Aceptar al estudiante | Descarga la hoja de vida; se crea la pasantía y se ocupa un cupo | Completada |
| 6 | D3 | Docente | Asignarse a la pasantía | Queda registrado como docente líder | Completada |
| 7 | S3 | Estudiante | Subir el plan de práctica | El documento queda en estado enviado | Completada |
| 8 | E4 | Empresa | Avalar el plan de práctica | Descarga y avala el documento | Completada |
| 8 | E5 | Empresa | Evaluar el desempeño | Guarda la evaluación y luego la modifica | Completada |
| 9 | D4 | Docente | Devolver el documento con nota baja | Impide aprobar con 2.5; lo devuelve con nota y observación | Completada tras corregir H-02 |
| 10 | S4 | Estudiante | Corregir el documento devuelto | Ve la observación y envía la versión corregida | Completada |
| 11 | D5 | Docente | Aprobar la corrección | El documento se aprueba con 4.2 | Completada |
| 11 | D6 | Docente | Emitir el veredicto final | La pasantía queda aprobada y el veredicto ya no puede cambiarse | Completada |
| 12 | E6 | Empresa | Publicar una oferta laboral | La oferta no ocupa cupos y el docente la verifica | Completada |
| 13 | S5 | Estudiante | Consultar el resultado final | Ve el resultado, la justificación y la evaluación de la empresa | Completada |
| 13 | S6 | Estudiante | Aplicar a la oferta laboral | Sin hoja de vida no puede enviar; con ella la postulación queda registrada | Completada |
| 14 | D7 | Docente | Revisar reportes y auditoría | Cifras del periodo, bitácora filtrada por usuario y archivo CSV | Completada |
| 15 | A2 | Administrador | Ampliar los cupos | No acepta cero cupos; amplía de 2 a 4 | Completada |
| 15 | A3 | Administrador | Crear un usuario, cambiar su contraseña y desactivarlo | La cuenta desactivada ya no puede ingresar | Completada |
| 15 | A4 | Administrador | Desbloquear una cuenta | Tras cinco intentos fallidos la cuenta se bloquea; se desbloquea y queda registrado | Completada |
| 15 | A5 | Administrador | Descargar el respaldo | Archivo SQL de 23,1 KB con su huella SHA-256 | Completada |

La primera corrida reveló dos errores de la plataforma, que se corrigieron antes de repetirla y que aparecen como H-01 y H-02 en la Tabla 6. El primero afectaba al tutorial, que no abría con ningún perfil. El segundo hacía que, cuando una acción fallaba dentro de una pasantía, el usuario terminara en el listado general en lugar de seguir en la pasantía que estaba revisando. Con las dos correcciones aplicadas, se reinstaló la base y se repitió la ejecución completa.

La ejecución también mostró tres puntos en los que los guiones suponían el ambiente de pruebas, y que se ajustaron. En producción no existen las cuentas del docente y del estudiante, por lo que el plan incluye ahora la preparación P0. La tarea S6 usaba una oferta laboral de demostración, así que la publicación de la oferta (E6) pasó al paso 12, antes de que el estudiante aplique. La tarea A4 usaba una cuenta que solo existe en el ambiente de pruebas y ahora usa la cuenta del estudiante participante.

## Resultados de la sesión simulada

Las veinticuatro tareas se completaron sin ayuda. El tiempo total estimado fue de 48 minutos y la dificultad promedio de 1,5 sobre 5. La Tabla 4 presenta el detalle por tarea y la Tabla 5 el resumen por perfil.

**Tabla 4**

*Tiempo estimado, dificultad y observación principal por tarea*

| Código | Perfil | Tarea | Tiempo (min) | Dificultad (1-5) | Observación principal |
|---|---|---|---|---|---|
| A1 | Administrador | Registrar alianza con empresa nueva | 4 | 3 | Formulario largo; la cuenta de la empresa pasaba inadvertida (H-08) |
| A2 | Administrador | Ampliar cupos | 1 | 1 | Directo; no acepta cero cupos |
| A3 | Administrador | Crear usuario y cambiar contraseña | 3 | 2 | El botón *Guardar* de cada fila es fácil de pasar por alto |
| A4 | Administrador | Desbloquear una cuenta | 2 | 1 | La cuenta bloqueada se identifica de inmediato |
| A5 | Administrador | Descargar respaldo | 1 | 1 | Faltaba explicar para qué sirve la huella SHA-256 (H-10) |
| E1 | Empresa | Entrar y revisar alianza | 1 | 1 | La alianza solo aparecía en *Convocatorias y empleo* (H-09) |
| E2 | Empresa | Publicar convocatoria | 3 | 2 | Claro; advierte que cada pasante ocupa un cupo |
| E3 | Empresa | Aceptar al estudiante | 3 | 3 | Se aceptaba con un selector y no con un botón (H-03) |
| E4 | Empresa | Avalar el plan de práctica | 2 | 2 | El diálogo reúne la descarga y la decisión |
| E5 | Empresa | Evaluar desempeño | 2 | 1 | Al editar, el estudiante recibía un aviso repetido (H-07) |
| E6 | Empresa | Publicar oferta laboral | 2 | 1 | Igual a la convocatoria; no usa cupos |
| D1 | Docente | Revisar el panel | 1 | 1 | El panel lleva directo a lo pendiente |
| D2 | Docente | Verificar convocatoria | 1 | 2 | *Verificar* no publicaba sin cambiar antes el selector (H-04) |
| D3 | Docente | Asignarse a la pasantía | 2 | 2 | Se encuentra en *Pasantías sin responsables* |
| D4 | Docente | Devolver documento con nota baja | 3 | 2 | Mensaje de bloqueo claro con nota 2.5 |
| D5 | Docente | Aprobar la corrección | 1 | 1 | Mismo diálogo de D4 |
| D6 | Docente | Emitir veredicto final | 2 | 1 | El diálogo sugiere el resultado según el promedio |
| D7 | Docente | Reportes y auditoría | 3 | 2 | La bitácora mostraba números en lugar de nombres (H-06) |
| S1 | Estudiante | Entrar y ubicarse | 2 | 1 | Tutorial de 11 pasos |
| S2 | Estudiante | Postularse con hoja de vida | 2 | 1 | La convocatoria se encuentra desde el inicio |
| S3 | Estudiante | Subir plan de práctica | 2 | 1 | Directo |
| S4 | Estudiante | Corregir documento devuelto | 2 | 2 | La observación del docente está en texto pequeño dentro de la tabla |
| S5 | Estudiante | Consultar resultado final | 1 | 1 | El texto de *Avisos* mencionaba «compromisos» (H-05) |
| S6 | Estudiante | Aplicar a oferta laboral | 2 | 1 | Sin hoja de vida no permite enviar, como se espera |

**Tabla 5**

*Resumen por perfil*

| Perfil | Tareas | Tiempo estimado (min) | Dificultad promedio |
|---|---|---|---|
| Administrador | 5 | 11 | 1,6 |
| Empresa | 6 | 13 | 1,7 |
| Docente | 7 | 13 | 1,6 |
| Estudiante | 6 | 11 | 1,2 |

La tarea con mayor dificultad estimada fue el registro de una alianza (A1), por la longitud del formulario. Le siguió la aceptación de un estudiante por parte de la empresa (E3), porque la acción se hacía con un selector y no con un botón. El perfil de estudiante obtuvo la dificultad promedio más baja, lo que resulta coherente con que sus tareas consisten sobre todo en consultar información y subir archivos.

## Hallazgos y correcciones

La Tabla 6 reúne los diez hallazgos de los dos ejercicios. Los dos de severidad media se detectaron en la ejecución por perfil y los ocho de severidad baja en la sesión simulada. Todos se corrigieron y se comprobaron con una nueva ejecución completa en el servidor, en la que las veinticinco tareas se completaron.

**Tabla 6**

*Hallazgos y su estado*

| ID | Tarea | Descripción | Severidad | Corrección | Estado |
|---|---|---|---|---|---|
| H-01 | S1 | El tutorial no abría en ninguna pantalla por un error de JavaScript | Media | Se reconstruyeron los pasos del recorrido para los cuatro perfiles | Cerrado |
| H-02 | D4 | Ante un error dentro de una pasantía, la plataforma enviaba al listado de pasantías | Media | La plataforma regresa al detalle de la pasantía | Cerrado |
| H-03 | E3 | La postulación se aceptaba eligiendo un estado en un selector y pulsando *Guardar* | Baja | Botones *Aceptar*, *En revisión* y *No seleccionar*, con confirmación | Cerrado |
| H-04 | D2 | *Verificar* no publicaba la convocatoria si no se cambiaba antes el selector | Baja | Botón directo *Verificar y publicar* | Cerrado |
| H-05 | S5 | La pantalla *Avisos* mencionaba un módulo que ya no existe | Baja | Texto nuevo sobre pasantías y postulaciones | Cerrado |
| H-06 | D7 | La bitácora mostraba números en lugar del nombre de cada elemento | Baja | La bitácora muestra el título del documento, la publicación, la pasantía, la empresa o el usuario | Cerrado |
| H-07 | E5 | Al editar la evaluación, el estudiante recibía un aviso idéntico al primero | Baja | El aviso dice «La empresa actualizó tu evaluación» | Cerrado |
| H-08 | A1 | En el formulario de alianza, la creación de la cuenta de la empresa pasaba inadvertida | Baja | Formulario dividido en dos secciones | Cerrado |
| H-09 | E1 | La información de la alianza solo aparecía en *Convocatorias y empleo* | Baja | La franja de la alianza aparece también en el inicio de la empresa | Cerrado |
| H-10 | A5 | La pantalla no explicaba para qué sirve la huella SHA-256 del respaldo | Baja | Texto explicativo en la pantalla de usuarios | Cerrado |

## Evidencias

Esta sección reúne las veintinueve capturas tomadas durante la corrida final en el servidor de producción, agrupadas por perfil. El código entre paréntesis indica la tarea de la Tabla 3 a la que corresponde cada captura.

### Administrador

**Figura 1**

*Creación de las cuentas del docente y del estudiante (P0)*

![Creación de las cuentas del docente y del estudiante (P0)](evidencias/produccion/P0-cuentas-creadas.png)

**Figura 2**

*Formulario de alianza dividido en dos secciones, corrección H-08 (A1)*

![Formulario de alianza dividido en dos secciones, corrección H-08 (A1)](evidencias/produccion/A1-formulario.png)

**Figura 3**

*Alianza registrada como vigente con 0 de 2 cupos en uso (A1)*

![Alianza registrada como vigente con 0 de 2 cupos en uso (A1)](evidencias/produccion/A1-alianza-registrada.png)

**Figura 4**

*Ampliación de los cupos de la alianza de 2 a 4 (A2)*

![Ampliación de los cupos de la alianza de 2 a 4 (A2)](evidencias/produccion/A2-ampliar.png)

**Figura 5**

*Usuario de prueba desactivado en la pantalla de usuarios y permisos (A3)*

![Usuario de prueba desactivado en la pantalla de usuarios y permisos (A3)](evidencias/produccion/A3-usuarios.png)

**Figura 6**

*Cuenta del estudiante bloqueada tras cinco intentos fallidos, con el botón para desbloquearla (A4)*

![Cuenta del estudiante bloqueada tras cinco intentos fallidos, con el botón para desbloquearla (A4)](evidencias/produccion/A4-bloqueada.png)

### Empresa

**Figura 7**

*Inicio de la empresa con la franja de la alianza, corrección H-09 (E1)*

![Inicio de la empresa con la franja de la alianza, corrección H-09 (E1)](evidencias/produccion/E1-alianza.png)

**Figura 8**

*Formulario de publicación de la convocatoria de pasantía (E2)*

![Formulario de publicación de la convocatoria de pasantía (E2)](evidencias/produccion/E2-formulario.png)

**Figura 9**

*Postulaciones recibidas con los botones de aceptación, corrección H-03 (E3)*

![Postulaciones recibidas con los botones de aceptación, corrección H-03 (E3)](evidencias/produccion/E3-postulaciones.png)

**Figura 10**

*Aval del plan de práctica por parte de la empresa (E4)*

![Aval del plan de práctica por parte de la empresa (E4)](evidencias/produccion/E4-avalar.png)

**Figura 11**

*Evaluación del desempeño del estudiante (E5)*

![Evaluación del desempeño del estudiante (E5)](evidencias/produccion/E5-evaluacion.png)

**Figura 12**

*Publicación de la oferta laboral, que no ocupa cupos (E6)*

![Publicación de la oferta laboral, que no ocupa cupos (E6)](evidencias/produccion/E6-oferta.png)

### Docente

**Figura 13**

*Panel del docente con la publicación pendiente y su notificación (D1)*

![Panel del docente con la publicación pendiente y su notificación (D1)](evidencias/produccion/D1-panel.png)

**Figura 14**

*Verificación de la convocatoria (D2)*

![Verificación de la convocatoria (D2)](evidencias/produccion/D2-verificar.png)

**Figura 15**

*Panel del docente con la pasantía nueva en la lista de pasantías sin responsables (D3)*

![Panel del docente con la pasantía nueva en la lista de pasantías sin responsables (D3)](evidencias/produccion/D3-sin-responsables.png)

**Figura 16**

*Asignación de la empresa evaluadora y del docente líder a la pasantía (D3)*

![Asignación de la empresa evaluadora y del docente líder a la pasantía (D3)](evidencias/produccion/D3-asignar.png)

**Figura 17**

*Mensaje que impide aprobar con nota 2.5, con el docente en la misma pasantía (D4)*

![Mensaje que impide aprobar con nota 2.5, con el docente en la misma pasantía (D4)](evidencias/produccion/D4-bloqueo-aprobar.png)

**Figura 18**

*Aprobación del documento corregido con nota 4.2 (D5)*

![Aprobación del documento corregido con nota 4.2 (D5)](evidencias/produccion/D5-aprobar.png)

**Figura 19**

*Formulario del veredicto final (D6)*

![Formulario del veredicto final (D6)](evidencias/produccion/D6-veredicto.png)

**Figura 20**

*Pasantía finalizada con veredicto e historial de auditoría (D6)*

![Pasantía finalizada con veredicto e historial de auditoría (D6)](evidencias/produccion/D6-finalizada.png)

**Figura 21**

*Reportes del periodo y bitácora de auditoría, corrección H-06 (D7)*

![Reportes del periodo y bitácora de auditoría, corrección H-06 (D7)](evidencias/produccion/D7-reportes.png)

### Estudiante

**Figura 22**

*Inicio del estudiante con la convocatoria abierta (S1)*

![Inicio del estudiante con la convocatoria abierta (S1)](evidencias/produccion/S1-inicio.png)

**Figura 23**

*Tutorial del estudiante después de la corrección H-01 (S1)*

![Tutorial del estudiante después de la corrección H-01 (S1)](evidencias/produccion/S1-tutorial.png)

**Figura 24**

*Postulación a la convocatoria con hoja de vida (S2)*

![Postulación a la convocatoria con hoja de vida (S2)](evidencias/produccion/S2-postulacion.png)

**Figura 25**

*Carga del plan de práctica (S3)*

![Carga del plan de práctica (S3)](evidencias/produccion/S3-subir.png)

**Figura 26**

*Documento devuelto con la observación del docente y envío de la corrección (S4)*

![Documento devuelto con la observación del docente y envío de la corrección (S4)](evidencias/produccion/S4-corregir.png)

**Figura 27**

*Avisos recibidos por el estudiante durante el proceso (S5)*

![Avisos recibidos por el estudiante durante el proceso (S5)](evidencias/produccion/S5-notificaciones.png)

**Figura 28**

*Resultado final con la justificación y la evaluación de la empresa (S5)*

![Resultado final con la justificación y la evaluación de la empresa (S5)](evidencias/produccion/S5-resultado.png)

**Figura 29**

*Postulación registrada en la oferta laboral (S6)*

![Postulación registrada en la oferta laboral (S6)](evidencias/produccion/S6-postulado.png)

## Conclusiones

Las veinticuatro tareas del plan pueden completarse en una instalación de producción de Pasantías FET 2.0.0, y los cuatro perfiles pueden realizar sus tareas principales sin ayuda. Las reglas del proceso se cumplen y sus mensajes son claros: el límite de cupos por alianza, la nota mínima de 3.0, la hoja de vida obligatoria en las ofertas laborales, el veredicto irreversible y el bloqueo por intentos fallidos. Los diez hallazgos quedaron corregidos antes de la entrega.

Estos resultados sirven de referencia para la sesión con los integrantes del equipo, en la que deben registrarse los tiempos reales, la dificultad percibida, las observaciones de cada persona y el cuestionario SUS. Si un participante no logra completar una tarea que aquí se completó, o la encuentra difícil, la causa probable es la claridad de la pantalla y no su funcionamiento, y conviene registrarla como un hallazgo nuevo.
