FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl ca-certificates gnupg libpq-dev libzip-dev procps poppler-utils \
    && docker-php-ext-install -j$(nproc) pdo pdo_pgsql pcntl bcmath zip \
    && pecl install redis && docker-php-ext-enable redis \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1

# Avoid "dubious ownership" when using bind mounts in dev containers.
RUN git config --system --add safe.directory /var/www/html
