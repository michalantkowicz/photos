#!/bin/bash
set -e

# Make sure the photo storage dir exists (kept out of the docroot's reachable
# paths by data/.htaccess).
mkdir -p /var/www/html/data

# Paint fixture photos for the seeded gallery sessions (idempotent — existing
# files are kept untouched).
php /usr/local/share/generate-fixtures.php

exec apache2-foreground
