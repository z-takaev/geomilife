#!/bin/bash

set -e

echo "Deploying..."
git pull origin main
php8.4 artisan down
php8.4 composer.phar install
php8.4 artisan migrate --force
php8.4 artisan optimize:clear
php8.4 artisan config:cache
php8.4 artisan event:cache
php8.4 artisan view:cache
php8.4 artisan route:cache
php8.4 artisan up
echo "Done!"
