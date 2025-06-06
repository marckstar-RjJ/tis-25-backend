# Stage 1: Builder - Install dependencies
FROM php:8.2-fpm-alpine as builder

# Add potentially missing dependencies for extensions and git
RUN apk add --no-cache libexif libpq libpng-dev libjpeg-turbo-dev git

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
RUN docker-php-ext-install -j$(nproc) gd

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /app

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Set Composer environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_NO_INTERACTION=1

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy the rest of the application
COPY . .

# Copy .env.example to .env
COPY .env.example .env

# Generate APP_KEY
RUN php artisan key:generate

# Run post-install scripts
RUN composer dump-autoload --optimize

# Set permissions for storage and bootstrap cache
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Stage 2: Production - Setup Nginx and PHP-FPM
FROM php:8.2-fpm-alpine

# Install Nginx and PostgreSQL client libraries for pdo_pgsql
RUN apk add --no-cache nginx postgresql-client postgresql-dev

# Install PHP extensions needed in stage-1
RUN docker-php-ext-install -j$(nproc) pdo_pgsql

# Remove default Nginx config directory
RUN rm -rf /etc/nginx/conf.d/

# Copy application code from builder stage
COPY --from=builder /app /var/www/html

# Copy custom Nginx configuration to replace the main config
COPY nginx.conf /etc/nginx/nginx.conf

# Debug: Print the content of the copied Nginx config
RUN cat /etc/nginx/nginx.conf

# Set permissions again for the final image
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Configure session directory
RUN mkdir -p /var/lib/php/sessions && chmod 777 /var/lib/php/sessions

# Ensure logs are writable by Nginx/PHP
RUN chmod -R 777 /var/www/html/storage/logs
RUN mkdir -p /var/log/nginx && chmod 777 /var/log/nginx
RUN touch /var/log/nginx/error.log /var/log/nginx/access.log && chmod 666 /var/log/nginx/error.log /var/log/nginx/access.log

# Expose port 80 (Nginx default)
EXPOSE 80

# Start Nginx and PHP-FPM
CMD ["sh", "-c", "php artisan migrate --force && nginx && php-fpm"]


##hola