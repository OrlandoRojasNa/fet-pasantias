# Informe de prueba de despliegue en máquina virtual

| | |
|---|---|
| **Producto** | Pasantías FET 2.0.0 |
| **Fecha de la prueba** | 05/11/2026 |
| **Objetivo** | Comprobar, en servidores limpios, que el [Manual de despliegue](../MANUAL_DESPLIEGUE.md) funciona tal como está escrito, y corregir lo que no funcione |
| **Resultado** | ✅ **Aprobado.** La plataforma quedó publicada con HTTPS y pasó los 8 puntos de la verificación posterior al despliegue. Durante la prueba se encontraron 5 problemas; todos quedaron corregidos en el código o en el manual. |

## 1. Ambiente de la prueba

| Elemento | Valor |
|---|---|
| Máquinas virtuales | 3 servidores **Ubuntu Server 24.04.5 LTS** recién creados, cada uno aislado y sin nada instalado (contenedores de sistema) |
| Software instalado por el manual | Apache 2.4.58, PHP 8.3.6, MariaDB 10.11.14 y Git |
| Dominio de prueba | `pasantias.fet.edu.co`, resuelto localmente hacia la IP de la máquina virtual |
| Certificado | Autofirmado: sin dominio público, Let's Encrypt no puede validar el dominio |
| Versión desplegada | Rama `docs-manuales-pruebas` (la que contiene los cambios de lanzamiento) |

| Máquina | Uso |
|---|---|
| VM 1 | Instalación **manual**, comando por comando, siguiendo las secciones 5.1 a 5.9. Prueba de respaldo y restauración (sección 9). |
| VM 2 | Primera prueba del **script de instalación automática** `deploy/instalar_servidor.sh` y de su ejecución repetida. |
| VM 3 | **Prueba final** en limpio del script ya corregido, con la verificación completa de la sección 7. |

## 2. Pasos ejecutados (VM 1, instalación manual)

| Sección | Paso | Resultado |
|---|---|---|
| 5.1 | Instalar Apache, MariaDB, Git, PHP y sus extensiones; activar `rewrite` y `headers` | ✅ PHP 8.3.6 |
| 5.2 | Crear la base `fet_intranet` y el usuario propio `pasantias` | ✅ |
| 5.3 | Clonar el repositorio en `/var/www/pasantias-fet` | ✅ |
| 5.4 | Crear el `.env` de producción (`APP_ENV=production`, usuario `pasantias`) y aplicar permisos | ✅ |
| 5.5 | `install.php --produccion` con el administrador inicial | ✅ «Administrador inicial creado» |
| 5.6 | VirtualHost con cabeceras de seguridad; `configtest` | ✅ `Syntax OK` |
| 5.7 | HTTPS con certificado autofirmado y redirección de HTTP a HTTPS | ✅ (procedimiento agregado al manual) |
| 5.8 | Ajustes de `php.ini` para producción | ✅ |
| 5.9 | Cortafuegos `ufw` | ⚠️ No aplicable: el aislamiento de la prueba no permite modificar el cortafuegos del sistema. En un servidor o VM real se aplica tal como dice el manual. |
| 9.1 | Script de respaldo diario | ✅ Se generan `base-AAAA-MM-DD.sql.gz` y `documentos-AAAA-MM-DD.tar.gz` |
| 9.2 | Restauración: se borró un usuario y se restauró el respaldo | ✅ El usuario volvió (3 → 2 → 3) y el sitio respondió 200 |
| 10.1 | Ejecutar de nuevo el instalador sobre la base existente | ✅ Sin duplicados (idempotente) |

## 3. Verificación posterior al despliegue (sección 7, VM 3)

| # | Verificación | Resultado obtenido | ✔ |
|---|---|---|---|
| 1 | `https://pasantias.fet.edu.co/` | 200, pantalla de acceso | ✅ |
| 2 | `http://pasantias.fet.edu.co/` | 301 → `https://pasantias.fet.edu.co/` | ✅ |
| 3 | Entrar con el administrador inicial | Abre el *Panel de pasantías* | ✅ |
| 4 | `/.env`, `/config/database.php`, `/database/install.php`, `/storage/`, `/storage/documents/`, `/tests/smoke_test.php`, `/.git/config` | **403** en todos | ✅ |
| 5 | Entrar con `admin@fet.edu.co` y `FET2026*` | Rechazado: «Identificación, correo o contraseña incorrectos» | ✅ |
| 6 | **Usuarios → Descargar respaldo SQL** | Archivo de 13,4 KB con su huella SHA-256 | ✅ |
| 7 | Crear una alianza, subir un PDF y descargarlo | Hoja de vida subida por un estudiante y descargada por la empresa (PDF íntegro). Un usuario sin sesión recibe 302 hacia el acceso. | ✅ |
| 8 | Errores de PHP en el registro de Apache | 0 | ✅ |

**Flujo funcional recorrido en producción:**

1. El administrador registró la alianza *Empresa de Verificación S.A.S.* con 1 cupo y creó un estudiante.
2. La empresa entró con su NIT y publicó una convocatoria.
3. El administrador la verificó.
4. El estudiante se postuló con su hoja de vida.
5. La empresa descargó la hoja de vida.
6. Al terminar, la alianza de prueba se suspendió.

**Controles adicionales comprobados:**

- La cookie de sesión sale con `secure; HttpOnly; SameSite=Lax`.
- Las cabeceras `X-Content-Type-Options`, `X-Frame-Options` y `Referrer-Policy` están presentes.
- Los archivos subidos quedan con permisos `-rw-r-----` (solo el servidor web los lee) y los respaldos con `-rw-------` (solo root).
- El respaldo diario queda programado en `/etc/cron.d/respaldo-pasantias`.
- Existe un único administrador, incluso después de ejecutar el instalador dos veces.

## 4. Problemas encontrados y corregidos

| # | Problema | Impacto | Corrección |
|---|---|---|---|
| H-01 | Los archivos subidos (hojas de vida y documentos) quedaban con permisos `666`, legibles y modificables por cualquier usuario del servidor. | Seguridad, alto | `store_evidence_upload()` ahora deja cada archivo en `640` y crea la carpeta con `750`. |
| H-02 | La pantalla de acceso mostraba el recuadro **Cuentas de demostración**, con la clave `FET2026*`, también en producción. Además listaba la cuenta *Gestor*, que ya no existe. | Seguridad e imagen, medio | El recuadro solo aparece si `APP_ENV` no es `production`. La lista quedó actualizada. Además, el `.env` ahora se carga al inicio de cada página, para que `APP_ENV` y `APP_BASE_URL` apliquen siempre. |
| H-03 | Después de aplicar los permisos de producción, `git checkout` no dejaba actualizar: git tomaba los cambios de permisos como modificaciones locales. | Operación, alto: bloqueaba la sección 10 | Se agregó `git config core.fileMode false` al manual (sección 5.4) y al script. |
| H-04 | Los respaldos quedaban con permisos amplios cuando la máscara del sistema es permisiva. | Seguridad, medio | Se agregó `umask 077` al script de respaldo del manual y del instalador automático. |
| H-05 | El manual solo cubría HTTPS con Let's Encrypt, que no sirve para servidores internos sin dominio público. Además, el script automático fallaba en servidores sin `sudo`. | Despliegue, medio | El manual incluye ahora el procedimiento con certificado propio o autofirmado (sección 5.7). El script instala `sudo` y `cron` si faltan. |

Después de las correcciones se repitió la instalación completa en una máquina nueva (VM 3), con resultado exitoso.

Las pruebas automáticas del ambiente de pruebas (`tests/smoke_test.php`) siguen en **8 de 8**.

## 5. Evidencias

**Cuenta de demostración rechazada en producción, sin el recuadro de cuentas demo:**

![Cuenta demo rechazada](img/01-cuenta-demo-rechazada.png)

**Panel del administrador inicial en la base de producción vacía:**

![Panel del administrador](img/02-admin-panel.png)

**Alianza registrada en producción:**

![Alianza creada](img/03-alianza-creada.png)

**La empresa recibe la postulación con la hoja de vida subida en el servidor:**

![Postulación con hoja de vida](img/04-empresa-postulacion-con-hv.png)

**Bitácora de auditoría con todo el recorrido:**

![Reportes y auditoría](img/05-reportes-auditoria.png)

## 6. Cómo repetir esta prueba

En una máquina virtual con Ubuntu Server 22.04 o 24.04 (ver el [Manual de despliegue, sección 5.10](../MANUAL_DESPLIEGUE.md#510-probar-el-despliegue-en-una-máquina-virtual-virtualbox)):

```bash
sudo apt install -y git
git clone https://github.com/OrlandoRojasNa/fet-pasantias.git ~/fet-pasantias && cd ~/fet-pasantias
sudo DOMINIO=pasantias.local ADMIN_CORREO=coordinacion.pasantias@fet.edu.co \
     ADMIN_ID=1075000000 ADMIN_CLAVE='ClaveSegura2026' bash deploy/instalar_servidor.sh
```

Luego recorre la tabla de la sección 3 de este informe.
