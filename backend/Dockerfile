# ---------- Stage 1: Build frontend assets ----------
FROM node:20-alpine AS assets

WORKDIR /app

# Copy package manifests first for better layer caching
COPY package.json package-lock.json* ./
RUN npm install

# Copy the source files Vite needs
COPY vite.config.js ./
COPY resources ./resources
COPY tailwind.config.js* postcss.config.js* ./

RUN npm run build


# ---------- Stage 2: PHP application ----------
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

# Bring in the compiled Vite assets from the build stage
COPY --from=assets /app/public/build ./public/build

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

# Execute the entrypoint script to run migrations/caching before starting the server
ENTRYPOINT ["docker-entrypoint.sh"]

# Start artisan development server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]