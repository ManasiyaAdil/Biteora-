#!/bin/bash
set -e

# Configure Apache port dynamically from Render's $PORT env variable
PORT_NUM=${PORT:-80}
echo "Starting Biteora on port $PORT_NUM..."
sed -i "s/80/$PORT_NUM/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# If no external DB_HOST is set, start the container's built-in MariaDB service
if [ -z "$DB_HOST" ] && [ -z "$MYSQLHOST" ] && [ -z "$MYSQL_HOST" ]; then
    echo "No external MySQL configured. Starting embedded MariaDB server..."
    service mariadb start || /etc/init.d/mariadb start

    # Ensure root user has empty password for local unix socket / 127.0.0.1
    mysql -e "CREATE DATABASE IF NOT EXISTS food CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" || true
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('');" 2>/dev/null || true
    mysql -e "FLUSH PRIVILEGES;" || true

    # Import baseline database schema if food table empty
    if [ -f /var/www/html/sql/food.sql ]; then
        mysql food < /var/www/html/sql/food.sql 2>/dev/null || true
    fi
    echo "Embedded MariaDB is ready and running on 127.0.0.1:3306!"
fi

# Hand over to Apache in the foreground
exec apache2-foreground
