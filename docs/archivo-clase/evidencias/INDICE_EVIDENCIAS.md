# Índice de evidencias — Login FET

Todas las capturas finales fueron actualizadas el 18 de agosto de 2026 en el entorno local. El nombre conserva el ID del caso para mantener la relación entre criterio, ejecución, resultado actual y archivo.

| Evidencia | Caso / defecto | Criterio | Tipo | Estado observado | Resultado actual visible |
|---|---|---|---|---|---|
| EV-TC-LOGIN-001.png | TC-LOGIN-001 | CA-01, CA-04 | Positivo | Pass | Panel Inicio con sesión activa mediante identificación. |
| EV-TC-LOGIN-002.png | TC-LOGIN-002 | CA-01 | Positivo | Pass | Panel Inicio con sesión activa mediante correo. |
| EV-TC-LOGIN-003.png | TC-LOGIN-003 | CA-02 | Negativo | Pass | Login rechazado con mensaje de credenciales incorrectas. |
| EV-TC-LOGIN-004.png | TC-LOGIN-004 | CA-02 | Negativo | Pass | Usuario inexistente rechazado con mensaje genérico. |
| EV-TC-LOGIN-005.png | TC-LOGIN-005 | CA-03 | Negativo | Pass | Validación de usuario/correo obligatorio. |
| EV-TC-LOGIN-006.png | TC-LOGIN-006 | CA-03 | Negativo | Pass | Validación de contraseña obligatoria. |
| EV-TC-LOGIN-007.png | TC-LOGIN-007 | CA-03 | Negativo | Pass | Validación del formato de identificación o correo. |
| EV-TC-LOGIN-008.png | TC-LOGIN-008 | CA-04 | Negativo | Pass | Cadena SQL rechazada sin iniciar sesión. |
| EV-TC-LOGIN-009.png | TC-LOGIN-009 | CA-04 | Negativo | Pass | Etiqueta script escapada como texto y sin ejecución. |
| EV-TC-LOGIN-010.png | TC-LOGIN-010 | CA-05 | Positivo | Pass | Panel restaurado mediante el token Recordarme. |
| EV-TC-LOGIN-011.png | TC-LOGIN-011 | CA-06 | Positivo | Pass | Cierre corregido, mensaje visible y sesión invalidada. |
| EV-TC-LOGIN-012.png | TC-LOGIN-012 | CA-07 | Positivo | Pass | Solicitud de recuperación procesada con respuesta genérica. |
| BUG-LOGIN-001.png | BUG-LOGIN-001 / TC-LOGIN-011 | CA-06 | Defecto real | Fail inicial | Antes de la corrección, el Login aparecía sin confirmación de cierre. |

## Resumen

- Casos ejecutados: 12.
- Capturas de ejecución: 12, una por caso.
- Positivos: 5.
- Negativos: 7.
- Estado final: 12 Pass, 0 Fail, 0 Blocked.
- Defectos reales: 1, corregido y reprobado satisfactoriamente.
