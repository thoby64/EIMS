# Multi-stage build for Laravel application
FROM php:8.4-fpm as base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpq-dev \
    libzip-dev \
    unzip \
    curl \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip \
    pcntl \
    && docker-php-ext-enable \
    pdo \
    pdo_pgsql \
    zip \
    pcntl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Install Node.js for Vite
FROM node:20-alpine as node-builder

WORKDIR /app

COPY package.json package-lock.json ./

# Install ALL dependencies (including dev) for building
RUN npm ci

COPY . .

RUN npm run build

# Final production image
FROM php:8.4-fpm

# Install runtime dependencies only
RUN apt-get update && apt-get install -y \
    libpq5 \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip \
    pcntl \
    && apt-get remove -y libpq-dev libzip-dev && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP
RUN echo "file_uploads = On" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/memory.ini && \
    echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini

# Set working directory
WORKDIR /app

# Copy composer files and dependencies from builder
COPY --from=base /app /app
COPY --from=base /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Copy built assets from node builder
COPY --from=node-builder /app/public/build ./public/build

# Create necessary directories
RUN mkdir -p storage/logs && \
    mkdir -p bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap

# Set permissions
RUN chmod -R 755 storage bootstrap

# Expose port
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD php -r "exit((file_exists('/app/storage/logs/laravel.log')) ? 0 : 1);"

# Start PHP-FPM
CMD ["php-fpm"]
