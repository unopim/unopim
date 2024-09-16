#!/bin/bash

composer install
npm install
php artisan unopim:install -n

# Hand back control to the default entrypoint
apache2-foreground
