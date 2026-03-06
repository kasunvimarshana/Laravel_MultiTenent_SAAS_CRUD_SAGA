# ============================================================
# Stage 1: Composer dependency installation
# ============================================================
FROM composer:2.7 AS vendor

WORKDIR /app

COPY composer.json composer.lock* ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-reqs \
    --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev

# ============================================================
# Stage 2: Final application image (PHP 8.3 + Nginx + php-fpm)
# ============================================================
FROM php:8.3-fpm-alpine AS app

LABEL maintainer="SAAS Platform Team"
LABEL service="saga-orchestrator"

# -----------------------------------------------------------------------
# System dependencies
# -----------------------------------------------------------------------
RUN apk add --no-cache \
        nginx \
        supervisor \
        curl \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        icu-dev \
        linux-headers \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
        bcmath \
        intl \
        opcache \
    && apk del --no-cache linux-headers

# -----------------------------------------------------------------------
# Pecl extensions
# -----------------------------------------------------------------------
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-cache $PHPIZE_DEPS

# -----------------------------------------------------------------------
# PHP-FPM configuration
# -----------------------------------------------------------------------
COPY docker/php/php.ini         /usr/local/etc/php/php.ini
COPY docker/php/php-fpm.conf    /usr/local/etc/php-fpm.d/www.conf

# -----------------------------------------------------------------------
# Nginx configuration
# -----------------------------------------------------------------------
COPY docker/nginx/nginx.conf    /etc/nginx/nginx.conf
COPY docker/nginx/default.conf  /etc/nginx/http.d/default.conf

# -----------------------------------------------------------------------
# Supervisor configuration (manages nginx + php-fpm + consumer)
# -----------------------------------------------------------------------
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

# -----------------------------------------------------------------------
# Application code
# -----------------------------------------------------------------------
WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# -----------------------------------------------------------------------
# Entrypoint
# -----------------------------------------------------------------------
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -sf http://localhost:8000/api/health || exit 1

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
