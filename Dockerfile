# DriveJob — εικόνα παραγωγής (Render / οποιοδήποτε Docker host)
# PHP 8.4 + Apache, docroot: public/ (μοναδικό entry point)
FROM php:8.4-apache

# Επεκτάσεις PHP: pdo_mysql (MySQL), gd (fpdf/εικόνες), zip
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip \
    && rm -rf /var/lib/apt/lists/*

# Τα env vars της πλατφόρμας (Render κλπ.) να φτάνουν στο $_ENV
RUN echo 'variables_order = "EGPCS"' > /usr/local/etc/php/conf.d/env-order.ini

# Composer από το επίσημο image
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# 1. Εξαρτήσεις πρώτα (κάνουν cache ως layer)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts

# 2. Κώδικας εφαρμογής (βλ. .dockerignore — ΧΩΡΙΣ .env/backups/κάδο)
COPY . /var/www/html/

# 3. Βελτιστοποιημένος autoloader τώρα που υπάρχει το src/
RUN composer dump-autoload --optimize --no-dev

# Docroot: public/ — τίποτα άλλο δεν σερβίρεται
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}/!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite

# Φάκελοι εγγραφής (uploads/logs/ουρά) — σε Render θέλουν persistent disk
RUN mkdir -p storage logs \
    && chown -R www-data:www-data storage logs

EXPOSE 80
CMD ["apache2-foreground"]
