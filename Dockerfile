FROM php:8.1-apache

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite (often needed for PHP apps)
RUN a2enmod rewrite

# Copy project files into the container
COPY . /var/www/html/

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html/
