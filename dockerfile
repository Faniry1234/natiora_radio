FROM php:8.2-cli

# Installer dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    curl \
    && docker-php-ext-install zip pdo pdo_mysql

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir dossier de travail
WORKDIR /var/www

# Copier les fichiers
COPY . .

# Installer dépendances Laravel
RUN composer install --no-dev --optimize-autoloader

# Donner permissions
RUN chmod -R 775 storage bootstrap/cache

# Exposer port
EXPOSE 8000

# Commande de lancement
CMD php artisan serve --host=0.0.0.0 --port=8000