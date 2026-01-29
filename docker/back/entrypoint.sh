#!/bin/bash
set -e

# Install composer dependencies if vendor directory doesn't exist or is empty
if [ ! -d "/var/www/html/vendor" ] || [ -z "$(ls -A /var/www/html/vendor 2>/dev/null)" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --optimize-autoloader
fi

# Start Apache in foreground
exec apache2-foreground
