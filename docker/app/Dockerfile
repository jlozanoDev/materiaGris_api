
FROM php:8.2-fpm

ARG UID=1000
ARG GID=1000
ENV COMPOSER_HOME=/tmp/composer

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    zlib1g-dev \
    libwebp-dev \
    pkg-config \
    ca-certificates \
    gnupg2 \
    curl \
    procps \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql bcmath gd mbstring xml zip intl exif pcntl \
    && pecl install redis && docker-php-ext-enable redis \
    && pecl install xdebug && docker-php-ext-enable xdebug \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer --version

WORKDIR /var/www/html

# Create developer user (mirror host uid) to avoid permission issues when mounting volumes
RUN groupadd -g ${GID} dev || true && \
    useradd -u ${UID} -g ${GID} -m -s /bin/bash dev || true && \
    usermod -aG www-data dev || true

# Ensure directories exist and have correct ownership
RUN mkdir -p /var/www/html && chown -R dev:www-data /var/www/html

# Copy custom PHP config files (e.g., conf.d/*.ini)
COPY conf.d/*.ini /usr/local/etc/php/conf.d/

EXPOSE 9000

# Xdebug listens on 9003 by default; expose for host debugging
EXPOSE 9003

CMD ["php-fpm"]
