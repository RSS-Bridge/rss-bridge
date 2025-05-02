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
      # for patching libcurl-impersonate
      patchelf \
      && \
    # install curl-impersonate library
    curlimpersonate_version=1.0.0rc2 && \
    { \
        { \
            [ $(arch) = 'aarch64' ] && \
            archive="libcurl-impersonate-v${curlimpersonate_version}.aarch64-linux-gnu.tar.gz" && \
            sha512sum="c8add80e7a0430a074edea1a11f73d03044c48e848e164af2d6f362866623e29bede207a50f18f95b1bc5ab3d33f5c31408be60a6da66b74a0d176eebe299116" \
        ; } \
        || { \
            [ $(arch) = 'armv7l' ] && \
            archive="libcurl-impersonate-v${curlimpersonate_version}.arm-linux-gnueabihf.tar.gz" && \
            sha512sum="d0403ca4ad55a8d499b120e5675c7b5a0dc4946af49c933e91fc24455ffe5e122aa21ee95554612ff5d1bd6faea1556e1e1b9c821918e2644cc9bcbddc05747a" \
        ; } \
        || { \
            [ $(arch) = 'x86_64' ] && \
            archive="libcurl-impersonate-v${curlimpersonate_version}.x86_64-linux-gnu.tar.gz" && \
            sha512sum="35cafda2b96df3218a6d8545e0947a899837ede51c90f7ef2980bd2d99dbd67199bc620000df28b186727300b8c7046d506807fb48ee0fbc068dc8ae01986339" \
        ; } \
    } && \
    curl -LO "https://github.com/lexiforest/curl-impersonate/releases/download/v${curlimpersonate_version}/${archive}" && \
    echo "$sha512sum  $archive" | sha512sum -c - && \
    mkdir -p /usr/local/lib/curl-impersonate && \
    tar xaf "$archive" -C /usr/local/lib/curl-impersonate && \
    patchelf --set-soname libcurl.so.4 /usr/local/lib/curl-impersonate/libcurl-impersonate.so && \
    rm "$archive" && \
    apt-get purge --assume-yes curl patchelf && \
    rm -rf /var/lib/apt/lists/*

ENV LD_PRELOAD /usr/local/lib/curl-impersonate/libcurl-impersonate.so
ENV CURL_IMPERSONATE chrome131

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
