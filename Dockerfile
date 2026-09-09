# ============ Stage 1: build the Vite frontend assets with Node ==============
FROM node:22-alpine AS frontend

WORKDIR /app

# Node dependencies (only re-run when package.json changes)
COPY package.json ./
RUN npm install --no-audit --no-fund

# Source files required by Vite/Tailwind (public/build is excluded from the
# build context via .dockerignore, so this always produces a fresh build)
COPY resources/ ./resources/
COPY vite.config.js tailwind.config.js postcss.config.js ./

RUN npm run build

# ============ Stage 2: PHP application image =================================
FROM php:8.3-cli

# 1. System dependencies + PHP extensions (including pdo_pgsql so the app
#    can be switched to Render PostgreSQL by setting DB_CONNECTION=pgsql)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libsqlite3-dev \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_sqlite \
        pdo_pgsql \
        intl \
        zip \
        gd \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. PHP production settings (opcache + upload limits)
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'memory_limit=256M'; \
        echo 'upload_max_filesize=10M'; \
        echo 'post_max_size=10M'; \
    } > /usr/local/etc/php/conf.d/zz-prod.ini

# The `php artisan serve` dev server handles concurrency through this
ENV PHP_CLI_SERVER_WORKERS=8

# 3. Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 4. PHP dependencies (only re-run when composer files change)
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --no-progress \
        --no-scripts

# 5. Node dependencies (only re-run when package.json changes)
COPY package.json ./
RUN npm install --no-audit --no-fund

# 6. Application source (vendor/node_modules/etc. excluded via .dockerignore)
COPY . .

# 7. Build the Vite frontend assets (@vite needs public/build in production)
#    and regenerate the Laravel package manifest
RUN npm run build \
    && php artisan package:discover --ansi \
    && rm -rf node_modules

# 8. Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Render Docker services default to port 10000; entrypoint also honours $PORT
EXPOSE 10000

# Create DB if needed, run migrations, seed only when fresh, then start the
# web server and the background queue worker (see docker/entrypoint.sh).
CMD ["/usr/local/bin/entrypoint.sh"]
