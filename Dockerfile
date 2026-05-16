# =====================================================================
# VS Furniture Laravel Backend (PHP 8.2 - FPM)
# Multi-stage build:
#   1) vendor   : composer install (production)
#   2) runtime  : php-fpm-alpine + الكود + الإضافات + entrypoint
# يخدم خلف Nginx (FastCGI 9000). لا يقوم بأي artisan وقت البناء؛ كل شيء
# يعتمد على .env يجري في entrypoint.sh عند الإقلاع.
# =====================================================================

# --- 1) مرحلة بناء الاعتمادات ---
FROM composer:2 AS vendor

WORKDIR /app

# نسخ ملفات الاعتمادات أولاً للاستفادة من cache
COPY composer.json composer.lock ./

# نسخ بقية الكود (يحتاج composer لمعرفة autoload paths عند dump-autoload)
COPY . .

# تثبيت الاعتمادات للإنتاج بدون تشغيل scripts (artisan قد يفشل بدون .env)
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader

# --- 2) المرحلة النهائية ---
FROM php:8.2-fpm-alpine AS runtime

WORKDIR /var/www/html

# اعتمادات النظام واللازمة لـ pecl + PHP extensions
RUN apk add --no-cache \
        bash \
        curl \
        git \
        icu-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        oniguruma-dev \
        freetype-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        mysql-client \
        tzdata \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        xml \
        zip \
    && apk del --no-network --purge

# إعدادات PHP المخصصة
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# نسخ الكود (مع الاعتمادات من المرحلة السابقة)
COPY --from=vendor /app /var/www/html

# entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# إنشاء مجلدات storage الضرورية + ضبط الصلاحيات
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        uploads \
    && chown -R www-data:www-data /var/www/html \
    && find /var/www/html/storage -type d -exec chmod 775 {} \; \
    && find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \;

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm", "-F"]
