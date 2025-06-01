# WEBHOMEBANK
# Author: nanawel # nospam # at # gmail # dot # com
#
# BUILD
#   docker build -t webhomebank .
#
# (you need to run "composer install" first to install the dependencies, see README.md)
#
# RUN (interactive mode; use -d instead of -i to run as background daemon)
#    docker run -i -t --rm \
#        -p 49080:80 \                               # App will be available from host at localhost:49080
#        -v /home/myuser/mybudgetdir:/data:ro \      # Replace the first part with the path to the directory holding your budget.xhb
#        --name my-webhomebank \                     # Name of the new container
#        webhomebank                                 # Name of the image (above in the build command)#          webhomebank                                     # Name of the image (above in the BUILD command)
#
# NOTICE: The .xhb file must be readable on the host by the UID the webserver of the container uses (www-data => UID 33).
#

# -----------------------------------------------

ARG appVersion=dev
ARG installXdebug=0

FROM node:20-alpine AS theme-builder

COPY src/ui/themes/ /themes

RUN cd /themes/default \
 && npm install \
 && npm run build \
 && rm -rf node_modules
RUN cd /themes/modern \
 && npm install \
 && npm run build \
 && rm -rf node_modules

# -----------------------------------------------

ARG installXdebug=0
FROM php:8.3-apache

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
 && apt-get install --no-install-recommends -y \
    wget \
    bzip2 \
    unzip \
    git \
    libicu-dev \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/* \
 && docker-php-ext-install -j$(nproc) intl

# Apache setup
RUN ln -sf /dev/stdout /var/log/apache2/access.log \
 && ln -sf /dev/stderr /var/log/apache2/error.log
 && a2enmod rewrite \
    deflate \
    expires

ARG installXdebug=0
RUN test "${installXdebug}" = "0" || pecl install xdebug
ENV XDEBUG=${installXdebug}

COPY docker/php.ini    /usr/local/etc/php/conf.d/zz-webhomebank.ini
COPY docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY src/ /var/www/html/

COPY --from=theme-builder /themes/ /var/www/html/ui/themes/

WORKDIR /var/www/html

RUN composer install \
 && mv -f /var/www/html/etc/local.ini.docker /var/www/html/etc/local.ini \
 && sed -i "s/^VERSION=.*/VERSION=${appVersion}/" /var/www/html/etc/app.ini \
 && mkdir -p /var/www/html/var \
 && chown -R www-data /var/www/html/var \
 && chmod -R 775 /var/www/html/var

# Override entrypoint to add conditional XDebug support
COPY docker/docker-entrypoint.sh /
ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["apache2-foreground"]

EXPOSE 80
