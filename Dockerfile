FROM debian:12-slim AS rssbridge

LABEL description="RSS-Bridge is a PHP project capable of generating RSS and Atom feeds for websites that don't have one."
LABEL repository="https://github.com/RSS-Bridge/rss-bridge"
LABEL website="https://github.com/RSS-Bridge/rss-bridge"

ARG DEBIAN_FRONTEND=noninteractive
RUN set -xe && \
    apt-get update && \
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
      # for downloading libcurl-impersonate
      curl \
      && \
    # install curl-impersonate library
    curlimpersonate_version=0.6.0 && \
    { \
        { \
            [ $(arch) = 'aarch64' ] && \
            archive="libcurl-impersonate-v${curlimpersonate_version}.aarch64-linux-gnu.tar.gz" && \
            sha512sum="d04b1eabe71f3af06aa1ce99b39a49c5e1d33b636acedcd9fad163bc58156af5c3eb3f75aa706f335515791f7b9c7a6c40ffdfa47430796483ecef929abd905d" \
        ; } \
        || { \
            [ $(arch) = 'armv7l' ] && \
            archive="libcurl-impersonate-v${curlimpersonate_version}.arm-linux-gnueabihf.tar.gz" && \
            sha512sum="05906b4efa1a6ed8f3b716fd83d476b6eea6bfc68e3dbc5212d65a2962dcaa7bd1f938c9096a7535252b11d1d08fb93adccc633585ff8cb8cec5e58bfe969bc9" \
        ; } \
        || { \
            [ $(arch) = 'x86_64' ] && \
            archive="libcurl-impersonate-v${curlimpersonate_version}.x86_64-linux-gnu.tar.gz" && \
            sha512sum="480bbe9452cd9aff2c0daaaf91f1057b3a96385f79011628a9237223757a9b0d090c59cb5982dc54ea0d07191657299ea91ca170a25ced3d7d410fcdff130ace" \
        ; } \
    } && \
    curl -LO "https://github.com/lwthiker/curl-impersonate/releases/download/v${curlimpersonate_version}/${archive}" && \
    echo "$sha512sum  $archive" | sha512sum -c - && \
    mkdir -p /usr/local/lib/curl-impersonate && \
    tar xaf "$archive" -C /usr/local/lib/curl-impersonate --wildcards 'libcurl-impersonate-ff.so*' && \
    rm "$archive" && \
    apt-get purge --assume-yes curl && \
    rm -rf /var/lib/apt/lists/*

ENV LD_PRELOAD /usr/local/lib/curl-impersonate/libcurl-impersonate-ff.so
ENV CURL_IMPERSONATE ff91esr

# logs should go to stdout / stderr
RUN ln -sfT /dev/stderr /var/log/nginx/error.log; \
	ln -sfT /dev/stdout /var/log/nginx/access.log; \
	chown -R --no-dereference www-data:adm /var/log/nginx/

COPY ./config/nginx.conf /etc/nginx/sites-available/default
COPY ./config/php-fpm.conf /etc/php/8.2/fpm/pool.d/rss-bridge.conf
COPY ./config/php.ini /etc/php/8.2/fpm/conf.d/90-rss-bridge.ini

COPY --chown=www-data:www-data ./ /app/

EXPOSE 80

ENTRYPOINT ["/app/docker-entrypoint.sh"]
