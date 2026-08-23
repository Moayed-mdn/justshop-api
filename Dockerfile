# ---------- Stage 1: البناء ----------
FROM php:8.2-fpm AS builder

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        bcmath \
        pcntl \
        exif \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install \
    --optimize-autoloader \
    --no-dev \
    --no-interaction \
    --no-scripts

COPY . /var/www
RUN composer dump-autoload --optimize --no-scripts

# ---------- Stage 2: صورة التشغيل النهائية ----------
FROM php:8.2-fpm

# نفس مكتبات الـ runtime لازمة عشان الإكستنشنز المنسوخة تشتغل صح
# (بس هون بدون composer إطلاقًا — مش موجود بهالـ stage أصلاً)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev \
        libonig-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# ننقل الإكستنشنز المبنية جاهزة من stage الأول
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# ننقل الكود + vendor/ الجاهزين (بدون composer نفسه)
COPY --from=builder /var/www /var/www

RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/testing \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh


ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
