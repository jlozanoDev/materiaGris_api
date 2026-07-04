# Imagen de produccion para Railway: nginx + php-fpm + supervisor en un solo contenedor.
# Railway inyecta PORT en runtime. MySQL/Redis se proveen como servicios gestionados de Railway.

FROM php:8.2-fpm-bookworm

ARG UID=1000
ARG GID=1000
ENV COMPOSER_HOME=/tmp/composer \
    DEBIAN_FRONTEND=noninteractive \
    PORT=80

# Dependencias del sistema, extensiones PHP y ffmpeg (necesario para STT)
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip zip curl ca-certificates gnupg2 procps \
        nginx supervisor gettext-base \
        libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev \
        libxml2-dev libicu-dev zlib1g-dev libwebp-dev pkg-config \
        ffmpeg \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql bcmath gd mbstring xml zip intl exif pcntl \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# 1) Instalar dependencias antes de copiar el resto (mejor cache de capas)
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# 2) Copiar el codigo de la app (respetar .dockerignore: sin vendor/node_modules/tests/.env)
COPY . /var/www/html

# Rediscover paquetes SIN dev (descarta caches stale del host que referencian paquetes dev como pail)
RUN rm -f bootstrap/cache/services.php bootstrap/cache/packages.php \
    && php artisan package:discover --ansi --no-interaction

# Permisos de storage y cache para php-fpm (www-data)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# 3) Configs de runtime copiadas despues del COPY . para no ser sobreescritas
COPY docker/nginx/vhost.prod.conf /etc/nginx/templates/default.template
COPY docker/app/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && rm -f /etc/nginx/sites-enabled/default

EXPOSE ${PORT}

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]