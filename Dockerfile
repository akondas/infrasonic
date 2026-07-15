# Infrasonic production image: FrankenPHP worker mode.
#
# The application is compiled at build time (`infra build`) and served by a
# pre-warmed worker, so containers start serving with no per-request boot cost.
FROM dunglas/frankenphp:1-php8.4

# Composer, copied from the official image (kept out of the runtime layer's PATH concerns).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install dependencies first for better layer caching.
COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Copy the application and compile it into runtime artifacts.
COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php bin/infra build

# FrankenPHP reads its Caddyfile from /etc/frankenphp/Caddyfile.
COPY Caddyfile /etc/frankenphp/Caddyfile

# OPcache + JIT tuning for the resident worker (biggest per-request lever).
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-infrasonic.ini"

ENV APP_DEBUG=false
EXPOSE 8080

# The base image's entrypoint launches FrankenPHP with the Caddyfile above.
