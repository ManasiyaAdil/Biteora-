FROM php:8.2-apache

# Install MariaDB server and client tools for self-contained cloud deployment
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y mariadb-server mariadb-client && \
    rm -rf /var/lib/apt/lists/*

# Install required PHP MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy project files
WORKDIR /var/www/html
COPY . /var/www/html/

# Ensure Unix line endings and execute permissions on start.sh
RUN sed -i 's/\r$//' /var/www/html/start.sh && \
    chmod +x /var/www/html/start.sh

# Ensure proper permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["/var/www/html/start.sh"]
