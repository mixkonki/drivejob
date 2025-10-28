# Εικόνα PHP + Apache
FROM php:8.2-apache

RUN apt-get update && apt-get install -y zip unzip git
COPY composer.json composer.lock ./
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN composer install


# Αντιγραφή όλων των αρχείων στο φάκελο του Apache
COPY . /var/www/html/

# Ενεργοποίηση rewrite module (αν χρειάζεται .htaccess)
RUN a2enmod rewrite

# Ορισμός φακέλου εργασίας
WORKDIR /var/www/html/

# Άνοιγμα θύρας 80
EXPOSE 80

# Εκτέλεση Apache
CMD ["apache2-foreground"]
