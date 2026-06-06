FROM php:8.2-apache
COPY . /var/www/html/
EXPOSE 80

FROM php:8.2-apache

# Installer l'extension PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Copier les fichiers du projet
COPY . /var/www/html/

# Activer mod_rewrite
RUN a2enmod rewrite

# Permissions
RUN chown -R www-data:www-data /var/www/html/