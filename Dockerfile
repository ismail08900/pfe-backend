FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    libzip-dev \
    zip \
    unzip \
    git \
    postgresql-dev \
    oniguruma-dev \
    libpng-dev \
    libxml2-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    gd \
    xml

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install Composer dependencies
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy Nginx configuration
COPY nginx.conf /etc/nginx/http.d/default.conf

# Setup permissions and make entrypoint executable
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod +x docker-entrypoint.sh

# Expose port 80
EXPOSE 80

# Entrypoint
ENTRYPOINT ["./docker-entrypoint.sh"]
