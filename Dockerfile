FROM php:7.4.29-fpm

LABEL description="RSS-Bridge is a PHP project capable of generating RSS and Atom feeds for websites that don't have one."
LABEL repository="https://github.com/RSS-Bridge/rss-bridge"
LABEL website="https://github.com/RSS-Bridge/rss-bridge"

RUN apt-get update && \
    apt-get install --yes --no-install-recommends \
      nginx \
      zlib1g-dev \
      libmemcached-dev && \
    pecl install memcached && \
    docker-php-ext-enable memcached

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY ./config/nginx.conf /etc/nginx/sites-enabled/default

COPY --chown=www-data:www-data ./ /app/

ENTRYPOINT ["/app/docker-entrypoint.sh"]
