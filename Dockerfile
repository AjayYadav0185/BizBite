FROM php:8.3-cli

# 1. Install Postgres system dependencies and drivers
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
    && docker-php-ext-configure pdo_pgsql --with-pdo-pgsql=/usr \
    && docker-php-ext-install \
        pdo \
        pdo_sqlite \
        pdo_pgsql \
        intl \
        zip \
        gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh


EXPOSE 10000

# Create DB, run migrations, seed, then start the web server and the
# background queue worker (see docker/entrypoint.sh). The entrypoint switches
# QUEUE_CONNECTION to "database" so notifications dispatched with
# ->afterResponse() are processed asynchronously by the worker.
CMD ["/usr/local/bin/entrypoint.sh"]
