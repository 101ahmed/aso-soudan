# ---- Build Vue SPA ----
FROM node:22-alpine AS frontend
WORKDIR /frontend
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend/ ./
RUN cp .env.production.example .env.production \
  && npm run build

# ---- PHP dependencies ----
FROM composer:2 AS vendor
WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install \
  --no-dev \
  --no-scripts \
  --no-interaction \
  --prefer-dist \
  --optimize-autoloader
COPY backend/ ./
RUN composer dump-autoload --optimize --no-dev --no-interaction

# ---- Runtime (Apache + PHP 8.3) ----
FROM php:8.3-apache

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
  && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    bcmath \
    zip \
  && a2enmod rewrite headers \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html
COPY --from=frontend /frontend/dist/ /tmp/spa-dist/
COPY docker/apache-ports.conf /etc/apache2/ports.conf
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/rdp-entrypoint.sh

RUN printf '%s\n' \
    '<Directory /var/www/html/public>' \
    '    Options FollowSymLinks' \
    '    AllowOverride All' \
    '    Require all granted' \
    '</Directory>' \
    > /etc/apache2/conf-available/rdp-public.conf \
  && a2enconf rdp-public \
  && rm -rf /var/www/html/public/assets \
  && cp -a /tmp/spa-dist/. /var/www/html/public/ \
  && rm -rf /tmp/spa-dist \
  && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
  && chmod +x /usr/local/bin/rdp-entrypoint.sh \
  && test -f /var/www/html/public/index.php \
  && test -f /var/www/html/public/index.html \
  && test -f /var/www/html/public/.htaccess

EXPOSE 8080
ENTRYPOINT ["/usr/local/bin/rdp-entrypoint.sh"]
CMD ["apache2-foreground"]
