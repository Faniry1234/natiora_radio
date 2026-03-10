FROM php:8.2-cli

# Installer dépendances système
RUN apt-get update && apt-get install -y git unzip libzip-dev curl \
    && docker-php-ext-install zip pdo pdo_mysql

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le dossier de travail
WORKDIR /var/www

# Copier le projet complet
COPY . .

# Créer les dossiers si manquants
RUN mkdir -p storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Installer les dépendances
RUN composer install --no-dev --optimize-autoloader

# Exposer le port
EXPOSE 8000

# Lancer Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]