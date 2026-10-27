# Arquitectura de Pasantías FET

Plataforma de gestión documental del proceso de pasantías de la FET: alianzas empresariales con cupos, convocatorias, postulaciones con hoja de vida, documentos de la pasantía, calificación del docente líder, veredicto final y auditoría.

## 1. Capas

```
┌──────────────────────── Navegador ────────────────────────┐
│ HTML + CSS (assets/css/styles.css)                        │
│ JavaScript: app.js (diálogos, menú, avisos)               │
│             tour.js (recorrido guiado por perfil)         │
└───────────────────────────┬───────────────────────────────┘
                            │ HTTP (formularios POST con token CSRF)
┌───────────────────────────▼───────────────────────────────┐
│ Aplicación PHP 8 (Apache · XAMPP)                         │
│ config/database.php     conexión (lee .env si existe)     │
│ includes/bootstrap.php  sesión, cabeceras de seguridad    │
│ includes/functions.php  BD, autenticación, roles, avisos, │
│                         auditoría, archivos, cupos        │
│ Páginas: dashboard, internships, opportunities,           │
│ alliances, reports, users, guide, notifications,          │
│ backup, evidence                                          │
└───────────────────────────┬───────────────────────────────┘
                            │ PDO con sentencias preparadas
┌───────────────────────────▼───────────────────────────────┐
│ MySQL / MariaDB (fet_intranet) · storage/documents        │
└───────────────────────────────────────────────────────────┘
```

## 2. Componentes

| Módulo | Archivo | Responsabilidad |
|---|---|---|
| Acceso | `index.php`, `logout.php`, `forgot-password.php` | Inicio de sesión, bloqueo por intentos, recordar sesión |
| Inicio | `dashboard.php` | Pendientes de cada perfil |
| Pasantías | `internships.php` | Documentos, aval de la empresa, calificación 0.0–5.0, evaluación de desempeño, veredicto final, historial de auditoría |
| Convocatorias y empleo | `opportunities.php` | Convocatorias de pasantía y ofertas laborales, verificación FET, postulaciones con hoja de vida (PDF) |
| Alianzas | `alliances.php` | Empresa aliada, cuenta de acceso, convenio, vigencia y cupos |
| Reportes y auditoría | `reports.php` | Resumen por periodo, resultados por alianza, CSV, bitácora |
| Usuarios | `users.php`, `backup.php` | Cuentas, perfiles, desbloqueo, contraseñas, respaldo SQL |
| Archivos | `evidence.php` | Descarga de documentos y hojas de vida con control de permisos |
| Ayuda | `guide.php`, `help.php`, `assets/js/tour.js` | Guía por perfil y recorrido guiado |
| Pruebas | `tests/smoke_test.php` | 8 flujos automáticos por HTTP |

## 3. Perfiles

| Perfil | Qué hace |
|---|---|
| Administrador | Crea alianzas y cupos, cuentas, respaldo SQL; ve todo |
| Docente líder de pasantías | Verifica publicaciones, asigna empresa y docente, califica documentos, audita, emite el veredicto final; ve todas las pasantías |
| Estudiante | Se postula (con hoja de vida), sube documentos, corrige, consulta notas y resultado |
| Empresa aliada | Publica convocatorias (con cupos) y ofertas laborales, elige pasantes, avala documentos, evalúa desempeño |

## 4. Relaciones principales de datos

```
users ─┬─1 alliances (company_id, cupos)
       ├─< opportunities (created_by) ─< applications (cv_file) >─ users (estudiante)
       ├─< internships (student_id, company_id, teacher_id, application_id)
       │        └─< internship_documents ─< document_comments
       ├─< notifications
       └─< audit_events
```

- Una empresa tiene como máximo una alianza (`alliances.company_id` único).
- Cupos en uso = pasantías `active` de la empresa. Aceptar una postulación de pasantía o asignar una pasantía activa a una empresa exige un cupo libre y una alianza vigente (`require_internship_slot`).
- Al aceptar una postulación a una convocatoria de pasantía se crea la pasantía (`create_internship_from_application`). Las ofertas laborales no crean pasantía ni usan cupos.

## 5. Decisiones técnicas

1. **Validación en el servidor**: cada página llama a `require_auth()` o `require_role()`; los estados se aceptan solo desde listas permitidas (`allowed_value`).
2. **CSRF**: todo formulario POST lleva un token de sesión verificado con `hash_equals`.
3. **Contraseñas**: `password_hash` (bcrypt). Bloqueo de 10 minutos tras 5 intentos; el administrador puede desbloquear.
4. **Propiedad**: la empresa solo modifica sus publicaciones y postulaciones; el estudiante solo ve sus pasantías.
5. **Archivos**: se guardan con nombre aleatorio en `storage/documents` (bloqueado por `.htaccess`) y se descargan por `evidence.php`, que valida permisos y registra la descarga en auditoría. La hoja de vida solo acepta PDF.
6. **Regla de aprobación**: documento aprobado con nota ≥ 3.0; veredicto «aprobada» solo con promedio ≥ 3.0. El veredicto es irreversible.
7. **Cabeceras**: CSP sin scripts externos, `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`.
8. **Configuración**: `config/database.php` lee `.env` (no versionado). Puerto por defecto 3306.
9. **Migraciones idempotentes**: `database/install.php` se puede ejecutar varias veces; convierte cuentas del antiguo perfil «gestor» en administrador y no borra tablas de módulos retirados.

## 6. Flujo principal

1. **Administrador** registra la alianza (empresa, cuenta, convenio, cupos).
2. **Empresa** publica la convocatoria → queda «En revisión».
3. **Docente líder** la verifica → «Publicada».
4. **Estudiante** se postula (hoja de vida opcional en pasantías, obligatoria en ofertas laborales).
5. **Empresa** acepta → se crea la pasantía y se ocupa un cupo.
6. **Docente líder** asigna el docente; el **estudiante** sube plan, informes y certificado.
7. **Empresa** avala; **docente** califica (0.0–5.0) y aprueba o devuelve con observaciones.
8. **Empresa** registra la evaluación de desempeño (1–5).
9. **Docente** emite el veredicto final con justificación; la pasantía termina y libera el cupo.
