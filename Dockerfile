FROM php:7-apache

ENV APACHE_DOCUMENT_ROOT=/app

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
	&& apt-get --yes update && apt-get --yes install libxml2-dev \
	&& docker-php-ext-install -j$(nproc) simplexml \
	&& sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
	&& sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -i '/CipherString = DEFAULT@SECLEVEL=2/d' /etc/ssl/openssl.cnf \
    && sed -i -e 's/memory_limit = 128M/memory_limit = 256M/g' $PHP_INI_DIR/php.ini

COPY ./ /app/
RUN chown -R www-data:www-data /app
