# WEBHOMEBANK
# Author: nanawel # nospam # at # gmail # dot # com
#
# BUILD
#   docker build -t webhomebank .
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

FROM php:5.6-apache

COPY resources/php.ini /usr/local/etc/php/
COPY src/ /var/www/html/

RUN apt-get update && apt-get install -y \
    nano \
    php5-intl \
    php5-gd

RUN a2enmod rewrite
RUN a2enmod deflate
RUN a2enmod expires

RUN mv -f /var/www/html/etc/local.ini.docker /var/www/html/etc/local.ini

RUN chown -R www-data /var/www
RUN chmod -R 775 /var/www

EXPOSE 80
