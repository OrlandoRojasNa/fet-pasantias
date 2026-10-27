# Arquitectura propuesta para la gestión de pasantías FET

## Estado

Propuesta pendiente de respuesta del usuario. Este documento no declara decisiones aprobadas ni módulos implementados. No se ha modificado el código existente.

## Revisión del proyecto

- Existe una aplicación PHP con autenticación mediante PDO y MySQL, sesiones, contraseñas con hash, protección CSRF, bloqueo temporal y opción de recordar sesión.
- El panel actual muestra información de la cuenta; no implementa el seguimiento de pasantías.
- La recuperación de contraseña se describe como simulada en el README existente.
- El esquema actual incluye usuarios y tablas de autenticación, sin roles de líder y empresa.
- El entorno dispone de PHP 8.2.12 y del cliente MySQL/MariaDB de XAMPP.
- El archivo de requisitos solicitado no existía; se transcribió el contenido del mensaje del usuario a `REQUISITOS-sistema-pasantias.md`.
- El acta adjunta plantea una intranet más amplia y acuerdos pendientes de ratificación. No define una rúbrica de evaluación de pasantes.
- La hoja de vida adjunta es un formato institucional; su incorporación como plantilla o formulario requiere aclaración.

## Stack propuesto

| Capa | Propuesta | Motivo |
| --- | --- | --- |
| Frontend | HTML semántico, CSS adaptable y JavaScript | Compatible con la aplicación existente y ejecutable en XAMPP. |
| Backend | PHP 8.2 con controladores, servicios y acceso PDO | Aprovecha el entorno y permite separar permisos, validaciones y reglas de negocio. |
| Base de datos | MySQL/MariaDB, tablas InnoDB y migraciones SQL versionadas | Permite transacciones, claves foráneas e integridad de los registros. |
| Informes | Generación de PDF y XLSX desde el backend | Las bibliotecas se seleccionarán tras confirmar stack y formato. |

## Estructura propuesta

```text
PruebaFet/
├── app/
│   ├── Controllers/
│   ├── Services/
│   ├── Repositories/
│   ├── Middleware/
│   └── Views/
├── config/
├── database/
│   ├── migrations/
│   └── seeds/
├── public/
│   ├── index.php
│   └── assets/
├── storage/
│   ├── documents/
│   └── logs/
├── tests/
├── docs/
├── REQUISITOS-sistema-pasantias.md
└── README.md
```

`public/` sería la raíz pública. Los documentos almacenados se descargarían a través de un controlador que verifica permisos. El README explicaría la configuración correspondiente en Apache/XAMPP. La reorganización del código existente se haría después de confirmar la tecnología.

## Autenticación y roles propuestos

- Contraseñas almacenadas mediante `password_hash` y comprobadas con `password_verify`.
- Sesiones del servidor, regeneración del identificador al iniciar sesión y cookies HttpOnly y SameSite; Secure al operar por HTTPS.
- Protección CSRF en operaciones de escritura, consultas preparadas, validación de entradas y escape de salidas HTML.
- Verificación de rol y pertenencia a la empresa en cada operación del backend, incluyendo consultas, descargas e informes.
- Líder: administración, supervisión, calificación e informes.
- Empresa: evaluaciones, evidencias y conversaciones de sus propios casos.
- La propuesta contempla al estudiante como perfil consultable; un acceso propio requiere decisión expresa.
- Auditoría con actor, fecha y valores anteriores y nuevos para los cambios relevantes.

## Decisiones solicitadas antes de programar

1. Confirmar PHP/MySQL en XAMPP o indicar otro stack.
2. Identificar SIGEDO mediante URL o nombre completo para definir la referencia visual.
3. Indicar si los Word son referencias, plantillas descargables o formularios que debe ofrecer el sistema.
4. Confirmar que escala, criterios, ponderaciones, cortes, documentos obligatorios y plazos sean configurables por el líder, o proporcionar las reglas institucionales exactas.
5. Confirmar la presentación separada de evaluaciones de líder y empresa mientras no exista una fórmula institucional de consolidación.
6. Confirmar la propuesta de contenido de informes: ficha del pasante, evaluaciones, hitos y documentos; con consolidaciones por empresa o periodo académico.
7. Confirmar que el líder cree las cuentas y gestione los estados del proceso.
8. Confirmar la propuesta de archivos PDF, DOCX, XLSX, JPG y PNG de hasta 10 MB.
9. Confirmar avisos internos asociados a cambios de estado y vencimientos configurados.

Estos puntos son propuestas, no requisitos institucionales confirmados. Las transiciones de estado y cualquier regla adicional que no quede resuelta deberán consultarse antes de implementar el módulo correspondiente.

## Entrega prevista tras la aclaración

Implementación y validación en los ocho pasos del archivo de requisitos, un commit por módulo, resumen de cada entrega, historias de usuario y documentación de instalación y publicación en GitHub. El destino mostrado por el usuario es `git@github.com:Metalcof/Fet.git`; todavía no se ha realizado ninguna publicación.
