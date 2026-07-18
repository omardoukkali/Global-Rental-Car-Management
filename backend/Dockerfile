# Stage 1: Build Inertia Frontend Assets
FROM node:20-alpine as frontend
WORKDIR /app
COPY backend/package.json backend/package-lock.json ./
RUN npm install
COPY backend/vite.config.js ./
COPY backend/resources resources/
RUN npm run build

# Stage 2: Build SPA Frontend Assets
FROM node:20-alpine as spa-builder
WORKDIR /spa
# Copy package files (we use wildcard in case frontend doesn't have package-lock yet)
COPY frontend/package*.json ./
RUN npm install
COPY frontend/ ./
RUN npm run build

# Stage 3: PHP-FPM Runtime (Target: app)
FROM php:8.2-fpm-alpine as app
WORKDIR /var/www/html

# Install system dependencies & Postgres PDO
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy Laravel application files
COPY backend/ .

# Copy built assets from Stage 1
COPY --from=frontend /app/public/build public/build

# Install PHP dependencies (production optimized)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Set permissions for storage & cache
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
# Updated entrypoint path for context shift
COPY backend/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

# Stage 4: Nginx Runtime (Target: web)
FROM nginx:alpine as web
WORKDIR /var/www/html

# Copy public folder containing index.php for FastCGI routing
COPY backend/public public/

# Copy built assets from Inertia frontend stage
COPY --from=frontend /app/public/build public/build

# Copy built assets from SPA frontend stage
COPY --from=spa-builder /spa/dist /var/www/html/spa-dist

# Deterministically create storage symlink inside the image
RUN ln -sf ../storage/app/public public/storage
