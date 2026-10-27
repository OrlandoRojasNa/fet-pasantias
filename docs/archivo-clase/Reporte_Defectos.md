# Reportes de defectos reales — ejecución de clase

## BUG-LOGIN-001

| Campo | Detalle |
|---|---|
| ID | BUG-LOGIN-001 |
| Título | No se muestra la confirmación después de cerrar sesión |
| Caso asociado | TC-LOGIN-011 |
| Severidad | Baja: el cierre funciona, pero falta retroalimentación al usuario |
| Prioridad | P2 |
| Entorno | Windows 11 Home 10.0.26200; Chrome 151.0.7922.76; Apache 2.4.58; PHP 8.2.12; MariaDB 10.4.32 |
| Versión detectada | Build inicial reproducido y documentado el 18/08/2026 |
| Estado | Corregido y cerrado |

## Precondiciones

El usuario `1076502055` inició sesión correctamente y se encuentra en el panel Inicio.

## Pasos para reproducir

1. Abrir el formulario de Login.
2. Ingresar `1076502055` y la contraseña válida.
3. Seleccionar **Ingresar**.
4. En el panel Inicio, seleccionar **Cerrar sesión**.

## Esperado frente a actual

- Esperado: cerrar la sesión, volver al Login y mostrar “La sesión se cerró correctamente.”
- Actual antes de corregir: la sesión se cerraba y el panel quedaba protegido, pero el Login aparecía sin mensaje de confirmación.

## Evidencia

`docs/evidencias/BUG-LOGIN-001.png` muestra el Login después del cierre sin la confirmación esperada.

## Causa y corrección

Al destruir la sesión también se eliminaba la cookie que debía identificar la nueva sesión donde se guardaba el mensaje temporal. Se corrigió iniciando una sesión con ID nuevo antes de registrar la confirmación.

## Reprueba

TC-LOGIN-011 fue ejecutado nuevamente el 18/08/2026. Resultado: **Pass**. Evidencia: `docs/evidencias/EV-TC-LOGIN-011.png`.

---

## BUG-LOGIN-002

| Campo | Detalle |
|---|---|
| ID | BUG-LOGIN-002 |
| Título | El campo usuario vacío muestra un mensaje diferente al criterio de aceptación |
| Caso asociado | TC-LOGIN-005 |
| Severidad | Media: incumple la validación específica y puede confundir al usuario |
| Prioridad | P2 |
| Entorno | Windows 11 Home; Chrome 151; Apache 2.4.58; PHP 8.2.12; MariaDB 10.4.32 |
| Versión detectada | Build controlado de clase |
| Estado | Corregido y cerrado |

### Precondiciones

El formulario de Login está abierto y el servicio se encuentra disponible.

### Pasos para reproducir

1. Dejar vacío el campo identificación o correo.
2. Ingresar `Fet2026*` como contraseña.
3. Seleccionar **Ingresar**.

### Esperado frente a actual

- Esperado: no autenticar y mostrar “El usuario o correo es obligatorio.”
- Actual antes de corregir: mostró “Usuario o contraseña incorrectos”.

### Evidencia

`docs/evidencias_clase/EV-CLASE-TC-LOGIN-005.png` muestra el resultado actual de la ejecución Fail.

### Causa y corrección

La rama de validación del identificador vacío reutilizaba el mensaje genérico de autenticación. Se restauró el mensaje específico definido por CA-03.

### Reprueba

TC-LOGIN-005 fue ejecutado nuevamente después de la corrección. Resultado: **Pass**. Evidencia: `docs/evidencias/EV-TC-LOGIN-005.png`.

## Casos Blocked

TC-LOGIN-010 y TC-LOGIN-012 no se reportan como defectos porque quedaron bloqueados por dependencias temporalmente no disponibles durante la ejecución controlada. Sus evidencias son `EV-CLASE-TC-LOGIN-010.png` y `EV-CLASE-TC-LOGIN-012.png`. Después de restaurar las dependencias, ambos pasaron en la reejecución.
