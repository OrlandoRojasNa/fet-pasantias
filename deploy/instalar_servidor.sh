#!/usr/bin/env bash
# Instala Pasantías FET en un servidor Ubuntu 22.04/24.04 limpio, siguiendo el Manual de despliegue (sección 5).
# Uso (como root o con sudo):
#   sudo DOMINIO=pasantias.fet.edu.co ADMIN_CORREO=coordinacion@fet.edu.co ADMIN_ID=1075000000 \
#        ADMIN_CLAVE='ClaveSegura2026' bash deploy/instalar_servidor.sh
# Variables opcionales: ADMIN_NOMBRE, DB_PASS (si no se da, se genera), VERSION (rama o etiqueta, por defecto main),
#   CERTBOT=1 para pedir un certificado de Let's Encrypt (requiere dominio público); si no, usa un certificado autofirmado.
set -euo pipefail
umask 027
: "${DOMINIO:?Falta DOMINIO}" "${ADMIN_CORREO:?Falta ADMIN_CORREO}" "${ADMIN_ID:?Falta ADMIN_ID}" "${ADMIN_CLAVE:?Falta ADMIN_CLAVE}"
ADMIN_NOMBRE=${ADMIN_NOMBRE:-"Coordinación de Pasantías"}
VERSION=${VERSION:-main}
REPO=${REPO:-https://github.com/OrlandoRojasNa/fet-pasantias.git}
DIR=${DIR:-/var/www/pasantias-fet}
# Si ya hay una instalación, se conserva la clave de base de datos de su .env.
[ -z "${DB_PASS:-}" ] && [ -f "$DIR/.env" ] && DB_PASS=$(grep -E '^DB_PASS=' "$DIR/.env" | cut -d= -f2-)
DB_PASS=${DB_PASS:-$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 24)}
paso(){ printf '\n\033[1;32m== %s\033[0m\n' "$*"; }
servicio(){ if command -v systemctl >/dev/null && [ -d /run/systemd/system ]; then systemctl "$1" "$2"; else service "$2" "$1"; fi; }

paso "1/8 Paquetes (Apache, PHP, MariaDB, Git)"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq sudo cron apache2 mariadb-server git openssl php libapache2-mod-php php-mysql php-mbstring php-curl php-xml >/dev/null
a2enmod -q rewrite headers ssl >/dev/null
servicio start mariadb; servicio start apache2

paso "2/8 Base de datos y usuario propio"
mysql -e "DELETE FROM mysql.global_priv WHERE User=''; DROP DATABASE IF EXISTS test;" 2>/dev/null || true
mysql -e "CREATE DATABASE IF NOT EXISTS fet_intranet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'pasantias'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER 'pasantias'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON fet_intranet.* TO 'pasantias'@'localhost'; FLUSH PRIVILEGES;"

paso "3/8 Código ($VERSION)"
if [ -d "$DIR/.git" ]; then git -C "$DIR" fetch -q --tags origin; else git clone -q "$REPO" "$DIR"; fi
git -C "$DIR" config core.fileMode false   # los permisos se ajustan en el paso 4; git no debe verlos como cambios
if git -C "$DIR" show-ref -q --verify "refs/remotes/origin/$VERSION"; then
  git -C "$DIR" checkout -q -B "$VERSION" "origin/$VERSION"   # rama: se actualiza a lo último publicado
else
  git -C "$DIR" checkout -q "$VERSION"                        # etiqueta de versión, por ejemplo v2.0.0
fi
git -C "$DIR" log --oneline -1

paso "4/8 Configuración (.env de producción) y permisos"
cat > "$DIR/.env" <<ENV
APP_NAME="Pasantías FET"
APP_ENV=production
APP_BASE_URL=
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=fet_intranet
DB_USER=pasantias
DB_PASS=${DB_PASS}
ENV
chown -R root:www-data "$DIR"
find "$DIR" -type d -exec chmod 750 {} \;
find "$DIR" -type f -exec chmod 640 {} \;
chown -R www-data:www-data "$DIR/storage"

paso "5/8 Base de datos de producción y administrador inicial"
cd "$DIR"
sudo -u www-data env FET_ADMIN_PASS="$ADMIN_CLAVE" php database/install.php --produccion \
  --admin-correo="$ADMIN_CORREO" --admin-identificacion="$ADMIN_ID" --admin-nombre="$ADMIN_NOMBRE"

paso "6/8 Apache: sitio, HTTPS y cabeceras de seguridad"
if [ "${CERTBOT:-0}" != 1 ]; then
  [ -f /etc/ssl/private/pasantias-fet.key ] || openssl req -x509 -nodes -days 825 -newkey rsa:2048 -subj "/CN=${DOMINIO}" \
    -keyout /etc/ssl/private/pasantias-fet.key -out /etc/ssl/certs/pasantias-fet.crt 2>/dev/null
fi
BLOQUE="    DocumentRoot ${DIR}
    <Directory ${DIR}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    Header always set X-Content-Type-Options \"nosniff\"
    Header always set X-Frame-Options \"SAMEORIGIN\"
    Header always set Referrer-Policy \"strict-origin-when-cross-origin\"
    ErrorLog \${APACHE_LOG_DIR}/pasantias-fet-error.log
    CustomLog \${APACHE_LOG_DIR}/pasantias-fet-access.log combined"
cat > /etc/apache2/sites-available/pasantias-fet.conf <<CONF
<VirtualHost *:80>
    ServerName ${DOMINIO}
    RewriteEngine On
    RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [END,R=301]
${BLOQUE}
</VirtualHost>
CONF
if [ "${CERTBOT:-0}" = 1 ]; then
  a2ensite -q pasantias-fet.conf; a2dissite -q 000-default.conf || true
  apt-get install -y -qq certbot python3-certbot-apache >/dev/null
  certbot --apache -d "$DOMINIO" --redirect --non-interactive --agree-tos -m "$ADMIN_CORREO"
else
  cat > /etc/apache2/sites-available/pasantias-fet-ssl.conf <<CONF
<VirtualHost *:443>
    ServerName ${DOMINIO}
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/pasantias-fet.crt
    SSLCertificateKeyFile /etc/ssl/private/pasantias-fet.key
${BLOQUE}
</VirtualHost>
CONF
  a2ensite -q pasantias-fet.conf pasantias-fet-ssl.conf; a2dissite -q 000-default.conf || true
fi

paso "7/8 PHP de producción"
for INI in /etc/php/*/apache2/php.ini; do
  sed -i 's/^display_errors = .*/display_errors = Off/; s/^log_errors = .*/log_errors = On/; s/^expose_php = .*/expose_php = Off/;
          s/^upload_max_filesize = .*/upload_max_filesize = 8M/; s/^post_max_size = .*/post_max_size = 10M/;
          s/^;\?session.cookie_httponly =.*/session.cookie_httponly = 1/' "$INI"
done
apache2ctl configtest
servicio restart apache2 || servicio start apache2

paso "8/8 Respaldo diario y cortafuegos"
cat > /usr/local/bin/respaldo-pasantias.sh <<S
#!/bin/bash
set -e
umask 077
DESTINO=/var/backups/pasantias-fet
FECHA=\$(date +%F)
mkdir -p "\$DESTINO"
mysqldump --single-transaction fet_intranet | gzip > "\$DESTINO/base-\$FECHA.sql.gz"
tar -czf "\$DESTINO/documentos-\$FECHA.tar.gz" -C ${DIR} storage/documents
find "\$DESTINO" -type f -mtime +30 -delete
S
chmod 700 /usr/local/bin/respaldo-pasantias.sh
echo "0 2 * * * root /usr/local/bin/respaldo-pasantias.sh" > /etc/cron.d/respaldo-pasantias
/usr/local/bin/respaldo-pasantias.sh && echo "Primer respaldo creado en /var/backups/pasantias-fet"
if command -v ufw >/dev/null && ufw status >/dev/null 2>&1; then ufw allow OpenSSH >/dev/null; ufw allow 'Apache Full' >/dev/null; ufw --force enable >/dev/null; echo "Cortafuegos activo"; else echo "ufw no disponible: configura el cortafuegos manualmente (puertos 22, 80 y 443)"; fi

printf '\n\033[1;32mListo.\033[0m Abre https://%s/ y entra con %s.\n' "$DOMINIO" "$ADMIN_CORREO"
printf 'La clave del usuario de base de datos quedó en %s/.env (solo legible por root y www-data).\n' "$DIR"
