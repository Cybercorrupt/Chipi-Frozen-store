#!/usr/bin/env bash
# Chipi Frozen Food — environment restore script.
# Run this if the pod was reset (apt packages are NOT persisted; only /app is).
# Usage: bash /app/scripts/restore_env.sh
set -e

echo "[1/4] Installing PHP + MariaDB + extensions..."
apt-get update -y
DEBIAN_FRONTEND=noninteractive apt-get install -y \
  php-cli php-mysql php-gd php-zip php-mbstring php-xml php-curl \
  mariadb-server default-mysql-client fonts-dejavu-core

echo "[2/4] Initializing MariaDB data dir (if needed) and starting server..."
mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld
if [ ! -d /var/lib/mysql/mysql ]; then
  mkdir -p /var/lib/mysql && chown -R mysql:mysql /var/lib/mysql
  mariadb-install-db --user=mysql --datadir=/var/lib/mysql --auth-root-authentication-method=normal
fi
nohup mysqld_safe --datadir=/var/lib/mysql >/tmp/mysqld.log 2>&1 &
sleep 12
mysqladmin ping

echo "[3/4] Creating database + user and importing schema (only if DB empty)..."
mysql -uroot <<SQL
CREATE DATABASE IF NOT EXISTS chipi_frozen_food CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'chipi'@'localhost' IDENTIFIED BY 'chipi123';
CREATE USER IF NOT EXISTS 'chipi'@'127.0.0.1' IDENTIFIED BY 'chipi123';
GRANT ALL PRIVILEGES ON chipi_frozen_food.* TO 'chipi'@'localhost';
GRANT ALL PRIVILEGES ON chipi_frozen_food.* TO 'chipi'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
TABLES=$(mysql -uchipi -pchipi123 chipi_frozen_food -N -e "SHOW TABLES;" | wc -l)
if [ "$TABLES" -eq 0 ]; then
  mysql -uchipi -pchipi123 chipi_frozen_food < /app/database/schema.sql
  echo "  schema imported."
else
  echo "  DB already has $TABLES tables, skipping import."
fi

echo "[4/4] (Re)starting PHP web server via supervisor..."
supervisorctl restart frontend || true
sleep 3
curl -s -o /dev/null -w "  HTTP %{http_code}\n" http://localhost:3000/
echo "Done. App should be live on port 3000."
