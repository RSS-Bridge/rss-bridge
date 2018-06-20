FROM ulsmith/alpine-apache-php7

COPY ./ /app/public/

RUN chown -R apache:root /app/public