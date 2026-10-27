# Guion de demostración: Intranet Privada FET

Duración sugerida: 15 a 20 minutos. Clave de todas las cuentas: `FET2026*`.

## 1. Qué es el software (1 minuto, lo dice el líder del equipo)

Según `README.md`, `REQUISITOS-Intranet-FET.md` y `docs/ARQUITECTURA.md`, es una **intranet web privada de la FET (campus Rivera, Huila)** que centraliza en un solo lugar:

- **ODS 8 (trabajo decente):** oportunidades de pasantía y empleo, con postulaciones.
- **ODS 9 (innovación):** proyectos de innovación con equipo, hitos y evidencias.
- **ODS 17 (alianzas):** aliados, convenios y actividades.
- **Actas y compromisos** en el Formato 1 de la FET, con responsables, fechas y evidencias.
- **Indicadores ODS**, avisos, usuarios, auditoría y respaldo de la base.

Tiene 5 perfiles con permisos distintos: administrador, gestor institucional, docente/líder, estudiante y empresa de pasantía. Fuera del alcance quedan notas, matrículas, pagos, nómina y firma electrónica.

Frase para abrir: *"Hoy la información de pasantías, proyectos, convenios y actas está dispersa en correos y Word. La intranet la junta, controla quién puede hacer qué y mide el aporte a los ODS 8, 9 y 17."*

Arquitectura en una frase: PHP 8 sobre Apache (XAMPP), base MariaDB con 18 llaves foráneas, HTML/CSS/JS sin librerías externas.

## 2. Antes de la clase

1. Abrir el panel de XAMPP y encender **Apache** y **MySQL**.
2. (Opcional, para datos limpios) en PowerShell:
   `C:\xampp\php\php.exe C:\xampp\htdocs\PruebaFet\database\install.php`
3. Abrir el navegador en `http://localhost/PruebaFet/`. Usar una ventana normal y una de **incógnito** para tener dos perfiles a la vez (por ejemplo, empresa y administrador) sin cerrar sesión cada vez.
4. Tener abierto VS Code con el proyecto, por si el profesor pide ver código.

| Perfil | Correo | Identificación |
|---|---|---|
| Administrador | admin@fet.edu.co | 1000000001 |
| Gestor | gestor@fet.edu.co | 1000000002 |
| Docente / líder | docente@fet.edu.co | 1000000003 |
| Estudiante | estudiante@fet.edu.co | 1076502055 |
| Empresa | empresa@fet.edu.co | 900123456 |

## 3. Recorrido paso a paso

### Paso 1. Acceso seguro (2 min)
- En el login, escribir una clave incorrecta: sale un mensaje genérico que no revela si la cuenta existe.
- Contar que a los 5 intentos fallidos la cuenta se bloquea 10 minutos, y mostrar "¿Olvidaste tu contraseña?" (avisa al administrador).
- Entrar con la **identificación** `1076502055` (también acepta correo) y marcar "Recordarme".
- Decir: *"Contraseñas con bcrypt, token CSRF en cada formulario y permisos validados en el servidor."*

### Paso 2. Estudiante (3 min) — sesión `estudiante@fet.edu.co`
- **Inicio:** resumen personal. Señalar el botón **(?)**, que lanza el recorrido guiado por perfil.
- **Oportunidades (ODS 8):** abrir una vacante verificada y pulsar **Postularme**. Mencionar que puede retirar la postulación.
- **Actas y compromisos:** abrir un compromiso propio e intentar marcarlo como completado **sin evidencia**: el sistema lo impide. Agregar una evidencia escrita y queda "Completado".

### Paso 3. Empresa de pasantía (2 min) — incógnito, `empresa@fet.edu.co`
- Mostrar que tiene un **panel propio** y un menú reducido.
- **Nueva oportunidad:** crearla. Queda **"En revisión"**, no se publica sola.
- **Postulaciones recibidas:** aceptar la del estudiante o marcarla "No seleccionada".
- Decir: *"La empresa solo ve y modifica sus propias vacantes."*

### Paso 4. Administrador (4 min) — `admin@fet.edu.co`
- **Campana de avisos:** aparece la vacante pendiente de revisar.
- **Oportunidades:** verificarla y publicarla. Volver a la sesión del estudiante para mostrar que le llegó el aviso.
- **Usuarios:** crear una cuenta, cambiar perfil, restablecer contraseña, activar o desactivar.
- **Calidad y pruebas → Respaldo SQL:** descargarlo y mostrar la huella **SHA-256** que verifica que no fue alterado.

### Paso 5. Gestor institucional (2 min) — `gestor@fet.edu.co`
- **Alianzas (ODS 17):** aliados, convenio con su estado y actividades.
- **Actas:** usar los filtros para el seguimiento de compromisos.
- **Indicadores:** elegir periodo, ver ODS 8, 9 y 17, cumplimiento por acta y por responsable, y **Exportar CSV**.

### Paso 6. Docente / líder (3 min) — `docente@fet.edu.co`
- **Innovación (ODS 9):** el proyecto con su equipo, hitos y evidencias.
- **Actas:** abrir una de las Actas 1 a 6 del proyecto en **Formato 1** (logo, participantes, objetivos, desarrollo, compromisos, conclusión, firmas) e **imprimir a PDF**.
- Decir: *"Las actas reales de nuestro proyecto están cargadas en el sistema con sus compromisos."*

## 4. Cierre: calidad y pruebas (3 min)

- En **Calidad y pruebas** mostrar los resultados de los 6 flujos automáticos (acceso, permisos, oportunidades, compromisos con evidencia, indicadores y respaldo), los hallazgos y la matriz de permisos.
- Si da tiempo, ejecutar en vivo: `C:\xampp\php\php.exe C:\xampp\htdocs\PruebaFet\tests\smoke_test.php` → "6 de 6 flujos pasan".
- Mostrar la documentación de pruebas del login en `docs/`:
  - `Pruebas_Caja_Negra.md`: particiones de equivalencia, valores límite y tabla de decisión.
  - `Test_Cases_Login.xlsx`: 12 casos (5 positivos y 7 negativos).
  - `Reporte_Defectos.md`: 2 defectos reales (BUG-LOGIN-001 y 002) corregidos y reprobados.
  - Resultado: primera ejecución 8 Pass, 2 Fail y 2 Blocked; final **12 Pass, 0 Fail y 0 Blocked**. Las evidencias están en `docs/evidencias/`.

## 5. Reparto sugerido entre el equipo

- **Orlando (frontend):** pasos 1 a 3 (interfaz, recorrido guiado, experiencia por perfil).
- **Hernán (backend):** pasos 4 y 5 (permisos, respaldo SHA-256, indicadores, base de datos).
- **Juan José (pruebas y auditoría):** sección 4 (smoke test, caja negra, defectos y evidencias).
- **Docente gestor (Miguel Urbano):** perfil docente y actas, si participa.

## 6. Preguntas probables y plan B

- **¿Cómo evitan la inyección SQL?** Con PDO y sentencias preparadas; además existe el caso TC-LOGIN-008.
- **¿Qué pasa si un estudiante entra a una URL de administrador?** Ve la página 403. Se puede mostrar abriendo `http://localhost/PruebaFet/users.php` con el estudiante.
- **¿Dónde se guardan los archivos de evidencia?** En `storage/documents`, con nombre aleatorio y bloqueado por `.htaccess`; se descargan solo por `evidence.php` con control de permisos.
- **Si la página no carga:** revisar que Apache y MySQL estén en verde en XAMPP. Si MySQL usa otro puerto (por ejemplo 3307), configúralo en DB_PORT del archivo .env.
- **Si los datos quedaron desordenados por ensayos:** volver a ejecutar `install.php`, que no duplica datos.
