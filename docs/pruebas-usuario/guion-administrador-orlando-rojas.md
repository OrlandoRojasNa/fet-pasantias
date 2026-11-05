# Guion de prueba de usuario · Administrador

| Campo | Valor |
|---|---|
| Participante | Orlando Rojas Narváez |
| Perfil | Administrador |
| Cuenta | admin@fet.edu.co (o identificación 1000000001) |
| Contraseña | `FET2026*` |
| Fecha | ____ / ____ / 2026 |
| Navegador y equipo | ____________________ |
| Observador(a) | ____________________ |

Asumes el papel del administrador de la plataforma, que crea alianzas empresariales (y con ellas cupos de pasantía), gestiona las cuentas y cuida la seguridad y los respaldos.

**Convenciones:** en *Resultado* escribe *Completó* (sin ayuda), *Con ayuda* o *No completó*. La *Dificultad* va de 1 (muy fácil) a 5 (muy difícil). El orden general de la sesión está en el [plan de pruebas](README.md#orden-de-la-sesión). La ayuda de cada pantalla está en el [manual de usuario](../manual/MANUAL_USUARIO.md).

## A1 · Registrar una alianza con una empresa nueva

**Turno en la sesión:** paso 1 · al inicio de la sesión.

**Qué debes lograr (sin mirar el manual):**

1. Entra como administrador.
2. Registra una alianza con una empresa nueva: razón social, sector, código de convenio, **2 cupos**, fechas y contacto.
3. Crea a la vez la cuenta de la empresa con NIT, correo de acceso y contraseña inicial. **Anota estos datos**: los usarás en el guion de empresa.
4. NIT: ____________  Correo: ____________________  Contraseña: ____________

**Resultado esperado:** La alianza aparece en la lista con *0 / 2* cupos en uso y estado *Vigente*.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| | | | `evidencias/A1-.png` |

**Observaciones:**

&nbsp;

---

## A2 · Ampliar los cupos de la alianza

**Turno en la sesión:** paso 15 · al final de la sesión.

**Qué debes lograr (sin mirar el manual):**

1. Edita la alianza que creaste y amplíala a 4 cupos.
2. Intenta bajarla a 0 cupos y observa qué pasa.

**Resultado esperado:** Los cupos quedan en 4. La plataforma no permite dejar menos cupos que los pasantes activos.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| | | | `evidencias/A2-.png` |

**Observaciones:**

&nbsp;

---

## A3 · Crear un usuario y cambiar una contraseña

**Turno en la sesión:** paso 15 · 

**Qué debes lograr (sin mirar el manual):**

1. Crea un estudiante nuevo de prueba.
2. Asígnale una contraseña nueva desde **Contraseña**.
3. Desactívalo.

**Resultado esperado:** El usuario aparece en la lista con estado *Inactivo* y ya no puede entrar.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| | | | `evidencias/A3-.png` |

**Observaciones:**

&nbsp;

---

## A4 · Desbloquear una cuenta

**Turno en la sesión:** paso 15 · pide a un compañero que, antes de esta tarea, intente entrar 5 veces con una clave equivocada en la cuenta juan.sabogal@fet.edu.co (en el ambiente de pruebas también sirve estudiante@fet.edu.co).

**Qué debes lograr (sin mirar el manual):**

1. Encuentra la cuenta bloqueada en *Usuarios*.
2. Desbloquéala.
3. Comprueba en *Reportes y auditoría* que el desbloqueo quedó registrado.

**Resultado esperado:** La cuenta vuelve a entrar con su contraseña correcta y la bitácora muestra el desbloqueo.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| | | | `evidencias/A4-.png` |

**Observaciones:**

&nbsp;

---

## A5 · Descargar el respaldo de la base de datos

**Turno en la sesión:** paso 15 · 

**Qué debes lograr (sin mirar el manual):**

1. Descarga el respaldo SQL desde *Usuarios*.
2. Ábrelo con el Bloc de notas y anota la huella SHA-256 que aparece al final del archivo.

**Resultado esperado:** Se descarga un archivo `.sql` con las tablas de la plataforma y, al final, su huella SHA-256.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| | | | `evidencias/A5-.png` |

**Observaciones:**

&nbsp;

---

## Cuestionario de satisfacción (SUS)

Marca de 1 (totalmente en desacuerdo) a 5 (totalmente de acuerdo).

| # | Afirmación | 1 | 2 | 3 | 4 | 5 |
|---|---|---|---|---|---|---|
| 1 | Creo que me gustaría usar esta plataforma con frecuencia. | | | | | |
| 2 | Encontré la plataforma innecesariamente compleja. | | | | | |
| 3 | Pensé que la plataforma era fácil de usar. | | | | | |
| 4 | Creo que necesitaría ayuda de una persona técnica para poder usarla. | | | | | |
| 5 | Las funciones de la plataforma están bien integradas. | | | | | |
| 6 | Pensé que había demasiada inconsistencia en la plataforma. | | | | | |
| 7 | Imagino que la mayoría de las personas aprendería a usarla muy rápido. | | | | | |
| 8 | Encontré la plataforma muy incómoda de usar. | | | | | |
| 9 | Me sentí muy seguro(a) usando la plataforma. | | | | | |
| 10 | Necesité aprender muchas cosas antes de poder usarla. | | | | | |

**Puntaje SUS:** ______ / 100 (se calcula en el [formato de resultados](formato-resultados.md))

## Comentarios finales

- ¿Qué fue lo más fácil?
- ¿Qué fue lo más difícil o confuso?
- ¿Qué cambiarías o agregarías?

**Firma del participante:** ____________________
