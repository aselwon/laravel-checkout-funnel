FROM node:22-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM php:8.3-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libsqlite3-dev libonig-dev libxml2-dev libzip-dev unzip git curl \
    && docker-php-ext-install pdo_pgsql pdo_sqlite mbstring bcmath zip pcntl dom \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY . .
RUN cp .env.example .env
RUN composer install --no-interaction --prefer-dist --optimize-autoloader
COPY --from=assets /app/public/build ./public/build
RUN chmod +x docker/entrypoint.sh
EXPOSE 8000
ENTRYPOINT ["/app/docker/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000", "--no-reload"]
