# Guion de prueba de usuario, sesión simulada · Administrador

| Campo | Valor |
|---|---|
| Participante | Orlando Rojas Narváez |
| Perfil | Administrador |
| Cuenta | orlando.rojas@fet.edu.co (administrador inicial creado al instalar) |
| Contraseña | La que se definió al instalar el servidor (en producción no existe `FET2026*`) |
| Fecha | 05 / 11 / 2026 |
| Navegador y equipo | Chromium, pantalla 1366 × 860, servidor de producción en máquina virtual |
| Observador(a) | Sesión simulada (recorrido cognitivo) |

> **Nota.** Este guion corresponde a una sesión simulada, realizada el 5 de noviembre de 2026 sobre el servidor de producción mediante un recorrido cognitivo con apoyo de un asistente de inteligencia artificial (ver el [informe de la sesión simulada](informe-sesion-simulada.md)). El resultado de cada tarea se comprobó en la plataforma; el tiempo es una estimación para una persona que usa la plataforma por primera vez, y la dificultad y las observaciones provienen de la revisión de cada pantalla. No recoge la opinión del participante, por lo que el cuestionario SUS y la firma quedan para la sesión real.

Asumes el papel del administrador de la plataforma, que crea alianzas empresariales (y con ellas cupos de pasantía), gestiona las cuentas y cuida la seguridad y los respaldos.

**Convenciones:** en *Resultado* escribe *Completó* (sin ayuda), *Con ayuda* o *No completó*. La *Dificultad* va de 1 (muy fácil) a 5 (muy difícil). El orden general de la sesión está en el [plan de pruebas](../README.md#orden-de-la-sesión). La ayuda de cada pantalla está en el [manual de usuario](../../manual/MANUAL_USUARIO.md).

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
| Completó | 4 (estimado) | 3 | [`A1-alianza-registrada.png`](../evidencias/produccion/A1-alianza-registrada.png) |

**Observaciones:** El formulario de *Nueva alianza* es largo: hay que bajar para ver la cuenta de la empresa (NIT, correo y contraseña inicial) y el botón de guardar. La opción «Crear una cuenta nueva con los datos de abajo» no es evidente a primera vista. Al guardar, el mensaje «Alianza registrada con 2 cupo(s)» y la fila *Vigente · 0 / 2* confirman bien el resultado.

---

## A2 · Ampliar los cupos de la alianza

**Turno en la sesión:** paso 15 · al final de la sesión.

**Qué debes lograr (sin mirar el manual):**

1. Edita la alianza que creaste y amplíala a 4 cupos.
2. Intenta bajarla a 0 cupos y observa qué pasa.

**Resultado esperado:** Los cupos quedan en 4. La plataforma no permite dejar menos cupos que los pasantes activos.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| Completó | 1 (estimado) | 1 | [`A2-ampliar.png`](../evidencias/produccion/A2-ampliar.png) |

**Observaciones:** El botón «Editar o ampliar» está en la misma fila de la alianza. La plataforma no acepta 0 cupos y el mensaje «cupos 2 → 4» deja claro el cambio.

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
| Completó | 3 (estimado) | 2 | [`A3-usuarios.png`](../evidencias/produccion/A3-usuarios.png) |

**Observaciones:** Crear la cuenta es directo. El perfil se cambia con un selector y el botón «Guardar» de cada fila, que es fácil de pasar por alto. Al desactivar, el estado cambia a *Inactivo* y la persona ya no puede entrar.

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
| Completó | 2 (estimado) | 1 | [`A4-bloqueada.png`](../evidencias/produccion/A4-bloqueada.png) |

**Observaciones:** La cuenta bloqueada se ve enseguida: hay un aviso arriba («1 cuenta(s) bloqueada(s)»), la etiqueta *Bloqueada* con la hora y un botón verde «Desbloquear». La bitácora registra el desbloqueo.

---

## A5 · Descargar el respaldo de la base de datos

**Turno en la sesión:** paso 15 · 

**Qué debes lograr (sin mirar el manual):**

1. Descarga el respaldo SQL desde *Usuarios*.
2. Ábrelo con el Bloc de notas y anota la huella SHA-256 que aparece al final del archivo.

**Resultado esperado:** Se descarga un archivo `.sql` con las tablas de la plataforma y, al final, su huella SHA-256.

| Resultado | Tiempo (min) | Dificultad (1-5) | Evidencia |
|---|---|---|---|
| Completó | 1 (estimado) | 1 | [`A3-usuarios.png`](../evidencias/produccion/A3-usuarios.png) |

**Observaciones:** «Descargar respaldo SQL» está a la vista en *Usuarios*. El archivo trae la huella SHA-256 al final. Falta explicar en pantalla para qué sirve la huella.

---

## Cuestionario de satisfacción (SUS)

No se diligencia en la simulación: el SUS mide la opinión de la persona y solo lo puede responder el participante real.

## Comentarios finales

- ¿Qué fue lo más fácil? Desbloquear una cuenta y descargar el respaldo: todo está a la vista.
- ¿Qué fue lo más difícil o confuso? El formulario de nueva alianza, por lo largo y por la opción de crear la cuenta de la empresa.
- ¿Qué cambiarías o agregarías? Dividir el formulario de alianza en dos partes (empresa y cuenta de acceso).

**Firma del participante:** No aplica (sesión simulada).
