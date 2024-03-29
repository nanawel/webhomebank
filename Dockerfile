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

ARG appVersion=dev

FROM php:7.4-apache-buster

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN apt-get update && apt-get install --no-install-recommends -y \
    wget \
    bzip2 \
    unzip \
    git \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libicu-dev \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install -j$(nproc) gd intl

# Log Apache access and errors to STDOUT/STDERR
RUN ln -sf /dev/stdout /var/log/apache2/access.log \
 && ln -sf /dev/stderr /var/log/apache2/error.log

RUN a2enmod rewrite \
    deflate \
    expires

RUN pecl install xdebug
ENV XDEBUG=0

COPY resources/php.ini    /usr/local/etc/php/conf.d/zz-webhomebank.ini
COPY resources/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY src/ /var/www/html/

WORKDIR /var/www/html

RUN composer install
RUN mv -f /var/www/html/etc/local.ini.docker /var/www/html/etc/local.ini \
 && sed -i "s/^VERSION=.*/VERSION=${appVersion}/" /var/www/html/etc/app.ini

RUN chown -R www-data /var/www \
 && chmod -R 775 /var/www

# Override entrypoint to add conditional XDebug support
COPY resources/docker-entrypoint.sh /
ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["apache2-foreground"]

EXPOSE 80
