#/bin/sh

cp .devcontainer/nginx.conf /etc/nginx/conf.d/default.conf
cp .devcontainer/xdebug.ini /etc/php/8.2/fpm/conf.d/xdebug.ini

#  This should download some dev-dependencies, like phpunit and the PHP code sniffers
composer global require "phpunit/phpunit:^9"
composer global require "squizlabs/php_codesniffer:^3.6"
composer global require "phpcompatibility/php-compatibility:^9.3"

#  We need to this manually for running the PHPCompatibility ruleset
phpcs --config-set installed_paths /root/.config/composer/vendor/phpcompatibility/php-compatibility

mkdir -p .vscode
cp .devcontainer/launch.json .vscode

echo '*' > whitelist.txt 

chmod a+x $(pwd)
rm -rf /var/www/html 
ln -s $(pwd) /var/www/html 

# Solves possible issue of cache directory not being accessible
chown www-data:www-data -R $(pwd)/cache 

nginx
php-fpm8.2 -D