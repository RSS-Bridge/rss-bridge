FROM rssbridge/rss-bridge:latest

RUN  apt-get update && \
    apt-get install --yes --no-install-recommends \
      git && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug