# Use official PHP image with Apache
FROM php:8.2-apache

# Enable PHP extensions if needed
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy project files to Apache web root
COPY . /var/www/html/

# Give permissions
RUN chmod -R 0777 /var/www/html/data
RUN chmod -R 0777 /var/www/html/uploads

# Expose Apache port
EXPOSE 80
