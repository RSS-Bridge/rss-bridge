#!/usr/bin/env bash

# Find custom files in the /config folder and copy them to the respective folders in /app. 
# Look for bridges that end in 'Bridge.php' and for the whitelist and config.ini files. Everything else is ignored
# This will overwrite previous configs and bridges. It also uses the default paths of the configs and bridges, so if there is no file that matches, rss-bridge works like default.

for file in `find /config/ -type f`; do
    file_name="$(basename "$file")" # Strip leading path
    if [[ $file_name= *" "* ]]; then
        printf 'Custom Bridge %s has a space in the name and will be skipped.\n' "$file_name"
        break
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
    esac
done

# Start apache
apache2-foreground
