#!/bin/bash

set -e
env

[ "$XDEBUG" == "0" ] || /usr/local/bin/docker-php-ext-enable xdebug

# Procede with standard entrypoint
exec /usr/local/bin/docker-php-entrypoint $*
