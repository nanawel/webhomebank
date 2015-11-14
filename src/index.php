<?php
if (!is_file('app/vendor/autoload.php')) {
    echo 'You must run `composer install` from this directory to install the dependencies first.';
    echo 'Visit https://getcomposer.org/ to download the necessary tool or use the package provided by your distro.';
    exit;
}

require 'app/vendor/autoload.php';

/* @var $fw \Base */
$fw = \Base::instance();

$fw->set('DEBUG',1);
if ((float)PCRE_VERSION<7.9)
	trigger_error('PCRE version is out of date');

$fw->config('etc/config.ini')
    ->config('etc/routing.ini')
    ->config('etc/app.ini')
    ->config('etc/local.ini');

if ($fw->get('DEBUG') > 1) {
    error_reporting(E_ALL | E_STRICT);
}

$fw->run();
