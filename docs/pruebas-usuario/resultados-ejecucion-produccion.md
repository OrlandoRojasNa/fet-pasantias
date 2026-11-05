# Ejecución de los guiones de prueba por perfil en el servidor de producción de Pasantías FET 2.0.0

El plan de pruebas de usuario de Pasantías FET define veinticuatro tareas distribuidas entre los cuatro perfiles de la plataforma: estudiante, empresa aliada, docente líder de pasantías y administrador. Antes de realizar la sesión con los integrantes del equipo, se ejecutaron esas mismas tareas sobre el servidor de producción para comprobar que cada una puede completarse y que la plataforma responde como lo describe el *Manual de usuario* (Rojas Narváez et al., 2026b).

Este informe describe esa ejecución, realizada el 5 de noviembre de 2026. Las veinticinco tareas ejecutadas, que incluyen las veinticuatro del plan y una de preparación, se completaron en la corrida final. Durante la primera corrida se encontraron dos errores de la plataforma que se corrigieron antes de repetirla.

La ejecución verifica el funcionamiento de cada tarea, pero no mide la experiencia de las personas. El tiempo que tarda un usuario, la dificultad que percibe y su satisfacción, medida con la escala SUS (Brooke, 1996), solo pueden obtenerse en la sesión con los participantes.

## Método

### Ambiente

Se utilizó el servidor descrito como VM 3 en el informe de despliegue (Rojas Narváez et al., 2026a). Es una instalación de Ubuntu Server 24.04.5 LTS hecha con el instalador automático, con Apache 2.4.58, PHP 8.3.6, MariaDB 10.11.14 y HTTPS. La base de datos se instaló en modo de producción, sin datos de demostración y con un único administrador inicial.

### Procedimiento

Las tareas de los cuatro guiones se automatizaron con Playwright 1.63 (Microsoft, 2026), una herramienta que controla un navegador Chromium. Cada perfil trabajó en una sesión independiente del navegador, y las tareas se ejecutaron en el orden de la sesión definido en el plan de pruebas. En cada tarea se verificaron los mensajes de la plataforma, los estados de los registros y el contenido de las pantallas, y se tomó una captura como evidencia.

Como la base de producción no contiene las cuentas de demostración, la ejecución empezó con la puesta en marcha de la sección 8 del manual de despliegue. En ese paso, identificado como P0, el administrador creó las cuentas del docente y del estudiante (ver Tabla 1). Las contraseñas fueron de prueba, solo existieron en el servidor de la prueba y la base se borró al terminar.

**Tabla 1**

*Cuentas utilizadas en la ejecución*

| Perfil | Cuenta | Origen |
|---|---|---|
| Administrador | orlando.rojas@fet.edu.co | Administrador inicial creado por el instalador |
| Docente líder | hernan.patino@fet.edu.co | Creada por el administrador en *Usuarios* (P0) |
| Estudiante | juan.sabogal@fet.edu.co | Creada por el administrador en *Usuarios* (P0) |
| Empresa | NIT 901222333, InnovaSoft Huila S.A.S. | Creada por el administrador al registrar la alianza (A1) |

## Resultados

### Resultado por tarea

La Tabla 2 presenta el resultado de cada tarea en el orden de la sesión. La columna *Paso* corresponde al turno dentro de la sesión del plan de pruebas. Las veinticinco tareas se completaron, lo que equivale a una tasa de éxito del 100 %, y no se registraron errores de JavaScript en ninguna pantalla.

**Tabla 2**

*Resultado de las tareas ejecutadas en el servidor de producción*

| Paso | Código | Perfil | Tarea | Comprobación | Resultado |
|---|---|---|---|---|---|
| 0 | P0 | Administrador | Crear las cuentas del docente y del estudiante | Las dos cuentas quedan activas | Completada |
| 1 | A1 | Administrador | Registrar una alianza con una empresa nueva | La alianza aparece vigente con 0 de 2 cupos en uso | Completada |
| 2 | E1 | Empresa | Entrar y revisar la alianza | Ingresa con el NIT y ve los cupos y la vigencia | Completada |
| 2 | E2 | Empresa | Publicar una convocatoria de pasantía | La convocatoria queda en revisión | Completada |
| 3 | D1 | Docente | Revisar el panel | Ve la publicación pendiente y su notificación | Completada |
| 3 | D2 | Docente | Verificar la convocatoria | La convocatoria queda publicada | Completada |
| 4 | S1 | Estudiante | Entrar y ubicarse | Ve la convocatoria abierta y el tutorial se abre | Completada después de corregir F-01 |
| 4 | S2 | Estudiante | Postularse con hoja de vida | La postulación queda registrada | Completada |
| 5 | E3 | Empresa | Aceptar al estudiante | Descarga la hoja de vida; se crea la pasantía y se ocupa un cupo | Completada |
| 6 | D3 | Docente | Asignarse a la pasantía | Queda registrado como docente líder | Completada |
| 7 | S3 | Estudiante | Subir el plan de práctica | El documento queda en estado enviado | Completada |
| 8 | E4 | Empresa | Avalar el plan de práctica | Descarga y avala el documento | Completada |
| 8 | E5 | Empresa | Evaluar el desempeño | Guarda la evaluación y luego la modifica | Completada |
| 9 | D4 | Docente | Devolver el documento con nota baja | La plataforma impide aprobar con 2.5; el documento se devuelve con nota y observación | Completada después de corregir F-02 |
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

### Errores encontrados

La primera corrida reveló dos errores de la plataforma, descritos en la Tabla 3. El primero afectaba al tutorial, que el manual de usuario presenta como la ayuda de cada pantalla y que no abría con ningún perfil. Al retirar los módulos que ya no hacían parte de la plataforma se había borrado el inicio de la lista de pasos del recorrido. El segundo hacía que, cuando una acción fallaba dentro de una pasantía, el usuario terminara en el listado general en lugar de seguir en la pasantía que estaba revisando.

**Tabla 3**

*Errores encontrados en la primera corrida*

| ID | Tarea | Descripción | Severidad | Corrección |
|---|---|---|---|---|
| F-01 | S1 | El tutorial no abría en ninguna pantalla; el navegador registraba el error `pages is not defined` | Media | Se reconstruyeron los pasos del recorrido en `assets/js/tour.js` para los cuatro perfiles |
| F-02 | D4 | Ante un error dentro de una pasantía, por ejemplo al intentar aprobar con 2.5, la plataforma enviaba al listado de pasantías | Media | La plataforma regresa al detalle de la pasantía cuando el error ocurre dentro de ella |

Con las dos correcciones aplicadas, se reinstaló la base de producción y se repitió la ejecución completa. El resultado fue el de la Tabla 2.

### Ajustes a los guiones

La ejecución también mostró tres puntos en los que los guiones suponían el ambiente de pruebas. Primero, en producción no existen las cuentas del docente y del estudiante, por lo que el plan incluye ahora la preparación P0. Segundo, la tarea S6 usaba una oferta laboral de demostración que no existe en producción, así que la publicación de la oferta (E6) pasó al paso 12, antes de que el estudiante aplique. Tercero, la tarea A4 usaba la cuenta `estudiante@fet.edu.co`, que solo existe en el ambiente de pruebas, y ahora usa la cuenta del estudiante participante.

### Evidencias

Las veintinueve capturas de la ejecución se encuentran en la carpeta `evidencias/produccion/`, nombradas con el código de cada tarea. Las figuras 1 a 6 muestran las más representativas.

**Figura 1**

*Creación de las cuentas del docente y del estudiante por parte del administrador (P0)*

![Cuentas creadas por el administrador](evidencias/produccion/P0-cuentas-creadas.png)

**Figura 2**

*Tutorial del estudiante después de la corrección F-01 (S1)*

![Tutorial del estudiante](evidencias/produccion/S1-tutorial.png)

**Figura 3**

*Postulación recibida por la empresa con la hoja de vida del estudiante (E3)*

![Postulaciones recibidas](evidencias/produccion/E3-postulaciones.png)

**Figura 4**

*Mensaje que impide aprobar con nota 2.5, con el docente en la misma pasantía (D4)*

![Bloqueo al aprobar con nota baja](evidencias/produccion/D4-bloqueo-aprobar.png)

**Figura 5**

*Pasantía finalizada con veredicto e historial de auditoría (D6)*

![Pasantía finalizada](evidencias/produccion/D6-finalizada.png)

**Figura 6**

*Cuenta bloqueada por intentos fallidos en la pantalla de usuarios (A4)*

![Cuenta bloqueada](evidencias/produccion/A4-bloqueada.png)

## Conclusiones

Las veinticuatro tareas del plan de pruebas pueden completarse en una instalación de producción de Pasantías FET 2.0.0. Las reglas principales del proceso se cumplen en el servidor: el límite de cupos por alianza, la nota mínima de 3.0, la hoja de vida obligatoria en las ofertas laborales, el veredicto irreversible y el bloqueo por intentos fallidos. Los dos errores encontrados habrían afectado a los participantes durante la sesión real y quedaron corregidos.

Esta ejecución sirve de referencia para la sesión con el equipo. Si un participante no logra completar una tarea que aquí sí se completó, la causa probable es la claridad de la pantalla y no su funcionamiento, y conviene registrarla como hallazgo en el formato de resultados.

## Referencias

Brooke, J. (1996). SUS: A "quick and dirty" usability scale. En P. W. Jordan, B. Thomas, B. A. Weerdmeester, & I. L. McClelland (Eds.), *Usability evaluation in industry* (pp. 189–194). Taylor & Francis.

Microsoft. (2026). *Playwright* (Versión 1.63) [Software]. https://playwright.dev

Rojas Narváez, O., Patiño Rico, H. F., & Sabogal García, J. J. (2026a). *Prueba de despliegue de Pasantías FET 2.0.0 en máquinas virtuales Ubuntu Server* [Informe técnico]. Fundación Escuela Tecnológica de Neiva.

Rojas Narváez, O., Patiño Rico, H. F., & Sabogal García, J. J. (2026b). *Manual de usuario: Pasantías FET versión 2.0.0* [Manual]. Fundación Escuela Tecnológica de Neiva.
