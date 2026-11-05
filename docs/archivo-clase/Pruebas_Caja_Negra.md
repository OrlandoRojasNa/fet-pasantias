# Diseño de pruebas de caja negra — Login FET

## Alcance

Se evalúa el comportamiento observable del login sin revisar su implementación interna. Las entradas se envían por la interfaz HTTP y se comparan con los criterios de aceptación. La suite automatizada está en `tests/run_blackbox.ps1`.

## Particiones de equivalencia

| Campo o estado | Clase válida | Clases inválidas | Casos representantes |
|---|---|---|---|
| Identificador | Identificación registrada de 6–20 dígitos o correo registrado | Cuenta no registrada, formato inválido, vacío, código malicioso | TC-001, TC-002, TC-004, TC-005, TC-007, TC-008, TC-009 |
| Contraseña | Clave correcta de la cuenta | Incorrecta, vacía, longitud superior a 255 | TC-001, TC-003, TC-006 |
| Recordarme | Marcado con credenciales válidas | Token ausente, expirado o manipulado | TC-010 |
| Sesión | Usuario autenticado | Visitante sin sesión o sesión cerrada | TC-001, TC-011 |
| Recuperación | Correo con formato válido | Correo con formato inválido o vacío | TC-012 y validación del formulario |

## Valores límite

| Regla | Debajo del límite | En el límite | Encima del límite | Respuesta esperada |
|---|---:|---:|---:|---|
| Identificación mínima | 5 dígitos | 6 dígitos | 7 dígitos | Rechazar 5; aceptar el formato desde 6 |
| Identificación máxima | 19 dígitos | 20 dígitos | 21 dígitos | Aceptar el formato hasta 20; rechazar 21 |
| Usuario/correo | 149 caracteres | 150 caracteres | 151 caracteres | Aceptar hasta 150; rechazar el exceso |
| Contraseña | 254 caracteres | 255 caracteres | 256 caracteres | Procesar hasta 255; rechazar el exceso |
| Campo obligatorio | 0 caracteres | 1 carácter | N/A | Rechazar vacío antes de autenticar |
| Intentos fallidos | 4 intentos | 5 intentos | 6.º intento | Al quinto, bloquear durante 10 minutos |

## Combinación mínima

Se seleccionó un representante por partición y se agregaron entradas de riesgo: inyección SQL, HTML/script, campo vacío y credencial inexistente. Los 12 casos cubren el camino positivo, los rechazos, la persistencia, la recuperación y el cierre de sesión sin intentar todas las combinaciones posibles.

## Tabla de decisión resumida

| Usuario válido | Contraseña válida | Campos completos | Resultado |
|---|---|---|---|
| Sí | Sí | Sí | Inicia sesión y redirige a Inicio |
| Sí | No | Sí | No inicia; mensaje genérico |
| No | Cualquiera | Sí | No inicia; mensaje genérico |
| Cualquiera | Cualquiera | No | No envía la autenticación; validación obligatoria |

## Resultado

Ejecución inicial de clase: **8 Pass, 2 Fail y 2 Blocked**. Después de corregir los dos resultados Fail y restaurar las dependencias bloqueadas, la ejecución final del 18 de agosto de 2026 obtuvo **12 Pass, 0 Fail y 0 Blocked**.
