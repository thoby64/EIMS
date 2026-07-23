# ==========================================
# Stage 1 - Composer Dependencies
# ==========================================
FROM php:8.4-apache AS vendor

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    gettext \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        zip \
        pcntl \
    && a2enmod rewrite headers expires deflate remoteip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader \
    --no-scripts

COPY . .

RUN composer dump-autoload --optimize

# ==========================================
# Stage 2 - Build Vite Assets
# ==========================================
FROM node:20-alpine AS assets

WORKDIR /app

COPY package*.json ./

RUN npm ci

COPY . .

RUN npm run build

# ==========================================
# Stage 3 - Production
# ==========================================
FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    gettext \
    git \
    unzip \
    curl \
    libpq5 \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        zip \
        pcntl \
    && a2enmod rewrite headers expires deflate remoteip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# PHP configuration
RUN printf "memory_limit=256M\nupload_max_filesize=100M\npost_max_size=100M\n" \
    > /usr/local/etc/php/conf.d/uploads.ini

# Copy Composer from vendor stage
COPY --from=vendor /usr/bin/composer /usr/bin/composer

# Copy Composer dependencies from vendor stage
COPY --from=vendor /var/www/html/vendor ./vendor

# Copy Vite build from assets stage
COPY --from=assets /app/public/build ./public/build

# Copy application files
COPY . .

# Apache virtual host
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

# Create Laravel writable directories
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache public/build && \
    chmod -R 775 storage bootstrap/cache public/build

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["apache2-foreground"]

