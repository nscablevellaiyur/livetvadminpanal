FROM php:8.2-apache

COPY . /var/www/html/

# Create folders if they don't exist
RUN mkdir -p /var/www/html/data \
    && mkdir -p /var/www/html/uploads \
    && mkdir -p /var/www/html/uploads/category \
    && mkdir -p /var/www/html/uploads/live

# Set permissions
RUN chmod -R 0777 /var/www/html/data \
    && chmod -R 0777 /var/www/html/uploads

EXPOSE 80
