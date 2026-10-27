# Índice de evidencias — ejecución inicial de clase

Esta carpeta conserva la ejecución controlada realizada antes de corregir el código. Se introdujeron temporalmente condiciones reproducibles para observar y clasificar resultados distintos. Las imágenes son capturas reales de esa ejecución y el código funcional fue restaurado después.

| Evidencia | Caso | Criterio | Estado | Resultado actual |
|---|---|---|---|---|
| EV-CLASE-TC-LOGIN-001.png | TC-LOGIN-001 | CA-01, CA-04 | Pass | Inicio de sesión correcto mediante identificación. |
| EV-CLASE-TC-LOGIN-002.png | TC-LOGIN-002 | CA-01 | Pass | Inicio de sesión correcto mediante correo. |
| EV-CLASE-TC-LOGIN-003.png | TC-LOGIN-003 | CA-02 | Pass | Contraseña incorrecta rechazada con mensaje genérico. |
| EV-CLASE-TC-LOGIN-004.png | TC-LOGIN-004 | CA-02 | Pass | Usuario inexistente rechazado sin revelar la cuenta. |
| EV-CLASE-TC-LOGIN-005.png | TC-LOGIN-005 | CA-03 | Fail | El campo vacío mostró un mensaje diferente al esperado. |
| EV-CLASE-TC-LOGIN-006.png | TC-LOGIN-006 | CA-03 | Pass | Contraseña vacía validada correctamente. |
| EV-CLASE-TC-LOGIN-007.png | TC-LOGIN-007 | CA-03 | Pass | Formato inválido rechazado. |
| EV-CLASE-TC-LOGIN-008.png | TC-LOGIN-008 | CA-04 | Pass | Inyección SQL rechazada. |
| EV-CLASE-TC-LOGIN-009.png | TC-LOGIN-009 | CA-04 | Pass | Script escapado y no ejecutado. |
| EV-CLASE-TC-LOGIN-010.png | TC-LOGIN-010 | CA-05 | Blocked | Dependencia de Recordarme no disponible; no se pudo completar el caso. |
| EV-CLASE-TC-LOGIN-011.png | TC-LOGIN-011 | CA-06 | Fail | Cerró la sesión, pero no mostró la confirmación esperada. |
| EV-CLASE-TC-LOGIN-012.png | TC-LOGIN-012 | CA-07 | Blocked | Servicio de recuperación no disponible; no se pudo completar el caso. |

## Totales de la ejecución inicial

- Pass: 8.
- Fail: 2.
- Blocked: 2.
- Casos negativos: 7.
- Defectos reportados: BUG-LOGIN-001 y BUG-LOGIN-002.

La reejecución posterior a las correcciones se conserva por separado en `docs/evidencias/` y obtuvo 12 Pass.

