FROM node:20-bookworm AS assets

WORKDIR /app

COPY package.json package-lock.json webpack.mix.js ./
COPY resources ./resources
COPY public ./public

RUN npm ci \
    && npm run prod \
    && printf '{"/js/app.js":"/js/app.js","/css/app.css":"/css/app.css"}\n' > public/mix-manifest.json

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock artisan ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libzip-dev unzip \
    && docker-php-ext-install pdo_mysql pdo_pgsql zip opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/ ./public/
COPY docker/render-start.sh /usr/local/bin/render-start.sh

RUN test -f public/mix-manifest.json \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache \
    && chmod +x /usr/local/bin/render-start.sh

CMD ["render-start.sh"]
