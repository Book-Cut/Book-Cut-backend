# 1. Imagen base con PHP 8.3 y Apache
FROM php:8.3-apache

# 2. Instalar dependencias del sistema y extensiones de PHP (PostgreSQL + MySQL + GD para fotos)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql pdo_mysql mbstring zip exif pcntl bcmath gd opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 3. Activar mod_rewrite de Apache para las rutas de Laravel (api.php)
RUN a2enmod rewrite

# 4. Apuntar Apache a la carpeta /public de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf ${APACHE_CONFDIR}/conf-available/*.conf

# 5. Instalar Composer dentro del contenedor
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Directorio de trabajo
WORKDIR /var/www/html

# 7. Copiar el código del proyecto
COPY . .

# 8. Instalar dependencias de producción de PHP (Composer)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 9. Asignar permisos correctos a storage y bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 10. Copiar y preparar script de inicio para enlazar explícitamente a 0.0.0.0:$PORT
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 10000

CMD ["/usr/local/bin/entrypoint.sh"]
