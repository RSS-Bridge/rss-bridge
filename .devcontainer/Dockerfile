FROM rssbridge/rss-bridge:latest

COPY --chmod=755 post-create-command.sh /usr/local/bin/post-create-command

ADD https://raw.githubusercontent.com/docker-library/php/master/docker-php-ext-enable /usr/local/bin/docker-php-ext-enable
RUN chmod u+x /usr/local/bin/docker-php-ext-enable

ADD https://getcomposer.org/installer /usr/local/bin/composer-installer.php
RUN chmod u+x /usr/local/bin/composer-installer.php
RUN php /usr/local/bin/composer-installer.php --check && \
    php /usr/local/bin/composer-installer.php --filename=composer --install-dir=/usr/local/bin

RUN apt-get update && \
    apt-get install -y \
      git \
      php-dev \
      make \
      unzip

RUN pecl install xdebug && \
    PHP_INI_DIR=/etc/php/8.2/fpm docker-php-ext-enable xdebug
