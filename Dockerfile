FROM php:8.2-cli-alpine

WORKDIR /var/www

# Composer binary from official image (more reliable than curl)
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# System deps (build + runtime)
RUN set -eux; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        sqlite-dev \
        oniguruma-dev; \
    apk add --no-cache \
        bash \
        curl \
        git \
        icu-data-full \
        libpng \
        libjpeg-turbo \
        freetype \
        sqlite-libs; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
        pdo_sqlite \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd; \
    apk del .build-deps

# Composer sanity check
RUN composer --version

# Runtime tools (race_test.sh needs curl + perl)
RUN apk add --no-cache perl

# Non-root user
RUN set -eux; \
    addgroup -g 1000 -S laravel; \
    adduser  -u 1000 -S laravel -G laravel

# Copy app sources
COPY --chown=laravel:laravel proj/ /var/www/

# Entrypoint
COPY --chown=laravel:laravel docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER laravel
EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

