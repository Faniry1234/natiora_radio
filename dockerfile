FROM php:8.1-fpm

# Install system dependencies (nginx + supervisor for simple single-container setup)
RUN apt-get update \
 && apt-get install -y --no-install-recommends nginx supervisor ca-certificates curl unzip git libzip-dev \
 && docker-php-ext-install zip pdo pdo_mysql \
 && rm -rf /var/lib/apt/lists/*

# Configure PHP for streaming endpoints: disable error output and output compression
RUN { \
    echo 'display_errors=0'; \
    echo 'zlib.output_compression=0'; \
    echo 'expose_php=0'; \
} > /usr/local/etc/php/conf.d/streaming.ini

# Create web root and copy app
WORKDIR /var/www/html
COPY . /var/www/html

# Nginx config and supervisord
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Install composer and run project install if composer.json exists
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist || true; fi

# Ensure permissions
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html || true

EXPOSE 80

# Start supervisord which will run php-fpm and nginx
CMD ["/usr/bin/supervisord","-n","-c","/etc/supervisor/conf.d/supervisord.conf"]