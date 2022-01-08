<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 08/06/16
 * Time: 19:39
 */

require __DIR__ . '/../vendor/autoload.php';
ini_set('error_log', 'stderr://');  //Force display PHP exceptions

/* @var $fw \Base */
$fw = \Base::instance();
$fw->config('../../etc/config.ini')
    ->config('../../etc/app.ini')
    ->config('etc/local.ini');

