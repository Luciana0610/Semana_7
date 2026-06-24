FROM php:8.2-apache

# Instalar extensión mysqli para PHP
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copiar todos los archivos del proyecto al directorio web de Apache
COPY . /var/www/html/

# Dar permisos adecuados
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
