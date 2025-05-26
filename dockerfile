# Stage 1: Builder - Install dependencies
FROM php:8.2-fpm-alpine as builder

# Add potentially missing dependencies for extensions
RUN apk add --no-cache libexif libpq

# Install PHP extensions
RUN apk add --no-cache \
    autoconf \
    g++ \
    make \
    php82-pear \
    php82-dev \
    postgresql-dev \
    oniguruma-dev # Required for mbstring

RUN docker-php-ext-install -j$(nproc) pdo_pgsql
RUN docker-php-ext-install -j$(nproc) mbstring
RUN docker-php-ext-install -j$(nproc) exif
RUN docker-php-ext-install -j$(nproc) pcntl
RUN docker-php-ext-install -j$(nproc) bcmath
RUN docker-php-ext-install -j$(nproc) ctype
RUN docker-php-ext-install -j$(nproc) openssl

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /app

# Copy application code (composer.json and composer.lock first for caching)
COPY composer.json composer.lock ./

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy the rest of the application code
COPY . .

# Generate application key (optional in builder, but good for consistency if .env is included)
# RUN php artisan key:generate # Needs .env or env vars

# Run npm install and build (if you use frontend assets built by Vite)
# RUN apk add --no-cache nodejs npm
# COPY package.json package-lock.json vite.config.js ./
# RUN npm install
# RUN npm run build

# Set permissions for storage and bootstrap cache
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Stage 2: Production - Setup Nginx and PHP-FPM
FROM php:8.2-fpm-alpine

# Install Nginx
RUN apk add --no-cache nginx

# Copy application code from builder stage
COPY --from=builder /app /var/www/html

# Copy custom Nginx configuration
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Set permissions again for the final image
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
# Ensure logs are writable by Nginx/PHP
RUN chmod -R 777 /var/www/html/storage/logs

# Expose port 80 (Nginx default)
EXPOSE 80

# Start Nginx and PHP-FPM
CMD ["sh", "-c", "nginx && php-fpm"]
