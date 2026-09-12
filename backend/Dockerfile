FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# Install system dependencies & required PHP extensions
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    oniguruma-dev \
    zip \
    unzip \
    bash \
    && docker-php-ext-install pdo pdo_pgsql zip mbstring bcmath opcache

# Install Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy Laravel backend code
COPY . .

# Install PHP dependencies
RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction --prefer-dist --optimize-autoloader

# Set permissions for storage and bootstrap cache
RUN chown -R www-data:www-data storage bootstrap/cache

# Copy entrypoint script if present and make it executable
RUN if [ -f docker-entrypoint.sh ]; then \
        cp docker-entrypoint.sh /usr/local/bin/ && \
        chmod +x /usr/local/bin/docker-entrypoint.sh; \
    fi

EXPOSE 8000

# Execute the entrypoint script to run migrations before starting the server
ENTRYPOINT ["docker-entrypoint.sh"]

# Start artisan development server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]