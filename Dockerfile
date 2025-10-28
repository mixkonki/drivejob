# Εικόνα PHP + Apache
FROM php:8.2-apache

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
