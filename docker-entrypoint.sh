#!/usr/bin/env bash

# - Find custom files (bridges, whitelist, config.ini) in the /config folder
# - Copy them to the respective folders in /app
# This will overwrite previous configs and bridges of same name
# If there are no matching files, rss-bridge works like default.

find /config/ -type f -name '*' -print0 2> /dev/null |
while IFS= read -r -d '' file; do
    file_name="$(basename "$file")" # Strip leading path
    if [[ $file_name = *" "* ]]; then
        printf 'Custom Bridge %s has a space in the name and will be skipped.\n' "$file_name"
        continue
    fi
    case "$file_name" in
    *Bridge.php)    yes | cp "$file" /app/bridges/ ;
                    chown www-data:www-data "/app/bridges/$file_name";
                    printf "Custom Bridge %s added.\n" $file_name;;
    config.ini.php) yes | cp "$file" /app/ ;
                    chown www-data:www-data "/app/$file_name";
                    printf "Custom config.ini.php added.\n";;
    whitelist.txt)  yes | cp "$file" /app/ ;
                    chown www-data:www-data "/app/$file_name";
                    printf "Custom whitelist.txt added.\n";;
    DEBUG)          yes | cp "$file" /app/ ;
                    chown www-data:www-data "/app/$file_name";
                    printf "DEBUG file added.\n";;
    esac
done

# This feature can set the internal port that apache uses to something else.
# If docker is run on network:service mode, no two containers can use port 80
# To use this, start the container with the additional environment variable "HTTP_PORT"
if [ ! -z ${HTTP_PORT} ]; then
	sed -i "s/80/$HTTP_PORT/g" /etc/nginx/sites-enabled/default
fi

# nginx will daemonize
nginx

# php-fpm will not
php-fpm
