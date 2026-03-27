FROM php:8.2-apache

# Copier les fichiers
COPY . /var/www/html/

# Activer Apache
RUN a2enmod rewrite

# Installer MySQL (si besoin)
RUN docker-php-ext-install pdo pdo_mysql

# Exposer le port
EXPOSE 80