# WEBHOMEBANK
# Author: nanawel # nospam # at # gmail # dot # com
#
# BUILD
#   docker build -t webhomebank .
#
# (you need to run "composer install" first to install the dependencies, see README.md)
#
# RUN (interactive mode; use -d instead of -i to run as background daemon)
#   docker run -i -t --rm \
#          -p 49080:80 \                                                       # App will be available from host at localhost:49080
#          -v /home/myuser/mybudget.xhb:/var/www/html/data/userfile.xhb:ro \   # Replace first part with the path to your .xhb file
#          --name my-webhomebank \                                             # Name of the new container
#          --restart=always                                                    # (Optional) If you want the container to be restarted at each boot (when using -d)
#          webhomebank                                                         # Name of the image (above in the BUILD command)
#
# NOTICE: The .xhb file must be readable on the host by the UID the webserver of the container uses (www-data => UID 33).
#

FROM php:7-apache

COPY resources/php.ini /usr/local/etc/php/
COPY src/ /var/www/html/

RUN apt-get update && apt-get install -y nano

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng12-dev \
        libicu-dev
RUN docker-php-ext-install -j$(nproc) gd
RUN docker-php-ext-install -j$(nproc) intl

# Log Apache access and errors to STDOUT/STDERR
RUN ln -sf /dev/stdout /var/log/apache2/access.log
RUN ln -sf /dev/stderr /var/log/apache2/error.log

RUN a2enmod rewrite
RUN a2enmod deflate
RUN a2enmod expires

RUN mv -f /var/www/html/etc/local.ini.docker /var/www/html/etc/local.ini

RUN chown -R www-data /var/www
RUN chmod -R 775 /var/www

EXPOSE 80
