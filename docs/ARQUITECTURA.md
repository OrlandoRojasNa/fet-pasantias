# Arquitectura de la Intranet Privada FET

Documento de los compromisos del Acta 5 (arquitectura, componentes, relaciones de datos y flujos por perfil).

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
│ includes/bootstrap.php  sesión, cabeceras de seguridad    │
│ includes/functions.php  BD, autenticación, roles, avisos, │
│                         auditoría, evidencias, íconos     │
│ Páginas por módulo: dashboard, opportunities, projects,   │
│ alliances, minutes, reports, quality, users, guide,       │
│ notifications, backup, evidence                           │
└───────────────────────────┬───────────────────────────────┘
                            │ PDO con sentencias preparadas
┌───────────────────────────▼───────────────────────────────┐
│ MariaDB (fet_intranet) · storage/documents (evidencias)   │
└───────────────────────────────────────────────────────────┘
```

## 2. Componentes

| Componente | Archivo | Responsabilidad |
|---|---|---|
| Acceso | `index.php`, `includes/functions.php` | Inicio de sesión, bloqueo tras 5 intentos, sesión recordada con token |
| Tablero | `dashboard.php` | Resumen por perfil (empresa tiene panel propio) |
| Oportunidades (ODS 8) | `opportunities.php` | Vacantes, verificación, postulaciones, retiro |
| Innovación (ODS 9) | `projects.php` | Proyectos, equipo, hitos y evidencias |
| Alianzas (ODS 17) | `alliances.php` | Aliados, convenios y actividades |
| Actas y compromisos | `minutes.php`, `includes/minute-form.php`, `includes/commitment-dialog.php` | Actas en Formato 1, compromisos con evidencia |
| Avisos | `notifications.php` | Campana y bandeja de avisos |
| Indicadores | `reports.php` | Indicadores por periodo, cumplimiento por acta, CSV |
| Calidad | `quality.php`, `tests/smoke_test.php` | Comprobaciones, flujos de prueba, hallazgos |
| Respaldo | `backup.php` | Volcado SQL con huella SHA-256 |
| Evidencias | `evidence.php` | Descarga con control de permisos |
| Tutorial | `guide.php`, `assets/js/tour.js` | Guía y recorrido guiado |

## 3. Relaciones principales de datos

```
users ─┬─< opportunities (created_by) ─< applications >─ users
       ├─< projects (leader_id) ─┬─< project_members >─ users
       │                         └─< milestones
       ├─< agreements (responsible_id) >─ partners
       │        └─< activities
       ├─< minutes (created_by) ─< commitments >─ users (responsible_id)
       ├─< findings (reported_by, assigned_to)
       ├─< notifications
       └─< audit_events
```

18 llaves foráneas. Al eliminar un acta se eliminan sus compromisos; al eliminar un usuario se conservan sus eventos de auditoría con usuario nulo.

## 4. Decisiones técnicas

1. **Validación en el servidor**: cada página llama a `require_auth()` o `require_role()`; los valores de estado se aceptan solo desde listas permitidas (`allowed_value`).
2. **CSRF**: todo formulario POST lleva un token de sesión verificado con `hash_equals`.
3. **Contraseñas**: `password_hash` (bcrypt). Bloqueo de 10 minutos tras 5 intentos.
4. **Propiedad**: la empresa solo modifica sus vacantes y las postulaciones recibidas; el estudiante solo actualiza sus compromisos.
5. **Evidencias**: los archivos se guardan con nombre aleatorio en `storage/documents` (bloqueado por `.htaccess`) y se descargan por `evidence.php`.
6. **Compromisos vencidos**: se recalculan antes de cada consulta (`refresh_overdue_commitments`).
7. **Un compromiso no se puede completar sin evidencia** escrita o adjunta.
8. **Cabeceras**: CSP sin scripts externos, `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`.
9. **Migraciones idempotentes**: `database/install.php` se puede ejecutar varias veces.

## 5. Flujos de navegación por perfil

**Estudiante**: Inicio → Oportunidades → Postularme → Avisos (resultado) → Actas y compromisos → Actualizar con evidencia.

**Empresa de pasantía**: Inicio (panel de empresa) → Oportunidades → Nueva oportunidad (en revisión) → la FET la publica → Postulaciones recibidas → Aceptar o «No seleccionada».

**Administrador**: Inicio → Usuarios (crear, perfil, contraseña) → Oportunidades (verificar) → Actas (registrar, asignar) → Indicadores → Calidad y pruebas (respaldo SQL).

**Gestor**: Oportunidades (verificar) → Alianzas (convenios) → Actas (seguimiento por filtros) → Indicadores (CSV).

**Docente / líder**: Innovación (proyectos, equipo, hitos) → Actas (Formato 1) → Calidad (hallazgos).
