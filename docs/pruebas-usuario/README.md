# Plan de pruebas de usuario · Pasantías FET

## Objetivo

Comprobar, con personas reales, que cada perfil puede completar sus tareas principales en la plataforma sin ayuda, e identificar errores, pasos confusos y mejoras antes de la entrega.

Estas pruebas se hacen sobre la plataforma ya funcional, la versión 2.0.0 de `main`.

## Participantes y perfiles

| Participante | Perfil que asume | Cuenta para entrar | Guion |
|---|---|---|---|
| Juan José Sabogal García | Estudiante | juan.sabogal@fet.edu.co | [guion-estudiante-juan-jose-sabogal.md](guion-estudiante-juan-jose-sabogal.md) |
| Hernán Felipe Patiño Rico | Docente líder de pasantías | hernan.patino@fet.edu.co | [guion-docente-hernan-patino.md](guion-docente-hernan-patino.md) |
| Orlando Rojas Narváez | Empresa aliada | La cuenta que él mismo crea como administrador en la tarea A1 | [guion-empresa-orlando-rojas.md](guion-empresa-orlando-rojas.md) |
| Orlando Rojas Narváez | Administrador | admin@fet.edu.co | [guion-administrador-orlando-rojas.md](guion-administrador-orlando-rojas.md) |

La contraseña de las cuentas es `FET2026*`. La asignación de perfiles se puede cambiar; si lo hacen, cambien también el nombre en el guion correspondiente.

## Preparación (antes de la sesión)

1. Instalar la plataforma siguiendo el [manual de despliegue](../MANUAL_DESPLIEGUE.md).
2. Dejar la base de datos limpia: borrar la base `fet_intranet` en phpMyAdmin y volver a correr `C:\xampp\php\php.exe database\install.php`.
3. Comprobar que las pruebas automáticas pasan (`tests\smoke_test.php`: *8 de 8*).
4. Tener a mano un PDF de hoja de vida y un PDF de "plan de práctica" (pueden ser cualquier PDF de menos de 5 MB).
5. Imprimir o abrir el guion de cada participante y el [formato de resultados](formato-resultados.md).

Si se hace en un solo computador, cada participante usa una ventana de **incógnito** distinta o un navegador distinto, para que las sesiones no se mezclen.

## Si la sesión se hace en el servidor de producción

El servidor de producción no trae cuentas de demostración ni la clave `FET2026*`. Antes del paso 1:

1. Orlando entra con el administrador inicial que se creó al instalar (sección 5.5 o 5.0 del [manual de despliegue](../MANUAL_DESPLIEGUE.md)).
2. En **Usuarios → Crear usuario** crea la cuenta de Hernán (perfil *Docente líder*, `hernan.patino@fet.edu.co`) y la de Juan José (perfil *Estudiante*, `juan.sabogal@fet.edu.co`), cada una con una contraseña propia.
3. Les entrega la contraseña a cada uno en persona. La cuenta de la empresa la crea Orlando en la tarea A1.

En los guiones, donde dice `FET2026*`, cada participante usa la contraseña que recibió.

El resultado de ejecutar estos guiones en un servidor de producción recién instalado está en [resultados-ejecucion-produccion.md](resultados-ejecucion-produccion.md). Una sesión simulada, con los guiones y el formato ya diligenciados como ejemplo, está en [simulacion/](simulacion/README.md).

## Orden de la sesión

El proceso pasa de un perfil a otro, así que las tareas se hacen en este orden. Cada guion indica en qué momento le toca a cada participante.

| # | Quién | Tarea |
|---|---|---|
| 1 | Orlando (Administrador) | A1: registra la alianza de una empresa nueva con 2 cupos. |
| 2 | Orlando (Empresa) | E1 y E2: entra con la cuenta nueva y publica una convocatoria de pasantía. |
| 3 | Hernán (Docente) | D1 y D2: revisa su inicio y verifica la convocatoria. |
| 4 | Juan José (Estudiante) | S1 y S2: revisa su inicio y se postula con su hoja de vida. |
| 5 | Orlando (Empresa) | E3: revisa la postulación y acepta a Juan José (se crea la pasantía). |
| 6 | Hernán (Docente) | D3: se asigna como docente líder de la pasantía. |
| 7 | Juan José (Estudiante) | S3: sube el plan de práctica. |
| 8 | Orlando (Empresa) | E4 y E5: avala el documento y evalúa el desempeño. |
| 9 | Hernán (Docente) | D4: califica el documento con una nota menor a 3.0 y lo devuelve. |
| 10 | Juan José (Estudiante) | S4: corrige el documento devuelto. |
| 11 | Hernán (Docente) | D5 y D6: aprueba la corrección y emite el veredicto final. |
| 12 | Orlando (Empresa) | E6: publica una oferta laboral. Hernán la verifica para que quede publicada. |
| 13 | Juan José (Estudiante) | S5 y S6: consulta su resultado, sus notificaciones y se postula a la oferta laboral. |
| 14 | Hernán (Docente) | D7: revisa reportes y auditoría y exporta el CSV. |
| 15 | Orlando (Administrador) | A2 a A5: amplía cupos, gestiona usuarios, desbloquea una cuenta y descarga el respaldo. |
| 16 | Todos | Responden el cuestionario de satisfacción (SUS) de su guion. |

**Duración estimada:** 60 a 75 minutos.

## Cómo registrar cada tarea

Por cada tarea, el participante (o quien observe) anota:

- **Resultado:** *Completó* (sin ayuda), *Con ayuda* o *No completó*.
- **Tiempo** aproximado en minutos.
- **Dificultad** de 1 (muy fácil) a 5 (muy difícil).
- **Observaciones:** qué confundió, qué esperaba encontrar o qué error vio.
- **Evidencia:** una captura de pantalla del resultado, guardada en `evidencias/` con el código de la tarea, por ejemplo `evidencias/S2-postulacion.png`.

Regla para quien observe: no ayudar mientras la persona intenta la tarea. Si pasan más de 3 minutos sin avanzar, se puede dar una pista y la tarea se registra como *Con ayuda*.

## Después de la sesión

1. Pasar los resultados de los cuatro guiones al [formato de resultados](formato-resultados.md).
2. Calcular el puntaje SUS de cada participante (la fórmula está en el formato).
3. Registrar cada error encontrado en la tabla de hallazgos, con su severidad.
4. Subir los guiones diligenciados, el formato y las evidencias a esta carpeta, mediante un PR.
