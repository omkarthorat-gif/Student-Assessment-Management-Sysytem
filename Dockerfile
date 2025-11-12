# Use official PHP image with Apache
FROM php:8.2-apache

# Copy all project files into container
COPY . /var/www/html/

# Expose port 10000 for Render
EXPOSE 10000

# Change Apache default port from 80 → 10000
RUN sed -i 's/80/10000/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Enable common PHP extensions (optional)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Start Apache
CMD ["apache2-foreground"]
