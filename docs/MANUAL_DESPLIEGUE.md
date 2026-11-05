# Manual de despliegue · Pasantías FET

Esta guía explica, paso a paso, cómo instalar Pasantías FET en un computador con Windows usando XAMPP, desde cero. Al final tendrás la plataforma funcionando en `http://localhost/PruebaFet/` con las cuentas de demostración.

**Tiempo estimado:** 20 a 30 minutos (la mayor parte es la descarga de XAMPP).

## Contenido

1. [Qué se necesita (descargas comunes)](#1-qué-se-necesita-descargas-comunes)
2. [Instalar XAMPP](#2-instalar-xampp)
3. [Iniciar Apache y MySQL](#3-iniciar-apache-y-mysql)
4. [Descargar el proyecto](#4-descargar-el-proyecto)
5. [Configurar la conexión (.env)](#5-configurar-la-conexión-env)
6. [Crear la base de datos](#6-crear-la-base-de-datos)
7. [Abrir la plataforma](#7-abrir-la-plataforma)
8. [Verificar con las pruebas automáticas](#8-verificar-con-las-pruebas-automáticas)
9. [Actualizar a una versión nueva](#9-actualizar-a-una-versión-nueva)
10. [Respaldos](#10-respaldos)
11. [Solución de problemas](#11-solución-de-problemas)
12. [Despliegue en un servidor (opcional)](#12-despliegue-en-un-servidor-opcional)

---

## 1. Qué se necesita (descargas comunes)

| Programa | Para qué | Dónde se descarga | ¿Obligatorio? |
|---|---|---|---|
| **XAMPP 8.2 para Windows** (trae Apache, PHP 8.2 y MariaDB/MySQL) | Servidor web, lenguaje y base de datos | https://www.apachefriends.org/es/download.html | Sí |
| **Navegador** actualizado (Chrome, Edge o Firefox) | Usar la plataforma | Ya viene en Windows (Edge) | Sí |
| **Git para Windows** | Descargar el proyecto y recibir actualizaciones | https://git-scm.com/download/win | Recomendado (también se puede bajar en ZIP) |
| **Visual Studio Code** | Editar el archivo `.env` o revisar el código | https://code.visualstudio.com/ | Opcional (sirve el Bloc de notas) |
| **Node.js LTS** | Solo para regenerar las capturas del manual | https://nodejs.org/es | Opcional |

**Requisitos del equipo:** Windows 10 u 11, 4 GB de RAM, 1 GB libre en disco y permisos de administrador para instalar programas.

> La plataforma usa PHP 8 con las extensiones `pdo_mysql`, `mbstring`, `fileinfo` y `curl`, y Apache con `mod_rewrite`. XAMPP ya las trae activadas, así que no hay que configurar nada extra.

## 2. Instalar XAMPP

1. Descarga el instalador de **XAMPP para Windows** con PHP 8.2 desde la página oficial.
2. Ejecútalo como administrador. Si Windows avisa sobre el Control de cuentas de usuario (UAC), acepta y continúa.
3. En **Select Components** deja marcados al menos **Apache**, **MySQL**, **PHP** y **phpMyAdmin**.
4. Usa la carpeta por defecto **`C:\xampp`**. Este manual asume esa ruta.
5. Termina la instalación y abre el **XAMPP Control Panel**.

## 3. Iniciar Apache y MySQL

1. En el **XAMPP Control Panel** pulsa **Start** en **Apache** y en **MySQL**.
2. Los dos nombres deben quedar con fondo verde. Frente a MySQL aparece el puerto en el que quedó (normalmente **3306**). Anótalo.

> **¿MySQL no arranca o usa otro puerto?** Si ya tienes otro MySQL instalado, el puerto 3306 puede estar ocupado. Cambia el de XAMPP: **Config → my.ini** en la fila de MySQL, reemplaza `port=3306` por `port=3307` en las dos secciones donde aparece, guarda y vuelve a pulsar **Start**. Luego usa ese puerto en el paso 5.
>
> **¿Apache no arranca?** Suele ser porque otro programa usa el puerto 80 (IIS, Skype u otro servidor). Ciérralo o cambia `Listen 80` por `Listen 8080` en **Config → httpd.conf**. En ese caso la dirección será `http://localhost:8080/PruebaFet/`.

## 4. Descargar el proyecto

La carpeta del proyecto debe quedar en **`C:\xampp\htdocs\PruebaFet`**.

### Opción A: con Git (recomendada)

Abre **PowerShell** o **Git Bash** y ejecuta:

```powershell
cd C:\xampp\htdocs
git clone https://github.com/OrlandoRojasNa/fet-pasantias.git PruebaFet
```

### Opción B: descargando el ZIP

1. Entra a https://github.com/OrlandoRojasNa/fet-pasantias.
2. Pulsa **Code → Download ZIP**.
3. Descomprime el archivo y renombra la carpeta `fet-pasantias-main` a **`PruebaFet`**.
4. Muévela a `C:\xampp\htdocs\`.

Comprueba que exista `C:\xampp\htdocs\PruebaFet\index.php`.

## 5. Configurar la conexión (.env)

La conexión a la base de datos se configura en el archivo `.env`. Ese archivo **no se sube a GitHub**, porque contiene la contraseña de tu base de datos. Cada persona crea el suyo.

1. En `C:\xampp\htdocs\PruebaFet` copia **`.env.example`** y nombra la copia **`.env`**.
   - En PowerShell: `copy .env.example .env`
   - Si Windows oculta las extensiones, activa **Vista → Extensiones de nombre de archivo** para ver bien el nombre.
2. Ábrelo con el Bloc de notas o VS Code. Este es el contenido por defecto:

   ```ini
   APP_NAME="Pasantías FET"
   APP_BASE_URL=/PruebaFet
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=fet_intranet
   DB_USER=root
   DB_PASS=
   ```

3. Ajusta lo que cambie en tu equipo:
   - **`DB_PORT`**: el puerto de MySQL que anotaste en el paso 3, por ejemplo `3307`.
   - **`DB_PASS`**: déjalo vacío si nunca le pusiste contraseña al usuario `root` de XAMPP, que es lo normal.
   - **`APP_BASE_URL`**: el nombre de la carpeta dentro de `htdocs`, precedido de `/`. Si la carpeta se llama `PruebaFet`, déjalo igual.

> Si no creas el `.env`, la plataforma usa los valores por defecto: puerto 3306, usuario `root` y sin contraseña.

## 6. Crear la base de datos

El instalador crea la base `fet_intranet`, sus tablas y los datos de demostración. Se puede ejecutar varias veces: no duplica datos y actualiza bases de versiones anteriores.

Con Apache y MySQL encendidos, abre **PowerShell** y ejecuta:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\PruebaFet\database\install.php
```

Si todo sale bien, verás:

```text
Pasantías FET instalada y actualizada correctamente.
Cuentas: admin@fet.edu.co, docente@fet.edu.co, estudiante@fet.edu.co, empresa@fet.edu.co | Clave: FET2026*
```

> Por seguridad, el instalador solo funciona desde la consola: si se abre en el navegador, responde *404*.

## 7. Abrir la plataforma

1. Abre `http://localhost/PruebaFet/`.
2. Entra con cualquiera de estas cuentas. La contraseña de todas es **`FET2026*`**.

| Perfil | Correo | Identificación |
|---|---|---|
| Administrador | admin@fet.edu.co | 1000000001 |
| Docente líder de pasantías | docente@fet.edu.co | 1000000003 |
| Estudiante | estudiante@fet.edu.co | 1076502055 |
| Empresa aliada | empresa@fet.edu.co | 900123456 |
| Docente líder (equipo) | hernan.patino@fet.edu.co | 1000000004 |
| Estudiante (equipo) | orlando.rojas@fet.edu.co | 1000000005 |
| Estudiante (equipo) | juan.sabogal@fet.edu.co | 1000000006 |

Para aprender a usar cada perfil, consulta el [Manual de usuario](manual/MANUAL_USUARIO.md).

## 8. Verificar con las pruebas automáticas

El proyecto trae una prueba de humo que recorre por HTTP los 8 flujos principales: acceso, permisos, convocatorias, pasantías, alianzas y cupos, empleo con hoja de vida, reportes, y respaldo con desbloqueo. Al terminar borra lo que creó.

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\PruebaFet\tests\smoke_test.php http://localhost/PruebaFet
```

El resultado esperado es `Resultado: 8 de 8 flujos pasan.`. Si algún flujo falla, el reporte muestra con una `X` qué comprobación no se cumplió.

## 9. Actualizar a una versión nueva

```powershell
cd C:\xampp\htdocs\PruebaFet
git pull
C:\xampp\php\php.exe database\install.php
```

`git pull` trae el código nuevo y el instalador aplica los cambios a la base de datos. Tu `.env` y los documentos subidos (`storage/documents`) no se tocan.

Si usaste la opción ZIP, descarga el ZIP nuevo y reemplaza los archivos, conservando tu `.env` y la carpeta `storage`. Luego vuelve a correr el instalador.

## 10. Respaldos

- **Desde la plataforma:** entra como administrador, ve a **Usuarios → Descargar respaldo SQL**. El archivo incluye su huella SHA-256 para comprobar que no se alteró.
- **Desde phpMyAdmin:** abre `http://localhost/phpmyadmin`, elige `fet_intranet` y usa **Exportar**.
- **Documentos subidos:** copia la carpeta `C:\xampp\htdocs\PruebaFet\storage\documents`.

Para restaurar, importa el `.sql` en phpMyAdmin (**Importar**) y devuelve la carpeta `storage\documents` a su lugar.

## 11. Solución de problemas

| Síntoma | Causa probable | Solución |
|---|---|---|
| `SQLSTATE[HY000] [2002] No connection could be made` al instalar | MySQL apagado o puerto equivocado | Enciende MySQL en XAMPP y revisa que `DB_PORT` en `.env` coincida con el puerto del panel. |
| `SQLSTATE[HY000] [1045] Access denied for user 'root'` | El usuario `root` tiene contraseña | Escríbela en `DB_PASS` del `.env`. |
| `"php" no se reconoce como un comando` | PHP no está en el PATH | Usa la ruta completa `C:\xampp\php\php.exe`, como en este manual. |
| La página sale sin estilos o los enlaces llevan a *Not Found* | `APP_BASE_URL` no coincide con el nombre de la carpeta | Pon en `APP_BASE_URL` el nombre exacto de la carpeta dentro de `htdocs`, por ejemplo `/PruebaFet`. |
| *Forbidden* al abrir `http://localhost/PruebaFet/docs` o `/database` | Es intencional | Esas carpetas están protegidas por `.htaccess`. La documentación se lee en GitHub o en el explorador de archivos. |
| No se puede subir un archivo | Supera los 5 MB o el formato no está permitido | La hoja de vida debe ser PDF. Los documentos de pasantía pueden ser PDF, imagen u Office. |
| Una cuenta quedó bloqueada | 5 intentos fallidos | Entra como administrador y usa **Usuarios → Desbloquear**, o vuelve a correr el instalador, que reactiva las cuentas de demostración. |
| Apache o MySQL se apagan solos | Puerto ocupado por otro programa | Consulta la nota del paso 3. |

## 12. Despliegue en un servidor (opcional)

Para publicar la plataforma en un servidor real (Linux con Apache, PHP 8.1 o superior y MariaDB/MySQL), sigue los mismos pasos y además:

1. Crea un usuario de base de datos propio, con contraseña, en lugar de `root`, y ponlo en `.env`.
2. Ajusta `APP_BASE_URL` (vacío si la plataforma queda en la raíz del dominio).
3. Habilita `AllowOverride All` para que el `.htaccess` proteja las carpetas internas.
4. Da permisos de escritura al usuario de Apache sobre `storage/documents`, `storage/logs` y `storage/exports`.
5. Usa **HTTPS**.
6. **Cambia las contraseñas de demostración** (o desactiva esas cuentas) desde **Usuarios** antes de abrir la plataforma a usuarios reales.
