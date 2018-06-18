FROM ulsmith/alpine-apache-php7

COPY --chown=apache:root ./ /app/public/