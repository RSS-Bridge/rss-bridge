FROM php:7.4.29-fpm

LABEL description="RSS-Bridge is a PHP project capable of generating RSS and Atom feeds for websites that don't have one."
LABEL repository="https://github.com/RSS-Bridge/rss-bridge"
LABEL website="https://github.com/RSS-Bridge/rss-bridge"

RUN apt-get update && \
    apt-get install --yes --no-install-recommends \
      nginx \
      zlib1g-dev \
      libzip-dev \
      libmemcached-dev && \
    docker-php-ext-install zip && \
    pecl install memcached && \
    docker-php-ext-enable memcached && \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY ./config/nginx.conf /etc/nginx/sites-enabled/default

COPY --chown=www-data:www-data ./ /app/

# curl-impersonate v0.5.1
RUN cd /usr/local/lib && \
    mkdir curl-impersonate && cd curl-impersonate && \
    curl -L "https://github.com/lwthiker/curl-impersonate/releases/download/v0.5.1/libcurl-impersonate-v0.5.1.x86_64-linux-gnu.tar.gz" | tar xvzf -
ENV LD_PRELOAD /usr/local/lib/curl-impersonate/libcurl-impersonate-ff.so
ENV CURL_IMPERSONATE ff91esr

EXPOSE 80

ENTRYPOINT ["/app/docker-entrypoint.sh"]
