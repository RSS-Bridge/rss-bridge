FROM php:7.1-apache

COPY * /var/www/html/
RUN echo '*' > whitelist.txt && chown -R www-data:www-data *