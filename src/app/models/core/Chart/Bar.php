<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 11/11/15
 * Time: 14:37
 */

namespace app\models\core\Chart;


class Bar extends AbstractChart
{
    protected $_defaultData = [
        'type'     => 'bar',
        'template' => 'common/chart/bar.phtml',
        'width'    => 800,
        'height'   => 300
    ];
}