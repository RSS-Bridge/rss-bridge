FROM ulsmith/alpine-apache-php7

COPY ./ /app/public/

VOLUME ["/rss-bridge"]

EXPOSE 80

RUN chown -R apache:root /app/public
