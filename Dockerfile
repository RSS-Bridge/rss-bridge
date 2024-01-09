FROM lwthiker/curl-impersonate:0.5-ff-slim-buster AS curlimpersonate

FROM debian:12-slim AS rssbridge

LABEL description="RSS-Bridge is a PHP project capable of generating RSS and Atom feeds for websites that don't have one."
LABEL repository="https://github.com/RSS-Bridge/rss-bridge"
LABEL website="https://github.com/RSS-Bridge/rss-bridge"

ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get update && \
    apt-get install --yes --no-install-recommends \
      ca-certificates \
      nginx \
      nss-plugin-pem \
      php-curl \
      php-fpm \
      php-intl \
      # php-json is enabled by default with PHP 8.2 in Debian 12
      php-mbstring \
      php-memcached \
      # php-opcache is enabled by default with PHP 8.2 in Debian 12
      # php-openssl is enabled by default with PHP 8.2 in Debian 12
      php-sqlite3 \
      php-xml \
      php-zip \
      # php-zlib is enabled by default with PHP 8.2 in Debian 12
      && \
    rm -rf /var/lib/apt/lists/*

# logs should go to stdout / stderr
RUN ln -sfT /dev/stderr /var/log/nginx/error.log; \
	ln -sfT /dev/stdout /var/log/nginx/access.log; \
	chown -R --no-dereference www-data:adm /var/log/nginx/

COPY --from=curlimpersonate /usr/local/lib/libcurl-impersonate-ff.so /usr/local/lib/curl-impersonate/
ENV LD_PRELOAD /usr/local/lib/curl-impersonate/libcurl-impersonate-ff.so
ENV CURL_IMPERSONATE ff91esr

COPY ./config/nginx.conf /etc/nginx/sites-available/default
COPY ./config/php-fpm.conf /etc/php/8.2/fpm/pool.d/rss-bridge.conf
COPY ./config/php.ini /etc/php/8.2/fpm/conf.d/90-rss-bridge.ini

COPY --chown=www-data:www-data ./ /app/

EXPOSE 80

ENTRYPOINT ["/app/docker-entrypoint.sh"]
