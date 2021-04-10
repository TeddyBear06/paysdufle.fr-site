#!/bin/sh

cd /usr/paysdufle.fr
composer install
php ./build.php local
cp -r /usr/paysdufle.fr/build/* /var/www/html/
exec docker-php-entrypoint apache2-foreground