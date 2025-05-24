<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 11/11/15
 * Time: 14:37
 */

namespace app\models\core\Chart;


class Doughnut extends AbstractChart
{
    protected $_defaultData = [
        'type'     => 'doughnut',
        'template' => 'common/chart/doughnut.phtml',
        'width'    => 300,
        'height'   => 300
    ];
}
