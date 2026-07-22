FROM composer:2 AS composer_deps

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --no-scripts

COPY . .

RUN composer dump-autoload --optimize


FROM node:24-bookworm-slim AS frontend_assets

WORKDIR /app

COPY package*.json ./

RUN npm ci

COPY resources resources
COPY public public
COPY vite.config.js ./

RUN npm run build


FROM php:8.4-apache AS production

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
ENV PORT=80

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        gettext-base \
        unzip \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        gd \
        intl \
        opcache \
        pdo_mysql \
        zip \
    && a2enmod rewrite headers expires \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer_deps /app /var/www/html
COPY --from=frontend_assets /app/public/build /var/www/html/public/build

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/eims-entrypoint

RUN chmod +x /usr/local/bin/eims-entrypoint \
    && mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["eims-entrypoint"]

CMD ["apache2-foreground"]
