# Intranet Privada FET 1.1

Aplicación web de la Fundación Escuela Tecnológica de Neiva «Jesús Oviedo Pérez» (campus Rivera, Huila) para gestionar oportunidades profesionales, proyectos de innovación, alianzas institucionales, actas, compromisos e indicadores de los ODS 8, 9 y 17.

## Instalación en XAMPP

1. Inicia Apache y MySQL.
2. Desde PowerShell ejecuta (se puede repetir; no duplica datos y migra bases anteriores):

   ```powershell
   C:\xampp\php\php.exe C:\xampp\htdocs\PruebaFet\database\install.php
   ```

3. Abre `http://localhost/PruebaFet/`.

## Cuentas de demostración

Todas usan la contraseña `FET2026*`.

| Perfil | Correo |
|---|---|
| Administrador | `admin@fet.edu.co` |
| Gestor institucional | `gestor@fet.edu.co` |
| Docente / líder | `docente@fet.edu.co` |
| Estudiante | `estudiante@fet.edu.co` |
| Empresa de pasantía | `empresa@fet.edu.co` |
| Equipo del proyecto | `hernan.patino@fet.edu.co`, `orlando.rojas@fet.edu.co`, `juan.sabogal@fet.edu.co` |

## Módulos

- **Acceso seguro** con permisos por rol, bloqueo por intentos y recuperación (avisa al administrador).
- **Oportunidades (ODS 8)**: la empresa crea vacantes, la FET las verifica, el estudiante se postula o retira su postulación.
- **Pasantías**: cuando la empresa acepta una postulación a una práctica se crea la pasantía. El estudiante sube sus documentos (plan, informes, evaluación, certificado), la empresa evaluadora los avala y el docente asignado los aprueba o los devuelve con observaciones. Cada perfil solo ve las pasantías en las que participa.
- **Innovación (ODS 9)**: proyectos con equipo, hitos y evidencias (texto o archivo).
- **Alianzas (ODS 17)**: aliados, convenios con estado y actividades.
- **Actas y compromisos**: actas con la estructura del Formato 1 de la FET (logo, participantes, objetivos, desarrollo, compromisos, conclusión, agenda, observaciones y firmas), impresión a PDF, compromisos con responsable, fecha, evidencia esperada y archivo de evidencia. Incluye las Actas 1 a 6 del proyecto con sus compromisos.
- **Avisos**: campana con avisos de compromisos asignados, cambios de postulaciones y solicitudes por revisar.
- **Indicadores ODS** por periodo, cumplimiento por acta y por responsable, exportación CSV.
- **Calidad y pruebas**: comprobaciones automáticas, resultado de los flujos de prueba, lista de hallazgos y matriz de permisos.
- **Respaldo SQL** verificable con huella SHA-256 (solo administrador).
- **Usuarios**: crear cuentas, cambiar perfil, restablecer contraseña, activar o desactivar.
- **Tutorial**: botón (?) en todas las pantallas con recorrido guiado por perfil (se abre solo la primera vez) y página «Guía de uso».

## Pruebas automáticas

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\PruebaFet\tests\smoke_test.php
```

Inicia sesión con los cinco perfiles y verifica siete flujos: acceso, permisos, oportunidades, documentación de pasantías, compromisos con evidencia, indicadores y respaldo. El resultado se muestra en «Calidad y pruebas».

## Documentación

- `docs/ARQUITECTURA.md`: capas, componentes, relaciones de datos, decisiones y flujos por perfil.
- `REQUISITOS-Intranet-FET.md`: perfiles y alcance.

## Equipo

- Miguel Antonio Urbano Silva: docente gestor.
- Hernán Felipe Patiño Rico: backend.
- Orlando Rojas Narváez: frontend.
- Juan José Sabogal García: seguimiento, pruebas y auditoría.
