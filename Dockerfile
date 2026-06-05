FROM serversideup/php:8.4-frankenphp AS php-base

WORKDIR /var/www/html

USER root
RUN php -m | grep -qi '^pdo_sqlite$' || install-php-extensions pdo_sqlite
USER www-data

FROM php-base AS app-source

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts --optimize-autoloader

COPY --chown=www-data:www-data . .
RUN php artisan package:discover --ansi \
    && php artisan wayfinder:generate --fresh --no-interaction

FROM node:24-bookworm-slim AS assets

WORKDIR /var/www/html

ENV WAYFINDER_COMMAND=true

COPY package.json package-lock.json ./
RUN npm ci

COPY --from=app-source /var/www/html/resources resources
COPY --from=app-source /var/www/html/public public
COPY --from=app-source /var/www/html/vite.config.ts /var/www/html/tsconfig.json ./
RUN npm run build

FROM php-base

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/database/database.sqlite \
    CACHE_STORE=database \
    SESSION_DRIVER=database \
    QUEUE_CONNECTION=database \
    BROADCAST_CONNECTION=log \
    OCTANE_SERVER=frankenphp \
    HEALTHCHECK_PATH=/up \
    PHP_OPCACHE_ENABLE=1 \
    AUTORUN_ENABLED=true

COPY --from=app-source --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=assets --chown=www-data:www-data /var/www/html/public/build public/build

RUN mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && touch database/database.sqlite

EXPOSE 8080

HEALTHCHECK --start-period=10s CMD healthcheck-octane

CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8080"]
