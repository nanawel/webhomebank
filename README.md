[![WebHomeBank](src/ui/themes/default/images/logo.png)](http://https://github.com/nanawel/webhomebank/)

A simple web-viewer for Homebank XML files. HomeBank is a free software that will assist you to manage your personal
accounting, released under the GPL v2 license.

Please visit [http://homebank.free.fr/]([http://homebank.free.fr/]) for more information about the original software.

# Previews

![Preview 1](/resources/screenshots/home_en_modern_600px.png "Preview 1")

![Preview 2](/resources/screenshots/home_en_default_part1.png "Preview 2")

![Preview 3](/resources/screenshots/home_en_default_part2.png "Preview 3")

![Preview 4](/resources/screenshots/home_en_default_part3.png "Preview 4")

More screenshots below:

- [Home page / accounts - EN - Default theme](/resources/screenshots/home_en_default_part1.png)

- [Vehicle cost report - EN - Modern theme](/resources/screenshots/vehiclecost_en_modern.png)

- [Operations list - EN - Modern theme](/resources/screenshots/operations_en_modern.png)

- [Operations list - FR - Modern theme](/resources/screenshots/operations_fr_modern.png)

# Installation

## From sources

Requirements:

 * [Apache 2.x]([http://httpd.apache.org/]) with the following modules enabled:
    * rewrite
    * php5
    * expires (optional)

(should also work with [Lighttpd]([http://www.lighttpd.net/]) or [Nginx]([http://nginx.org/])))


 * PHP 5.5+ with the following extensions enabled:
    * mcrypt
    * pdo_sqlite
    * intl
    * sqlite3

### Instructions

Clone repository into the folder of your choice and set up your webserver to point to it. You may want to create a
dedicated virtual host.

Copy the sample configuration etc/local.ini.sample to etc/local.ini and set the BUDGET_FILE variable to point to your
XHB file (HomeBank save file).

Open your browser and go to the index.php of the application.

If you plan to use a reverse-proxy/load-balancer, please read the section below in order to configure the special
`X-External-Base-Path` header directive.

You can override any configuration directive present in `etc/*.ini` files from `local.ini`. Just add the corresponding
section if necessary. Some examples are already commented in it.


## With provided Docker build file

Clone repository at the location of your choice, it can be a temporary folder.

Then build the image (based on [php:5.6-apache]([https://github.com/docker-library/php/blob/cf1e938f3721632443e01734bcfcbcf1160ea539/5.6/apache/Dockerfile])).

    docker build -t webhomebank .

Run it (you may want to tune some settings)

    docker run -it --rm \
        -p 49080:80 \                                                       # App will be available from host at localhost:49080
        -v /home/myuser/mybudget.xhb:/var/www/html/data/userfile.xhb:ro \   # Replace first part with the path to your .xhb file
        --name my-webhomebank \                                             # Name of the new container
        webhomebank                                                         # Name of the image (above in the build command)

**Notice (1):** The .xhb file must be readable on the host by the UID the webserver of the container uses (www-data: UID 33).

**Notice (2):** The container does not support SSL. You may want to use a SSL-enabled reverse-proxy before it to secure
the connection.

### Apache configuration when used as reverse-proxy

In this example, we want the application to be accessible from path `/whb` from the RP on the standard HTTP port
(ex: [http://mywebhost.me/whb]([http://mywebhost.me/whb])), while the Docker container exposes its port 80 on the port
49080 on localhost.

The following directives should be placed into your VirtualHost listening on port 80.

    ProxyRequests Off
    ProxyPreserveHost On
    ProxyPass /whb http://localhost:49080/
    ProxyPassReverse /whb http://localhost:49080/
    <Location /whb>
        RequestHeader set X-External-Base-Path /whb
    </Location>

### Apache configuration when used as reverse-proxy with SSL

In this example, we want the application to be accessible from path `/whb` from the RP on the standard HTTPS port
(ex: [https://mywebhost.me/whb]([https://mywebhost.me/whb])), while the Docker container exposes its port 80 on the port
49080 on localhost. Hence SSL is provided **solely by the RP**.

The following directives should be placed into your VirtualHost listening on port 443.

    ProxyRequests Off
    ProxyPreserveHost On
    ProxyPass /whb http://localhost:49080/
    ProxyPassReverse /whb http://localhost:49080/
    <Location /whb>
        RequestHeader set X-External-Base-Path /whb
        RequestHeader set X-Forwarded-Proto https
    </Location>

# License

See LICENSE

# Third Party Libraries

- [Fat-Free Framework](http://fatfreeframework.com/home) (GPL v3)

- [Chart.js](http://www.chartjs.org/) (MIT license)

- [Charts.Scatter.js](http://dima117.github.io/Chart.Scatter) (MIT license)

- [jQuery](http://jquery.com/) (MIT license)

- [Foundation](http://foundation.zurb.com/) (MIT license)

- [Zend Framework 2](http://framework.zend.com/) (New BSD License)

- [Composer](https://getcomposer.org/) (MIT license)

- [Grunt](http://gruntjs.com/) (MIT license)

# Legal Notice

Copyright (c) 2015 Anaël Ollier &lt;nanawel&#64;gmail&#46;com&gt;