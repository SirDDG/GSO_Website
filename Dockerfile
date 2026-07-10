# 1. Gamitin ang official PHP image na may kasama nang Apache
FROM php:8.2-apache

# 2. I-install ang mga PHP extensions na kailangan ng website mo
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 3. I-configure ang Apache na makinig sa port 8080 (Required for Cloud Run)
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# 4. I-copy ang iyong website files mula sa computer mo papunta sa folder ng Apache
COPY . /var/www/html/

# 5. (Optional) Baguhin ang permission para siguradong readable ang files
RUN chown -R www-data:www-data /var/www/html

# 6. I-expose ang port 8080
EXPOSE 8080