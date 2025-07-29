FROM php:8.2-apache

# Install necessary extensions for MySQL only
RUN docker-php-ext-install pdo_mysql

# Enable Apache mod_rewrite for Laravel
RUN a2enmod rewrite

# Set working directory to the root (adjust if using a subdirectory)
WORKDIR /var/www/html

# Copy pre-prepared Laravel files (if available) or install if not
COPY . .

# If no pre-prepared files, install Laravel 11
RUN if [ ! -f artisan ]; then \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer create-project --prefer-dist laravel/laravel:^11.0 . --no-dev; \
    fi

# Set permissions for the Laravel directory
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Update Apache document root to point to public
RUN sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www\/html\/public/' /etc/apache2/sites-available/000-default.conf

# Start Apache in the foreground
CMD ["apache2-foreground"]