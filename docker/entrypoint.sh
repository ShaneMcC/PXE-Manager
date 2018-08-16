#!/bin/sh
set -e

echo "Running init.php"
/usr/local/bin/php /pxemanager/admin/init.php

echo "Starting webserver"
exec /usr/local/bin/docker-php-entrypoint "$@"
