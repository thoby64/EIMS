FROM php:8.4-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    git \
    curl \
    zip \
    unzip \
    gettext \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_pgsql gd zip pcntl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Install Node.js and build assets
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    npm install && \
    npm run build && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite

# Configure Apache to serve from public directory
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Update Apache configuration to allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Add comprehensive Apache configuration for Laravel and assets
RUN cat > /etc/apache2/conf-available/laravel.conf << 'EOF'
<Directory /var/www/html/public>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

<Directory /var/www/html/public/build>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
    ExpiresActive On
    ExpiresDefault "access plus 30 days"
    Header set Cache-Control "public, max-age=31536000, immutable"
</Directory>

<Directory /var/www/html>
    AllowOverride All
</Directory>

# Security and performance headers
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"

# Asset caching
<FilesMatch "\.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$">
    Header set Cache-Control "public, max-age=31536000, immutable"
    ExpiresActive On
    ExpiresDefault "access plus 1 year"
</FilesMatch>

# Enable gzip compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
    DeflateCompressionLevel 9
</IfModule>
EOF

RUN a2enconf laravel
RUN a2enmod headers expires deflate

# Set permissions for storage and bootstrap
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/build

# PHP configuration
RUN printf "memory_limit=256M\nupload_max_filesize=100M\npost_max_size=100M\n" \
    > /usr/local/etc/php/conf.d/uploads.ini

# Create Laravel writable directories
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache public/build && \
    chmod -R 775 storage bootstrap/cache public/build

EXPOSE 80

# Clear caches at runtime (not build) - critical for Render deployments
CMD sh -c 'php artisan route:clear && php artisan cache:clear && apache2-foreground'

