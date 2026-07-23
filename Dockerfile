# ==========================================
# Stage 1 - PHP Dependencies
# ==========================================
FROM php:8.4-fpm AS vendor

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        zip \
        pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

# Install vendors without running Laravel scripts
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader \
    --no-scripts

# Copy the application
COPY . .

# Now artisan exists, so Composer scripts can run
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
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    libpq5 \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        zip \
        pcntl \
    && apt-get purge -y \
        libpq-dev \
        libzip-dev \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/*

# PHP settings
RUN printf "memory_limit=256M\nupload_max_filesize=100M\npost_max_size=100M\n" \
    > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app

# Copy application
COPY . .

# Copy Composer dependencies
COPY --from=vendor /app/vendor ./vendor

# Copy built Vite assets
COPY --from=assets /app/public/build ./public/build

# Create writable directories
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]

