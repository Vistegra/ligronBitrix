#!/bin/bash
set -e

# Создаем необходимые директории если они отсутствуют
mkdir -p /var/www/html/public
mkdir -p /var/log/nginx

# Устанавливаем правильные права
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# Запускаем nginx и PHP-FPM
nginx
exec php-fpm