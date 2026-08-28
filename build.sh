#!/usr/bin/env bash

echo '==> composer install'
composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction --no-progress

echo '==> importmap:install'
php bin/console importmap:install

echo '==> asset-map:compile'
rm -rf public/assets
php bin/console asset-map:compile

echo '==> migrations'
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo '==> cache'
php bin/console cache:clear
php bin/console cache:warmup

echo '==> done'
