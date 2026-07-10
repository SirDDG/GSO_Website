# 1. Gamitin ang official PHP image na may kasama nang Apache
FROM php:8.2-apache

# 2. I-install ang mga PHP extensions na kailangan ng website mo (halimbawa: mysqli para sa database)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 3. I-copy ang iyong website files mula sa computer mo papunta sa folder ng Apache
# Ang /var/www/html ay ang default folder ng Apache sa loob ng container
COPY . /var/www/html/

# 4. (Optional) Baguhin ang permission para siguradong readable ang files
RUN chown -R www-data:www-data /var/www/html

# 5. I-expose ang port 80 para ma-access ng internet ang website mo
EXPOSE 80