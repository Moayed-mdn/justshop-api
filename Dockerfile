FROM php:8.2-fpm

RUN apt-get update && apt-get install -y --no-install-recommends\ 
 libzip-dev\
 &&  docker-php-ext-install pdo_mysql mbstring bcmath pcntl exif zip\
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# Set working directory
WORKDIR /var/www
# Copy composer files first (for better caching)
COPY composer.json composer.lock ./
# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev --no-interaction --no-scripts
# Copy application code
COPY . /var/www
# Run composer again to trigger scripts
RUN composer dump-autoload --optimize


# create  new directories instead of the current of the project
RUN mkdir -p storage/framework/{cache,sessions,views,testing} storage/logs bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache
