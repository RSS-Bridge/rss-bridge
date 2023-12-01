#!/usr/bin/env bash

# - Find custom files (bridges, formats, whitelist, config.ini) in the /config folder
# - Copy them to the respective folders in /app
# This will overwrite previous configs, bridges and formats of same name
# If there are no matching files, rss-bridge works like default.

find /config/ -type f -name '*' -print0 2> /dev/null |
while IFS= read -r -d '' file; do
    file_name="$(basename "$file")" # Strip leading path
    if [[ $file_name = *" "* ]]; then
        printf 'Custom file %s has a space in the name and will be skipped.\n' "$file_name"
        continue
    fi
    case "$file_name" in
    *Bridge.php)    yes | cp "$file" /app/bridges/ ;
                    chown www-data:www-data "/app/bridges/$file_name";
                    printf "Custom Bridge %s added.\n" $file_name;;
    *Format.php)    yes | cp "$file" /app/formats/ ;
                    chown www-data:www-data "/app/formats/$file_name";
                    printf "Custom Format %s added.\n" $file_name;;
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

# php-fpm should not daemonize
php-fpm8.2 --nodaemonize
