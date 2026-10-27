# Pasantías FET 2.0

Plataforma web de la Fundación Escuela Tecnológica de Neiva «Jesús Oviedo Pérez» (campus Rivera, Huila) para la **gestión documental del proceso de pasantías**: alianzas empresariales con cupos, convocatorias de pasantía, ofertas laborales con hoja de vida, documentos de la pasantía, calificación y aprobación por el docente líder, y auditoría de todo el proceso.

## Instalación rápida en XAMPP

1. Copia o clona el proyecto en `C:\xampp\htdocs\PruebaFet`.
2. Inicia **Apache** y **MySQL** en el panel de XAMPP.
3. Si tu MySQL no usa el puerto 3306, copia `.env.example` como `.env` y cambia `DB_PORT` (por ejemplo `DB_PORT=3307`).
4. Crea o actualiza la base de datos (se puede repetir; no duplica datos y migra bases anteriores):

   ```powershell
   C:\xampp\php\php.exe C:\xampp\htdocs\PruebaFet\database\install.php
   ```

5. Abre `http://localhost/PruebaFet/`.

## Cuentas de demostración

Todas usan la contraseña `FET2026*`.

| Perfil | Correo |
|---|---|
| Administrador | `admin@fet.edu.co` |
| Docente líder de pasantías | `docente@fet.edu.co`, `hernan.patino@fet.edu.co` |
| Estudiante | `estudiante@fet.edu.co`, `orlando.rojas@fet.edu.co`, `juan.sabogal@fet.edu.co` |
| Empresa aliada | `empresa@fet.edu.co` (alianza `CONV-DEMO-001` con 3 cupos) |

## Módulos

- **Pasantías**: el estudiante sube sus documentos (plan, informes, certificado…), la empresa los avala y el docente líder los califica de 0.0 a 5.0 (con 3.0 o más se aprueban; con menos se devuelven con observaciones). La empresa evalúa el desempeño (1 a 5) y el docente emite el veredicto final (aprobada con promedio de 3.0 o más). Los docentes líderes ven todas las pasantías con su **historial de auditoría**.
- **Convocatorias y empleo**: las empresas con alianza vigente publican convocatorias de pasantía y, de forma opcional, ofertas laborales. La FET las verifica antes de publicarlas. El estudiante se postula con su **hoja de vida en PDF** (obligatoria en ofertas laborales). Aceptar a un estudiante en una pasantía crea la pasantía y ocupa un cupo.
- **Alianzas**: el administrador registra la empresa aliada, crea su cuenta, define el convenio, la vigencia y los **cupos de pasantía**. Ampliar los cupos abre más plazas.
- **Reportes y auditoría**: resumen por periodo, cupos y resultados por empresa, exportación CSV y bitácora de quién hizo qué y cuándo.
- **Usuarios**: crear cuentas, cambiar perfil, desbloquear, restablecer contraseñas y descargar el **respaldo SQL** con huella SHA-256.
- **Avisos** en la campana y **tutorial** guiado por perfil (botón ?) más la página «Guía de uso».

## Pruebas automáticas

Con Apache y MySQL encendidos:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\PruebaFet\tests\smoke_test.php http://localhost/PruebaFet
```

Inicia sesión con los cuatro perfiles y verifica 8 flujos: acceso, permisos por perfil, convocatoria y postulación, documentación y veredicto de la pasantía, alianzas y cupos, oferta laboral con hoja de vida, reportes y respaldo. Los datos de prueba se borran al terminar.

## Documentación

- `docs/ARQUITECTURA.md`: capas, componentes, perfiles, datos, decisiones y flujo principal.
- `REQUISITOS-Intranet-FET.md`: alcance de la versión 2.0.
- `docs/archivo-clase/`: material de entregas anteriores.

## Equipo

- Miguel Antonio Urbano Silva: docente gestor.
- Hernán Felipe Patiño Rico: backend.
- Orlando Rojas Narváez: frontend.
- Juan José Sabogal García: seguimiento, pruebas y auditoría.
