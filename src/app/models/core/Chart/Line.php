<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 11/11/15
 * Time: 14:37
 */

namespace app\models\core\Chart;


class Line extends AbstractChart
{
    protected $_defaultData = array(
        'type'     => 'line',
        'template' => 'common/chart/line.phtml',
        'width'    => 800,
        'height'   => 300,
        'class'    => 'toolbar-top-right'
    );
}