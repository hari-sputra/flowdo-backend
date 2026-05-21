FROM php:8.4-fpm-alpine

# Install system dependencies & PostgreSQL PHP extension
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Salin source code
COPY . .

EXPOSE 9000
CMD ["php-fpm"]
