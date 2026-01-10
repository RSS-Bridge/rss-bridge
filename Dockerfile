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
    curlimpersonate_version=1.2.5 && \
    { \
        { \
            [ $(arch) = 'aarch64' ] && \
            archive="libcurl-impersonate-v${curlimpersonate_version}.aarch64-linux-gnu.tar.gz" && \
            sha512sum="cd340819d27c03e6833e746c1de181aa828f5986f4152fe0d55d5ea1b0a7c5328db129f9146d6369d2c2e20facd7c0a67e32cc916dddc74d1557106f89636dd0" \
        ; } \
        || { \
            [ $(arch) = 'armv7l' ] && \
            archive="libcurl-impersonate-v${curlimpersonate_version}.arm-linux-gnueabihf.tar.gz" && \
            sha512sum="143e57779c4872557e8becfd91bf9c92d9085b1c964d103a39b6e85253e3f3257796d205de4b49f6bc25c8ad0a39e5d4ec4f51391037e27d32d6355e52c5d346" \
        ; } \
        || { \
            [ $(arch) = 'x86_64' ] && \
            archive="libcurl-impersonate-v${curlimpersonate_version}.x86_64-linux-gnu.tar.gz" && \
            sha512sum="816e7d08110f2f5a6e7e2364e7c1d9ec0cc371e9b5024e0239db937379f057bb40ec80e56d0c49cdaf80b7f560888511c1bda5516b850a6d54c46a2eccc94dc6" \
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

ENV LD_PRELOAD=/usr/local/lib/curl-impersonate/libcurl-impersonate.so
ENV CURL_IMPERSONATE=chrome142

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
