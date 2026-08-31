#!/usr/bin/env bash
# Serve Chipi Frozen Food (Native PHP) on port 3000.
# Self-heals: if PHP/MariaDB are missing (pod reset wipes apt packages), restore first.
set -e

# 1. Ensure PHP + MariaDB are installed (apt packages are NOT persisted across pod resets).
if ! command -v php >/dev/null 2>&1 || ! command -v mysqld_safe >/dev/null 2>&1; then
  echo "[serve] PHP/MariaDB missing -> running restore_env.sh"
  bash /app/scripts/restore_env.sh || true
fi

# 2. Ensure MariaDB is running.
mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld || true
if ! mysqladmin ping >/dev/null 2>&1; then
  echo "[serve] starting MariaDB..."
  if [ ! -d /var/lib/mysql/mysql ]; then
    mkdir -p /var/lib/mysql && chown -R mysql:mysql /var/lib/mysql
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql --auth-root-authentication-method=normal || true
  fi
  nohup mysqld_safe --datadir=/var/lib/mysql >/tmp/mysqld.log 2>&1 &
  for i in $(seq 1 30); do mysqladmin ping >/dev/null 2>&1 && break; sleep 1; done
fi

# 3. Ensure database + user + schema exist.
mysql -uroot >/dev/null 2>&1 <<'SQL' || true
CREATE DATABASE IF NOT EXISTS chipi_frozen_food CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'chipi'@'localhost' IDENTIFIED BY 'chipi123';
CREATE USER IF NOT EXISTS 'chipi'@'127.0.0.1' IDENTIFIED BY 'chipi123';
GRANT ALL PRIVILEGES ON chipi_frozen_food.* TO 'chipi'@'localhost';
GRANT ALL PRIVILEGES ON chipi_frozen_food.* TO 'chipi'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
TABLES=$(mysql -uchipi -pchipi123 chipi_frozen_food -N -e "SHOW TABLES;" 2>/dev/null | wc -l)
if [ "$TABLES" -eq 0 ]; then
  echo "[serve] importing schema..."
  mysql -uchipi -pchipi123 chipi_frozen_food < /app/database/schema.sql || true
fi

# 4. Make upload dirs writable.
chmod -R 0777 /app/uploads 2>/dev/null || true

# 5. Serve. exec so supervisor manages the PHP process directly on port 3000.
echo "[serve] starting PHP built-in server on 0.0.0.0:3000 (docroot /app)"
exec php -d display_errors=0 -d upload_max_filesize=20M -d post_max_size=25M -S 0.0.0.0:3000 -t /app /app/scripts/router.php
