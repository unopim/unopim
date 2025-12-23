#!/bin/bash

php artisan queue:listen --queue=system,default
